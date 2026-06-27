-- ============================================================
-- FIX: SQL Triggers for CTG (Cuti Tanpa Gaji / Unpaid Leave)
-- ============================================================
-- Run this in Supabase Dashboard > SQL Editor
--
-- Problem:
-- 1. update_leave_balance_on_approval: Deducts balance for ALL leave types,
--    including CTG (unpaid leave). This causes the transaction to rollback
--    when balance is insufficient (sisa >= 0 constraint).
-- 2. restore_leave_balance_on_cancel: Restores balance when ANY leave is
--    cancelled, including CTG. Since CTG doesn't deduct balance on approval,
--    cancelling CTG would give free leave days.
--
-- Solution:
-- Both triggers now check leave_type_id and skip balance operations for CTG.

-- ============================================================
-- FIX 1: update_leave_balance_on_approval
-- Skip balance deduction for CTG (unpaid leave)
-- ============================================================
CREATE OR REPLACE FUNCTION update_leave_balance_on_approval()
RETURNS TRIGGER AS $$
DECLARE
    leave_type_code VARCHAR(10);
BEGIN
    -- Get the leave type code
    SELECT kode INTO leave_type_code
    FROM leave_types
    WHERE id = NEW.leave_type_id;

    -- Skip balance deduction for CTG (unpaid leave)
    IF leave_type_code = 'CTG' THEN
        RETURN NEW;
    END IF;

    -- Deduct balance for regular leave types
    IF NEW.status = 'disetujui' AND OLD.status = 'pending' THEN
        UPDATE leave_balances
        SET
            terpakai = terpakai + NEW.total_hari,
            sisa = sisa - NEW.total_hari
        WHERE user_id = NEW.user_id
        AND tahun = EXTRACT(YEAR FROM NEW.tanggal_mulai);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ============================================================
-- FIX 2: restore_leave_balance_on_cancel
-- Skip balance restoration for CTG (unpaid leave)
-- ============================================================
CREATE OR REPLACE FUNCTION restore_leave_balance_on_cancel()
RETURNS TRIGGER AS $$
DECLARE
    leave_type_code VARCHAR(10);
BEGIN
    -- Get the leave type code
    SELECT kode INTO leave_type_code
    FROM leave_types
    WHERE id = OLD.leave_type_id;

    -- Skip balance restoration for CTG (unpaid leave)
    IF leave_type_code = 'CTG' THEN
        RETURN NEW;
    END IF;

    -- Restore balance for regular leave types
    IF NEW.status IN ('ditolak', 'dibatalkan') AND OLD.status = 'disetujui' THEN
        UPDATE leave_balances
        SET
            terpakai = terpakai - OLD.total_hari,
            sisa = sisa + OLD.total_hari
        WHERE user_id = OLD.user_id
        AND tahun = EXTRACT(YEAR FROM OLD.tanggal_mulai);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ============================================================
-- Activity Log Retention Policy
-- Delete activity logs older than 90 days to prevent unbounded growth
-- ============================================================
CREATE OR REPLACE FUNCTION cleanup_old_activity_logs() RETURNS void AS $$
BEGIN
    DELETE FROM activity_logs WHERE created_at < NOW() - INTERVAL '90 days';
END;
$$ LANGUAGE plpgsql;

-- Verify: Check that both functions are updated
SELECT proname, prosrc
FROM pg_proc
WHERE proname IN ('update_leave_balance_on_approval', 'restore_leave_balance_on_cancel');
 
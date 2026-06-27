-- =============================================
-- SIMCUTI - Supabase Database Schema
-- =============================================

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =============================================
-- 1. PROFILES TABLE
-- =============================================
CREATE TABLE profiles (
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    profile_photo_url TEXT,
    role VARCHAR(20) NOT NULL DEFAULT 'karyawan' CHECK (role IN ('admin', 'manager', 'karyawan')),
    department_id UUID REFERENCES departments(id) ON DELETE SET NULL,
    jatah_cuti_tahunan INTEGER DEFAULT 12 CHECK (jatah_cuti_tahunan >= 0),
    sisa_cuti INTEGER DEFAULT 12 CHECK (sisa_cuti >= 0),
    two_factor_enabled BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    last_login_at TIMESTAMP WITH TIME ZONE,
    last_login_ip INET,
    failed_login_attempts INTEGER DEFAULT 0,
    locked_until TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes for profiles
CREATE INDEX idx_profiles_role ON profiles(role);
CREATE INDEX idx_profiles_department ON profiles(department_id);
CREATE INDEX idx_profiles_is_active ON profiles(is_active);

-- =============================================
-- 2. DEPARTMENTS TABLE
-- =============================================
CREATE TABLE departments (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    nama VARCHAR(100) NOT NULL,
    kode VARCHAR(20) UNIQUE NOT NULL,
    manager_id UUID REFERENCES profiles(id) ON DELETE SET NULL,
    deskripsi TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    deleted_at TIMESTAMP WITH TIME ZONE
);

-- Index for departments
CREATE INDEX idx_departments_kode ON departments(kode);
CREATE INDEX idx_departments_is_active ON departments(is_active);

-- =============================================
-- 3. LEAVE TYPES TABLE
-- =============================================
CREATE TABLE leave_types (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    nama VARCHAR(100) NOT NULL,
    kode VARCHAR(10) UNIQUE NOT NULL,
    max_hari_per_pengajuan INTEGER DEFAULT 30 CHECK (max_hari_per_pengajuan > 0),
    butuh_dokumen BOOLEAN DEFAULT false,
    deskripsi TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    deleted_at TIMESTAMP WITH TIME ZONE
);

-- Index for leave types
CREATE INDEX idx_leave_types_kode ON leave_types(kode);
CREATE INDEX idx_leave_types_is_active ON leave_types(is_active);

-- =============================================
-- 4. LEAVE REQUESTS TABLE
-- =============================================
CREATE TABLE leave_requests (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    leave_type_id UUID NOT NULL REFERENCES leave_types(id) ON DELETE RESTRICT,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    total_hari INTEGER NOT NULL CHECK (total_hari > 0),
    alasan TEXT NOT NULL,
    lampiran_url TEXT,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'disetujui', 'ditolak', 'dibatalkan')),
    disetujui_oleh UUID REFERENCES profiles(id) ON DELETE SET NULL,
    tanggal_disetujui TIMESTAMP WITH TIME ZONE,
    alasan_penolakan TEXT,
    catatan_approval TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    deleted_at TIMESTAMP WITH TIME ZONE,
    CONSTRAINT check_dates CHECK (tanggal_selesai >= tanggal_mulai)
);

-- Indexes for leave requests
CREATE INDEX idx_leave_requests_user ON leave_requests(user_id);
CREATE INDEX idx_leave_requests_status ON leave_requests(status);
CREATE INDEX idx_leave_requests_dates ON leave_requests(tanggal_mulai, tanggal_selesai);
CREATE INDEX idx_leave_requests_created ON leave_requests(created_at DESC);

-- Prevent overlapping leave dates (race condition protection)
-- This trigger enforces date overlap prevention at the database level,
-- closing the TOCTOU race condition that exists in PHP application code.
CREATE OR REPLACE FUNCTION prevent_overlapping_leave()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status IN ('pending', 'disetujui') THEN
        IF EXISTS (
            SELECT 1 FROM leave_requests
            WHERE user_id = NEW.user_id
              AND id != NEW.id
              AND status IN ('pending', 'disetujui')
              AND tanggal_mulai <= NEW.tanggal_selesai
              AND tanggal_selesai >= NEW.tanggal_mulai
        ) THEN
            RAISE EXCEPTION 'Tanggal cuti tumpang tindih dengan pengajuan lain yang sudah ada';
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE CONSTRAINT TRIGGER prevent_overlapping_leave_trigger
    AFTER INSERT OR UPDATE ON leave_requests
    DEFERRABLE INITIALLY DEFERRED
    FOR EACH ROW
    EXECUTE FUNCTION prevent_overlapping_leave();

-- =============================================
-- 5. LEAVE BALANCES TABLE
-- =============================================
CREATE TABLE leave_balances (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    tahun INTEGER NOT NULL CHECK (tahun >= 2020 AND tahun <= 2100),
    total_jatah INTEGER NOT NULL DEFAULT 12 CHECK (total_jatah >= 0),
    terpakai INTEGER DEFAULT 0 CHECK (terpakai >= 0),
    sisa INTEGER NOT NULL CHECK (sisa >= 0),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(user_id, tahun)
);

-- Index for leave balances
CREATE INDEX idx_leave_balances_user ON leave_balances(user_id);
CREATE INDEX idx_leave_balances_tahun ON leave_balances(tahun);

-- =============================================
-- 6. ACTIVITY LOGS TABLE
-- =============================================
CREATE TABLE activity_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES auth.users(id) ON DELETE SET NULL,
    aksi VARCHAR(50) NOT NULL,
    deskripsi TEXT NOT NULL,
    model_type VARCHAR(255),
    model_id UUID,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes for activity logs
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_aksi ON activity_logs(aksi);
CREATE INDEX idx_activity_logs_created ON activity_logs(created_at DESC);
CREATE INDEX idx_activity_logs_model ON activity_logs(model_type, model_id);

-- =============================================
-- 7. CAPTCHA SESSIONS TABLE
-- =============================================
CREATE TABLE captcha_sessions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    session_key VARCHAR(64) UNIQUE NOT NULL,
    captcha_text VARCHAR(10) NOT NULL,
    ip_address INET NOT NULL,
    attempts INTEGER DEFAULT 0 CHECK (attempts >= 0 AND attempts <= 3),
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Index for captcha sessions
CREATE INDEX idx_captcha_session_key ON captcha_sessions(session_key);
CREATE INDEX idx_captcha_expires ON captcha_sessions(expires_at);
CREATE INDEX idx_captcha_ip ON captcha_sessions(ip_address);

-- =============================================
-- 8. TWO FACTOR CODES TABLE
-- =============================================
CREATE TABLE two_factor_codes (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    kode VARCHAR(6) NOT NULL,
    used BOOLEAN DEFAULT false,
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Index for two factor codes
CREATE INDEX idx_2fa_user ON two_factor_codes(user_id);
CREATE INDEX idx_2fa_expires ON two_factor_codes(expires_at);
CREATE INDEX idx_2fa_used ON two_factor_codes(used);

-- =============================================
-- TRIGGERS FOR UPDATED_AT
-- =============================================

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_profiles_updated_at BEFORE UPDATE ON profiles
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_departments_updated_at BEFORE UPDATE ON departments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_leave_types_updated_at BEFORE UPDATE ON leave_types
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_leave_requests_updated_at BEFORE UPDATE ON leave_requests
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_leave_balances_updated_at BEFORE UPDATE ON leave_balances
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =============================================
-- ROW LEVEL SECURITY (RLS) POLICIES
-- =============================================

-- Enable RLS on all tables
ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE departments ENABLE ROW LEVEL SECURITY;
ALTER TABLE leave_types ENABLE ROW LEVEL SECURITY;
ALTER TABLE leave_requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE leave_balances ENABLE ROW LEVEL SECURITY;
ALTER TABLE activity_logs ENABLE ROW LEVEL SECURITY;

-- PROFILES POLICIES
CREATE POLICY "Users can view own profile"
    ON profiles FOR SELECT
    USING (auth.uid() = id);

CREATE POLICY "Users can update own profile"
    ON profiles FOR UPDATE
    USING (auth.uid() = id AND role = role);

CREATE POLICY "Admins can view all profiles"
    ON profiles FOR SELECT
    USING ((SELECT role FROM profiles WHERE id = auth.uid()) = 'admin');

CREATE POLICY "Admins can manage all profiles"
    ON profiles FOR ALL
    USING ((SELECT role FROM profiles WHERE id = auth.uid()) = 'admin');

CREATE POLICY "Managers can view team profiles"
    ON profiles FOR SELECT
    USING (
        (SELECT role FROM profiles WHERE id = auth.uid()) = 'manager'
        AND department_id = (SELECT department_id FROM profiles WHERE id = auth.uid())
    );

-- DEPARTMENTS POLICIES
CREATE POLICY "Authenticated users can view active departments"
    ON departments FOR SELECT
    USING (auth.role() = 'authenticated' AND is_active = true);

CREATE POLICY "Admins can manage departments"
    ON departments FOR ALL
    USING ((SELECT role FROM profiles WHERE id = auth.uid()) = 'admin');

-- LEAVE TYPES POLICIES
CREATE POLICY "Authenticated users can view active leave types"
    ON leave_types FOR SELECT
    USING (auth.role() = 'authenticated' AND is_active = true);

CREATE POLICY "Admins can manage leave types"
    ON leave_types FOR ALL
    USING ((SELECT role FROM profiles WHERE id = auth.uid()) = 'admin');

-- LEAVE REQUESTS POLICIES
CREATE POLICY "Users can view own leave requests"
    ON leave_requests FOR SELECT
    USING (auth.uid() = user_id);

CREATE POLICY "Users can create own leave requests"
    ON leave_requests FOR INSERT
    WITH CHECK (auth.uid() = user_id);

CREATE POLICY "Users can update own pending leave requests"
    ON leave_requests FOR UPDATE
    USING (auth.uid() = user_id AND status = 'pending');

CREATE POLICY "Managers can view team leave requests"
    ON leave_requests FOR SELECT
    USING (
        (SELECT role FROM profiles WHERE id = auth.uid()) IN ('manager', 'admin')
        AND user_id IN (
            SELECT id FROM profiles 
            WHERE department_id = (SELECT department_id FROM profiles WHERE id = auth.uid())
        )
    );

CREATE POLICY "Managers can approve team leave requests"
    ON leave_requests FOR UPDATE
    USING (
        (SELECT role FROM profiles WHERE id = auth.uid()) IN ('manager', 'admin')
        AND status = 'pending'
        AND user_id IN (
            SELECT id FROM profiles 
            WHERE department_id = (SELECT department_id FROM profiles WHERE id = auth.uid())
        )
    );

CREATE POLICY "Admins can view all leave requests"
    ON leave_requests FOR SELECT
    USING ((SELECT role FROM profiles WHERE id = auth.uid()) = 'admin');

CREATE POLICY "Admins can manage all leave requests"
    ON leave_requests FOR ALL
    USING ((SELECT role FROM profiles WHERE id = auth.uid()) = 'admin');

-- LEAVE BALANCES POLICIES
CREATE POLICY "Users can view own leave balance"
    ON leave_balances FOR SELECT
    USING (auth.uid() = user_id);

CREATE POLICY "Admins can view all leave balances"
    ON leave_balances FOR SELECT
    USING ((SELECT role FROM profiles WHERE id = auth.uid()) = 'admin');

CREATE POLICY "Managers can view team leave balances"
    ON leave_balances FOR SELECT
    USING (
        (SELECT role FROM profiles WHERE id = auth.uid()) = 'manager'
        AND user_id IN (
            SELECT id FROM profiles 
            WHERE department_id = (SELECT department_id FROM profiles WHERE id = auth.uid())
        )
    );

CREATE POLICY "System can manage leave balances"
    ON leave_balances FOR ALL
    USING ((SELECT role FROM profiles WHERE id = auth.uid()) IN ('admin', 'manager'));

-- ACTIVITY LOGS POLICIES
CREATE POLICY "Users can view own activity logs"
    ON activity_logs FOR SELECT
    USING (auth.uid() = user_id);

CREATE POLICY "Admins can view all activity logs"
    ON activity_logs FOR SELECT
    USING ((SELECT role FROM profiles WHERE id = auth.uid()) = 'admin');

CREATE POLICY "System can insert activity logs"
    ON activity_logs FOR INSERT
    WITH CHECK (true);

-- =============================================
-- STORAGE BUCKET SETUP
-- =============================================

-- Create storage bucket for leave attachments
INSERT INTO storage.buckets (id, name, public)
VALUES ('leave-attachments', 'leave-attachments', false)
ON CONFLICT (id) DO NOTHING;

-- Storage policies for leave-attachments bucket
CREATE POLICY "Users can upload own attachments"
    ON storage.objects FOR INSERT
    WITH CHECK (
        bucket_id = 'leave-attachments'
        AND (storage.foldername(name))[1] = auth.uid()::text
    );

CREATE POLICY "Users can view own attachments"
    ON storage.objects FOR SELECT
    USING (
        bucket_id = 'leave-attachments'
        AND (storage.foldername(name))[1] = auth.uid()::text
    );

CREATE POLICY "Managers can view team attachments"
    ON storage.objects FOR SELECT
    USING (
        bucket_id = 'leave-attachments'
        AND (storage.foldername(name))[1] IN (
            SELECT id::text FROM profiles 
            WHERE department_id = (SELECT department_id FROM profiles WHERE id = auth.uid())
        )
    );

CREATE POLICY "Admins can view all attachments"
    ON storage.objects FOR SELECT
    USING (
        bucket_id = 'leave-attachments'
        AND (SELECT role FROM profiles WHERE id = auth.uid()) = 'admin'
    );

CREATE POLICY "Admins can delete attachments"
    ON storage.objects FOR DELETE
    USING (
        bucket_id = 'leave-attachments'
        AND (SELECT role FROM profiles WHERE id = auth.uid()) = 'admin'
    );

-- =============================================
-- FUNCTIONS FOR BUSINESS LOGIC
-- =============================================

-- Function to check and update leave balance on approval (atomic, prevents race conditions)
CREATE OR REPLACE FUNCTION check_and_update_leave_balance_on_approval()
RETURNS TRIGGER AS $$
DECLARE
    v_balance leave_balances%ROWTYPE;
BEGIN
    IF NEW.status = 'disetujui' AND OLD.status = 'pending' THEN
        -- Lock the balance row for this user/year to prevent concurrent modifications
        SELECT * INTO v_balance
        FROM leave_balances
        WHERE user_id = NEW.user_id
        AND tahun = EXTRACT(YEAR FROM NEW.tanggal_mulai)
        FOR UPDATE;

        IF NOT FOUND THEN
            RAISE EXCEPTION 'Saldo cuti tidak ditemukan untuk tahun ini';
        END IF;

        IF v_balance.sisa < NEW.total_hari THEN
            RAISE EXCEPTION 'Saldo cuti tidak mencukupi. Sisa: % hari, Dibutuhkan: % hari', v_balance.sisa, NEW.total_hari;
        END IF;

        -- Update balance atomically
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

CREATE TRIGGER trigger_check_and_update_leave_balance
    BEFORE UPDATE ON leave_requests
    FOR EACH ROW
    EXECUTE FUNCTION check_and_update_leave_balance_on_approval();

-- Function to restore leave balance on rejection/cancellation
CREATE OR REPLACE FUNCTION restore_leave_balance_on_cancel()
RETURNS TRIGGER AS $$
BEGIN
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

CREATE TRIGGER trigger_restore_leave_balance
    AFTER UPDATE ON leave_requests
    FOR EACH ROW
    EXECUTE FUNCTION restore_leave_balance_on_cancel();

-- Function to cleanup expired captcha sessions
CREATE OR REPLACE FUNCTION cleanup_expired_captcha()
RETURNS void AS $$
BEGIN
    DELETE FROM captcha_sessions WHERE expires_at < NOW();
END;
$$ LANGUAGE plpgsql;

-- Function to cleanup expired 2FA codes
CREATE OR REPLACE FUNCTION cleanup_expired_2fa_codes()
RETURNS void AS $$
BEGIN
    DELETE FROM two_factor_codes WHERE expires_at < NOW();
END;
$$ LANGUAGE plpgsql;

-- Function to cleanup old activity logs (older than 90 days)
CREATE OR REPLACE FUNCTION cleanup_old_activity_logs()
RETURNS void AS $$
BEGIN
    DELETE FROM activity_logs WHERE created_at < NOW() - INTERVAL '90 days';
END;
$$ LANGUAGE plpgsql;

-- =============================================
-- SEED DATA
-- =============================================

-- Insert default leave types
INSERT INTO leave_types (nama, kode, max_hari_per_pengajuan, butuh_dokumen, deskripsi) VALUES
('Cuti Tahunan', 'CT', 30, false, 'Cuti tahunan sesuai hak karyawan'),
('Cuti Sakit', 'CS', 14, true, 'Cuti sakit dengan surat dokter'),
('Cuti Darurat', 'CD', 3, false, 'Cuti untuk keperluan darurat mendadak'),
('Cuti Melahirkan', 'CM', 90, true, 'Cuti melahirkan untuk karyawan wanita'),
('Cuti Menikah', 'CK', 3, false, 'Cuti untuk pernikahan karyawan'),
('Cuti Tanpa Gaji', 'CTG', 365, false, 'Cuti tanpa gaji (unpaid leave)');

-- Insert default departments
INSERT INTO departments (nama, kode, deskripsi) VALUES
('IT Department', 'IT', 'Information Technology'),
('HR Department', 'HR', 'Human Resources'),
('Finance Department', 'FIN', 'Finance and Accounting'),
('Marketing Department', 'MKT', 'Marketing and Sales'),
('Operations Department', 'OPS', 'Operations');

-- =============================================
-- 9. NOTIFICATIONS TABLE
-- =============================================
CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'default' CHECK (type IN ('approved', 'rejected', 'pending', 'info', 'default')),
    is_read BOOLEAN DEFAULT false,
    link TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes for notifications
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_created ON notifications(created_at DESC);

-- Enable RLS on notifications
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Users can view own notifications"
    ON notifications FOR SELECT
    USING (auth.uid() = user_id);

CREATE POLICY "System can insert notifications"
    ON notifications FOR INSERT
    WITH CHECK (true);

CREATE POLICY "Users can update own notifications"
    ON notifications FOR UPDATE
    USING (auth.uid() = user_id);

COMMENT ON TABLE notifications IS 'User notification center';

-- =============================================
-- ENABLE REALTIME
-- =============================================

-- Enable realtime for specific tables (run this in Supabase Dashboard)
-- ALTER PUBLICATION supabase_realtime ADD TABLE leave_requests;
-- ALTER PUBLICATION supabase_realtime ADD TABLE activity_logs;

-- =============================================
-- COMMENTS
-- =============================================

COMMENT ON TABLE profiles IS 'Extended user profile information';
COMMENT ON TABLE departments IS 'Company departments master data';
COMMENT ON TABLE leave_types IS 'Leave types configuration';
COMMENT ON TABLE leave_requests IS 'Employee leave requests';
COMMENT ON TABLE leave_balances IS 'Annual leave balance tracking';
COMMENT ON TABLE activity_logs IS 'System activity audit trail';
COMMENT ON TABLE captcha_sessions IS 'Custom captcha session storage';
COMMENT ON TABLE two_factor_codes IS 'Two-factor authentication codes';
 
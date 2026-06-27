-- ============================================================
-- FIX: Infinite Recursion on profiles RLS policies
-- ============================================================
-- Run this in Supabase Dashboard > SQL Editor
-- This fixes the "infinite recursion detected in policy for relation profiles" error

-- Step 1: Create helper function with SECURITY DEFINER (bypasses RLS)
CREATE OR REPLACE FUNCTION public.get_user_role(user_id uuid)
RETURNS text
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = ''
AS $$
    SELECT role FROM public.profiles WHERE id = user_id;
$$;

-- Step 2: Drop ALL recursive policies on profiles
DROP POLICY IF EXISTS "Users can view own profile" ON profiles;
DROP POLICY IF EXISTS "Users can update own profile" ON profiles;
DROP POLICY IF EXISTS "Admins can view all profiles" ON profiles;
DROP POLICY IF EXISTS "Admins can manage all profiles" ON profiles;
DROP POLICY IF EXISTS "Managers can view team profiles" ON profiles;
DROP POLICY IF EXISTS "profiles_select_all" ON profiles;
DROP POLICY IF EXISTS "profiles_update_own" ON profiles;
DROP POLICY IF EXISTS "profiles_insert" ON profiles;

-- Step 3: Create safe non-recursive policies
CREATE POLICY "profiles_select_all" ON profiles FOR SELECT USING (true);
CREATE POLICY "profiles_update_own" ON profiles FOR UPDATE USING (auth.uid() = id);
CREATE POLICY "profiles_insert" ON profiles FOR INSERT WITH CHECK (auth.uid() = id);

-- Step 4: Drop recursive policies on other tables that reference profiles
DROP POLICY IF EXISTS "Admins can manage departments" ON departments;
DROP POLICY IF EXISTS "Admins can manage leave types" ON leave_types;
DROP POLICY IF EXISTS "Admins can view all leave requests" ON leave_requests;
DROP POLICY IF EXISTS "Admins can manage all leave requests" ON leave_requests;
DROP POLICY IF EXISTS "Users can view own leave balance" ON leave_balances;
DROP POLICY IF EXISTS "Admins can view all leave balances" ON leave_balances;
DROP POLICY IF EXISTS "Managers can view team leave balances" ON leave_balances;
DROP POLICY IF EXISTS "System can manage leave balances" ON leave_balances;
DROP POLICY IF EXISTS "Admins can view all activity logs" ON activity_logs;

-- Step 5: Recreate policies using the helper function (no recursion)
CREATE POLICY "Admins can manage departments" ON departments FOR ALL
    USING (public.get_user_role(auth.uid()) = 'admin');

CREATE POLICY "Admins can manage leave types" ON leave_types FOR ALL
    USING (public.get_user_role(auth.uid()) = 'admin');

CREATE POLICY "Admins can view all leave requests" ON leave_requests FOR SELECT
    USING (public.get_user_role(auth.uid()) = 'admin');

CREATE POLICY "Admins can manage all leave requests" ON leave_requests FOR ALL
    USING (public.get_user_role(auth.uid()) IN ('admin', 'manager'));

CREATE POLICY "Users can view own leave balance" ON leave_balances FOR SELECT
    USING (auth.uid() = user_id);

CREATE POLICY "Admins can view all leave balances" ON leave_balances FOR SELECT
    USING (public.get_user_role(auth.uid()) = 'admin');

CREATE POLICY "Managers can view team leave balances" ON leave_balances FOR SELECT
    USING (
        public.get_user_role(auth.uid()) = 'manager'
        AND user_id IN (
            SELECT id FROM public.profiles
            WHERE department_id = (SELECT department_id FROM public.profiles WHERE id = auth.uid())
        )
    );

CREATE POLICY "System can manage leave balances" ON leave_balances FOR ALL
    USING (public.get_user_role(auth.uid()) IN ('admin', 'manager'));

CREATE POLICY "Admins can view all activity logs" ON activity_logs FOR SELECT
    USING (public.get_user_role(auth.uid()) = 'admin');

-- Step 6: Keep existing simple policies that don't cause recursion
-- (leave_requests own policies, two_factor_codes, captcha, notifications)
-- These are already fine from supabase_schema.sql

-- Verify: this query should return 0 rows (no recursive policies left)
SELECT schemaname, tablename, policyname, qual
FROM pg_policies
WHERE tablename = 'profiles'
  AND qual LIKE '%FROM%profiles%';
 
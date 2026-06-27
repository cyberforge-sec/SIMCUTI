-- Add optional attachment path column for leave request uploads.
-- Safe to run multiple times in Supabase SQL Editor.
ALTER TABLE public.leave_requests
ADD COLUMN IF NOT EXISTS lampiran_url TEXT;

COMMENT ON COLUMN public.leave_requests.lampiran_url IS 'Private Supabase Storage path for leave request attachment';
 
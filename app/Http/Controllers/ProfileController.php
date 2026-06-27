<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    protected SupabaseService $supabase;
    protected ActivityLogService $activityLog;

    // Menginisialisasi class dan dependensi
    public function __construct(SupabaseService $supabase, ActivityLogService $activityLog)
    {
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    // Menampilkan form untuk mengubah data
    public function edit()
    {
        $userId = Session::get('user_id');
        $profile = $this->supabase->selectSingle('profiles', 'id', $userId);

        $departments = $this->supabase->select('departments', 'id,nama', ['is_active' => 'true']);

        return view('profile.edit', compact('profile', 'departments'));
    }

    // Memproses dan memperbarui data di database
    public function update(Request $request)
    {
        $userId = Session::get('user_id');
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'department_id' => 'nullable|string',
        ]);

        $data = [
            'full_name' => $request->full_name,
            'phone' => $request->phone,
        ];

        if (Session::get('user_role') === 'admin' && $request->has('department_id') && $request->department_id) {
            $dept = $this->supabase->selectSingle('departments', 'id', $request->department_id);
            if (!$dept) {
                return back()->withErrors(['department_id' => 'Departemen tidak valid.'])->withInput();
            }
            $data['department_id'] = $request->department_id;
            Session::put('user_department_id', $data['department_id']);
        } elseif (Session::get('user_role') === 'admin' && $request->has('department_id')) {
            $data['department_id'] = null;
            Session::forget('user_department_id');
        }

        $result = $this->supabase->update('profiles', ['id' => $userId], $data, true);

        if ($result['success']) {
            Session::put('user_name', $request->full_name);
            $this->activityLog->log('update', 'Memperbarui profil');
            return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
        }

        return back()->withErrors(['error' => 'Gagal memperbarui profil.'])->withInput();
    }

    /**
     * Batas dimensi gambar dalam pixel (lebar × tinggi).
     * Mencegah eksploitasi memori (image bomb) lewat pustaka GD.
     */
    protected const MAX_IMAGE_PIXELS = 50_000_000;

    // Fungsi untuk menangani proses updatePhoto
    public function updatePhoto(Request $request)
    {
        $userId = Session::get('user_id');
        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $file = $request->file('photo');

        // Validasi dimensi gambar
        // to prevent image bomb attacks (small file, massive pixel count)
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo) {
            $pixelCount = $imageInfo[0] * $imageInfo[1];
            if ($pixelCount > self::MAX_IMAGE_PIXELS) {
                return back()->withErrors(['photo' => 'Dimensi gambar terlalu besar. Maksimal 50 megapixels.']);
            }
        }

        // Strip EXIF metadata for privacy
        try {
            if (in_array($file->getMimeType(), ['image/jpeg']) && function_exists('imagecreatefromjpeg')) {
                $image = imagecreatefromjpeg($file->getRealPath());
                if ($image) {
                    imagejpeg($image, $file->getRealPath(), 90);
                    imagedestroy($image);
                }
            } elseif ($file->getMimeType() === 'image/png' && function_exists('imagecreatefrompng')) {
                $image = imagecreatefrompng($file->getRealPath());
                if ($image) {
                    imagepng($image, $file->getRealPath(), 9);
                    imagedestroy($image);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('EXIF strip failed for profile photo: ' . $e->getMessage());
        }

        $bucket = config('services.supabase.storage_bucket');
        $fileName = 'attachments/profiles/' . $userId . '/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        \Illuminate\Support\Facades\Log::info('Profile photo upload starting', [
            'user_id' => $userId,
            'bucket' => $bucket,
            'filename' => $fileName,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $uploadResult = $this->supabase->uploadFile($bucket, $fileName, file_get_contents($file->getRealPath()), $file->getMimeType(), true);

        \Illuminate\Support\Facades\Log::info('Profile photo upload result', $uploadResult);

        if ($uploadResult['success']) {
            // Use admin=true for profile update to ensure RLS doesn't block legitimate updates
            $updateResult = $this->supabase->update('profiles', ['id' => $userId], ['profile_photo_url' => $fileName], true);
            \Illuminate\Support\Facades\Log::info('Profile database update result', $updateResult);

            $photoUrl = $this->supabase->getSignedUrl($bucket, $fileName, 604800);
            \Illuminate\Support\Facades\Log::info('Signed URL generated', ['url' => $photoUrl]);

            Session::put('profile_photo_url', $photoUrl);
            \Illuminate\Support\Facades\Log::info('Session updated with photo URL', ['session_photo_url' => Session::get('profile_photo_url')]);

            $this->activityLog->log('update', 'Memperbarui foto profil');
            return redirect()->route('profile.edit')->with('success', 'Foto profil berhasil diperbarui.');
        }

        \Illuminate\Support\Facades\Log::error('Profile photo upload failed', ['error' => $uploadResult['error'] ?? 'unknown']);
        return back()->withErrors(['photo' => 'Gagal upload foto.']);
    }

    // Fungsi untuk menangani proses deletePhoto
    public function deletePhoto()
    {
        $userId = Session::get('user_id');
        $bucket = config('services.supabase.storage_bucket');

        $profile = $this->supabase->selectSingle('profiles', 'id', $userId);
        if (!empty($profile['profile_photo_url'])) {
            $this->supabase->deleteFile($bucket, [$profile['profile_photo_url']]);
            $this->supabase->update('profiles', ['id' => $userId], ['profile_photo_url' => null], true);
            Session::forget('profile_photo_url');
            $this->activityLog->log('update', 'Menghapus foto profil');
        }

        return redirect()->route('profile.edit')->with('success', 'Foto profil berhasil dihapus.');
    }

    // Menghapus data dari database
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $userId = Session::get('user_id');
        $email = Session::get('user_email');

        $result = $this->supabase->signIn($email, $request->password);
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah. Akun tidak dihapus.',
            ], 403);
        }

        if (isset($result['data']['access_token'])) {
            $this->supabase->signOut($result['data']['access_token']);
        }

        // Menghapus data terkait
        $this->supabase->delete('notifications', ['user_id' => $userId], true);
        $this->supabase->delete('activity_logs', ['user_id' => $userId], true);
        $this->supabase->delete('leave_balances', ['user_id' => $userId], true);
        $this->supabase->delete('leave_requests', ['user_id' => $userId], true);
        $this->supabase->delete('two_factor_codes', ['user_id' => $userId], true);

        // Menghapus profil pengguna
        $this->supabase->delete('profiles', ['id' => $userId], true);

        // Menghapus akun pengguna
        $this->supabase->adminDeleteUser($userId);

        // Clear session
        Session::flush();

        return response()->json([
            'success' => true,
            'message' => 'Akun Anda telah dihapus permanen.',
        ]);
    }
}
  
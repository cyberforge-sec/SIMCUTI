<?php

namespace App\Http\Controllers;

use App\Services\CaptchaService;

class CaptchaController extends Controller
{
    protected CaptchaService $captcha;

    // Menginisialisasi class dan dependensi
    public function __construct(CaptchaService $captcha)
    {
        $this->captcha = $captcha;
    }

    // Fungsi untuk menangani proses generate
    public function generate()
    {
        $imageData = $this->captcha->create();

        return response($imageData)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    // Fungsi untuk menangani proses refresh
    public function refresh()
    {
        $imageData = $this->captcha->create();

        return response()->json([
            'success' => true,
            'captcha' => $imageData,
        ]);
    }
}
 
<?php

namespace App\Http\Controllers;

use App\Services\CaptchaService;

class CaptchaController extends Controller
{
    protected CaptchaService $captcha;

    public function __construct(CaptchaService $captcha)
    {
        $this->captcha = $captcha;
    }

    public function generate()
    {
        $imageData = $this->captcha->create();

        return response($imageData)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function refresh()
    {
        $imageData = $this->captcha->create();

        return response()->json([
            'success' => true,
            'captcha' => $imageData,
        ]);
    }
}

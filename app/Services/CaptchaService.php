<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class CaptchaService
{
    protected SupabaseService $supabase;
    protected int $length;
    protected int $width;
    protected int $height;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
        $this->length = config('captcha.length', 6);
        $this->width = config('captcha.width', 200);
        $this->height = config('captcha.height', 60);
    }

    /**
     * Generate captcha text (exclude confusing chars: O, 0, I, 1, l)
     */
    protected function generateText(): string
    {
        $characters = config('captcha.characters', 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
        $text = '';
        for ($i = 0; $i < $this->length; $i++) {
            $text .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $text;
    }

    /**
     * Create captcha image and store in Supabase
     */
    public function create(): string
    {
        // Probabilistic cleanup: run bulk cleanup every ~20 requests
        // to prevent unbounded growth of expired captcha_sessions
        if (random_int(1, 20) === 1) {
            $this->cleanupExpired();
        }

        $text = $this->generateText();
        $sessionKey = Str::random(64);
        $ipAddress = request()->ip();

        // Store in Supabase captcha_sessions table (admin=true to bypass RLS)
        $this->supabase->insert('captcha_sessions', [
            'session_key' => $sessionKey,
            'captcha_text' => $text,
            'ip_address' => $ipAddress,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ], true);

        // Store session key in Laravel session
        Session::put('captcha_key', $sessionKey);

        // Create image with GD
        $image = imagecreatetruecolor($this->width, $this->height);

        // Gradient background
        $startColor = imagecolorallocate($image, 79, 70, 229);
        $endColor = imagecolorallocate($image, 67, 56, 202);
        for ($i = 0; $i < $this->height; $i++) {
            $ratio = $i / $this->height;
            $r = (int) (79 + ($ratio * (67 - 79)));
            $g = (int) (70 + ($ratio * (56 - 70)));
            $b = (int) (229 + ($ratio * (202 - 229)));
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $i, $this->width, $i, $color);
        }

        // Noise lines
        $lineColor = imagecolorallocate($image, 129, 140, 248);
        for ($i = 0; $i < 5; $i++) {
            imageline($image, rand(0, $this->width), rand(0, $this->height), rand(0, $this->width), rand(0, $this->height), $lineColor);
        }

        // Noise dots
        $dotColor = imagecolorallocate($image, 203, 213, 225);
        for ($i = 0; $i < 100; $i++) {
            imagesetpixel($image, rand(0, $this->width), rand(0, $this->height), $dotColor);
        }

        // Draw text with rotation and shadow
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $shadowColor = imagecolorallocate($image, 0, 0, 0);
        $font = $this->getFont();
        $charWidth = $this->width / ($this->length + 2);

        for ($i = 0; $i < $this->length; $i++) {
            $char = $text[$i];
            $angle = rand(-20, 20);
            $x = ($i + 1) * $charWidth + rand(-5, 5);
            $y = $this->height / 2 + rand(-5, 10);
            $fontSize = rand(config('captcha.font_size_min', 20), config('captcha.font_size_max', 26));

            // Shadow
            if ($font && file_exists($font)) {
                imagettftext($image, $fontSize, $angle, (int) $x + 2, (int) $y + 2, $shadowColor, $font, $char);
                imagettftext($image, $fontSize, $angle, (int) $x, (int) $y, $textColor, $font, $char);
            } else {
                // Fallback to built-in font
                imagestring($image, 5, (int) $x, (int) ($y - 10), $char, $textColor);
            }
        }

        // Output as base64 PNG
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($imageData);
    }

    /**
     * Get font path
     */
    protected function getFont(): ?string
    {
        $fontPath = resource_path('fonts/captcha.ttf');
        if (file_exists($fontPath)) return $fontPath;

        $systemFont = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        if (file_exists($systemFont)) return $systemFont;

        return null;
    }

    /**
     * Verify captcha input
     */
    public function verify(string $input): bool
    {
        $sessionKey = Session::get('captcha_key');
        if (!$sessionKey) return false;

        // Get captcha from Supabase using service role key (bypass RLS)
        // Must use admin because user is not authenticated yet during login/register
        $records = $this->getCaptchaRecord($sessionKey);
        if (empty($records)) {
            $this->cleanup($sessionKey);
            return false;
        }

        $captchaData = $records[0];

        // Check expiry
        if (strtotime($captchaData['expires_at']) < time()) {
            $this->cleanup($sessionKey);
            return false;
        }

        // Check attempts
        if ($captchaData['attempts'] >= config('captcha.max_attempts', 3)) {
            $this->cleanup($sessionKey);
            return false;
        }

        // Increment attempts
        $this->supabase->update('captcha_sessions', ['session_key' => $sessionKey], [
            'attempts' => $captchaData['attempts'] + 1,
        ], true);

        // Verify (case-insensitive)
        $isValid = strtoupper(trim($input)) === strtoupper($captchaData['captcha_text']);

        if ($isValid) {
            $this->cleanup($sessionKey);
            Session::forget('captcha_key');
        }

        return $isValid;
    }

    /**
     * Cleanup captcha session
     */
    public function cleanup(?string $sessionKey = null): void
    {
        if ($sessionKey) {
            $this->supabase->delete('captcha_sessions', ['session_key' => $sessionKey], true);
        }
    }

    /**
     * Bulk cleanup all expired captcha sessions via Supabase RPC.
     * Prevents unbounded table growth from repeated page loads.
     */
    public function cleanupExpired(): void
    {
        try {
            $this->supabase->rpc('cleanup_expired_captcha', [], true);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Captcha bulk cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Get captcha record using service role key (bypasses RLS)
     * This is needed because during login/register, the user is not authenticated yet
     */
    protected function getCaptchaRecord(string $sessionKey): array
    {
        try {
            $url = config('services.supabase.url');
            $serviceKey = config('services.supabase.service_key');

            $client = new \GuzzleHttp\Client();
            $response = $client->get("{$url}/rest/v1/captcha_sessions", [
                'headers' => [
                    'apikey' => $serviceKey,
                    'Authorization' => 'Bearer ' . $serviceKey,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'select' => '*',
                    'session_key' => 'eq.' . $sessionKey,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Captcha record fetch failed: ' . $e->getMessage());
            return [];
        }
    }
}

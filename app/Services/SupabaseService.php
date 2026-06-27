<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SupabaseService
{
    protected Client $httpClient;
    protected string $url;
    protected string $serviceKey;
    protected string $anonKey;
    protected string $bucket;

    public function __construct()
    {
        // Ambil konfigurasi dari file .env
        $this->url = rtrim(config('services.supabase.url'), '/');
        $this->serviceKey = config('services.supabase.service_key');
        $this->anonKey = config('services.supabase.anon_key');
        $this->bucket = config('services.supabase.storage_bucket');

        // Siapkan HTTP client (Guzzle) buat request ke Supabase
        $this->httpClient = new Client([
            'base_uri' => $this->url,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Get admin-level headers (service role key)
     */
    protected function adminHeaders(): array
    {
        // Balikin header dengan hak akses admin
        return [
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey,
        ];
    }

    /**
     * Get user-level headers (anon key or user access token)
     */
    protected function userHeaders(?string $accessToken = null): array
    {
        // Pakai token dari parameter, session, atau default ke anon key
        $token = $accessToken ?? Session::get('supabase_access_token') ?? $this->anonKey;
        return [
            'apikey' => $this->anonKey,
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    // AUTH METHODS

    /**
     * Sign in with email and password
     */
    public function signIn(string $email, string $password): array
    {
        try {
            // Tembak API Supabase buat proses login user
            $response = $this->httpClient->post('/auth/v1/token?grant_type=password', [
                'headers' => [
                    'apikey' => $this->anonKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => $email,
                    'password' => $password,
                ],
            ]);

            // Kalau sukses, parse datanya jadi array
            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data];
        } catch (GuzzleException $e) {
            // Kalau gagal, ambil pesan errornya dan catat di log
            $error = $this->parseError($e);
            Log::error('Supabase signIn failed: ' . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Sign up a new user
     */
    public function signUp(string $email, string $password, array $metadata = []): array
    {
        try {
            // Tembak API buat daftar akun baru
            $response = $this->httpClient->post('/auth/v1/signup', [
                'headers' => [
                    'apikey' => $this->anonKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'data' => $metadata,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error('Supabase signUp failed: ' . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Sign out
     */
    public function signOut(?string $accessToken = null): array
    {
        try {
            // Logout dengan kirim token akses saat ini
            $token = $accessToken ?? Session::get('supabase_access_token');
            $this->httpClient->post('/auth/v1/logout', [
                'headers' => [
                    'apikey' => $this->anonKey,
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            return ['success' => true];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error('Supabase signOut failed: ' . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Get current user from access token
     */
    public function getUser(?string $accessToken = null): ?array
    {
        try {
            // Cek dulu apakah token ada, kalau nggak ada ya batal
            $token = $accessToken ?? Session::get('supabase_access_token');
            if (!$token) return null;

            // Ambil data user yang lagi login
            $response = $this->httpClient->get('/auth/v1/user', [
                'headers' => [
                    'apikey' => $this->anonKey,
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Supabase getUser failed: ' . $this->parseError($e));
            return null;
        }
    }

    /**
     * Send password reset email
     * @param string $email
     * @param string|null $redirectTo URL to redirect after clicking the email link
     */
    public function resetPasswordEmail(string $email, ?string $redirectTo = null): array
    {
        try {
            $json = ['email' => $email];

            // Mengatur URL redirect
            // Tambahin URL redirect kalau emang disediain
            if ($redirectTo) {
                $json['redirectTo'] = $redirectTo;
            }

            // Kirim email buat reset password
            $response = $this->httpClient->post('/auth/v1/recover', [
                'headers' => [
                    'apikey' => $this->anonKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $json,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error('Supabase resetPassword failed: ' . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Verify OTP token (for recovery, email, etc.)
     * Returns access_token on success which can be used for subsequent requests
     */
    public function verifyOtp(string $token, string $type = 'recovery'): array
    {
        try {
            // Verifikasi kode OTP yang dimasukin user
            $response = $this->httpClient->post('/auth/v1/verify', [
                'headers' => [
                    'apikey' => $this->anonKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'token' => $token,
                    'type'  => $type,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error('Supabase verifyOtp failed: ' . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Update user password (requires valid access token from verifyOtp or session)
     */
    public function updatePassword(string $password, string $accessToken): array
    {
        try {
            // Ganti password pakai token akses yang valid
            $response = $this->httpClient->put('/auth/v1/user', [
                'headers' => [
                    'apikey' => $this->anonKey,
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['password' => $password],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error('Supabase updatePassword failed: ' . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Admin: create user (service role)
     */
    public function adminCreateUser(string $email, string $password, array $userData = []): array
    {
        try {
            // Bikin user baru langsung dari akses level admin
            $response = $this->httpClient->post('/auth/v1/admin/users', [
                'headers' => $this->adminHeaders(),
                'json' => array_merge([
                    'email' => $email,
                    'password' => $password,
                    'email_confirm' => true,
                ], $userData),
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error('Supabase adminCreateUser failed: ' . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Admin: delete user (service role)
     */
    public function adminDeleteUser(string $userId): array
    {
        try {
            // Hapus user secara permanen dari Supabase via admin
            $this->httpClient->delete("/auth/v1/admin/users/{$userId}", [
                'headers' => $this->adminHeaders(),
            ]);
            return ['success' => true];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error('Supabase adminDeleteUser failed: ' . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            // Minta token akses baru pakai refresh token
            $response = $this->httpClient->post('/auth/v1/token?grant_type=refresh_token', [
                'headers' => [
                    'apikey' => $this->anonKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['refresh_token' => $refreshToken],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error('Supabase refreshToken failed: ' . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    // DATABASE (PostgREST) METHODS

    /**
     * Select records from a table
     */
    public function select(string $table, string $columns = '*', array $filters = [], ?string $accessToken = null): array
    {
        try {
            // Siapin query buat narik data sesuai filter
            $query = array_merge(['select' => $columns], $this->buildFilterQuery($filters));

            // Request ambil data dari tabel
            $response = $this->httpClient->get("/rest/v1/{$table}", [
                'headers' => array_merge($this->userHeaders($accessToken), [
                    'Prefer' => 'return=representation',
                ]),
                'query' => $query,
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            Log::error("Select from {$table} failed: " . $this->parseError($e));
            return [];
        }
    }

    /**
     * Select records using admin/service role key (bypasses RLS)
     */
    public function selectAdmin(string $table, string $columns = '*', array $filters = []): array
    {
        try {
            // Tarik data pakai akses admin, jadi tembus semua rule (RLS)
            $query = array_merge(['select' => $columns], $this->buildFilterQuery($filters));

            $response = $this->httpClient->get("/rest/v1/{$table}", [
                'headers' => array_merge($this->adminHeaders(), [
                    'Prefer' => 'return=representation',
                ]),
                'query' => $query,
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            Log::error("SelectAdmin from {$table} failed: " . $this->parseError($e));
            return [];
        }
    }

    /**
     * Select single record
     */
    public function selectSingle(string $table, string $column, $value, string $columns = '*', ?string $accessToken = null): ?array
    {
        try {
            // Ambil tepat satu baris data
            $response = $this->httpClient->get("/rest/v1/{$table}", [
                'headers' => array_merge($this->userHeaders($accessToken), [
                    'Accept' => 'application/vnd.pgrst.object+json',
                ]),
                'query' => [
                    'select' => $columns,
                    $column => 'eq.' . $value, // Cocokin nilai kolom persis
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error("SelectSingle from {$table} failed: " . $this->parseError($e));
            return null;
        }
    }

    /**
     * Select with advanced query (ordering, limiting, etc.)
     */
    public function selectAdvanced(string $table, array $options = [], ?string $accessToken = null, bool $admin = false): array
    {
        try {
            $query = [
                'select' => $options['columns'] ?? '*',
            ];

            // Filters
            // Pasang semua filter tambahan dari opsi
            if (isset($options['filters'])) {
                foreach ($options['filters'] as $col => $val) {
                    $query[$col] = $val;
                }
            }

            // Ordering
            // Urutin hasil kalau diminta
            if (isset($options['order'])) {
                $query['order'] = $options['order'];
            }

            // Limit
            // Batasin jumlah data yang keluar
            if (isset($options['limit'])) {
                $query['limit'] = $options['limit'];
            }

            // Offset
            // Buat ngelewatin sekian data (berguna buat pagination)
            if (isset($options['offset'])) {
                $query['offset'] = $options['offset'];
            }

            // Tentukan pakai header admin atau user biasa
            $headers = $admin ? $this->adminHeaders() : $this->userHeaders($accessToken);

            $response = $this->httpClient->get("/rest/v1/{$table}", [
                'headers' => array_merge($headers, [
                    'Prefer' => 'return=representation',
                ]),
                'query' => $query,
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            Log::error("SelectAdvanced from {$table} failed: " . $this->parseError($e));
            return [];
        }
    }

    /**
     * Insert a record
     */
    public function insert(string $table, array $data, bool $admin = false): array
    {
        try {
            // Masukin record/baris baru ke dalam tabel
            $headers = $admin ? $this->adminHeaders() : $this->userHeaders();
            $response = $this->httpClient->post("/rest/v1/{$table}", [
                'headers' => array_merge($headers, [
                    'Prefer' => 'return=representation',
                    'Content-Type' => 'application/json',
                ]),
                'json' => $data,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $result];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error("Insert into {$table} failed: " . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Update records
     */
    public function update(string $table, array $filters, array $data, bool $admin = false): array
    {
        try {
            // Update data yang udah ada sesuai dengan filternya
            $headers = $admin ? $this->adminHeaders() : $this->userHeaders();
            $query = $this->buildFilterQuery($filters);

            $response = $this->httpClient->patch("/rest/v1/{$table}", [
                'headers' => array_merge($headers, [
                    'Prefer' => 'return=representation',
                    'Content-Type' => 'application/json',
                ]),
                'query' => $query,
                'json' => $data,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $result];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error("Update {$table} failed: " . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Delete records
     */
    public function delete(string $table, array $filters, bool $admin = false): array
    {
        try {
            // Hapus baris di tabel kalau cocok sama filter yang dikasih
            $headers = $admin ? $this->adminHeaders() : $this->userHeaders();
            $query = $this->buildFilterQuery($filters);

            $response = $this->httpClient->delete("/rest/v1/{$table}", [
                'headers' => array_merge($headers, [
                    'Prefer' => 'return=representation',
                ]),
                'query' => $query,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $result];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error("Delete from {$table} failed: " . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Count records
     */
    public function count(string $table, array $filters = [], bool $admin = false): int
    {
        try {
            // Hitung jumlah data yang sesuai kriteria di tabel
            $headers = $admin ? $this->adminHeaders() : $this->userHeaders();
            $query = array_merge(['select' => 'id'], $this->buildFilterQuery($filters));

            $response = $this->httpClient->get("/rest/v1/{$table}", [
                'headers' => array_merge($headers, [
                    'Prefer' => 'count=exact',
                ]),
                'query' => $query,
            ]);

            // Ambil info count dari header Content-Range (Supabase nge-set ini)
            $contentRange = $response->getHeader('Content-Range');
            if (!empty($contentRange)) {
                $parts = explode('/', $contentRange[0]);
                return (int) ($parts[1] ?? 0);
            }

            // Kalau nggak ketemu di header, hitung manual isi arraynya
            $data = json_decode($response->getBody()->getContents(), true);
            return count($data ?? []);
        } catch (GuzzleException $e) {
            Log::error("Count {$table} failed: " . $this->parseError($e));
            return 0;
        }
    }

    /**
     * Execute RPC (Remote Procedure Call / custom SQL function)
     */
    public function rpc(string $function, array $params = [], bool $admin = false): array
    {
        try {
            // Jalanin fungsi SQL buatan sendiri (RPC) di database
            $headers = $admin ? $this->adminHeaders() : $this->userHeaders();
            $response = $this->httpClient->post("/rest/v1/rpc/{$function}", [
                'headers' => array_merge($headers, [
                    'Content-Type' => 'application/json',
                ]),
                'json' => $params,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $result];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error("RPC {$function} failed: " . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    // STORAGE METHODS

    /**
     * Upload file to storage
     */
    public function uploadFile(string $bucket, string $path, $fileContent, string $contentType = 'application/octet-stream', bool $admin = false): array
    {
        try {
            // Upload file ke bucket storage Supabase
            $headers = $admin ? $this->adminHeaders() : $this->userHeaders();
            $response = $this->httpClient->post("/storage/v1/object/{$bucket}/{$path}", [
                'headers' => array_merge($headers, [
                    'Content-Type' => $contentType,
                    'x-upsert' => 'false', // Biar nggak nipah/niban file kalau udah ada
                ]),
                'body' => $fileContent,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data, 'path' => $path];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error("Upload to {$bucket}/{$path} failed: " . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Get signed URL for private file
     */
    public function getSignedUrl(string $bucket, string $path, int $expiresIn = 3600): ?string
    {
        try {
            // Dapetin URL sementara buat akses file yang sifatnya privat
            $response = $this->httpClient->post("/storage/v1/object/sign/{$bucket}/{$path}", [
                'headers' => array_merge($this->adminHeaders(), [
                    'Content-Type' => 'application/json',
                ]),
                'json' => ['expiresIn' => $expiresIn],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $signedUrl = $data['signedURL'] ?? '';

            // Supabase returns signedURL without /storage/v1 prefix, so we need to add it
            // Kalau prefix /storage/v1 belum ada, kita tambahin aja biar bener linknya
            if ($signedUrl && !str_starts_with($signedUrl, '/storage/v1/')) {
                $signedUrl = '/storage/v1' . $signedUrl;
            }

            return $this->url . $signedUrl;
        } catch (GuzzleException $e) {
            Log::error("SignedUrl for {$bucket}/{$path} failed: " . $this->parseError($e));
            return null;
        }
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(string $bucket, array $paths): array
    {
        try {
            // Hapus daftar file dari bucket
            $response = $this->httpClient->delete("/storage/v1/object/{$bucket}", [
                'headers' => array_merge($this->userHeaders(), [
                    'Content-Type' => 'application/json',
                ]),
                'json' => ['prefixes' => $paths],
            ]);

            return ['success' => true];
        } catch (GuzzleException $e) {
            $error = $this->parseError($e);
            Log::error("Delete from storage {$bucket} failed: " . $error);
            return ['success' => false, 'error' => $error];
        }
    }

    // OAUTH METHODS

    /**
     * Get OAuth login URL for a provider (github, google, etc.)
     */
    public function getOAuthUrl(string $provider, string $redirectTo): string
    {
        // Bikin URL buat login via pihak ketiga kayak Google atau Github
        $params = http_build_query([
            'provider' => $provider,
            'redirect_to' => $redirectTo,
        ]);

        return $this->url . '/auth/v1/authorize?' . $params;
    }

    /**
     * Verify an access token and return user data
     */
    public function verifyAccessToken(string $accessToken): ?array
    {
        // Ngecek token sambil ngambil data usernya
        return $this->getUser($accessToken);
    }

    // HELPER METHODS

    /**
     * Build PostgREST filter query array from filters map.
     */
    protected function buildFilterQuery(array $filters): array
    {
        $query = [];
        // Loop tiap filter dan ubah formatnya biar sesuai sintaks PostgREST
        foreach ($filters as $col => $val) {
            // Kalau filternya array, gabungin jadi filter 'in'
            if (is_array($val)) {
                $query[$col] = 'in.(' . implode(',', $val) . ')';
                continue;
            }

            // Kalau null, pakai operator 'is.null'
            if ($val === null) {
                $query[$col] = 'is.null';
                continue;
            }

            $value = (string) $val;
            // Kalau di string udah ada operator eksplisit (misal 'eq.', 'gt.'), pakai itu langsung
            if (preg_match('/^(eq|neq|gt|gte|lt|lte|like|ilike|in|is|not|cs|cd|ov|sl|sr|nxr|nxl|adj|fts|plfts|phfts|wfts)\./', $value)) {
                $query[$col] = $value;
                continue;
            }

            // Kalau nggak dikasih operator spesifik, otomatis pakai 'eq' (sama dengan)
            $query[$col] = 'eq.' . $value;
        }
        return $query;
    }

    /**
     * Parse Guzzle error into a safe, user-displayable message.
     * Logs the full detailed error server-side for debugging,
     * but strips internal DB/schema details before returning to prevent
     * information disclosure (table names, column names, constraint details).
     */
    protected function parseError(GuzzleException $e): string
    {
        // Tangkap pesan asli bawaan dari errornya
        $detailedError = $e->getMessage();
        $statusCode = 0;

        // Coba bongkar response error dari Supabase kalau ada detailnya
        if (method_exists($e, 'getResponse') && $e->getResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            $detailedError = $data['message'] ?? $data['error_description'] ?? $data['error'] ?? $body;
        }

        // Log the FULL detailed error for server-side debugging
        // Catat detail asli error ini di sistem biar gampang di-debug sama developer
        Log::error('Supabase error (detail)', [
            'message' => $detailedError,
            'status_code' => $statusCode,
        ]);


        // Auth errors from Supabase - sanitize to generic messages
        // Bikin pesan yang ramah user buat error seputar otentikasi
        if ($statusCode === 400 || $statusCode === 401 || $statusCode === 422) {
            // Generic auth messages - never expose internal details
            // Jangan pernah tampilin detail teknis database ke tampilan user
            if (str_contains(strtolower($detailedError), 'email_not_confirmed')) {
                return 'Email belum diverifikasi. Silakan cek kotak masuk email Anda.';
            }
            if (str_contains(strtolower($detailedError), 'invalid') || str_contains(strtolower($detailedError), 'credentials')) {
                return 'Email atau password salah.';
            }
            if (str_contains(strtolower($detailedError), 'user_already_exists') || str_contains(strtolower($detailedError), 'already registered')) {
                return 'Email sudah terdaftar. Silakan gunakan email lain atau login.';
            }
            return 'Data yang dimasukkan tidak valid. Silakan periksa kembali.';
        }

        // Rate limiting
        // Penanganan kalau user terlalu barbar spam request
        if ($statusCode === 429) {
            return 'Terlalu banyak percobaan. Silakan coba lagi nanti.';
        }

        // Menangani error
        // to prevent leaking table names, column names, or schema details
        // Buat error lain (kayak server nge-lag dll), kembalikan pesan umum aja
        return 'Terjadi kesalahan pada server. Silakan coba lagi.';
    }

    /**
     * Get the Supabase URL
     */
    public function getUrl(): string
    {
        // Balikin URL Supabase yang diset
        return $this->url;
    }

}

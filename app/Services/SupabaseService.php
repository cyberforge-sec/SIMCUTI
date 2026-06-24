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
        $this->url = rtrim(config('services.supabase.url'), '/');
        $this->serviceKey = config('services.supabase.service_key');
        $this->anonKey = config('services.supabase.anon_key');
        $this->bucket = config('services.supabase.storage_bucket');

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
        $token = $accessToken ?? Session::get('supabase_access_token') ?? $this->anonKey;
        return [
            'apikey' => $this->anonKey,
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    // ============================================================
    // AUTH METHODS
    // ============================================================

    /**
     * Sign in with email and password
     */
    public function signIn(string $email, string $password): array
    {
        try {
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

            $data = json_decode($response->getBody()->getContents(), true);
            return ['success' => true, 'data' => $data];
        } catch (GuzzleException $e) {
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
            $token = $accessToken ?? Session::get('supabase_access_token');
            if (!$token) return null;

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

            // Set redirect URL so Supabase sends a link back to our app
            if ($redirectTo) {
                $json['redirectTo'] = $redirectTo;
            }

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

    // ============================================================
    // DATABASE (PostgREST) METHODS
    // ============================================================

    /**
     * Select records from a table
     */
    public function select(string $table, string $columns = '*', array $filters = [], ?string $accessToken = null): array
    {
        try {
            $query = array_merge(['select' => $columns], $this->buildFilterQuery($filters));

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
            $response = $this->httpClient->get("/rest/v1/{$table}", [
                'headers' => array_merge($this->userHeaders($accessToken), [
                    'Accept' => 'application/vnd.pgrst.object+json',
                ]),
                'query' => [
                    'select' => $columns,
                    $column => 'eq.' . $value,
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
            if (isset($options['filters'])) {
                foreach ($options['filters'] as $col => $val) {
                    $query[$col] = $val;
                }
            }

            // Ordering
            if (isset($options['order'])) {
                $query['order'] = $options['order'];
            }

            // Limit
            if (isset($options['limit'])) {
                $query['limit'] = $options['limit'];
            }

            // Offset
            if (isset($options['offset'])) {
                $query['offset'] = $options['offset'];
            }

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
            $headers = $admin ? $this->adminHeaders() : $this->userHeaders();
            $query = array_merge(['select' => 'id'], $this->buildFilterQuery($filters));

            $response = $this->httpClient->get("/rest/v1/{$table}", [
                'headers' => array_merge($headers, [
                    'Prefer' => 'count=exact',
                ]),
                'query' => $query,
            ]);

            $contentRange = $response->getHeader('Content-Range');
            if (!empty($contentRange)) {
                $parts = explode('/', $contentRange[0]);
                return (int) ($parts[1] ?? 0);
            }

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

    // ============================================================
    // STORAGE METHODS
    // ============================================================

    /**
     * Upload file to storage
     */
    public function uploadFile(string $bucket, string $path, $fileContent, string $contentType = 'application/octet-stream', bool $admin = false): array
    {
        try {
            $headers = $admin ? $this->adminHeaders() : $this->userHeaders();
            $response = $this->httpClient->post("/storage/v1/object/{$bucket}/{$path}", [
                'headers' => array_merge($headers, [
                    'Content-Type' => $contentType,
                    'x-upsert' => 'false',
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
            $response = $this->httpClient->post("/storage/v1/object/sign/{$bucket}/{$path}", [
                'headers' => array_merge($this->adminHeaders(), [
                    'Content-Type' => 'application/json',
                ]),
                'json' => ['expiresIn' => $expiresIn],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $signedUrl = $data['signedURL'] ?? '';

            // Supabase returns signedURL without /storage/v1 prefix, so we need to add it
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

    // ============================================================
    // OAUTH METHODS
    // ============================================================

    /**
     * Get OAuth login URL for a provider (github, google, etc.)
     */
    public function getOAuthUrl(string $provider, string $redirectTo): string
    {
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
        return $this->getUser($accessToken);
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Build PostgREST filter query array from filters map.
     */
    protected function buildFilterQuery(array $filters): array
    {
        $query = [];
        foreach ($filters as $col => $val) {
            if (is_array($val)) {
                $query[$col] = 'in.(' . implode(',', $val) . ')';
            } else {
                $query[$col] = 'eq.' . $val;
            }
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
        $detailedError = $e->getMessage();
        $statusCode = 0;

        if (method_exists($e, 'getResponse') && $e->getResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            $detailedError = $data['message'] ?? $data['error_description'] ?? $data['error'] ?? $body;
        }

        // Log the FULL detailed error for server-side debugging
        Log::error('Supabase error (detail)', [
            'message' => $detailedError,
            'status_code' => $statusCode,
        ]);

        // --- Sanitize: return safe messages for user-facing display ---

        // Auth errors from Supabase - sanitize to generic messages
        if ($statusCode === 400 || $statusCode === 401 || $statusCode === 422) {
            // Generic auth messages - never expose internal details
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
        if ($statusCode === 429) {
            return 'Terlalu banyak percobaan. Silakan coba lagi nanti.';
        }

        // For all other errors (403, 404, 409, 5xx), return generic messages
        // to prevent leaking table names, column names, or schema details
        return 'Terjadi kesalahan pada server. Silakan coba lagi.';
    }

    /**
     * Get the Supabase URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

}

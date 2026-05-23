<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Organisasi;
use App\Models\AnggotaOrganisasi;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * SocialiteController — Menangani autentikasi OAuth 2.0 dengan Google.
 *
 * Flow:
 * 1. Frontend hit GET /api/auth/google/redirect
 * 2. Backend redirect ke Google OAuth consent screen
 * 3. Google callback ke GET /api/auth/google/callback
 * 4. Backend buat Sanctum token, simpan di httpOnly cookie
 * 5. Backend redirect ke frontend /auth/callback
 */
class SocialiteController extends Controller
{
    /** Nama cookie untuk menyimpan Sanctum token */
    private const COOKIE_NAME = 'moneflo_token';

    /** Durasi cookie dalam menit (7 hari) */
    private const COOKIE_MINUTES = 60 * 24 * 7;

    /**
     * Redirect pengguna ke halaman consent Google OAuth.
     * Route: GET /api/auth/google/redirect
     */
    public function redirectToProvider(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $action = $request->query('action', 'login');
        $email = $request->query('email', '');
        
        $state = base64_encode(json_encode(['action' => $action, 'email' => $email]));

        return Socialite::driver('google')
            ->stateless()
            ->with(['prompt' => 'select_account', 'state' => $state]) // Selalu tampilkan pilihan akun
            ->redirect();
    }

    /**
     * Handle callback dari Google setelah user mengizinkan akses.
     * Route: GET /api/auth/google/callback
     */
    public function handleProviderCallback(Request $request)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $stateRaw = $request->query('state', '');
        
        $action = 'login';
        $expectedEmail = '';
        if ($stateRaw) {
            $decoded = json_decode(base64_decode($stateRaw), true);
            if (is_array($decoded)) {
                $action = $decoded['action'] ?? 'login';
                $expectedEmail = $decoded['email'] ?? '';
            }
        }

        try {
            // Ambil data user dari Google
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return redirect("{$frontendUrl}/auth/callback?error=oauth_failed&message=" . urlencode($e->getMessage()));
        }

        // --- Alur Khusus Verifikasi Ulang Google (Reset Password) ---
        if ($action === 'verify_password') {
            if ($googleUser->getEmail() !== $expectedEmail) {
                return redirect("{$frontendUrl}/dashboard/pengaturan?google_verified=error&message=" . urlencode('Akun Google yang Anda pilih tidak cocok dengan akun saat ini.'));
            }

            $verifyToken = (string) rand(100000, 999999);
            Cache::put('google_verify_' . $googleUser->getEmail(), $verifyToken, now()->addMinutes(10));
            return redirect("{$frontendUrl}/dashboard/pengaturan?google_verified=true&token={$verifyToken}");
        }
        // -------------------------------------------------------------

        try {
            // Cari user berdasarkan provider_id atau email
            $user = User::where('provider', 'google')
                        ->where('provider_id', $googleUser->getId())
                        ->first();

            if (!$user) {
                // Coba cari berdasarkan email (user mungkin sudah daftar manual)
                $user = User::where('email', $googleUser->getEmail())->first();
            }

            if ($user) {
                // Update data OAuth jika user sudah ada
                $user->update([
                    'provider'       => 'google',
                    'provider_id'    => $googleUser->getId(),
                    'provider_token' => $googleUser->token,
                    // Update avatar hanya jika belum ada custom avatar
                    'avatar'         => $user->avatar ?: $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            } else {
                // User belum terdaftar, arahkan ke form pendaftaran frontend
                $name = urlencode($googleUser->getName());
                $email = urlencode($googleUser->getEmail());
                return redirect("{$frontendUrl}/register?email={$email}&name={$name}&oauth=true");
            }

            // Cek apakah user aktif
            if (!$user->is_active) {
                return redirect("{$frontendUrl}/auth/callback?error=account_inactive");
            }

            // Update waktu login terakhir
            $user->updateLastLogin();

            // Hapus token lama agar tidak menumpuk
            $user->tokens()->where('name', 'moneflo-app')->delete();

            // Buat Sanctum API token baru
            $token = $user->createToken('moneflo-app')->plainTextToken;

            // Set httpOnly cookie yang berisi token
            // Di production: secure=true + SameSite=None WAJIB untuk cross-domain
            // (Frontend Cloudflare Pages ≠ domain Backend DomCloud)
            $isProduction = app()->environment('production');
            $cookie = Cookie::make(
                name:     self::COOKIE_NAME,
                value:    $token,
                minutes:  self::COOKIE_MINUTES,
                path:     '/',
                domain:   null,
                secure:   $isProduction,   // true di production (HTTPS)
                httpOnly: true,
                raw:      false,
                sameSite: $isProduction ? 'None' : 'Lax',  // None wajib untuk cross-domain
            );

            return redirect("{$frontendUrl}/auth/callback?status=success&role={$user->role}")
                ->withCookie($cookie);

        } catch (\Exception $e) {
            return redirect("{$frontendUrl}/auth/callback?error=server_error&message=" . urlencode($e->getMessage()));
        }
    }

    /**
     * Ambil data user yang sedang login dari httpOnly cookie.
     * Route: GET /api/user
     * Middleware: auth:sanctum
     */
    public function getAuthenticatedUser(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // Load relasi keanggotaan secara aman
            $user->load(['keanggotaan.organisasi']);

            // Ambil organisasi aktif user (pertama yang ditemukan)
            // Catatan: tidak filter is_active di sini agar organisasi yang suspended tetap terdeteksi
            $organisasi = null;
            try {
                $organisasi = $user->organisasi()
                    ->wherePivot('is_active', true)
                    ->first();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Gagal load organisasi user', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            return response()->json([
                'user' => [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'email'         => $user->email,
                    'avatar'        => $user->avatar_url,
                    'initials'      => $user->initials,
                    'role'          => $user->role,
                    'is_active'     => $user->is_active,
                    'last_login_at' => $user->last_login_at?->toISOString(),
                    'provider'      => $user->provider,
                ],
                'organisasi' => $organisasi ? [
                    'id'               => $organisasi->id,
                    'name'             => $organisasi->name,
                    'code'             => $organisasi->code,
                    'type'             => $organisasi->type,
                    'email'            => $organisasi->email,
                    'phone'            => $organisasi->phone,
                    'logo_url'         => $organisasi->logo_url,
                    'role_anggota'     => $organisasi->pivot->role ?? null,
                    'is_active'        => (bool) $organisasi->is_active,
                    'is_suspended'     => (bool) $organisasi->is_suspended,
                    'suspended_reason' => $organisasi->suspended_reason,
                    'suspended_at'     => $organisasi->suspended_at?->toISOString(),
                ] : null,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('getAuthenticatedUser error', [
                'user_id' => $request->user()?->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Terjadi kesalahan saat mengambil profil'], 500);
        }
    }

    /**
     * Logout: hapus Sanctum token dan httpOnly cookie.
     * Route: POST /api/logout
     * Middleware: auth:sanctum
     */
    public function logout(Request $request): JsonResponse
    {
        // Hapus token saat ini jika ada
        if ($token = $request->user()->currentAccessToken()) {
            $token->delete();
        }

        // Hapus cookie
        $cookie = Cookie::forget(self::COOKIE_NAME);

        return response()->json(['message' => 'Berhasil logout'])
                         ->withCookie($cookie);
    }

    /**
     * Login manual dengan email & password (fallback non-OAuth).
     * Route: POST /api/auth/login
     */
    public function loginWithCredentials(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !password_verify($request->password, $user->password ?? '')) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Akun Anda tidak aktif'], 403);
        }

        $user->updateLastLogin();
        $user->tokens()->where('name', 'moneflo-app')->delete();

        $token = $user->createToken('moneflo-app')->plainTextToken;

        $isProduction = app()->environment('production');
        $cookie = Cookie::make(
            name:     self::COOKIE_NAME,
            value:    $token,
            minutes:  60 * 24 * 7,
            path:     '/',
            domain:   null,
            secure:   $isProduction,
            httpOnly: true,
            raw:      false,
            sameSite: $isProduction ? 'None' : 'Lax',
        );

        return response()->json([
            'message' => 'Login berhasil',
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'role'     => $user->role,
                'avatar'   => $user->avatar_url,
                'initials' => $user->initials,
            ],
        ])->withCookie($cookie);
    }

    /**
     * Pendaftaran manual (Organisasi baru).
     * Route: POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'  => 'required|string|max:255', // Nama Organisasi
            'type'  => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|min:11|max:20',
            'pass'  => 'nullable|string|min:8', // Optional untuk Google OAuth
            'desc'  => 'nullable|string',
        ]);

        // Validasi OTP jika bukan dari Google OAuth
        if (!$request->get('oauth')) {
            $request->validate(['otp' => 'required|string']);

            $cachedOtp = Cache::get('otp_' . $request->email);
            if (!$cachedOtp || $cachedOtp !== $request->otp) {
                return response()->json(['message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'], 400);
            }

            // Hapus OTP setelah berhasil diverifikasi
            Cache::forget('otp_' . $request->email);
        }

        try {
            DB::beginTransaction();

            // 1. Buat User Baru
            $user = User::create([
                // Gunakan nama organisasi ditambah keterangan sebagai default nama user
                'name'              => 'Admin ' . $request->name,
                'email'             => $request->email,
                'password'          => $request->pass ? Hash::make($request->pass) : null,
                'role'              => 'user',
                'is_active'         => true,
                'provider'          => $request->get('oauth') ? 'google' : null,
                'email_verified_at' => now(),
            ]);

            // 2. Buat Organisasi
            $orgCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $request->name), 0, 4)) . rand(1000, 9999);
            $organisasi = Organisasi::create([
                'name'        => $request->name,
                'code'        => $orgCode,
                'type'        => $request->type,
                'email'       => $request->email,
                'phone'       => $request->phone,
                'description' => $request->desc,
                'is_active'   => true,
                'created_by'  => $user->id,
            ]);

            // 3. Hubungkan User dengan Organisasi sebagai Ketua
            AnggotaOrganisasi::create([
                'user_id'       => $user->id,
                'organisasi_id' => $organisasi->id,
                'role'          => 'ketua',
                'joined_at'     => now(),
                'is_active'     => true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Registrasi berhasil! Silakan masuk.',
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mendaftar: ' . $e->getMessage()
            ], 500);
        }
    }
}

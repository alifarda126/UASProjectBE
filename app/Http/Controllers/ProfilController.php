<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Cache;

/**
 * ProfilController — Manajemen profil user (update, avatar, password).
 */
class ProfilController extends Controller
{
    /** Ambil data profil user yang sedang login */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('organisasi');

        return response()->json([
            'data' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'avatar'        => $this->getAvatarUrl($user->avatar),
                'initials'      => $user->initials,
                'role'          => $user->role,
                'provider'      => $user->provider,
                'is_active'     => $user->is_active,
                'last_login_at' => $user->last_login_at?->toISOString(),
                'created_at'    => $user->created_at?->toISOString(),

                'organisasi' => $user->organisasi->map(fn ($o) => [
                    'id'           => $o->id,
                    'name'         => $o->name,
                    'code'         => $o->code,
                    'role_anggota' => $o->pivot->role,
                ]),
            ],
        ]);
    }


    /** Update nama dan email profil */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        if ($user->email !== $validated['email']) {

            $request->validate([
                'otp' => 'required|string'
            ]);

            $cachedOtp = Cache::get(
                'otp_' . $validated['email']
            );

            if (
                !$cachedOtp ||
                $cachedOtp !== $request->otp
            ) {
                return response()->json([
                    'message' =>
                        'Kode OTP tidak valid atau sudah kedaluwarsa.'
                ], 400);
            }

            Cache::forget(
                'otp_' . $validated['email']
            );
        }

        $oldEmail = $user->email;

        $user->update($validated);

        // Sinkronisasi email ke organisasi yang dibuat oleh user ini
        if (
            isset($validated['email']) &&
            $oldEmail !== $validated['email']
        ) {

            \App\Models\Organisasi::where(
                'created_by',
                $user->id
            )->update([
                'email' => $validated['email']
            ]);
        }

        return response()->json([
            'message' => 'Profil berhasil diupdate',

            'data' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'avatar'   => $this->getAvatarUrl($user->avatar),
                'initials' => $user->initials,
            ],
        ]);
    }


    /** Ganti password */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Cek jika menggunakan Google Verification Token
        if ($request->has('google_token')) {

            $cachedToken = Cache::get(
                'google_verify_' . $user->email
            );

            if (
                !$cachedToken ||
                $cachedToken !== $request->google_token
            ) {
                return response()->json([
                    'message' =>
                        'Sesi verifikasi Google tidak valid atau sudah kedaluwarsa. Silakan coba lagi.'
                ], 400);
            }

            Cache::forget(
                'google_verify_' . $user->email
            );

            $request->validate([
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->letters()
                        ->numbers()
                ],
            ]);

        } else {

            // User OAuth biasa tidak bisa ganti password tanpa token
            if ($user->provider === 'google') {

                return response()->json([
                    'message' =>
                        'Akun Google tidak bisa mengubah password di sini'
                ], 422);
            }

            // Cek apakah menggunakan OTP
            if ($request->has('otp')) {

                $request->validate([
                    'otp' => [
                        'required',
                        'string',
                        'size:6'
                    ],

                    'password' => [
                        'required',
                        'confirmed',
                        Password::min(8)
                            ->letters()
                            ->numbers()
                    ],
                ]);

                $cachedOtp = Cache::get(
                    'otp_' . $user->email
                );

                if (
                    !$cachedOtp ||
                    $cachedOtp !== $request->otp
                ) {
                    return response()->json([
                        'message' =>
                            'Kode OTP tidak valid atau sudah kedaluwarsa.'
                    ], 400);
                }

                Cache::forget(
                    'otp_' . $user->email
                );

            } else {

                $request->validate([
                    'current_password' => 'required',

                    'password' => [
                        'required',
                        'confirmed',
                        Password::min(8)
                            ->letters()
                            ->numbers()
                    ],
                ]);

                // Verifikasi password lama
                if (
                    !Hash::check(
                        $request->current_password,
                        $user->password ?? ''
                    )
                ) {
                    return response()->json([
                        'message' =>
                            'Password lama tidak sesuai'
                    ], 422);
                }
            }
        }

        $user->update([
            'password' => Hash::make(
                $request->password
            )
        ]);

        return response()->json([
            'message' =>
                'Password berhasil diubah'
        ]);
    }


    /**
     * Upload foto profil (avatar).
     *
     * File baru disimpan ke Supabase S3.
     * Database hanya menyimpan PATH, bukan signed URL.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' =>
                'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();

        $disk = Storage::disk('s3');

        /*
         * Hapus avatar lama jika avatar lama merupakan
         * file milik storage kita.
         *
         * Jika avatar berupa URL eksternal (contohnya Google),
         * jangan dihapus.
         */
        $oldAvatarPath = $this->resolveAvatarPath(
            $user->avatar
        );

        if ($oldAvatarPath) {

            try {

                if ($disk->exists($oldAvatarPath)) {
                    $disk->delete($oldAvatarPath);
                }

            } catch (\Throwable $e) {

                Log::warning(
                    'Gagal menghapus avatar lama',
                    [
                        'path'    => $oldAvatarPath,
                        'message' => $e->getMessage(),
                    ]
                );
            }
        }

        /*
         * Buat nama file unik.
         */
        $extension = strtolower(
            $request
                ->file('avatar')
                ->getClientOriginalExtension()
        );

        $safeName =
            'avatar-'
            . $user->id
            . '-'
            . \Illuminate\Support\Str::random(12)
            . '.'
            . $extension;

        $path = 'avatars/' . $safeName;

        /*
         * Upload langsung ke Supabase S3.
         */
        try {

            $stream = fopen(
                $request->file('avatar')->getRealPath(),
                'rb'
            );

            if ($stream === false) {
                throw new \RuntimeException(
                    'File temporary avatar tidak dapat dibuka.'
                );
            }

            try {

                $uploaded = $disk->put(
                    $path,
                    $stream
                );

            } finally {

                fclose($stream);
            }

            if (!$uploaded) {
                throw new \RuntimeException(
                    'Upload avatar gagal.'
                );
            }

        } catch (\Throwable $e) {

            Log::error(
                'Upload avatar S3 gagal',
                [
                    'user_id'   => $user->id,
                    'path'      => $path,
                    'message'   => $e->getMessage(),
                    'exception' => get_class($e),
                ]
            );

            return response()->json([
                'message' =>
                    'Gagal mengupload avatar: '
                    . $e->getMessage(),
            ], 422);
        }

        /*
         * Simpan PATH saja ke database.
         *
         * Jangan simpan temporary/signed URL karena URL
         * tersebut memiliki masa berlaku.
         */
        $user->update([
            'avatar' => $path
        ]);

        /*
         * Generate signed URL baru untuk frontend.
         */
        $avatarUrl = $this->getAvatarUrl(
            $path
        );

        return response()->json([
            'message' =>
                'Avatar berhasil diupload',

            'avatar_url' =>
                $avatarUrl,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    /**
     * Generate signed URL avatar.
     *
     * Mendukung:
     * - avatar baru berupa path S3
     * - avatar lama berupa path S3
     * - avatar lama berupa URL S3
     * - avatar eksternal seperti URL Google
     */
    private function getAvatarUrl(
        ?string $avatar
    ): ?string {

        if (!$avatar) {
            return null;
        }

        /*
         * Kalau avatar adalah URL eksternal dan bukan URL
         * Supabase storage kita, gunakan URL tersebut langsung.
         */
        $path = $this->resolveAvatarPath(
            $avatar
        );

        if (!$path) {

            if (
                filter_var(
                    $avatar,
                    FILTER_VALIDATE_URL
                )
            ) {
                return $avatar;
            }

            return null;
        }

        try {

            return Storage::disk('s3')
                ->temporaryUrl(
                    $path,
                    now()->addMinutes(60)
                );

        } catch (\Throwable $e) {

            Log::warning(
                'Gagal membuat signed URL avatar',
                [
                    'path'    => $path,
                    'message' => $e->getMessage(),
                ]
            );

            return null;
        }
    }


    /**
     * Cari path avatar dari database.
     *
     * Mendukung data lama yang:
     * 1. sudah menyimpan path
     * 2. menyimpan URL S3 lama
     * 3. menyimpan URL Supabase Storage.
     */
    private function resolveAvatarPath(
        ?string $avatar
    ): ?string {

        if (!$avatar) {
            return null;
        }

        $avatar = trim($avatar);

        if ($avatar === '') {
            return null;
        }

        /*
         * Kalau bukan URL, anggap sebagai path storage.
         */
        if (
            !filter_var(
                $avatar,
                FILTER_VALIDATE_URL
            )
        ) {

            /*
             * Hanya proses path milik folder avatar.
             */
            $path = ltrim(
                $avatar,
                '/'
            );

            if (
                str_starts_with(
                    $path,
                    'avatars/'
                )
            ) {
                return $path;
            }

            return null;
        }

        /*
         * URL Supabase S3:
         *
         * https://...storage.supabase.co/storage/v1/s3/
         * moneflo-storage/avatars/file.png
         */
        $endpoint = rtrim(
            (string) env('AWS_ENDPOINT'),
            '/'
        );

        $bucket = trim(
            (string) env('AWS_BUCKET'),
            '/'
        );

        if (
            $endpoint !== '' &&
            $bucket !== ''
        ) {

            $prefix =
                $endpoint
                . '/'
                . $bucket
                . '/';

            if (
                str_starts_with(
                    $avatar,
                    $prefix
                )
            ) {

                $path = urldecode(
                    substr(
                        $avatar,
                        strlen($prefix)
                    )
                );

                if (
                    str_starts_with(
                        $path,
                        'avatars/'
                    )
                ) {
                    return ltrim(
                        $path,
                        '/'
                    );
                }
            }
        }

        /*
         * Fallback untuk URL Supabase Storage REST:
         *
         * /storage/v1/object/public/bucket/avatars/...
         */
        try {

            $parsed = parse_url(
                $avatar
            );

            $urlPath = $parsed['path'] ?? '';

            $markers = [
                '/storage/v1/object/public/' . $bucket . '/',
                '/storage/v1/object/sign/' . $bucket . '/',
                '/storage/v1/object/authenticated/' . $bucket . '/',
            ];

            foreach ($markers as $marker) {

                $position = strpos(
                    $urlPath,
                    $marker
                );

                if ($position !== false) {

                    $path = urldecode(
                        substr(
                            $urlPath,
                            $position + strlen($marker)
                        )
                    );

                    if (
                        str_starts_with(
                            $path,
                            'avatars/'
                        )
                    ) {
                        return ltrim(
                            $path,
                            '/'
                        );
                    }
                }
            }

        } catch (\Throwable $e) {

            Log::warning(
                'Gagal membaca path avatar lama',
                [
                    'message' =>
                        $e->getMessage()
                ]
            );
        }

        /*
         * URL eksternal seperti Google tidak diproses.
         */
        return null;
    }
}

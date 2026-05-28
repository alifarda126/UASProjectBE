<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
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
                'avatar'        => $user->avatar_url,
                'initials'      => $user->initials,
                'role'          => $user->role,
                'provider'      => $user->provider,
                'is_active'     => $user->is_active,
                'last_login_at' => $user->last_login_at?->toISOString(),
                'created_at'    => $user->created_at?->toISOString(),
                'organisasi'    => $user->organisasi->map(fn($o) => [
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
            $request->validate(['otp' => 'required|string']);
            
            $cachedOtp = Cache::get('otp_' . $validated['email']);
            if (!$cachedOtp || $cachedOtp !== $request->otp) {
                return response()->json(['message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'], 400);
            }
            
            Cache::forget('otp_' . $validated['email']);
        }

        $oldEmail = $user->email;
        $user->update($validated);

        // Sinkronisasi email ke organisasi yang dibuat oleh user ini (jika email berubah)
        if (isset($validated['email']) && $oldEmail !== $validated['email']) {
            \App\Models\Organisasi::where('created_by', $user->id)
                ->update(['email' => $validated['email']]);
        }

        return response()->json([
            'message' => 'Profil berhasil diupdate',
            'data' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'avatar'   => $user->avatar_url,
                'initials' => $user->initials,
            ],
        ]);
    }

    /** Ganti password (hanya untuk user non-OAuth) */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        // Cek jika menggunakan Google Verification Token
        if ($request->has('google_token')) {
            $cachedToken = Cache::get('google_verify_' . $user->email);
            if (!$cachedToken || $cachedToken !== $request->google_token) {
                return response()->json(['message' => 'Sesi verifikasi Google tidak valid atau sudah kedaluwarsa. Silakan coba lagi.'], 400);
            }
            Cache::forget('google_verify_' . $user->email);
            
            $request->validate([
                'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            ]);
            
            // Jika token valid, user google diizinkan ganti password
        } else {
            // User OAuth biasa tidak bisa ganti password tanpa token
            if ($user->provider === 'google') {
                return response()->json(['message' => 'Akun Google tidak bisa mengubah password di sini'], 422);
            }

            // Cek apakah menggunakan OTP
            if ($request->has('otp')) {
                $request->validate([
                    'otp'      => 'required|string|size:6',
                    'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
                ]);

                $cachedOtp = Cache::get('otp_' . $user->email);
                if (!$cachedOtp || $cachedOtp !== $request->otp) {
                    return response()->json(['message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'], 400);
                }
                Cache::forget('otp_' . $user->email);
            } else {
                $request->validate([
                    'current_password' => 'required',
                    'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
                ]);

                // Verifikasi password lama
                if (!Hash::check($request->current_password, $user->password ?? '')) {
                    return response()->json(['message' => 'Password lama tidak sesuai'], 422);
                }
            }
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password berhasil diubah']);
    }

    /** Upload foto profil (avatar) */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            // Gambar: JPG/PNG/WEBP, maks. 2MB
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();
        $disk = config('filesystems.default');

        // Hapus avatar lama jika ada dan bukan URL eksternal
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            Storage::disk($disk)->delete($user->avatar);
        }

        // Simpan avatar baru ke disk aktif (S3 atau local)
        $path = $request->file('avatar')->store('avatars', $disk);
        $user->update(['avatar' => $path]);

        return response()->json([
            'message'    => 'Avatar berhasil diupload',
            'avatar_url' => $user->avatar_url,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class OtpController extends Controller
{
    /**
     * Send OTP to the specified email.
     * Route: POST /api/auth/send-otp
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'email'  => 'required|email',
            'action' => 'required|string|in:register,update_email,forgot_password',
        ]);

        $email = $request->email;
        $action = $request->action;

        // Jika action forgot_password, pastikan email sudah terdaftar
        if ($action === 'forgot_password') {
            $userExists = \App\Models\User::where('email', $email)->exists();
            if (!$userExists) {
                return response()->json([
                    'message' => 'Email tidak terdaftar. Periksa kembali email Anda.'
                ], 404);
            }
        }

        // Generate a 6-digit OTP
        $otp = (string) rand(100000, 999999);

        // Store OTP in cache for 5 minutes
        Cache::put('otp_' . $email, $otp, now()->addMinutes(5));

        // Send OTP via email
        try {
            Mail::to($email)->send(new OtpMail($otp, $action));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengirim email OTP: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Kode OTP berhasil dikirim ke email Anda.'
        ]);
    }

    /**
     * Verify OTP only (without consuming it from cache).
     * Route: POST /api/auth/verify-otp
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $cachedOtp = Cache::get('otp_' . $request->email);

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json(['message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'], 400);
        }

        return response()->json(['message' => 'Kode OTP valid.']);
    }

    /**
     * Verify OTP and reset user password.
     * Route: POST /api/auth/forgot-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'                 => 'required|email|exists:users,email',
            'otp'                   => 'required|string|size:6',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $email = $request->email;

        $cachedOtp = Cache::get('otp_' . $email);
        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json(['message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'], 400);
        }

        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'Akun dengan email tersebut tidak ditemukan.'], 404);
        }

        // Reset password dan hapus OTP dari cache
        $user->update(['password' => \Illuminate\Support\Facades\Hash::make($request->password)]);
        Cache::forget('otp_' . $email);

        return response()->json(['message' => 'Kata sandi berhasil diubah. Silakan login dengan kata sandi baru Anda.']);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    /**
     * Send OTP to the specified email.
     * Route: POST /api/auth/send-otp
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'email'  => 'required|email:rfc,dns',
            'action' => 'required|string|in:register,update_email,forgot_password',
        ]);

        $email  = $request->email;
        $action = $request->action;

        // Generate a cryptographically random 6-digit OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Persist OTP in cache for 5 minutes (overwrites any previous OTP for this email)
        Cache::put('otp_' . $email, $otp, now()->addMinutes(5));

        // Dispatch OTP mail to queue (non-blocking) to avoid SMTP timeout on the HTTP request
        try {
            Mail::to($email)->queue(new OtpMail($otp, $action));
        } catch (\Exception $e) {
            // Remove the cached OTP so a stale code is never accepted
            Cache::forget('otp_' . $email);

            Log::error('OTP mail queue dispatch failed', [
                'email'  => $email,
                'action' => $action,
                'error'  => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Gagal mengirim email OTP. Periksa koneksi server atau konfigurasi email, lalu coba lagi.',
            ], 500);
        }

        Log::info('OTP mail queued successfully', [
            'email'  => $email,
            'action' => $action,
        ]);

        return response()->json([
            'message' => 'Kode OTP berhasil dikirim ke email Anda. Kode berlaku selama 5 menit.',
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
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = $request->email;

        $cachedOtp = Cache::get('otp_' . $email);
        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json(['message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'], 400);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'Akun dengan email tersebut tidak ditemukan.'], 404);
        }

        // Update password and invalidate the OTP immediately
        $user->update(['password' => Hash::make($request->password)]);
        Cache::forget('otp_' . $email);

        Log::info('Password reset via OTP', ['email' => $email]);

        return response()->json(['message' => 'Kata sandi berhasil diubah. Silakan login dengan kata sandi baru Anda.']);
    }
}

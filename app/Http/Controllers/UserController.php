<?php

namespace App\Http\Controllers;

use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Jenssegers\Agent\Agent;

class UserController extends Controller
{
    /**
     * Get current user session (active session)
     */
    public function getCurrentSession(Request $request): JsonResponse
    {
        $session = UserSession::where('user_id', $request->user()->id)
            ->where('is_current', true)
            ->first();
        
        if (!$session) {
            // Deteksi dari request jika belum ada di database
            $agent = new Agent();
            
            $device = 'Desktop';
            $icon = 'fa-desktop';
            if ($agent->isMobile()) {
                $device = 'Mobile';
                $icon = 'fa-mobile-alt';
            } elseif ($agent->isTablet()) {
                $device = 'Tablet';
                $icon = 'fa-tablet-alt';
            }
            
            // Deteksi OS
            $os = $agent->platform();
            if (empty($os)) {
                $ua = $request->userAgent();
                if (str_contains($ua, 'Windows')) $os = 'Windows';
                elseif (str_contains($ua, 'Mac')) $os = 'MacOS';
                elseif (str_contains($ua, 'Linux')) $os = 'Linux';
                elseif (str_contains($ua, 'Android')) $os = 'Android';
                elseif (str_contains($ua, 'iPhone')) $os = 'iOS';
                else $os = 'Unknown';
            }
            
            // Deteksi Browser
            $browser = $agent->browser();
            if (empty($browser)) {
                $ua = $request->userAgent();
                if (str_contains($ua, 'Edg')) $browser = 'Edge';
                elseif (str_contains($ua, 'Chrome')) $browser = 'Chrome';
                elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
                elseif (str_contains($ua, 'Safari')) $browser = 'Safari';
                else $browser = 'Unknown';
            }
            
            return response()->json([
                'device' => $device,
                'os' => $os,
                'browser' => $browser,
                'location' => 'Belum terdeteksi',
                'icon' => $icon,
                'ip_address' => $request->ip(),
                'last_activity' => null,
            ]);
        }
        
        return response()->json([
            'device' => $session->device,
            'os' => $session->os,
            'browser' => $session->browser,
            'location' => $session->location,
            'icon' => $session->icon,
            'ip_address' => $session->ip_address,
            'last_activity' => $session->last_activity?->diffForHumans(),
        ]);
    }
}
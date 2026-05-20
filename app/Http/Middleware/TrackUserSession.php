<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserSession;
use Illuminate\Support\Facades\Session;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya track jika user login
        if (auth()->check()) {
            $agent = new Agent();
            $sessionId = Session::getId();
            
            // Deteksi device
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
            
            // Dapatkan lokasi dari IP
            $location = $this->getLocationFromIp($request->ip());
            
            // Update atau create session
            UserSession::updateOrCreate(
                ['session_id' => $sessionId],
                [
                    'user_id' => auth()->id(),
                    'device' => $device,
                    'os' => $os,
                    'browser' => $browser,
                    'ip_address' => $request->ip(),
                    'location' => $location,
                    'icon' => $icon,
                    'is_current' => true,
                    'last_activity' => now(),
                ]
            );
            
            // Set session lain menjadi non-current
            UserSession::where('user_id', auth()->id())
                ->where('session_id', '!=', $sessionId)
                ->update(['is_current' => false]);
        }
        
        return $next($request);
    }
    
    /**
     * Get location from IP address
     */
    private function getLocationFromIp($ip): string
    {
        // Jika IP lokal
        if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return 'Lokal (Development)';
        }
        
        try {
            // Gunakan ipapi.co
            $context = stream_context_create([
                'http' => ['timeout' => 3]
            ]);
            $response = @file_get_contents("https://ipapi.co/{$ip}/json/", false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['city']) && isset($data['country_name'])) {
                    return $data['city'] . ', ' . $data['country_name'];
                }
            }
        } catch (\Exception $e) {
            // Fallback ke timezone
        }
        
        // Fallback berdasarkan zona waktu
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $city = explode('/', $timezone);
        $cityName = end($city);
        
        return $cityName . ' (Berdasarkan Zona Waktu)';
    }
}
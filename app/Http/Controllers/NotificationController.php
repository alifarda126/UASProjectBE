<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Notification;

class NotificationController extends Controller
{
    /** List semua notifikasi user yang sedang login */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'title'      => $n->title,
                'message'    => $n->message,
                'type'       => $n->type,
                'icon'       => $n->icon,
                'link'       => $n->link,
                'is_read'    => $n->is_read,
                'read_at'    => $n->read_at?->toISOString(),
                'created_at' => $n->created_at?->toISOString(),
            ]);

        return response()->json([
            'data'         => $notifications,
            'unread_count' => $request->user()->notifications()->unread()->count(),
        ]);
    }

    /** Tandai satu notifikasi sebagai sudah dibaca */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();
            
        if (!$notification) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan'], 404);
        }
        
        $notification->markAsRead();
        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca']);
    }

    /** Tandai semua notifikasi user sebagai sudah dibaca */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->notifications()->unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        return response()->json(['message' => 'Semua notifikasi sudah ditandai dibaca']);
    }

    /** Hapus notifikasi */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();
            
        if (!$notification) {
            return response()->json(['message' => 'Notifikasi tidak ditemukan'], 404);
        }
        
        $notification->delete();
        return response()->json(['message' => 'Notifikasi berhasil dihapus']);
    }
}
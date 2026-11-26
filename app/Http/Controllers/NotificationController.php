<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!Schema::hasTable('notifications')) {
            return response()->json([
                'unread_count'  => 0,
                'notifications' => [],
            ]);
        }

        $unreadCount = $user->unreadNotifications()->count();
        $items = $user->notifications()
            ->latest()
            ->limit(15)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?? [];

                return [
                    'id'              => $notification->id,
                    'read'            => $notification->read_at !== null,
                    'module'          => $data['module'] ?? 'SATURNUS',
                    'form_number'     => $data['form_number'] ?? '-',
                    'commenter_name'  => $data['commenter_name'] ?? 'User',
                    'commenter_role'  => $data['commenter_role'] ?? '',
                    'comment_excerpt' => $data['comment_excerpt'] ?? '',
                    'open_url'        => route('notifications.open', ['id' => $notification->id]),
                    'time_ago'        => $notification->created_at?->diffForHumans() ?? 'Baru saja',
                    'created_at'      => $notification->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'unread_count'  => $unreadCount,
            'notifications' => $items,
        ]);
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $targetUrl = $notification->data['url'] ?? route('saturnus.dashboard');

        return redirect($targetUrl);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'success'      => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (Schema::hasTable('notifications')) {
            $user->unreadNotifications()->update(['read_at' => now()]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'unread_count' => 0,
            ]);
        }

        return back();
    }
}

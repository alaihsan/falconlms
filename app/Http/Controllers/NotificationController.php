<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Notifications', [
            'breadcrumbs' => [['title' => __('Notifications'), 'href' => route('notifications.index')]],
            'notifications' => $user->notifications()->limit(50)->get()->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? '',
                'body' => $notification->data['body'] ?? '',
                'isRead' => $notification->read_at !== null,
                'date' => $notification->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Mark a notification as read and go to what it is about.
     */
    public function open(Request $request, string $id): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Only follow the path, never a full URL, so a notification can't send users off-site.
        $url = parse_url((string) ($notification->data['url'] ?? '/'));
        $target = '/'.ltrim($url['path'] ?? '', '/').(isset($url['query']) ? '?'.$url['query'] : '');

        return redirect()->to($target);
    }

    public function readAll(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}

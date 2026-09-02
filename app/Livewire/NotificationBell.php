<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = !$this->open;
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function openNotification(string $id)
    {
        $notification = Auth::user()->notifications()->whereKey($id)->first();
        if (!$notification) {
            return;
        }

        $notification->markAsRead();
        $this->open = false;

        if ($url = $notification->data['url'] ?? null) {
            return $this->redirect($url);
        }
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.notification-bell', [
            'notifications' => $user->notifications()->latest()->limit(8)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}

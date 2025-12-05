# ⚡ راهنمای پیاده‌سازی Real-Time Notifications

این راهنما نحوه پیاده‌سازی اطلاع‌رسانی‌های لحظه‌ای با Pusher را نشان می‌دهد.

---

## 📋 پیش‌نیازها

✅ Pusher در سیستم موجود است  
✅ Broadcasting در Laravel فعال است  
✅ Frontend از Echo استفاده می‌کند

---

## 🔧 مرحله 1: ایجاد Event برای Broadcasting

### فایل: `app/Events/Notification/NewNotification.php`

```php
<?php

namespace App\Events\Notification;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct($notification, User $user)
    {
        $this->notification = $notification;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.' . $this->user->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'data' => $this->notification->data,
            'read_at' => $this->notification->read_at,
            'created_at' => $this->notification->created_at,
        ];
    }
}
```

---

## 🔧 مرحله 2: به‌روزرسانی NotificationService

### فایل: `app/Services/NotificationService.php`

```php
// اضافه کردن این import
use App\Events\Notification\NewNotification;

// در متد sendNotification، بعد از ارسال نوتیف:
public function sendNotification(User $user, string $eventSlug, array $data = [])
{
    // ... کد موجود ...
    
    // ارسال نوتیف
    $notification = $user->notify(new CustomEventNotification($event, $notificationData, $channels));
    
    // Broadcast برای Real-Time (فقط برای database notifications)
    if (in_array('database', $channels)) {
        // گرفتن آخرین نوتیف از دیتابیس
        $latestNotification = $user->notifications()->latest()->first();
        
        if ($latestNotification) {
            event(new NewNotification($latestNotification, $user));
        }
    }
}
```

---

## 🔧 مرحله 3: تنظیم Channel Authorization

### فایل: `routes/channels.php`

```php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

---

## 🔧 مرحله 4: Frontend - اتصال به Pusher

### فایل: `zanburak-frontend/src/views/components/NotificationBell.vue`

```vue
<template>
    <div class="notification-bell">
        <button @click="toggleDropdown" class="relative">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <span v-if="unreadCount > 0" 
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <div v-if="showDropdown" class="notification-dropdown">
            <div class="notification-header">
                <h3>اعلان‌ها</h3>
                <button @click="markAllAsRead" v-if="unreadCount > 0">
                    همه را خوانده شده علامت بزن
                </button>
            </div>
            <div class="notification-list">
                <div v-for="notification in notifications" 
                     :key="notification.id"
                     :class="['notification-item', { 'unread': !notification.read_at }]"
                     @click="handleNotificationClick(notification)">
                    <div class="notification-content">
                        <p class="notification-message">{{ notification.data.message }}</p>
                        <span class="notification-time">{{ formatTime(notification.created_at) }}</span>
                    </div>
                </div>
                <div v-if="notifications.length === 0" class="empty-state">
                    هیچ اعلانی وجود ندارد
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axiosInstance from '@/plugins/axios';

export default {
    name: 'NotificationBell',
    data() {
        return {
            showDropdown: false,
            notifications: [],
            unreadCount: 0,
            echo: null,
        };
    },
    mounted() {
        this.initializeEcho();
        this.loadNotifications();
        this.listenForNotifications();
    },
    beforeUnmount() {
        if (this.echo) {
            this.echo.disconnect();
        }
    },
    methods: {
        initializeEcho() {
            const token = JSON.parse(localStorage.getItem('token'));
            if (!token) return;

            window.Pusher = Pusher;
            this.echo = new Echo({
                broadcaster: 'pusher',
                key: process.env.VUE_APP_PUSHER_APP_KEY || 'ac886b00865db1457c3f',
                cluster: process.env.VUE_APP_PUSHER_APP_CLUSTER || 'ap2',
                forceTLS: true,
                authEndpoint: `${process.env.VUE_APP_API_BASE_URL}/broadcasting/auth`,
                auth: {
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                },
            });
        },
        
        listenForNotifications() {
            if (!this.echo) return;
            
            const userId = JSON.parse(localStorage.getItem('user'))?.id;
            if (!userId) return;

            this.echo.private(`notifications.${userId}`)
                .listen('.notification.new', (data) => {
                    // اضافه کردن نوتیف جدید به لیست
                    this.notifications.unshift(data);
                    this.unreadCount++;
                    
                    // نمایش Toast
                    this.$toast.success(data.data.message, {
                        position: 'top-right',
                        duration: 5000,
                    });
                });
        },
        
        async loadNotifications() {
            try {
                const response = await axiosInstance.get('/panel/notifications');
                this.notifications = response.data.notifications;
                this.unreadCount = response.data.unread_count;
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        },
        
        async markAllAsRead() {
            try {
                await axiosInstance.post('/panel/notifications/mark-all-read');
                this.notifications.forEach(n => n.read_at = new Date());
                this.unreadCount = 0;
            } catch (error) {
                console.error('Error marking as read:', error);
            }
        },
        
        async handleNotificationClick(notification) {
            // Mark as read
            if (!notification.read_at) {
                try {
                    await axiosInstance.post(`/panel/notifications/${notification.id}/read`);
                    notification.read_at = new Date();
                    this.unreadCount--;
                } catch (error) {
                    console.error('Error marking as read:', error);
                }
            }
            
            // Navigate to action URL
            if (notification.data.action_url) {
                this.$router.push(notification.data.action_url);
            }
            
            this.showDropdown = false;
        },
        
        toggleDropdown() {
            this.showDropdown = !this.showDropdown;
            if (this.showDropdown) {
                this.loadNotifications();
            }
        },
        
        formatTime(time) {
            // استفاده از moment.js یا date-fns
            return new Date(time).toLocaleDateString('fa-IR');
        },
    },
};
</script>

<style scoped>
.notification-bell {
    position: relative;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 400px;
    max-height: 500px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    overflow-y: auto;
}

.notification-item {
    padding: 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.2s;
}

.notification-item:hover {
    background: #f5f5f5;
}

.notification-item.unread {
    background: #f0f9ff;
    font-weight: 500;
}

.notification-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.notification-time {
    font-size: 12px;
    color: #666;
}
</style>
```

---

## 🔧 مرحله 5: API Endpoints

### فایل: `app/Http/Controllers/Api/Panel/NotificationController.php`

```php
public function index()
{
    $user = auth('api')->user();
    $notifications = $user->notifications()
        ->orderBy('created_at', 'desc')
        ->take(50)
        ->get();
    
    $unreadCount = $user->unreadNotifications()->count();
    
    return response()->json([
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
    ]);
}

public function markAsRead($id)
{
    $user = auth('api')->user();
    $notification = $user->notifications()->findOrFail($id);
    $notification->markAsRead();
    
    return response()->json(['message' => 'Notification marked as read']);
}

public function markAllAsRead()
{
    $user = auth('api')->user();
    $user->unreadNotifications->markAsRead();
    
    return response()->json(['message' => 'All notifications marked as read']);
}
```

---

## 🔧 مرحله 6: Routes

### فایل: `routes/api/panel.php`

```php
Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});
```

---

## ✅ تست

1. **تست Broadcasting**:
```bash
php artisan tinker
>>> event(new \App\Events\Notification\NewNotification($notification, $user));
```

2. **تست Frontend**:
- باز کردن صفحه
- ارسال یک نوتیف از Backend
- باید بلافاصله در Frontend نمایش داده شود

---

## 🎯 نکات مهم

1. **Performance**: برای تعداد زیاد کاربران، از Queue استفاده کنید
2. **Security**: حتماً Channel Authorization را چک کنید
3. **Error Handling**: در صورت قطع اتصال، Retry Logic اضافه کنید
4. **Rate Limiting**: محدود کردن تعداد نوتیف در یک بازه زمانی

---

## 🚀 بهبودهای بعدی

1. **Notification Grouping**: گروه‌بندی نوتیف‌های مشابه
2. **Sound/Vibration**: صدا و لرزش برای نوتیف‌های مهم
3. **Rich Notifications**: تصویر و دکمه در نوتیف
4. **Notification Actions**: انجام عملیات از خود نوتیف

---

**نکته**: این راهنما یک پیاده‌سازی پایه است. می‌توانید بر اساس نیاز خود آن را توسعه دهید! 🎉


<?php

use App\Services\NotificationService;

if (!function_exists('frontendUrl')) {
    /**
     * Helper function برای ساخت URL فرانت‌اند
     *
     * @param string $path
     * @return string
     */
    function frontendUrl(string $path = ''): string
    {
        $baseUrl = rtrim(config('app.frontend_url', 'https://zanburak.ir'), '/');
        $path = ltrim($path, '/');
        return $path ? "{$baseUrl}/{$path}" : $baseUrl;
    }
}

if (!function_exists('sendNotification')) {
    /**
     * Helper function برای ارسال اطلاع‌رسانی
     *
     * @param \App\Models\User $user
     * @param string $eventSlug
     * @param array $data
     * @return void
     */
    function sendNotification($user, string $eventSlug, array $data = [])
    {
        $service = new NotificationService();
        $service->sendNotification($user, $eventSlug, $data);
    }
}

if (!function_exists('sendBulkNotification')) {
    /**
     * Helper function برای ارسال اطلاع‌رسانی به چند کاربر
     *
     * @param array $userIds
     * @param string $eventSlug
     * @param array $data
     * @return void
     */
    function sendBulkNotification(array $userIds, string $eventSlug, array $data = [])
    {
        $service = new NotificationService();
        $service->sendBulkNotification($userIds, $eventSlug, $data);
    }
}


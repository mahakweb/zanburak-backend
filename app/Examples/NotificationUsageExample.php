<?php

namespace App\Examples;

use App\Services\NotificationService;
use App\Models\User;
use App\Models\Comment;
use App\Models\Question;
use App\Models\Course;

/**
 * مثال‌های استفاده از NotificationService
 * 
 * این فایل فقط برای راهنمایی است و نباید در کد اصلی استفاده شود.
 */
class NotificationUsageExample
{
    protected $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * مثال 1: تایید دیدگاه
     */
    public function commentApprovedExample(User $user, Comment $comment)
    {
        $this->notificationService->sendNotification(
            $user,
            'comment-approved',
            [
                'message' => "دیدگاه شما در مورد «{$comment->commentable->title}» تایید شد.",
                'subject' => 'تایید دیدگاه',
                'action_url' => url("/panel/comments"),
                'action_text' => 'مشاهده دیدگاه',
                'sms_message' => "دیدگاه شما در سایت زنبورک تایید شد.",
            ]
        );
    }

    /**
     * مثال 2: بهترین پاسخ
     */
    public function bestAnswerExample(User $user, Question $question)
    {
        $this->notificationService->sendNotification(
            $user,
            'best-answer',
            [
                'message' => "پاسخ شما به سوال «{$question->subject}» به عنوان بهترین پاسخ انتخاب شد.",
                'subject' => 'بهترین پاسخ',
                'action_url' => url("/discuss/{$question->slug}"),
                'action_text' => 'مشاهده سوال',
                'sms_message' => "پاسخ شما به عنوان بهترین پاسخ انتخاب شد.",
            ]
        );
    }

    /**
     * مثال 3: آپدیت دوره
     */
    public function courseUpdatedExample(User $user, Course $course)
    {
        $this->notificationService->sendNotification(
            $user,
            'course-updated',
            [
                'message' => "دوره «{$course->title}» به‌روزرسانی شد و محتوای جدیدی اضافه شده است.",
                'subject' => 'به‌روزرسانی دوره',
                'action_url' => url("/course/{$course->slug}"),
                'action_text' => 'مشاهده دوره',
                'sms_message' => "دوره {$course->title} به‌روزرسانی شد.",
            ]
        );
    }

    /**
     * مثال 4: پاسخ به دیدگاه
     */
    public function replyToCommentExample(User $user, Comment $comment, User $replier)
    {
        $this->notificationService->sendNotification(
            $user,
            'reply-to-comment',
            [
                'message' => "{$replier->first_name} {$replier->last_name} به دیدگاه شما پاسخ داد.",
                'subject' => 'پاسخ به دیدگاه',
                'action_url' => url("/panel/comments"),
                'action_text' => 'مشاهده پاسخ',
                'sms_message' => "شما یک پاسخ جدید به دیدگاه خود دریافت کرده‌اید.",
            ]
        );
    }

    /**
     * مثال 5: دنبال‌کننده جدید
     */
    public function newFollowerExample(User $user, User $follower)
    {
        $this->notificationService->sendNotification(
            $user,
            'new-follower',
            [
                'message' => "{$follower->first_name} {$follower->last_name} شما را دنبال کرد.",
                'subject' => 'دنبال‌کننده جدید',
                'action_url' => url("/profile/{$follower->username}"),
                'action_text' => 'مشاهده پروفایل',
                'sms_message' => "شما یک دنبال‌کننده جدید دارید.",
            ]
        );
    }

    /**
     * مثال 6: ارسال اطلاع‌رسانی برای چند کاربر
     */
    public function bulkNotificationExample(array $userIds, Course $course)
    {
        $this->notificationService->sendBulkNotification(
            $userIds,
            'new-course-notification',
            [
                'message' => "دوره جدید «{$course->title}» منتشر شد.",
                'subject' => 'دوره جدید',
                'action_url' => url("/course/{$course->slug}"),
                'action_text' => 'مشاهده دوره',
                'sms_message' => "دوره جدید {$course->title} منتشر شد.",
            ]
        );
    }
}


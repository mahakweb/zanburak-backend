<?php

namespace App\Providers;

use App\Events\Comment\CommentApproved;
use App\Events\Comment\CommentOnArticle;
use App\Events\Comment\ReplyToComment;
use App\Events\Course\CertificateIssued;
use App\Events\Course\CourseCompleted;
use App\Events\Course\CourseNearCompletion;
use App\Events\Course\CourseProgress;
use App\Events\Course\CourseUpdated;
use App\Events\Course\GetCourse;
use App\Events\Course\NewCourse;
use App\Events\Course\NewCourseInCategory;
use App\Events\Discount\DiscountApplied;
use App\Events\Discuss\Mentioned;
use App\Events\Payment\CoursePurchased;
use App\Events\Payment\PaymentFailed;
use App\Events\Payment\VipUpgraded;
use App\Events\Post\PostLiked;
use App\Events\Report\ReportApproved;
use App\Events\Report\ReportRejected;
use App\Events\FollowUser;
use App\Events\Mission\PurchaseEvent;
use App\Events\Score\Discuss\SelectBestAnswer;
use App\Events\Score\Discuss\SubmitNewAnswer;
use App\Events\Score\Discuss\SubmitNewQuestion;
use App\Events\User\ChangeMobile;
use App\Events\User\ChangePassword;
use App\Listeners\Comment\SendCommentApprovedNotification;
use App\Listeners\Comment\SendCommentOnArticleNotification;
use App\Listeners\Comment\SendReplyToCommentNotification;
use App\Listeners\Course\GetCourseListener;
use App\Listeners\Course\SendCertificateIssuedNotification;
use App\Listeners\Course\SendCourseCompletedNotification;
use App\Listeners\Course\SendCourseNearCompletionNotification;
use App\Listeners\Course\SendCourseProgressNotification;
use App\Listeners\Course\SendCourseUpdatedNotification;
use App\Listeners\Course\SendNewCourseInCategoryNotification;
use App\Listeners\Course\SendNewCourseNotification;
use App\Listeners\Discount\SendDiscountAppliedNotification;
use App\Listeners\Discuss\SendMentionedNotification;
use App\Listeners\Payment\SendCoursePurchasedNotification;
use App\Listeners\Payment\SendPaymentFailedNotification;
use App\Listeners\Payment\SendVipUpgradedNotification;
use App\Listeners\Post\SendPostLikedNotification;
use App\Listeners\Report\SendReportApprovedNotification;
use App\Listeners\Report\SendReportRejectedNotification;
use App\Listeners\Mission\Purchases\FirstPurchaseListener;
use App\Listeners\Score\Discuss\SelectBestAnswerListener;
use App\Listeners\Score\Discuss\SubmitNewAnswerListener;
use App\Listeners\Score\Discuss\SubmitNewQuestionListener;
use App\Listeners\SendFollowNotification;
use App\Listeners\SendLoginNotification;
use App\Listeners\SendWelcomeNotification;
use App\Listeners\User\ChangeMobileListener;
use App\Listeners\User\ChangePasswordListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            SendWelcomeNotification::class
        ],

        Login::class => [
            SendLoginNotification::class,
        ],

        SubmitNewQuestion::class => [
            SubmitNewQuestionListener::class,
        ],

        SubmitNewAnswer::class => [
            SubmitNewAnswerListener::class,
        ],

        SelectBestAnswer::class => [
            SelectBestAnswerListener::class,
        ],

        FollowUser::class => [
            SendFollowNotification::class,
        ],

        GetCourse::class => [
            GetCourseListener::class,
        ],

        ChangeMobile::class => [
            ChangeMobileListener::class,
        ],

        // ChangePassword::class => [
        //     ChangePasswordListener::class,
        // ],
        PasswordReset::class => [
            ChangePasswordListener::class,
        ],

        CommentApproved::class => [
            SendCommentApprovedNotification::class,
        ],

        ReplyToComment::class => [
            SendReplyToCommentNotification::class,
        ],

        CommentOnArticle::class => [
            SendCommentOnArticleNotification::class,
        ],

        PaymentFailed::class => [
            SendPaymentFailedNotification::class,
        ],

        CoursePurchased::class => [
            SendCoursePurchasedNotification::class,
        ],

        VipUpgraded::class => [
            SendVipUpgradedNotification::class,
        ],

        CourseUpdated::class => [
            SendCourseUpdatedNotification::class,
        ],

        NewCourse::class => [
            SendNewCourseNotification::class,
        ],

        NewCourseInCategory::class => [
            SendNewCourseInCategoryNotification::class,
        ],

        ReportApproved::class => [
            SendReportApprovedNotification::class,
        ],

        ReportRejected::class => [
            SendReportRejectedNotification::class,
        ],

        DiscountApplied::class => [
            SendDiscountAppliedNotification::class,
        ],

        PostLiked::class => [
            SendPostLikedNotification::class,
        ],

        Mentioned::class => [
            SendMentionedNotification::class,
        ],

        CourseProgress::class => [
            SendCourseProgressNotification::class,
        ],

        CourseNearCompletion::class => [
            SendCourseNearCompletionNotification::class,
        ],

        CourseCompleted::class => [
            SendCourseCompletedNotification::class,
        ],

        CertificateIssued::class => [
            SendCertificateIssuedNotification::class,
        ],

        // start mission
        PurchaseEvent::class => [
            FirstPurchaseListener::class
        ],


        // end mission 
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}

<?php

namespace App\Providers;

use App\Events\Course\GetCourse;
use App\Events\FollowUser;
use App\Events\Mission\PurchaseEvent;
use App\Events\Score\Discuss\SelectBestAnswer;
use App\Events\Score\Discuss\SubmitNewAnswer;
use App\Events\Score\Discuss\SubmitNewQuestion;
use App\Events\User\ChangeMobile;
use App\Events\User\ChangePassword;
use App\Listeners\Course\GetCourseListener;
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

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
use App\Events\Mission\InvitationEvent;
use App\Events\Mission\CourseCompletionEvent;
use App\Events\Mission\CommunityActivityEvent;
use App\Events\Mission\CoursePublishingEvent;
use App\Events\Score\Discuss\SelectBestAnswer;
use App\Events\Score\Discuss\SubmitNewAnswer;
use App\Events\Score\Discuss\SubmitNewQuestion;
use App\Events\Score\Episode\EpisodeFullyWatched;
use App\Events\Score\Comment\CommentOnEpisode;
use App\Events\Score\Rating\RatingSubmitted;
use App\Events\Score\Profile\ProfileCompleted;
use App\Events\Score\User\EmailVerified;
use App\Events\Score\User\MobileVerified;
use App\Events\Score\User\DailyLogin;
use App\Events\Score\User\UserFollowed;
use App\Events\Score\User\UserRegistered;
use App\Events\Score\Report\ReportApprovedForScores;
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
use App\Listeners\Mission\Purchases\LoyalBuyerListener;
use App\Listeners\Mission\Purchases\TopSpenderListener;
use App\Listeners\Mission\Purchases\FastPurchaseListener;
use App\Listeners\Mission\Purchases\DiscountPurchaseListener;
use App\Listeners\Mission\Purchases\CategoryCompleterListener;
use App\Listeners\Mission\Purchases\DiversePurchaserListener;
use App\Listeners\Mission\Purchases\SkillUpgradeListener;
use App\Listeners\Mission\Purchases\TopPurchaserListener;
use App\Listeners\Mission\Purchases\LoyalCustomerListener;
use App\Listeners\Mission\Invitations\ActiveInviterListener;
use App\Listeners\Mission\Invitations\SuccessfulInviterListener;
use App\Listeners\Mission\Invitations\NetworkBuilderListener;
use App\Listeners\Mission\Invitations\SiteAmbassadorListener;
use App\Listeners\Mission\Invitations\SocialNetworkerListener;
use App\Listeners\Mission\Invitations\InfluentialInviterListener;
use App\Listeners\Mission\Invitations\CommunityGrowerListener;
use App\Listeners\Mission\Invitations\ContinuousInviterListener;
use App\Listeners\Mission\CourseCompletions\CourseCompletionListener;
use App\Listeners\Mission\CourseCompletions\DiverseCompletionListener;
use App\Listeners\Mission\CourseCompletions\FastestCompletionListener;
use App\Listeners\Mission\CourseCompletions\InteractiveCourseListener;
use App\Listeners\Mission\CourseCompletions\OngoingLearningListener;
use App\Listeners\Mission\CourseCompletions\PersistenceListener;
use App\Listeners\Mission\CourseCompletions\SpecialCourseListener;
use App\Listeners\Mission\CourseCompletions\SpecialistCompletionListener;
use App\Listeners\Mission\CommunityActivities\ActiveContributorListener;
use App\Listeners\Mission\CommunityActivities\TopResponderListener;
use App\Listeners\Mission\CommunityActivities\PopularCommenterListener;
use App\Listeners\Mission\CommunityActivities\ValuableCommentListener;
use App\Listeners\Mission\CommunityActivities\InquirerListener;
use App\Listeners\Mission\CommunityActivities\PersistentInquirerListener;
use App\Listeners\Mission\CommunityActivities\ProblemSolverListener;
use App\Listeners\Mission\CommunityActivities\IssueReporterListener;
use App\Listeners\Mission\CommunityActivities\ConstructiveFeedbackListener;
use App\Listeners\Mission\CommunityActivities\AnalyticalResponderListener;
use App\Listeners\Mission\CoursePublishing\FirstPublicationListener;
use App\Listeners\Mission\CoursePublishing\FastPublisherListener;
use App\Listeners\Mission\CoursePublishing\TopTeacherListener;
use App\Listeners\Mission\CoursePublishing\ActiveCollaboratorListener;
use App\Listeners\Mission\CoursePublishing\InnovativePublisherListener;
use App\Listeners\Mission\CoursePublishing\InteractivePublisherListener;
use App\Listeners\Mission\CoursePublishing\SkillImprovementListener;
use App\Listeners\Mission\CoursePublishing\GoldenFeedbackListener;
use App\Listeners\Mission\CoursePublishing\PositiveFeedbackListener;
use App\Listeners\Mission\CoursePublishing\PublishingExtrasListener;
use App\Listeners\Mission\CourseCompletions\ProgressMakerListener;
use App\Listeners\Mission\Invitations\InvitationExtrasListener;
use App\Listeners\Mission\CommunityActivities\RemainingCommunityMissionsListener;
use App\Listeners\Score\Discuss\SelectBestAnswerListener;
use App\Listeners\Score\Discuss\SubmitNewAnswerListener;
use App\Listeners\Score\Discuss\SubmitNewQuestionListener;
use App\Listeners\Score\Episode\EpisodeFullyWatchedListener;
use App\Listeners\Score\Course\CourseCompletedListener;
use App\Listeners\Score\Course\CertificateIssuedListener;
use App\Listeners\Score\Comment\CommentOnEpisodeListener;
use App\Listeners\Score\Comment\ReplyToCommentListener;
use App\Listeners\Score\Rating\RatingSubmittedListener;
use App\Listeners\Score\Post\PostLikedListener;
use App\Listeners\Score\Profile\ProfileCompletedListener;
use App\Listeners\Score\User\EmailVerifiedListener;
use App\Listeners\Score\User\MobileVerifiedListener;
use App\Listeners\Score\User\DailyLoginListener;
use App\Listeners\Score\User\UserFollowedListener;
use App\Listeners\Score\User\NewUserRegistrationListener;
use App\Listeners\Score\User\InviterRewardListener;
use App\Listeners\Score\User\InviteeRewardListener;
use App\Listeners\Score\Payment\FirstPurchaseListener as ScoreFirstPurchaseListener;
use App\Listeners\Score\Payment\VipUpgradedListener;
use App\Listeners\Score\Report\ReportApprovedListener;
use App\Listeners\SendFollowNotification;
use App\Listeners\SendLoginNotification;
use App\Listeners\SendWelcomeNotification;
use App\Listeners\User\ChangeMobileListener;
use App\Listeners\User\ChangePasswordListener;
use Illuminate\Auth\Events\Login;
use App\Listeners\BlockDisabledNotificationChannels;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Notifications\Events\NotificationSending;
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
            \App\Listeners\Score\User\FollowUserListener::class,
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
            ReplyToCommentListener::class,
        ],

        CommentOnArticle::class => [
            SendCommentOnArticleNotification::class,
        ],

        PaymentFailed::class => [
            SendPaymentFailedNotification::class,
        ],

        CoursePurchased::class => [
            SendCoursePurchasedNotification::class,
            ScoreFirstPurchaseListener::class,
        ],

        VipUpgraded::class => [
            SendVipUpgradedNotification::class,
            VipUpgradedListener::class,
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
            PostLikedListener::class,
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
            CourseCompletedListener::class,
        ],

        CertificateIssued::class => [
            SendCertificateIssuedNotification::class,
            CertificateIssuedListener::class,
        ],

        // Mission Events - Purchases
        PurchaseEvent::class => [
            FirstPurchaseListener::class,
            LoyalBuyerListener::class,
            TopSpenderListener::class,
            FastPurchaseListener::class,
            DiscountPurchaseListener::class,
            CategoryCompleterListener::class,
            DiversePurchaserListener::class,
            SkillUpgradeListener::class,
            TopPurchaserListener::class,
            LoyalCustomerListener::class,
            TopTeacherListener::class,
            PublishingExtrasListener::class,
        ],

        // Mission Events - Invitations
        InvitationEvent::class => [
            ActiveInviterListener::class,
            SuccessfulInviterListener::class,
            NetworkBuilderListener::class,
            SiteAmbassadorListener::class,
            SocialNetworkerListener::class,
            InfluentialInviterListener::class,
            CommunityGrowerListener::class,
            ContinuousInviterListener::class,
            InvitationExtrasListener::class,
        ],

        // Mission Events - Course Completions
        CourseCompletionEvent::class => [
            CourseCompletionListener::class,
            DiverseCompletionListener::class,
            FastestCompletionListener::class,
            InteractiveCourseListener::class,
            OngoingLearningListener::class,
            PersistenceListener::class,
            SpecialCourseListener::class,
            SpecialistCompletionListener::class,
            ProgressMakerListener::class,
        ],

        // Mission Events - Community Activities
        CommunityActivityEvent::class => [
            ActiveContributorListener::class,
            TopResponderListener::class,
            PopularCommenterListener::class,
            ValuableCommentListener::class,
            InquirerListener::class,
            PersistentInquirerListener::class,
            ProblemSolverListener::class,
            IssueReporterListener::class,
            ConstructiveFeedbackListener::class,
            AnalyticalResponderListener::class,
            RemainingCommunityMissionsListener::class,
        ],

        // Mission Events - Course Publishing
        CoursePublishingEvent::class => [
            FirstPublicationListener::class,
            FastPublisherListener::class,
            TopTeacherListener::class,
            ActiveCollaboratorListener::class,
            InnovativePublisherListener::class,
            InteractivePublisherListener::class,
            SkillImprovementListener::class,
            GoldenFeedbackListener::class,
            PositiveFeedbackListener::class,
            PublishingExtrasListener::class,
        ],

        // Score events
        EpisodeFullyWatched::class => [
            EpisodeFullyWatchedListener::class,
        ],

        CommentOnEpisode::class => [
            CommentOnEpisodeListener::class,
        ],

        RatingSubmitted::class => [
            RatingSubmittedListener::class,
            PositiveFeedbackListener::class,
            GoldenFeedbackListener::class,
            PublishingExtrasListener::class,
        ],

        ProfileCompleted::class => [
            ProfileCompletedListener::class,
        ],

        EmailVerified::class => [
            EmailVerifiedListener::class,
        ],

        MobileVerified::class => [
            MobileVerifiedListener::class,
        ],

        DailyLogin::class => [
            DailyLoginListener::class,
        ],

        UserFollowed::class => [
            UserFollowedListener::class,
        ],

        ReportApprovedForScores::class => [
            ReportApprovedListener::class,
        ],

        NotificationSending::class => [
            BlockDisabledNotificationChannels::class,
        ],

        UserRegistered::class => [
            NewUserRegistrationListener::class,
            InviterRewardListener::class,
            InviteeRewardListener::class,
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

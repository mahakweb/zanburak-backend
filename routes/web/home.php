<?php

use App\Events\Mission\PurchaseEvent;
use App\Models\Mission;
use App\Models\User;
use App\Models\UserMission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/test-event', function () {
    $user = User::find(1);
    event(new PurchaseEvent($user));
});

Route::get('/send-test-email', function () {
    $to = 'miladmahaki17@gmail.com';
    $subject = 'Test Email';
    $message = 'Hello, this is a test email sent from Laravel!';

    if (
        Mail::raw($message, function ($email) use ($to, $subject) {
            $email->to($to)->subject($subject);
        })
    ) {
        return "successfully sent";
    } else {
        return "not sent";
    }

    return 'Test email sent!';
});



Route::get('/test-pdf', function () {
    // $data = [
    //     'title' => 'Certificate of Completion',
    //     'name' => 'John Doe',
    //     'course' => 'Laravel Mastery Course'
    // ];

    // $pdf = Pdf::loadView('certificate', $data);

    // $pdf->setPaper('a4', 'landscape');

    // return $pdf->download('certificate.pdf');


    $data = [
        'title' => 'Certificate of Completion',
        'name' => 'John Doe',
        'course' => 'Laravel Mastery Course'
    ];

    return view('certificate', $data);
});


Route::get('/', [\App\Http\Controllers\Home\IndexController::class, 'index'])->name('index');

Route::get('paths', [\App\Http\Controllers\Home\IndexController::class, 'paths'])->name('paths');

Route::get('terms', [\App\Http\Controllers\Home\IndexController::class, 'terms'])->name('terms');

Route::get('contact-us', [\App\Http\Controllers\Home\IndexController::class, 'contactUs'])->name('contact-us');

Route::post('contact-us-message', [\App\Http\Controllers\Home\IndexController::class, 'sendMessage'])->name('contact-us-message');

Route::get('about-us', [\App\Http\Controllers\Home\IndexController::class, 'aboutUs'])->name('about-us');

Route::get('vip', [\App\Http\Controllers\Home\IndexController::class, 'vip'])->name('vip');

Route::get('request-project', [\App\Http\Controllers\Home\IndexController::class, 'requestProject'])->middleware(['auth', 'verified.mobile'])->name('request-project');

Route::post('request-project', [\App\Http\Controllers\Home\IndexController::class, 'storeRequestProject'])->middleware(['auth', 'verified.mobile'])->name('request-project');


// Start editor Route ------------------------------------------------------------

Route::post('editorUpload', [\App\Http\Controllers\Home\IndexController::class, 'editorUpload'])->name('editor-upload')->middleware('auth');

// End editor Route ------------------------------------------------------------


// Start Home Route ------------------------------------------------------------
Route::get('/courses', [\App\Http\Controllers\Home\CourseController::class, 'allCourse'])->name('all-course');

Route::get('/course/{courseSlug}', [\App\Http\Controllers\Home\CourseController::class, 'SingleCourse'])->name('course-single');

Route::get('/course/{courseSlug}/trailer', [\App\Http\Controllers\Home\CourseController::class, 'getTrailer'])->name('course-single-get-trailer');

Route::get('/course/{courseSlug}/episode/{episode}', [\App\Http\Controllers\Home\CourseController::class, 'SingleEpisode'])->name('episode-single');


Route::get('episode/{episode}/playlist', [\App\Http\Controllers\Home\CourseController::class, 'episodeVideo'])->name('episode-video');

Route::get('course/{course}/playlist', [\App\Http\Controllers\Home\CourseController::class, 'courseVideo'])->name('course-video');

Route::get('/key', [\App\Http\Controllers\Home\CourseController::class, 'videoKey'])->name('video-key')->middleware('signed');

Route::get('/stream', [\App\Http\Controllers\Home\CourseController::class, 'videoM3u8'])->name('video-m3u8')->middleware('signed');

Route::get('/stream.m3u8', [\App\Http\Controllers\Home\CourseController::class, 'videoPlaylist'])->name('video-playlist')->middleware('signed');


Route::get('episode/{episode}/download/check', [\App\Http\Controllers\Home\CourseController::class, 'episodeDownloadCheck'])->name('episode-download-check');

Route::get('episode/{episode}/download', [\App\Http\Controllers\Home\CourseController::class, 'episodeDownload'])->name('episode-download')->middleware('signed');




Route::post('comment/send', [\App\Http\Controllers\Home\CommentController::class, 'store'])->name('send-comment');

Route::post('rating/send', [\App\Http\Controllers\Home\RatingController::class, 'store'])->name('send-rate');

Route::post('like/send', [\App\Http\Controllers\Home\LikeController::class, 'store'])->name('send-like');

Route::post('bookmark/send', [\App\Http\Controllers\Home\BookmarkController::class, 'store'])->name('send-bookmark');

Route::post('follow/send', [\App\Http\Controllers\Home\FollowController::class, 'store'])->name('send-follow');

Route::post('discount/set', [\App\Http\Controllers\Home\DisountController::class, 'set'])->name('set-discount')->middleware('auth');

//End Home Route----------------------------------------------------------------



//Start Search Route----------------------------------------------------------------



Route::get('search', [\App\Http\Controllers\Home\SearchController::class, 'search'])->name('search');




// Start Search Route ------------------------------------------------------------






// Start Profile Route ------------------------------------------------------------


Route::get('/@{username}', [\App\Http\Controllers\Home\ProfileController::class, 'index'])->name('profile-index');

Route::get('/@{username}/articles', [\App\Http\Controllers\Home\ProfileController::class, 'articles'])->name('profile-articles');

Route::get('/@{username}/discuss', [\App\Http\Controllers\Home\ProfileController::class, 'discuss'])->name('profile-discuss');

Route::get('/@{username}/answers', [\App\Http\Controllers\Home\ProfileController::class, 'answers'])->name('profile-answers');





//End Profile Route----------------------------------------------------------------








//Start discuss Route-----------------------------------------------------------

Route::prefix('/discuss')->group(callback: function () {

    Route::get('/', [\App\Http\Controllers\Discuss\DiscussController::class, 'all'])->name('discuss-all');

    Route::get('/search-question-create', [\App\Http\Controllers\Discuss\DiscussController::class, 'ajaxSearchCreate'])->name('discuss-search-question-create');

    Route::get('/create', [\App\Http\Controllers\Discuss\DiscussController::class, 'createQuestionForm'])->middleware('auth')->name('discuss-create-question');

    Route::post('/store-question', [\App\Http\Controllers\Discuss\DiscussController::class, 'storeQuestion'])->middleware('auth')->name('discuss-store-question');

    Route::get('/{questionSlug}', [\App\Http\Controllers\Discuss\DiscussController::class, 'question'])->name('discuss-question');


    Route::post('/set-best-answer', [\App\Http\Controllers\Discuss\DiscussController::class, 'setBestAnswer'])->name('discuss-set-best-answer');

    Route::post('/store-answer', [\App\Http\Controllers\Discuss\DiscussController::class, 'storeAnswer'])->name('discuss-store-answer');

    Route::post('send-report', [\App\Http\Controllers\Discuss\DiscussController::class, 'sendReport'])->name('send-report');



    Route::post('like', [\App\Http\Controllers\Home\LikeController::class, 'discussLike'])->name('discuss-like');

    Route::post('dislike', [\App\Http\Controllers\Home\LikeController::class, 'discussDislike'])->name('discuss-dislike');
});

//end discuss Route-----------------------------------------------------------






//Start Student Route-----------------------------------------------------------

Route::prefix('/student')->middleware(['auth', 'student'])->group(callback: function () {

    Route::get('/', [\App\Http\Controllers\Student\HomeController::class, 'index'])->name('student-home');



    Route::get('/payments', [\App\Http\Controllers\Student\HomeController::class, 'payments'])->name('student-payments');

    Route::get('/courses', [\App\Http\Controllers\Student\HomeController::class, 'courses'])->name('student-courses');

    Route::get('/discounts', [\App\Http\Controllers\Student\HomeController::class, 'discounts'])->name('student-discounts');

    Route::get('/followed', [\App\Http\Controllers\Student\HomeController::class, 'followed'])->name('student-followed');

    Route::get('/notifications', [\App\Http\Controllers\Student\HomeController::class, 'notifications'])->name('student-notifications');

    Route::get('/read-notifications', [\App\Http\Controllers\Student\HomeController::class, 'readNotifications'])->name('student-read-notifications');

    Route::get('/questions', [\App\Http\Controllers\Student\HomeController::class, 'questions'])->name('student-questions');

    //Start Profile Route-----------------------------

    Route::get('/profile', [\App\Http\Controllers\Student\Profile\ProfileController::class, 'edit'])->name('student-profile');

    Route::patch('/profile', [\App\Http\Controllers\Student\Profile\ProfileController::class, 'update']);

    Route::patch('/update-mobile', [\App\Http\Controllers\Student\Profile\ProfileController::class, 'updateMobile'])->name('student-update-mobile');

    Route::post('/update-profilePicture', [\App\Http\Controllers\Student\Profile\ProfileController::class, 'updateProfilePic'])->name('student-update-profile-pic');

    Route::post('/update-coverPicture', [\App\Http\Controllers\Student\Profile\ProfileController::class, 'updateCoverPic'])->name('student-update-cover-pic');

    Route::post('/verify-token', [\App\Http\Controllers\Student\Profile\ProfileController::class, 'verifyToken'])->name('student-verify-token-mobile');

    Route::post('/terminate-session', [\App\Http\Controllers\Student\Profile\ProfileController::class, 'terminateSession'])->name('terminate-session');

    //End Profile Route-------------------------------

    //Start wallet Route-----------------------------

    Route::get('/wallet', [\App\Http\Controllers\Student\Wallet\WalletController::class, 'index'])->name('student-wallet');

    Route::post('/wallet/increase', [\App\Http\Controllers\Student\Wallet\WalletController::class, 'increase'])->name('student-wallet-increase');

    Route::get('/wallet/callback', [\App\Http\Controllers\Student\Wallet\WalletController::class, 'callback'])->name('student-wallet-callback');

    //End wallet Route-------------------------------





    Route::get('payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment-callback');




    //Start vip plan Route-----------------------------

    Route::get('/vip', [\App\Http\Controllers\Student\Vip\VipController::class, 'index'])->name('student-vip');

    Route::post('/vip/register', [\App\Http\Controllers\Student\Vip\VipController::class, 'register'])->name('student-vip-register');

    Route::get('/vip/callback', [\App\Http\Controllers\Student\Vip\VipController::class, 'callback'])->name('student-vip-callback');

    //End vip plan Route-------------------------------


    //Start cart Route-----------------------------

    Route::get('/cart', [\App\Http\Controllers\Student\CartController::class, 'index'])->name('cart');
    Route::post('/cart/add', [\App\Http\Controllers\Student\CartController::class, 'addToCart'])->name('add-to-cart');
    Route::post('/cart/delete', [\App\Http\Controllers\Student\CartController::class, 'deleteFromCart'])->name('delete-from-cart');
    Route::post('/cart/destroy', [\App\Http\Controllers\Student\CartController::class, 'destroyCart'])->name('destroy-cart');

    Route::post('/get-course', [\App\Http\Controllers\Student\CartController::class, 'purchase'])->name('student-purchase-course');
    Route::get('/get-course/callback', [\App\Http\Controllers\Student\CartController::class, 'callback'])->name('student-purchase-callback');
    Route::post('/get-course/wallet', [\App\Http\Controllers\Student\CartController::class, 'purchaseWithWallet'])->name('student-purchase-with-wallet');

    //End cart Route-------------------------------


});

//End Student Route-----------------------------------------------------------







//Start Instructor Route-----------------------------------------------------------
Route::prefix('/instructor')->middleware(['auth', 'teacher'])->group(function () {

    Route::get('/', [\App\Http\Controllers\Instructor\HomeController::class, 'index'])->name('instructor-home');

    //Start Profile Route-----------------------------

    Route::get('/profile', [\App\Http\Controllers\Instructor\Profile\ProfileController::class, 'edit'])->name('instructor-profile');

    //End Profile Route-------------------------------

    //Start Course Route-----------------------------

    Route::get('/courses', [\App\Http\Controllers\Instructor\Courses\CourseController::class, 'index'])->name('instructor-show-courses');

    Route::get('/course/create', [\App\Http\Controllers\Instructor\Courses\CourseController::class, 'create'])->name('instructor-add-course');

    Route::post('/course/create', [\App\Http\Controllers\Instructor\Courses\CourseController::class, 'store']);

    Route::get('/course/{course}/edit', [\App\Http\Controllers\Instructor\Courses\CourseController::class, 'edit'])->name('instructor-edit-course');

    Route::patch('/course/{course}/edit', [\App\Http\Controllers\Instructor\Courses\CourseController::class, 'update']);

    Route::delete('/course/{course}/delete', [\App\Http\Controllers\Instructor\Courses\CourseController::class, 'destroy'])->name('instructor-delete-course');

    //End Course Route-----------------------------

    //Start Section Route-----------------------------

    Route::get('/course/{course}/section/create', [\App\Http\Controllers\Instructor\Courses\SectionController::class, 'create'])->name('instructor-add-section');

    Route::post('/course/{course}/section/create', [\App\Http\Controllers\Instructor\Courses\SectionController::class, 'store']);

    Route::get('/course/{course}/section/{section}/edit', [\App\Http\Controllers\Instructor\Courses\SectionController::class, 'edit'])->name('instructor-edit-section');

    Route::patch('/course/{course}/section/{section}/edit', [\App\Http\Controllers\Instructor\Courses\SectionController::class, 'update']);

    Route::delete('/course/{course}/section/{section}/delete', [\App\Http\Controllers\Instructor\Courses\SectionController::class, 'destroy'])->name('instructor-delete-section');

    //End Section Route-----------------------------

    //Start Episode Route-----------------------------

    Route::get('/course/{course}/section/{section}/episode/create', [\App\Http\Controllers\Instructor\Courses\EpisodeController::class, 'create'])->name('instructor-add-episode');

    Route::post('/course/{course}/section/{section}/episode/create', [\App\Http\Controllers\Instructor\Courses\EpisodeController::class, 'store']);

    Route::get('/course/{course}/section/{section}/episode/{episode}/edit', [\App\Http\Controllers\Instructor\Courses\EpisodeController::class, 'edit'])->name('instructor-edit-episode');

    Route::patch('/course/{course}/section/{section}/episode/{episode}/edit', [\App\Http\Controllers\Instructor\Courses\EpisodeController::class, 'update']);

    Route::delete('/course/{course}/section/{section}/episode/{episode}/delete', [\App\Http\Controllers\Instructor\Courses\EpisodeController::class, 'destroy'])->name('instructor-delete-episode');

    //End Episode Route-----------------------------
});



//End Instructor Route-----------------------------------------------------------



//Start newsletter Route-----------------------------------------------------------

Route::post('register-newsletter', [\App\Http\Controllers\Home\IndexController::class, 'registerNewsletter'])->name('register-newsletter');

//End newsletter Route-----------------------------------------------------------



Auth::routes(['verify' => true]);

Route::get('mobile/verify', function () {

    return view('auth.verify-mobile');

})->name('verification.mobile');

Route::patch('/password/change', [\App\Http\Controllers\Auth\ChangePasswordController::class, 'update'])->name('change-password');


Route::get('/oauth/{driver}/redirect', [\App\Http\Controllers\Auth\OAuthController::class, 'redirect'])->name('social-oauth');

Route::get('/oauth/{driver}/callback', [\App\Http\Controllers\Auth\OAuthController::class, 'callback'])->name('social-callback');

Route::get('/home', [\App\Http\Controllers\Home\HomeController::class, 'index'])->name('home');

// SEO Routes - Moved to API routes
<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;

class EmailVerificationController extends Controller
{
    public function resend(Request $request)
    {
        $user = auth('api')->user();

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email resent']);
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = \App\Models\User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link'], 403);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $user->markEmailAsVerified();

        event(new Verified($user));
        
        // Fire custom event for points
        event(new \App\Events\Score\User\EmailVerified($user));

        // return response()->json([
        //     'message' => 'Email verified successfully',
        //     'redirect_url' => config('app.frontend_url') . '/email-verified' 
        // ], 200);
        return redirect(config('app.frontend_url') . '/email-verified')->with('message', 'Email verified successfully');
    }
}

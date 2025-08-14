<?php

namespace App\Listeners\User;

// use App\Events\User\ChangePassword;
use Illuminate\Auth\Events\PasswordReset;
use App\Notifications\User\ChangePasswordNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ChangePasswordListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\User\ChangePassword  $event
     * @return void
     */
    public function handle(PasswordReset $event)
    {
        $user = $event->user;
        $currentToken = $user->currentAccessToken();
        if($currentToken){
            $user->tokens()->whereNot('id', $currentToken->id)->delete();
        }else{
            $user->tokens()->delete();
        }
        $user->sessions()->delete();


        $user->notify(new ChangePasswordNotification());
    }
}

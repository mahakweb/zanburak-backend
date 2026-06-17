<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Permission;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Quiz\Quiz::class => \App\Policies\QuizPolicy::class,
        \App\Models\Quiz\QuizQuestion::class => \App\Policies\QuizQuestionPolicy::class,
        \App\Models\Quiz\QuizAttempt::class => \App\Policies\QuizAttemptPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();


        // Gate::before(function($user){
        //     if($user->isSuperUser()) return true;
        // });

        //TODO add

        // foreach (Permission::all() as $permission){
        //     Gate::define($permission->name, function ($user) use($permission){
        //         return $user->hasPermission($permission);
        //     });
        // }
    }
}

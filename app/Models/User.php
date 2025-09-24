<?php

namespace App\Models;

use App\Contracts\Likeable;
use App\Models\Cart;
use App\Models\Like;
use App\Models\VideoView;
use Cviebrock\EloquentTaggable\Taggable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LaravelInteraction\Bookmark\Concerns\Bookmarker;
use Laravel\Sanctum\HasApiTokens;
use Overtrue\LaravelFollow\Traits\Followable;
use Overtrue\LaravelFollow\Traits\Follower;
use Overtrue\LaravelSubscribe\Traits\Subscriber;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Support\Facades\URL;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Taggable, Follower, Followable, Subscriber, Bookmarker;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'mobile',
        'mobile_verified_at',
        'password',
        'is_superuser',
        'is_staff',
        'role',
        'username',
        'wallet_balance',
        'remember_token',
        'last_seen',
        'profile_pic',
        'cover_pic',
        'active',
        'deactivated_by',
        'deactivation_reason',
        'deactivated_until',
        'failed_login_attempts',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'active' => 'boolean',
        'is_superuser' => 'boolean',
        'is_staff' => 'boolean',
    ];


    // public function scopeStatus($query, $status)
    // {
    //     if (empty($status) || $status === 'all') {
    //         return $query;
    //     }

    //     return match ($status) {
    //         'active' => $query->where('active', 1),
    //         'inactive' => $query->where('active', 0),
    //         default => $query,
    //     };
    // }

    public function scopeStatus($query, $status)
    {
        if (empty($status) || $status === 'all') {
            return $query;
        }

        return match ($status) {
            'active' => $query->where('active', 1)
                ->where(function ($q) {
                        $q->whereNull('deactivated_until')
                        ->orWhere('deactivated_until', '<=', now());
                    }),
            'inactive' => $query->where(function ($q) {
                    $q->where('active', 0)
                    ->orWhere('deactivated_until', '>', now());
                }),
            default => $query,
        };
    }


    public function scopeRole($query, $role)
    {
        if (empty($role) || $role === 'all') {
            return $query;
        }

        return match ($role) {
            'superuser' => $query->where('is_superuser', 1),
            'administrator' => $query->where('is_staff', 1),
            'user' => $query->where('is_staff', 0)->where('is_superuser', 0),
            default => $query,
        };
    }

    public function scopeSort($query, $value)
    {
        return match ($value) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    public function scopeSubscription($query, $subscription)
    {
        if (empty($subscription) || $subscription === 'all') {
            return $query;
        }

        $vipCondition = function ($q) {
            $q->where('expired_at', '>', now());
        };

        if ($subscription === 'vip') {
            return $query->whereHas('plans', $vipCondition);
        }

        if ($subscription === 'normal') {
            return $query->whereDoesntHave('plans', $vipCondition);
        }

        return $query;
    }

    public function scopeSearch($query, $term)
    {
        if (!$term)
            return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('username', 'like', "%{$term}%");
        });
    }



    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable');
    }

    //--------------------------------------------------

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function accessiblePrivateQuestions()
    {
        return Question::where('is_private', true)
            ->where(function ($query) {
                $query->where('user_id', $this->id)
                    ->orWhere(function ($q) {
                        $q->where(function ($subQuery) {
                            $subQuery->whereNotNull('allowed_user_ids')
                                ->get()
                                ->filter(function ($question) {
                                    $allowedUsers = json_decode($question->allowed_user_ids, true);
                                    return in_array($this->id, $allowedUsers);
                                });
                        });
                    });
            })->get();
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    // like functions

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function like(Likeable $likeable): self
    {
        if ($this->hasLiked($likeable)) {
            return $this;
        }

        // (new Like())
        //     ->user()->associate($this)
        //     ->likeable()->associate($likeable)
        //     ->save();
        $this->likes()->create([
            'type' => 'like',
            'likeable_type' => get_class($likeable),
            'likeable_id' => $likeable->id,
        ]);

        return $this;
    }

    public function toggleLike(Likeable $likeable): self
    {
        if ($this->hasLiked($likeable)) {
            return $this->unlike($likeable);
        }

        $this->like($likeable);

        return $this;
    }

    public function unlike(Likeable $likeable): self
    {
        if (!$this->hasLiked($likeable)) {
            return $this;
        }

        $likeable->likes()
            ->whereHas('user', fn($q) => $q->whereId($this->id))
            ->where('type', 'like')
            ->delete();

        return $this;
    }

    public function hasLiked(Likeable $likeable): bool
    {
        if (!($likeable->exists && $likeable->likes()->where('type', 'like')->exists())) {
            return false;
        }

        return $likeable->likes()
            ->whereHas('user', fn($q) => $q->whereId($this->id))
            ->where('type', 'like')
            ->exists();
    }

    //  dislike functions

    public function dislike(Likeable $likeable): self
    {
        if ($this->hasDisliked($likeable)) {
            return $this;
        }

        // (new Like())
        //     ->user()->associate($this)
        //     ->likeable()->associate($likeable)
        //     ->save();
        $this->likes()->create([
            'type' => 'dislike',
            'likeable_type' => get_class($likeable),
            'likeable_id' => $likeable->id,
        ]);

        return $this;
    }

    public function toggleDislike(Likeable $likeable): self
    {
        if ($this->hasDisliked($likeable)) {
            return $this->unDislike($likeable);
        }

        $this->dislike($likeable);

        return $this;
    }

    public function unDislike(Likeable $likeable): self
    {
        if (!$this->hasDisLiked($likeable)) {
            return $this;
        }

        $likeable->likes()
            ->whereHas('user', fn($q) => $q->whereId($this->id))
            ->where('type', 'dislike')
            ->delete();

        return $this;
    }

    public function hasDisliked(Likeable $likeable): bool
    {
        if (!($likeable->exists && $likeable->likes()->where('type', 'dislike')->exists())) {
            return false;
        }

        return $likeable->likes()
            ->whereHas('user', fn($q) => $q->whereId($this->id))
            ->where('type', 'dislike')
            ->exists();
    }

    public function hasVerifiedEmail()
    {
        return !!$this->mobile_verified_at;
    }

    public function addCourse()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function aclRuleFileManager()
    {
        return $this->hasMany(AclRuleFileManager::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class)->withTimestamps()->withPivot(["course_id", "user_id", "price", "payment_id", "completed_at", "created_at", "updated_at"]);
    }

    public function hasCourse(Course $course)
    {
        // return $course->type == 'free' ? true :!!$this->courses()->wherePivot('course_id' , $course->id)->first();
        if ($course->type == 'free') {
            return true;
        } else if ($course->type == 'cash') {
            return !!$this->courses()->wherePivot('course_id', $course->id)->first();
        } else if ($course->type == 'cash-vip') {
            return $this->hasVip() || $this->courses()->wherePivot('course_id', $course->id)->first() ? true : false;
        }
    }

    public function completedCourses()
    {
        return $this->courses()->wherePivotNotNull('completed_at');
    }

    // public function currentCourses()
    // {
    //     return $this->belongsToMany(Course::class, 'course_user', 'user_id', 'course_id')
    //         ->whereHas('section.episode.videos', function ($query) {
    //             $query->where('type', 'stream')
    //                 ->whereDoesntHave('VideoViews', function ($subQuery) {
    //                     $subQuery->select('id')
    //                         ->whereColumn('video_id', 'videos.id')
    //                         ->where('user_id', $this->id)
    //                         ->orderByDesc('updated_at')
    //                         ->limit(1)
    //                         ->where('watched', true);
    //                 });
    //         });
    // }


    // public function currentCourses()
    // {
    //     return $this->courses()
    //         ->whereHas('section.episode.videos', function ($query) {
    //             $query->where('type', 'stream')
    //                 ->whereDoesntHave('VideoViews', function ($subQuery) {
    //                     $subQuery->select('id')
    //                         ->whereColumn('video_id', 'videos.id')
    //                         ->where('user_id', $this->id)
    //                         ->orderByDesc('updated_at') // یا orderByDesc('updated_at') بسته به ستون تاریخ شما
    //                         ->limit(1)
    //                         ->where('watched', true);
    //                 });
    //         });
    //     // TODO add if to check have a episode
    // }


    public function currentCourses()
    {
        return $this->courses()
            ->where('publish', 1)
            ->where(function ($query) {
                $query->whereDoesntHave('section.episode.videos', function ($subQuery) {
                    // if course doesent have any video 
                    $subQuery->where('type', 'stream');
                })
                    ->orWhereHas('section.episode.videos', function ($subQuery) {
                        // if course have any video but user didnt watch it
                        $subQuery->where('type', 'stream')
                            ->whereDoesntHave('VideoViews', function ($innerQuery) {
                            $innerQuery->select('id')
                                ->whereColumn('video_id', 'videos.id')
                                ->where('user_id', $this->id)
                                ->orderByDesc('updated_at')
                                ->limit(1)
                                ->where('watched', true);
                        });
                    });
            });
    }


    public function isSuperUser()
    {
        return $this->is_superuser;
    }

    public function isStaffUser()
    {
        return $this->is_staff;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    public function hasRole($roles)
    {
        return !!$roles->intersect($this->roles)->all();
    }

    public function hasPermission($permission)
    {
        return $this->permissions->contains('name', $permission->name) || $this->hasRole($permission->roles);
    }

    public function info()
    {
        return $this->hasOne(Info::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class)->withPivot(["payment_id", "price", "description", "purchase_type", "expired_at", "created_at", "updated_at"]);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function project()
    {
        return $this->hasMany(Project::class);
    }

    public function team()
    {
        return $this->hasOne(Team::class);
    }

    public function activeCode()
    {
        return $this->hasMany(ActiveCode::class);
    }

    // public function hasVip()
    // {
    //     if ($userPlan = $this->plans()->wherePivot('expired_at', '>', Carbon::now())->first()) {
    //         return Carbon::now()->diffInSeconds($userPlan->pivot->expired_at);
    //     } else {
    //         return false;
    //     }
    // }

    public function hasVip()
    {
        return $this->plans()
            ->wherePivot('expired_at', '>', now())
            ->exists();
    }

    public function percentVip()
    {
        if ($this->hasVip()) {
            $activePlan = $this->plans()->wherePivot('expired_at', '>', Carbon::now())->first();
            $total_time = $activePlan->period_time * 24 * 60 * 60; // seconds
            $remaining_time = $this->hasVip();

            return ($remaining_time / $total_time) * 100;
        } else {
            return false;
        }
    }

    // public function activeVipPlan()
    // {
    //     if ($this->hasVip()) {
    //         $activePlan = $this->plans()->wherePivot('expired_at', '>', Carbon::now())->first();
    //         return $activePlan;
    //     } else {
    //         return false;
    //     }
    // }

    public function activeVipPlan()
    {
        return $this->plans()
            ->wherePivot('expired_at', '>', now())
            ->first();
    }

    public function expiredVipPlan()
    {
        return $this->plans()->wherePivot('expired_at', '<', Carbon::now())->orderBy('pivot_expired_at', 'desc')->get();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function canGetEpisode(Episode $episode)
    {
        $course = $episode->section->course;
        $type = $course->type;

        if ($episode->lock()) {
            if ($type == "cash" && !$this->hasCourse($course)) {

                return ["type" => $type, "can" => false];
            } elseif ($type == "cash-vip") {

                if (!$this->hasCourse($course) && !$this->hasVip()) {

                    return ["type" => $type, "can" => false];
                }
            }
        }

        return ["type" => $type, "can" => true];
    }

    public function missions()
    {
        return $this->hasMany(Mission::class);
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    public function currentScore()
    {
        return $this->scores->sum('score');
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function BestAnswers()
    {
        // $count = Question::join('answers', 'questions.best_answer', '=', 'answers.id')->join('users', 'answers.user_id', '=', 'users.id')->where('users.id', $this->id)->get();
        $count = Answer::whereHas('question', function ($query) {
            $query->where('best_answer', '=', DB::raw('answers.id'));
        })->where('user_id', $this->id)->get();
        return $count;
    }

    public function videoViews()
    {
        return $this->hasMany(VideoView::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }


    public function logins()
    {
        return $this->hasMany(UserLogin::class);
    }

    public function providers()
    {
        return $this->hasMany(UserProvider::class);
    }


    public function sendEmailVerificationNotification()
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $this->id,
                'hash' => sha1($this->email),
            ]
        );

        $apiVerificationUrl = str_replace('/email/verify', '/api/email/verify', $verificationUrl);

        $this->notify(new \App\Notifications\Auth\VerifyEmail($apiVerificationUrl));
    }



    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function isDeactivated(): bool
    {
        if (!$this->active)
            return true;
        if ($this->deactivated_until && now()->lessThan($this->deactivated_until))
            return true;
        return false;
    }

    public function isTemporarilyDeactivated(): bool
    {
        return $this->deactivated_until !== null && Carbon::now()->lessThan($this->deactivated_until);
    }

    public function isPermanentlyDeactivated(): bool
    {
        return !$this->active;
    }


    public function deactivationMessage(): ?string
    {
        if (!$this->active)
            return "Your account is permanently deactivated.";
        if ($this->deactivated_until && now()->lessThan($this->deactivated_until)) {
            $minutes = now()->diffInMinutes($this->deactivated_until);
            return "Your account is temporarily locked for {$minutes} minutes.";
        }
        return null;
    }
}

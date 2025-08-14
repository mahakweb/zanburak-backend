<?php

namespace App\Models;

use App\Contracts\Likeable;
use App\Models\Cart;
use App\Models\Concerns\Likes;
use App\Models\Status;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentTaggable\Taggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use LaravelInteraction\Bookmark\Concerns\Bookmarkable;
use Laravel\Scout\Searchable;
use Overtrue\LaravelSubscribe\Traits\Subscribable;
use App\Traits\CascadesDeletes;


class Course extends Model implements Likeable
{
    use CascadesDeletes, HasFactory, Searchable, Taggable, Sluggable, Subscribable, Bookmarkable, Likes;

    protected $fillable = [
        'teacher_id',
        'title',
        'english_title',
        'short_description',
        'description',
        'total_time',
        'start_date',
        'end_date',
        'price',
        'publish',
        'status_id',
        'level_id',
        'type',
        'disk',
        'trailer',
        'poster',
        'attached_file',
    ];

    public function getCascadeRelations(): array
    {
        return [
            'carts',
            'views',
            'section',
            'videos',
            'comments',
            'likes',
            'bookmarkableBookmarks',
            'tags',
            'attachs',
            'ratings',
            'certificates',
        ];

    }

    public function deleteMediaFiles()
    {
        foreach (['poster', 'attached_file'] as $field) {
            $url = $this->{$field};

            if (!$url) {
                continue;
            }

            foreach (config('filesystems.disks') as $disk => $config) {
                if (!isset($config['url'])) {
                    continue;
                }

                $baseUrl = rtrim($config['url'], '/');

                if (str_starts_with($url, $baseUrl)) {
                    $relativePath = ltrim(str_replace($baseUrl, '', $url), '/');
                    Storage::disk($disk)->delete($relativePath);
                    break;
                }
            }
        }
    }


    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }



    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // $array = $this->toArray();

        // unset($array['updated_at']);

        // return $array;
        return [
            'title' => $this->title,
            'english_title' => $this->english_title,
            'slug' => $this->slug,
            'description' => $this->description,
        ];
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'english_title',
            ],
        ];
    }

    public function scopeOrder($query, $value)
    {
        return match ($value) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->whereHas('status', function ($query) use ($value) {
                    $statusIds = Status::where('english_title', $value)->pluck('id');

                    if ($statusIds->isEmpty()) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereIn('id', $statusIds)->orderBy('created_at', 'desc');
                    }
                }),
        };
    }

    // public function scopeCat($query, $categories)
    // {
    //     if (!is_array($categories) || empty($categories)) {
    //         return $query;
    //     }

    //     $categoryIds = Category::whereIn('title', $categories)->pluck('id');

    //     if ($categoryIds->isEmpty()) {
    //         return $query->whereRaw('1 = 0');
    //     }

    //     return $query->whereHas('category', function ($query) use ($categoryIds) {
    //         $query->whereIn('id', $categoryIds);
    //     });
    // }

    // public function scopeType($query, $value)
    // {
    //     if (empty($value) || !is_array($value)) {
    //         return $query;
    //     }

    //     return $query->whereIn('type', $value);
    // }

    // public function scopeLevel($query, $levels)
    // {
    //     if (!is_array($levels) || empty($levels)) {
    //         return $query;
    //     }

    //     $levelIds = Level::whereIn('slug', $levels)->pluck('id');

    //     if ($levelIds->isEmpty()) {
    //         return $query->whereRaw('1 = 0');
    //     }

    //     return $query->whereIn('level_id', $levelIds);
    // }

    // public function scopeStatus($query, $statuses)
    // {
    //     if (!is_array($statuses) || empty($statuses)) {
    //         return $query;
    //     }

    //     $statusIds = Status::whereIn('slug', $statuses)->pluck('id');

    //     if ($statusIds->isEmpty()) {
    //         return $query->whereRaw('1 = 0');
    //     }

    //     return $query->whereIn('status_id', $statusIds);
    // }

    // public function scopePublish($query, $publish)
    // {
    //     if (is_null($publish) || $publish === 'all') {
    //         return $query;
    //     }

    //     return $query->where('publish', $publish);
    // }


    public function scopeCat($query, $category)
    {
        if (empty($category) || $category === 'all') {
            return $query;
        }

        $slugs = is_array($category) ? $category : [$category];

        $categoryIds = Category::whereIn('slug', $slugs)->pluck('id');

        if ($categoryIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('category', function ($q) use ($categoryIds) {
            $q->whereIn('id', $categoryIds);
        });
    }

    public function scopeType($query, $type)
    {
        if (empty($type) || $type === 'all') {
            return $query;
        }

        $types = is_array($type) ? $type : [$type];

        return $query->whereIn('type', $types);
    }

    public function scopeLevel($query, $level)
    {
        if (empty($level) || $level === 'all') {
            return $query;
        }

        $slugs = is_array($level) ? $level : [$level];

        $levelIds = Level::whereIn('slug', $slugs)->pluck('id');

        if ($levelIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('level_id', $levelIds);
    }

    public function scopeStatus($query, $status)
    {
        if (empty($status) || $status === 'all') {
            return $query;
        }

        $slugs = is_array($status) ? $status : [$status];

        $statusIds = Status::whereIn('slug', $slugs)->pluck('id');

        if ($statusIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('status_id', $statusIds);
    }

    public function scopePublish($query, $publish)
    {
        if (empty($publish) || $publish === 'all') {
            return $query;
        }
        if ($publish === 'published') {
            return $query->where('publish', 1);
        }
        if ($publish === 'draft') {
            return $query->where('publish', 0);
        }

        // return $query->where('publish', $publish);
    }


    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function viewCount()
    {
        return $this->views()->count();
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps()->withPivot(["course_id", "user_id", "price", "payment_id", "completed_at", "created_at", "updated_at"]);
    }

    public function attachs(): MorphMany
    {
        return $this->morphMany(Attach::class, 'attachable');
    }

    public function section()
    {
        return $this->hasMany(Section::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function category()
    {
        return $this->belongsToMany(Category::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function videos()
    {
        return $this->morphMany(Video::class, 'videoable');
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class);
    }

    public function paths()
    {
        return $this->belongsToMany(Path::class)->withTimestamps();
    }

    // return count of all episode with publish equal to 1
    public function numberOfEpisode()
    {
        $count = 0;
        foreach ($this->section as $item) {
            $count += $item->episode()->where('publish', 1)->count();
        }
        return $count;
    }

    // return count of all episode include every publish equal to 1 or 0
    public function numberOfAllEpisode()
    {
        $count = 0;
        foreach ($this->section as $item) {
            $count += $item->episode()->count();
        }
        return $count;
    }

    public function numberOfSection()
    {
        return $this->section()->count();
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function averageRating()
    {
        return ($this->ratings()->avg('rating')) ? $this->ratings()->avg('rating') : 0;
    }

    public function sumRating()
    {
        return $this->ratings()->sum('rating');
    }

    public function sumOfAllRate()
    {
        $quantity = $this->ratings()->count();

        return $quantity;
    }

    public function sumOfRateNumber($rateNumber = 5)
    {
        $quantityOfRateNumber = $this->ratings()->where('rating', '=', $rateNumber)->count();

        return $quantityOfRateNumber;
    }

    public function averageOfRateNumber($rateNumber = 5)
    {
        $quantity = $this->ratings()->count();
        if ($quantity == 0) {
            $quantity = 1;
        }
        $quantityOfRateNumber = $this->ratings()->where('rating', '=', $rateNumber)->count();

        return number_format($quantityOfRateNumber / $quantity, 2) * 100;
    }

    public function checkUserRateThis($user_id = null)
    {
        $check = Rating::query()
            ->where('rateable_type', '=', $this->getMorphClass())
            ->where('rateable_id', '=', $this->id)
            ->where('user_id', '=', $user_id ? $user_id : Auth::id())
            ->first();
        if ($check) {
            return $check;
        } else {
            return false;
        }
    }

    public function checkUserRateNumber($rate)
    {
        $check = Rating::query()
            ->where('rating', '=', $rate)
            ->where('rateable_type', '=', $this->getMorphClass())
            ->where('rateable_id', '=', $this->id)
            ->where('user_id', '=', Auth::id())
            ->first();
        if ($check) {
            return true;
        } else {
            return false;
        }
    }

    public function totalTime($published = true)
    {
        $totalTime = 0;
        foreach ($this->section as $item => $value) {
            if ($published)
                $totalTime += $value->episode()->where('publish', 1)->sum('total_time');
            else
                $totalTime += $value->episode()->sum('total_time');
        }
        return $totalTime;
    }

    public function isCompletedByUser($userId)
    {
        //if number of episodes equal to zero return false
        if ($this->numberOfEpisode() == 0) {
            return false;
        }

        return $this->section->every(function ($section) use ($userId) {
            return $section->episode->where('publish', 1)->every(function ($episode) use ($userId) {
                return $episode->videos->where('type', 'stream')->every(function ($video) use ($userId) {
                    $view = VideoView::where('user_id', $userId)
                        ->where('video_id', $video->id)
                        ->latest()
                        ->first();
                    return $view && $view->watched;
                });
            });
        });
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}

<?php

namespace App\Models;

use App\Contracts\Likeable;
use App\Models\Concerns\Likes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model implements Likeable
{
    use HasFactory, Likes;
    protected $fillable = [
        'user_id',
        'comment',
        'parent_id',
        'approved',
        'commentable_id',
        'commentable_type',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }


    public function commentable()
    {
        return $this->morphTo();
    }

    public function childs()
    {
        return $this->hasMany(Comment::class, 'parent_id', 'id');
    }

    // public function descendants($approved = true)
    // {
    //     $collection = new \Illuminate\Support\Collection();
    //     foreach ($this->childs as $chi) {
    //         if ($chi->approved === $approved) {
    //             $collection->add($chi);
    //             $collection = $collection->merge($chi->descendants());
    //         }
    //     }
    //     return $collection;
    // }

    public function descendants($approved = true)
    {
        $collection = collect();

        foreach ($this->childs as $child) {
            if (is_null($approved) || $child->approved == $approved) {
                $collection->add($child);
            }

            $collection = $collection->merge($child->descendants($approved));
        }

        return $collection;
    }

}

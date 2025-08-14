<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class Event extends Model
{
    use HasFactory, Sluggable;
    protected $fillable = [
        'title',
        'english_title',
        'slug',
        'description',
        'icon',
        'event_group_id',
        'is_email_enabled',
        'is_sms_enabled',
        'is_telegram_enabled',
        'is_site_enabled'
    ];


    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'english_title',
            ],
        ];
    }

    public function eventGroup()
    {
        return $this->belongsTo(EventGroup::class);
    }

    public function preferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function isChannelEnabled($channel)
    {
        switch ($channel) {
            case 'via_email':
                return $this->is_email_enabled;
            case 'via_sms':
                return $this->is_sms_enabled;
            case 'via_telegram':
                return $this->is_telegram_enabled;
            case 'via_site':
                return $this->is_site_enabled;
            default:
                return false;
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attach extends Model
{
    use HasFactory;
    protected $fillable = [
        'attachable_id',
        'attachable_type',
        'title',
        'url'
    ];


    public function deleteMediaFiles()
    {
        foreach (['url'] as $field) {
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



    /**
     * Get the parent attachable model (course , section , episode).
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}

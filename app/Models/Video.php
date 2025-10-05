<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\VideoView;
use Illuminate\Support\Facades\Storage;
use App\Traits\CascadesDeletes;

class Video extends Model
{
    use HasFactory, CascadesDeletes;
    protected $fillable = [
        'type',
        'path',
        'disk',
        'duration',
        'quality',
        'videoable_id',
        'videoable_type',
        'status',
    ];

    public function getCascadeRelations(): array
    {
        return [
            'videoViews',
        ];
    }

    public function deleteMediaFiles()
    {
        if (!$this->path || !$this->disk) {
            return;
        }

        $storage = Storage::disk($this->disk);
        $folderPath = dirname($this->path);

        if ($storage->exists($folderPath)) {
            $files = $storage->allFiles($folderPath);
            foreach ($files as $file) {
                $storage->delete($file);
            }
            $storage->deleteDirectory($folderPath);
        }
    }


    public function videoable()
    {
        return $this->morphTo();
    }


    public function videoViews()
    {
        return $this->hasMany(VideoView::class);
    }
}

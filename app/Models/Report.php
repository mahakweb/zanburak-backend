<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'reportable_id',
        'reportable_type',
        'report',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function reportable()
    {
        return $this->morphTo();
    }

    /**
     * Normalize reportable_type to full class name
     * This ensures both short names (Question) and full class names (App\Models\Question) work
     */
    public function normalizeReportableType()
    {
        $type = $this->getOriginal('reportable_type');
        
        // If it's already a full class name, return it
        if (strpos($type, 'App\\Models\\') === 0) {
            return $type;
        }
        
        // If it's a short name, convert it to full class name
        $fullClassName = "App\\Models\\" . $type;
        if (class_exists($fullClassName)) {
            $this->setAttribute('reportable_type', $fullClassName);
            $this->syncOriginalAttribute('reportable_type');
            return $fullClassName;
        }
        
        // Fallback to original
        return $type;
    }
}

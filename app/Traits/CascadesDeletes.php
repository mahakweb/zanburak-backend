<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait CascadesDeletes
{
    public static function bootCascadesDeletes()
    {
        static::deleting(function (Model $model) {
            // حذف فایل‌های مدل
            if (method_exists($model, 'deleteMediaFiles')) {
                $model->deleteMediaFiles();
            }

            // حذف روابط
            foreach ($model->getCascadeRelations() as $relationName) {
                if (!method_exists($model, $relationName)) {
                    continue; // اگر متد وجود نداشت، رد شو
                }

                $relation = $model->$relationName();

                if ($relation instanceof Relation) {
                    foreach ($relation->get() as $related) {
                        if (method_exists($related, 'deleteMediaFiles')) {
                            $related->deleteMediaFiles();
                        }

                        if (method_exists($related, 'delete')) {
                            $related->delete();
                        }
                    }
                }
            }
        });
    }

    abstract public function getCascadeRelations(): array;
}

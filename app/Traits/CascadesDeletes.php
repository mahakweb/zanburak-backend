<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait CascadesDeletes
{
    public static function bootCascadesDeletes()
    {
        static::deleting(function (Model $model) {
            static::safeDeleteMediaFiles($model);

            foreach ($model->getCascadeRelations() as $relationName) {
                if (!method_exists($model, $relationName)) {
                    continue;
                }

                $relation = $model->$relationName();

                if ($relation instanceof Relation) {
                    foreach ($relation->get() as $related) {
                        static::safeDeleteMediaFiles($related);

                        if (method_exists($related, 'delete')) {
                            $related->delete();
                        }
                    }
                }
            }
        });
    }

    protected static function safeDeleteMediaFiles(Model $model): void
    {
        if (!method_exists($model, 'deleteMediaFiles')) {
            return;
        }

        try {
            $model->deleteMediaFiles();
        } catch (\Throwable $e) {
            Log::warning('Failed to delete media files during cascade delete', [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    abstract public function getCascadeRelations(): array;
}

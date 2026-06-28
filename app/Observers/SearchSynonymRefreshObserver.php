<?php

namespace App\Observers;

use App\Jobs\RebuildSearchSynonyms;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Question;

class SearchSynonymRefreshObserver
{
    public function saved(Course|Episode|Question $model): void
    {
        RebuildSearchSynonyms::dispatch();
    }

    public function deleted(Course|Episode|Question $model): void
    {
        RebuildSearchSynonyms::dispatch();
    }
}

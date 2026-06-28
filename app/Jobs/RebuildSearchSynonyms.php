<?php

namespace App\Jobs;

use App\Services\Search\SearchSynonymRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RebuildSearchSynonyms implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 60;

    public function handle(SearchSynonymRegistry $registry): void
    {
        $registry->refresh();
    }

    public function uniqueId(): string
    {
        return 'rebuild-search-synonyms';
    }
}

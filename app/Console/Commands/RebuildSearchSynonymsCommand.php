<?php

namespace App\Console\Commands;

use App\Services\Search\SearchSynonymRegistry;
use Illuminate\Console\Command;

class RebuildSearchSynonymsCommand extends Command
{
    protected $signature = 'search:rebuild-synonyms';

    protected $description = 'Rebuild search synonym groups from published courses, episodes, and questions';

    public function handle(SearchSynonymRegistry $registry): int
    {
        $synonyms = $registry->refresh();
        $count = count($synonyms);

        $this->info("Rebuilt {$count} synonym groups from content.");

        if ($count > 0) {
            $this->line('Sample groups:');
            foreach (array_slice($synonyms, 0, 5) as $key => $group) {
                $this->line("  [{$key}] ".implode(' | ', array_slice($group, 0, 6)));
            }
        }

        $this->comment('Run `php artisan scout:sync-index-settings` to push synonyms to Meilisearch.');

        return self::SUCCESS;
    }
}

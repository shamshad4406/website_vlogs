<?php

namespace App\Console\Commands;

use App\Models\SearchLog;
use App\Models\VlogView;
use Illuminate\Console\Command;

class PruneOldData extends Command
{
    protected $signature = 'data:prune {--days=90 : Keep raw rows for this many days}';

    protected $description = 'Prune raw view and search rows that have already been aggregated';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $views = VlogView::where('viewed_at', '<', $cutoff)->delete();
        $searches = SearchLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$views} view row(s) and {$searches} search row(s) older than {$days} days.");
        $this->line('Daily aggregates are kept, so analytics history is not lost.');

        return self::SUCCESS;
    }
}

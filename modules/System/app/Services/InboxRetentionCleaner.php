<?php

namespace Modules\System\App\Services;

use Illuminate\Support\Facades\Cache;
use Modules\System\App\Models\InboxMessage;
use Modules\System\App\Support\InboxConfigRegistrar;

class InboxRetentionCleaner
{
    public function cleanupIfDue(): void
    {
        $days = InboxConfigRegistrar::retentionDays();
        if ($days <= 0) {
            return;
        }

        if (Cache::has('inbox_retention_cleaned_at')) {
            return;
        }

        $cutoff = now()->subDays($days);
        InboxMessage::withTrashed()
            ->where('created_at', '<', $cutoff)
            ->forceDelete();

        Cache::put('inbox_retention_cleaned_at', 1, now()->addHours(6));
    }
}

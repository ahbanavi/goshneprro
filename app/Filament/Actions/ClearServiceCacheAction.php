<?php

namespace App\Filament\Actions;

use App\Interfaces\Party;
use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;

class ClearServiceCacheAction extends Action
{
    protected string|Closure|null $failureNotificationTitle = 'Failed to Clear Cache';

    protected string|Closure|null $successNotificationTitle = 'Cache Cleared';

    protected string|Htmlable|Closure|null $label = 'Clear Cache';

    public static function getDefaultName(): ?string
    {
        return 'clear-cache';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->color('gray');

        $this->icon('heroicon-o-trash');

        $this->requiresConfirmation();

        $this->badge(fn (Party $record) => $record->cacheLen());

        $this->action(function (Party $record) {
            if ($record->clearCache()) {
                $this->success();
            } else {
                $this->failure();
            }
        });
    }
}

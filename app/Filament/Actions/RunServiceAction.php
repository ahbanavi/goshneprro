<?php

namespace App\Filament\Actions;

use App\Interfaces\Party;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class RunServiceAction extends Action
{
    protected string|Closure|null $failureNotificationTitle = 'Failed to get data';

    public static function getDefaultName(): ?string
    {
        return 'run';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->color('success');

        $this->icon('heroicon-o-play');

        $this->action(function (Party $record) {
            $count = $record->run();

            if ($count < 0) {
                $this->failure();
            } elseif ($count === 0) {
                Notification::make()
                    ->title('No new products found')
                    ->info()
                    ->seconds(15)
                    ->send();
            } else {
                Notification::make()
                    ->title("{$count} new products found")
                    ->icon('heroicon-o-paper-airplane')
                    ->success()
                    ->seconds(30)
                    ->send();
            }
        });
    }
}

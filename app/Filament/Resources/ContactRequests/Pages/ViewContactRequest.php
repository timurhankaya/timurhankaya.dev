<?php

namespace App\Filament\Resources\ContactRequests\Pages;

use App\Filament\Resources\ContactRequests\ContactRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactRequest extends ViewRecord
{
    protected static string $resource = ContactRequestResource::class;

    /** Opening a message is reading it. */
    public function mount(int|string $record): void
    {
        parent::mount($record);
        if ($this->record->read_at === null) {
            $this->record->forceFill(['read_at' => now()])->save();
        }
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

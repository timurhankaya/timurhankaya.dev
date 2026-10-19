<?php

namespace App\Filament\Resources\ContactRequests\Schemas;

use App\Filament\Resources\ContactRequests\ContactRequestResource;
use App\Models\ContactRequest;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContactRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('name')->label('Ad'),
            TextEntry::make('email')->label('E-posta')->copyable()->url(fn (ContactRequest $record): string => 'mailto:'.$record->email),
            TextEntry::make('company')->label('Şirket')->placeholder('—'),
            TextEntry::make('topic')->label('Konu')->formatStateUsing(fn (string $state): string => ContactRequestResource::label('form_topics', $state)),
            TextEntry::make('services')->label('İhtiyaç')->placeholder('—')->badge()->formatStateUsing(fn (string $state): string => ContactRequestResource::label('form_service_options', $state)),
            TextEntry::make('budget')->label('Bütçe')->placeholder('—'),
            TextEntry::make('timeline')->label('Zaman')->placeholder('—')->formatStateUsing(fn (string $state): string => ContactRequestResource::label('form_timelines', $state)),
            TextEntry::make('locale')->label('Dil')->formatStateUsing(fn (string $state): string => strtoupper($state)),
            TextEntry::make('created_at')->label('Gönderildi')->dateTime('d.m.Y H:i'),
            TextEntry::make('message')->label('Mesaj')->columnSpanFull()->prose(),
        ]);
    }
}

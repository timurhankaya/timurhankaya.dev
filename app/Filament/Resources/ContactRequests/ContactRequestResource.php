<?php

namespace App\Filament\Resources\ContactRequests;

use App\Filament\Resources\ContactRequests\Pages\ListContactRequests;
use App\Filament\Resources\ContactRequests\Pages\ViewContactRequest;
use App\Filament\Resources\ContactRequests\Schemas\ContactRequestInfolist;
use App\Filament\Resources\ContactRequests\Tables\ContactRequestsTable;
use App\Models\ContactRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContactRequestResource extends Resource
{
    protected static ?string $model = ContactRequest::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Mesaj';

    protected static ?string $pluralModelLabel = 'Gelen mesajlar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    /** Labels come from the public form, in the admin panel's language. */
    public static function label(string $group, ?string $value): string
    {
        return $value === null ? '—' : (string) data_get(trans('brand.'.$group, [], 'tr'), $value, $value);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContactRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactRequestsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $unread = ContactRequest::whereNull('read_at')->count();

        return $unread > 0 ? (string) $unread : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactRequests::route('/'),
            'view' => ViewContactRequest::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Resources\ContactRequests\Tables;

use App\Filament\Resources\ContactRequests\ContactRequestResource;
use App\Models\ContactRequest;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContactRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            IconColumn::make('read_at')->label('Okundu')->boolean()->state(fn (ContactRequest $record): bool => $record->read_at !== null),
            TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
            TextColumn::make('topic')->label('Konu')->badge()->formatStateUsing(fn (string $state): string => ContactRequestResource::label('form_topics', $state)),
            TextColumn::make('name')->label('Ad')->searchable(),
            TextColumn::make('email')->label('E-posta')->searchable()->copyable(),
            TextColumn::make('company')->label('Şirket')->placeholder('—'),
            TextColumn::make('message')->label('Mesaj')->limit(60),
        ])->filters([
            SelectFilter::make('topic')->label('Konu')->options(trans('brand.form_topics', [], 'tr')),
            TernaryFilter::make('read_at')->label('Okundu')->nullable(),
        ])->recordActions([ViewAction::make(), DeleteAction::make()]);
    }
}

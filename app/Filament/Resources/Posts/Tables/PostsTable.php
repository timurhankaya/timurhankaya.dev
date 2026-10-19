<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Enums\PostType;
use App\Filament\Resources\Posts\PostActions;
use App\Models\Post;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('updated_at', 'desc')->columns([
            TextColumn::make('title')->label('Başlık')->searchable()->limit(55)->sortable(),
            TextColumn::make('type')->label('Tür')->badge(),
            TextColumn::make('case_meta.project_name')->label('Proje')->placeholder('—'),
            TextColumn::make('state')->label('Durum')->state(fn (Post $record): string => $record->trashed() ? 'Silindi' : (! $record->published_at ? 'Taslak' : ($record->published_at->isFuture() ? 'Zamanlanmış' : 'Yayında')))->badge(),
            TextColumn::make('published_at')->label('Yayın tarihi')->dateTime('d.m.Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('type')->label('Tür')->options(PostType::class),
            SelectFilter::make('state')->label('Durum')->options(['draft' => 'Taslak', 'scheduled' => 'Zamanlanmış', 'published' => 'Yayında'])
                ->query(function (Builder $query, array $data): Builder {
                    return match ($data['value'] ?? null) {
                        'draft' => $query->whereNull('published_at'),
                        'scheduled' => $query->where('published_at', '>', now()),
                        'published' => $query->published(),
                        default => $query,
                    };
                }),
            TrashedFilter::make(),
        ])->recordActions([EditAction::make(), PostActions::preview(), PostActions::publish(), PostActions::unpublish(), DeleteAction::make(), RestoreAction::make()]);
    }
}

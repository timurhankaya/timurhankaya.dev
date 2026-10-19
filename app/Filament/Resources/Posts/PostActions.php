<?php

namespace App\Filament\Resources\Posts;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

final class PostActions
{
    public static function preview(): Action
    {
        return Action::make('preview')->label('Önizle')->icon('heroicon-o-eye')
            ->url(fn (Post $record) => route('admin.posts.preview', ['id' => $record->id, 'locale' => app()->getLocale()]))->openUrlInNewTab();
    }

    public static function publish(): Action
    {
        return Action::make('publish')->label('Şimdi yayınla')->icon('heroicon-o-paper-airplane')->requiresConfirmation()
            ->visible(fn (Post $record) => ! $record->trashed() && (! $record->published_at || $record->published_at->isFuture()))
            ->action(function (Post $record): void {
                try {
                    $record->update(['published_at' => now()]);
                    Notification::make()->title('Yayınlandı')->success()->send();
                } catch (ValidationException $error) {
                    Notification::make()->title('Yayınlanamadı')->body(implode(' ', $error->validator->errors()->all()))->danger()->persistent()->send();
                }
            });
    }

    public static function unpublish(): Action
    {
        return Action::make('unpublish')->label('Taslağa al')->icon('heroicon-o-eye-slash')->requiresConfirmation()
            ->visible(fn (Post $record) => ! $record->trashed() && $record->published_at !== null)
            ->action(fn (Post $record) => $record->update(['published_at' => null]));
    }
}

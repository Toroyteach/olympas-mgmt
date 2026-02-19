<?php

namespace App\Filament\Resources\Media\SocialPosts\Tables;

use App\Enums\ContentType;
use App\Enums\DispatchStatus;
use App\Jobs\PublishSocialPostJob;
use App\Models\Media\SocialPost;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SocialPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('content')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('content_type')
                    ->badge()
                    ->formatStateUsing(fn (ContentType $state) => $state->label()),

                TextColumn::make('dispatches_count')
                    ->counts('dispatches')
                    ->label('Accounts'),

                TextColumn::make('published_count')
                    ->label('Published')
                    ->getStateUsing(
                        fn (SocialPost $r) => $r->dispatches()->where('status', 'published')->count()
                            . '/' . $r->dispatches()->count()
                    )
                    ->color(fn (SocialPost $r) => $r->dispatches()->where('status', 'failed')->exists() ? 'danger' : 'success'),

                TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->placeholder('Immediate')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('retry_failed')
                    ->label('Retry Failed')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (SocialPost $r) => $r->dispatches()->where('status', 'failed')->exists())
                    ->requiresConfirmation()
                    ->action(function (SocialPost $record) {
                        $failed = $record->dispatches()
                            ->where('status', DispatchStatus::Failed)
                            ->where('attempts', '<', 3)
                            ->get();

                        foreach ($failed as $dispatch) {
                            $dispatch->update(['status' => DispatchStatus::Pending]);
                            PublishSocialPostJob::dispatch($dispatch);
                        }

                        Notification::make()
                            ->title("Retrying {$failed->count()} dispatch(es)")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}

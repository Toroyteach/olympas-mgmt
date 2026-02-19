<?php

namespace App\Filament\Resources\Media\AiImages\Pages;

use App\Enums\ImageStatus;
use App\Filament\Resources\Media\AiImages\AiImageResource;
use App\Jobs\GenerateAiImageJob;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;

class ViewAiImage extends ViewRecord
{
    protected static string $resource = AiImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('retry')
                ->label('Retry Generation')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn() => $this->record->status === ImageStatus::Failed)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'pending', 'error_message' => null]);
                    GenerateAiImageJob::dispatch($this->record);

                    Notification::make()
                        ->title('Retrying image generation')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('download')
                ->label('Download Image')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn() => $this->record->status === ImageStatus::Completed && $this->record->generated_image_path)
                ->url(fn() => $this->record->generated_image_url)
                ->openUrlInNewTab(),

            Actions\DeleteAction::make(),
        ];
    }

    // public function infolist(Infolist $infolist): Infolist
    // {
    //     return $infolist->schema([
    //         Section::make('Generated Image')
    //             ->schema([
    //                 ImageEntry::make('generated_image_path')
    //                     ->label('')
    //                     ->disk(config('ai-images.storage_disk', 'public'))
    //                     ->height(400)
    //                     ->columnSpanFull()
    //                     ->visible(fn($record) => $record->status === ImageStatus::Completed),

    //                 Infolists\Components\TextEntry::make('status')
    //                     ->badge()
    //                     ->color(fn($state) => $state instanceof ImageStatus ? $state->color() : 'gray')
    //                     ->visible(fn($record) => $record->status !== ImageStatus::Completed),

    //                 Infolists\Components\TextEntry::make('error_message')
    //                     ->label('Error')
    //                     ->color('danger')
    //                     ->visible(fn($record) => $record->status === ImageStatus::Failed),
    //             ]),

    //         Section::make('Details')
    //             ->columns(2)
    //             ->schema([
    //                 TextEntry::make('title'),
    //                 TextEntry::make('provider')->badge(),
    //                 TextEntry::make('prompt')->columnSpanFull(),

    //                 TextEntry::make('aspect_ratio')
    //                     ->formatStateUsing(fn($state) => $state->label()),

    //                 TextEntry::make('quality')
    //                     ->formatStateUsing(fn($state) => $state->label()),

    //                 TextEntry::make('generation_time_ms')
    //                     ->label('Generation Time')
    //                     ->formatStateUsing(fn(?int $state) => $state ? number_format($state / 1000, 1) . ' seconds' : '—'),

    //                 TextEntry::make('created_at')
    //                     ->dateTime('M d, Y H:i'),

    //                 ImageEntry::make('reference_image_path')
    //                     ->label('Reference Image')
    //                     ->disk(config('ai-images.storage_disk', 'public'))
    //                     ->height(150)
    //                     ->visible(fn($record) => $record->reference_image_path !== null),
    //             ]),
    //     ]);
    // }
}

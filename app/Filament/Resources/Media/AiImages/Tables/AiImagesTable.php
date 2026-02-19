<?php

namespace App\Filament\Resources\Media\AiImages\Tables;

use App\Enums\ImageStatus;
use App\Enums\ImageAspectRatio;
use App\Enums\ImageQuality;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AiImagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('generated_image_path')
                    ->label('Image')
                    ->disk(config('ai-images.storage_disk', 'public'))
                    ->square()
                    ->size(60),

                TextColumn::make('title')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('provider')
                    ->badge(),

                TextColumn::make('aspect_ratio')
                    ->label('Ratio')
                    ->formatStateUsing(fn ($state) => $state instanceof ImageAspectRatio ? $state->label() : $state),

                TextColumn::make('quality')
                    ->formatStateUsing(fn ($state) => $state instanceof ImageQuality ? $state->label() : $state),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state instanceof ImageStatus ? $state->color() : 'gray'),

                TextColumn::make('generation_time_ms')
                    ->label('Time')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state / 1000, 1) . 's' : '—')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(ImageStatus::cases())->mapWithKeys(
                        fn ($case) => [$case->value => $case->label()]
                    )),

                SelectFilter::make('provider')
                    ->options([
                        'openai' => 'OpenAI',
                        'gemini' => 'Gemini',
                        'xai' => 'xAI',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

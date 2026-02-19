<?php

namespace App\Filament\Resources\Media\SocialAccounts\Tables;

use App\Enums\SocialPlatform;
use App\Models\Media\SocialAccount;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SocialAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('platform')
                    ->badge()
                    ->formatStateUsing(fn (SocialPlatform $state) => $state->label())
                    ->color(fn (SocialPlatform $state) => $state->color()),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('dispatches_count')
                    ->counts('dispatches')
                    ->label('Posts'),
                TextColumn::make('updated_at')->since(),
            ])
            ->actions([
                EditAction::make(),
                Action::make('toggle')
                    ->label(fn (SocialAccount $r) => $r->is_active ? 'Disable' : 'Enable')
                    ->icon(fn (SocialAccount $r) => $r->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (SocialAccount $r) => $r->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (SocialAccount $r) => $r->update(['is_active' => ! $r->is_active])),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}

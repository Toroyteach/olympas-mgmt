<?php

namespace App\Filament\Resources\WarehouseEntries\Pages;

use App\Filament\Resources\WarehouseEntries\WarehouseEntryResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewWarehouseEntry extends ViewRecord
{
    protected static string $resource = WarehouseEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Entry Details')
                    ->schema([
                        TextEntry::make('reference_number')->label('Reference'),
                        TextEntry::make('status')->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'completed' => 'success',
                                'pending'   => 'warning',
                                'cancelled' => 'danger',
                                default     => 'gray',
                            }),
                        TextEntry::make('supplier_name')->label('Supplier')->placeholder('—'),
                        TextEntry::make('supplier_invoice')->label('Invoice #')->placeholder('—'),
                        TextEntry::make('user.name')->label('Received By'),
                        TextEntry::make('received_at')->label('Received At')->dateTime('d M Y H:i'),
                        TextEntry::make('notes')->label('Notes')->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Products Received')
                    ->schema([
                        RepeatableEntry::make('itemsWithTrashedProducts')
                            ->label('Product Items')
                            ->schema([
                                TextEntry::make('product.name')->label('Product')->weight('bold'),
                                TextEntry::make('quantity')->label('Quantity'),
                                TextEntry::make('unit_cost')->label('Unit Cost')->money('KES')->placeholder('—'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}

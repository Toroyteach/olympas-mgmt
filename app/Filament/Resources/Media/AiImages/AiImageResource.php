<?php

namespace App\Filament\Resources\Media\AiImages;

use App\Filament\Resources\Media\AiImages\Schemas\AiImageForm;
use App\Filament\Resources\Media\AiImages\Tables\AiImagesTable;
use App\Filament\Resources\Media\AiImages\Pages\CreateAiImage;
use App\Filament\Resources\Media\AiImages\Pages\EditAiImage;
use App\Filament\Resources\Media\AiImages\Pages\ListAiImages;
use App\Models\Media\AiImage;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiImageResource extends Resource
{
    protected static ?string $model = AiImage::class;

    protected static ?string $slug = 'media/ai-images';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Sparkles;

    protected static string | UnitEnum | null $navigationGroup = 'Media';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return AiImageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiImagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiImages::route('/'),
            'create' => CreateAiImage::route('/create'),
            'edit' => EditAiImage::route('/{record}/edit'),
        ];
    }

    // public static function getNavigationBadge(): ?string
    // {
    //     /** @var class-string<Model> $modelClass */
    //     $modelClass = static::$model;

    //     return (string) $modelClass::where('status', 'new')->count();
    // }
}

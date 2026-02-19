<?php

namespace App\Filament\Resources\Media\SocialPosts;

use App\Filament\Resources\Media\SocialPosts\Pages\CreateSocialPost;
use App\Filament\Resources\Media\SocialPosts\Pages\EditSocialPost;
use App\Filament\Resources\Media\SocialPosts\Pages\ListSocialPosts;
use App\Filament\Resources\Media\SocialPosts\RelationManagers\DispatchesRelationManager;
use App\Filament\Resources\Media\SocialPosts\Schemas\SocialPostForm;
use App\Filament\Resources\Media\SocialPosts\Tables\SocialPostsTable;
use App\Models\Media\SocialPost;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SocialPostResource extends Resource
{
    protected static ?string $model = SocialPost::class;

    protected static ?string $slug = 'media/social-posts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::PencilSquare;

    protected static string | UnitEnum | null $navigationGroup = 'Media';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'Social Media Posts';

    public static function form(Schema $schema): Schema
    {
        return SocialPostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SocialPostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DispatchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSocialPosts::route('/'),
            'create' => CreateSocialPost::route('/create'),
            'edit' => EditSocialPost::route('/{record}/edit'),
        ];
    }
}

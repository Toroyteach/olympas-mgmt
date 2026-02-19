<?php

namespace App\Filament\Resources\Media\SocialAccounts;

use App\Filament\Resources\Media\SocialAccounts\Pages\CreateSocialAccount;
use App\Filament\Resources\Media\SocialAccounts\Pages\EditSocialAccount;
use App\Filament\Resources\Media\SocialAccounts\Pages\ListSocialAccounts;
use App\Filament\Resources\Media\SocialAccounts\Schemas\SocialAccountForm;
use App\Filament\Resources\Media\SocialAccounts\Tables\SocialAccountsTable;
use App\Models\Media\SocialAccount;
use App\Services\SocialPostingService;
use BackedEnum;
use UnitEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SocialAccountResource extends Resource
{
    protected static ?string $model = SocialAccount::class;

    protected static ?string $slug = 'media/social-accounts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static string | UnitEnum | null $navigationGroup = 'Media';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'Social Media Accounts';

    public static function form(Schema $schema): Schema
    {
        return SocialAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SocialAccountsTable::configure($table);
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
            'index' => ListSocialAccounts::route('/'),
            'create' => CreateSocialAccount::route('/create'),
            'edit' => EditSocialAccount::route('/{record}/edit'),
        ];
    }
}

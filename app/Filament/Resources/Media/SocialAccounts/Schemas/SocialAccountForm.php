<?php

namespace App\Filament\Resources\Media\SocialAccounts\Schemas;

use App\Enums\SocialPlatform;
use App\Services\SocialPostingService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SocialAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Account Details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('platform')
                    ->options(
                        collect(SocialPlatform::cases())
                            ->mapWithKeys(fn($p) => [$p->value => $p->label()])
                    )
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn(Set $set) => $set('credentials', [])),

                Toggle::make('is_active')
                    ->default(true),

                Textarea::make('notes')
                    ->rows(2),
            ]),

            Section::make('Credentials')
                ->description('Stored encrypted. These are the API tokens/keys for this platform.')
                ->schema(fn(Get $get): array => static::buildCredentialFields($get('platform')))
                ->visible(fn(Get $get) => filled($get('platform'))),
        ]);
    }

    protected static function buildCredentialFields(?string $platform): array
    {
        if (! $platform) {
            return [];
        }

        $fields = SocialPostingService::getCredentialFields($platform);

        return collect($fields)->map(
            fn(string $field) => TextInput::make("credentials.{$field}")
                ->label(str($field)->replace('_', ' ')->title())
                ->password()
                ->revealable()
        )->all();
    }
}

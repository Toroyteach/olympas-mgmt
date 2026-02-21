<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Support\RawHtmlString;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile Photo')
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->dehydrated(fn ($state) => filled($state))
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(1)
                    ->columns(1),

                Section::make('Account Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Display Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('status')
                            ->options([
                                'active'    => 'Active',
                                'inactive'  => 'Inactive',
                                'suspended' => 'Suspended',
                            ])
                            ->required()
                            ->default('active')
                            ->native(false),
                    ])
                    ->columnSpan(2)
                    ->columns(2),

                Section::make('Personal Details')
                    ->schema([
                        TextInput::make('first_name')
                            ->maxLength(255),

                        TextInput::make('last_name')
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('job_title')
                            ->maxLength(255),

                        TextInput::make('department')
                            ->maxLength(255),

                        Textarea::make('bio')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Roles & Permissions')
                    ->schema([
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),

                        // Select::make('permissions')
                        //     ->label('Direct Permissions')
                        //     ->options(Permission::query()->pluck('name', 'id'))
                        //     ->multiple()
                        //     ->preload()
                        //     ->searchable()
                        //     ->getSearchResultsUsing(
                        //         fn(string $search) => Permission::where('name', 'like', "%{$search}%")
                        //             ->limit(50)
                        //             ->pluck('name', 'id')
                        //     )
                        //     ->columnSpanFull()
                        //     ->helperText('Assign direct permissions in addition to role-based permissions.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Account Meta')
                    ->schema([
                        Placeholder::make('email_verified_at')
                            ->label('Email Verified')
                            ->content(fn(?User $record): string => $record?->email_verified_at
                                ? $record->email_verified_at->diffForHumans()
                                : 'Not verified'),

                        Placeholder::make('last_login_at')
                            ->label('Last Login')
                            ->content(fn(?User $record): string => $record?->last_login_at
                                ? $record->last_login_at->diffForHumans()
                                : 'Never'),

                        Placeholder::make('invitation_status')
                            ->label('Invitation Status')
                            ->content(function (?User $record): string {
                                if (! $record) {
                                    return 'Will be sent on create';
                                }
                                if ($record->invitation_accepted_at) {
                                    return 'Accepted ' . $record->invitation_accepted_at->diffForHumans();
                                }
                                if ($record->invitation_sent_at) {
                                    return 'Pending (sent ' . $record->invitation_sent_at->diffForHumans() . ')';
                                }
                                return 'Not yet invited';
                            }),

                        Placeholder::make('created_at')
                            ->label('Member Since')
                            ->content(fn(?User $record): string => $record?->created_at
                                ? $record->created_at->toFormattedDateString()
                                : '-'),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->hiddenOn('create'),
            ])
            ->columns(3);
    }
}

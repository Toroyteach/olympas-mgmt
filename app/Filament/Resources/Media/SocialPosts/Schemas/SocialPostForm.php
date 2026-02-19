<?php

namespace App\Filament\Resources\Media\SocialPosts\Schemas;

use App\Enums\ContentType;
use App\Models\Media\SocialAccount;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SocialPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Post Content')->schema([
                TextInput::make('title')
                    ->maxLength(255),

                Textarea::make('content')
                    ->required()
                    ->rows(4)
                    ->maxLength(5000),

                TextInput::make('url')
                    ->url()
                    ->maxLength(2048)
                    ->helperText('Optional link to include with the post.'),

                Select::make('content_type')
                    ->options(
                        collect(ContentType::cases())
                            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                    )
                    ->default('text')
                    ->required()
                    ->reactive(),

                FileUpload::make('media_paths')
                    ->label('Media Files')
                    ->multiple()
                    ->directory('social-media')
                    ->acceptedFileTypes([
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                        'video/mp4', 'video/quicktime', 'video/webm',
                    ])
                    ->maxSize(50 * 1024) // 50MB
                    ->visible(fn (Get $get) => in_array($get('content_type'), ['image', 'video', 'carousel']))
                    ->helperText('Upload images or videos for the post.'),
            ]),

            Section::make('Publishing')->schema([
                CheckboxList::make('account_ids')
                    ->label('Post To')
                    ->options(
                        SocialAccount::active()
                            ->get()
                            ->mapWithKeys(fn ($a) => [$a->id => "{$a->name} ({$a->platform->label()})"])
                    )
                    ->required()
                    ->columns(2)
                    ->helperText('Select which accounts to publish this post to.'),

                DateTimePicker::make('scheduled_at')
                    ->label('Schedule For')
                    ->native(false)
                    ->minDate(now())
                    ->helperText('Leave empty to publish immediately.'),
            ]),
        ]);
    }
}

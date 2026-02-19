<?php

namespace App\Filament\Resources\Media\AiImages\Schemas;

use App\Enums\ImageAspectRatio;
use App\Enums\ImageQuality;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Image Details')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Christmas 2026 Company Greeting')
                        ->columnSpanFull(),

                    Textarea::make('prompt')
                        ->required()
                        ->rows(4)
                        ->maxLength(2000)
                        ->placeholder('Describe the image you want to generate. Be specific about colors, style, elements, and mood.')
                        ->helperText('Be detailed and specific. The system will add professional context automatically.')
                        ->columnSpanFull(),

                    FileUpload::make('reference_image_path')
                        ->label('Reference Image (Optional)')
                        ->image()
                        ->directory('ai-references')
                        ->maxSize(config('ai-images.max_reference_size_kb', 4096))
                        ->helperText('Upload a company logo, brand asset, or reference image the AI should incorporate.')
                        ->columnSpanFull(),
                ]),

            Section::make('Generation Settings')
                ->columns(3)
                ->schema([
                    Select::make('aspect_ratio')
                        ->options(collect(ImageAspectRatio::cases())->mapWithKeys(
                            fn ($case) => [$case->value => $case->label()]
                        ))
                        ->default(ImageAspectRatio::Square->value)
                        ->required(),

                    Select::make('quality')
                        ->options(collect(ImageQuality::cases())->mapWithKeys(
                            fn ($case) => [$case->value => $case->label()]
                        ))
                        ->default(ImageQuality::Medium->value)
                        ->required(),

                    Select::make('provider')
                        ->options([
                            'openai' => 'OpenAI (DALL-E)',
                            'gemini' => 'Google Gemini',
                            'xai' => 'xAI (Grok)',
                        ])
                        ->default(config('ai-images.default_provider', 'openai'))
                        ->required(),
                ]),
        ]);
    }
}

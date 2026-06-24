<?php

namespace App\Filament\Resources\Lessons\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('lesson_title')
                    ->required()
                    ->label('Lesson Title'),

                Textarea::make('description')
                    ->label('Description'),

                Select::make('section_model_id')
                    ->relationship('section', 'section_name')
                    ->required(),

                Select::make('subject_model_id')
                    ->relationship('subject', 'subject_name')
                    ->required(),

                FileUpload::make('attachment')
                    ->label('Upload PDF')
                    ->disk('public')
                    ->directory('lessons')
                    ->acceptedFileTypes(['application/pdf'])
                    ->preserveFilenames()
                    ->afterStateUpdated(function ($state, $set, $record) {
                        if ($state && $record) {
                            $record->addMedia($state->getRealPath())
                                   ->usingFileName($state->getClientOriginalName())
                                   ->toMediaCollection('attachments');
                        }
                    }),
            ]);
    }
}

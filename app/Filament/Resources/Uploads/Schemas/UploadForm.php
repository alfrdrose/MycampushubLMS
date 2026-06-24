<?php

namespace App\Filament\Resources\Uploads\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UploadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                
                FileUpload::make('attachment')
                ->label('Upload PDF')
                ->disk('public')
                ->directory('uploads')
                ->preserveFilenames()
                ->enableDownload()
                ->enableOpen()
                ->acceptedFileTypes(['application/pdf']) // restrict to PDFs
                ->afterStateUpdated(function ($state, $set, $record) {
                    if ($state && $record) {
                        $record->addMedia($state->getRealPath())
                            ->usingFileName($state->getClientOriginalName())
                            ->toMediaCollection('pdfs'); // collection named 'pdfs'
                    }
                }),
            ]);
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LawDocumentResource\Pages;
use App\Jobs\ProcessLawDocument;
use App\Models\LawDocument;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LawDocumentResource extends Resource
{
    protected static ?string $model = LawDocument::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Law Documents';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->maxLength(255),

            Select::make('category')
                ->options([
                    'act' => 'Act',
                    'regulation' => 'Regulation',
                    'decree' => 'Decree',
                    'constitution' => 'Constitution',
                    'subsidiary_legislation' => 'Subsidiary Legislation',
                    'court_rule' => 'Court Rule',
                    'executive_order' => 'Executive Order',
                    'criminal' => 'Criminal Law',
                    'civil' => 'Civil Law',
                    'constitutional_law' => 'Constitutional Law',
                    'commercial' => 'Commercial Law',
                    'property' => 'Property Law',
                    'family' => 'Family Law',
                    'labour' => 'Labour Law',
                    'other' => 'Other',
                ])
                ->searchable(),

            Select::make('jurisdiction')
                ->options([
                    'federal' => 'Federal',
                    'fct' => 'FCT (Abuja)',
                    'state:abia' => 'Abia State',
                    'state:adamawa' => 'Adamawa State',
                    'state:akwa_ibom' => 'Akwa Ibom State',
                    'state:anambra' => 'Anambra State',
                    'state:bauchi' => 'Bauchi State',
                    'state:bayelsa' => 'Bayelsa State',
                    'state:benue' => 'Benue State',
                    'state:borno' => 'Borno State',
                    'state:cross_river' => 'Cross River State',
                    'state:delta' => 'Delta State',
                    'state:ebonyi' => 'Ebonyi State',
                    'state:edo' => 'Edo State',
                    'state:ekiti' => 'Ekiti State',
                    'state:enugu' => 'Enugu State',
                    'state:gombe' => 'Gombe State',
                    'state:imo' => 'Imo State',
                    'state:jigawa' => 'Jigawa State',
                    'state:kaduna' => 'Kaduna State',
                    'state:kano' => 'Kano State',
                    'state:katsina' => 'Katsina State',
                    'state:kebbi' => 'Kebbi State',
                    'state:kogi' => 'Kogi State',
                    'state:kwara' => 'Kwara State',
                    'state:lagos' => 'Lagos State',
                    'state:nasarawa' => 'Nasarawa State',
                    'state:niger' => 'Niger State',
                    'state:ogun' => 'Ogun State',
                    'state:ondo' => 'Ondo State',
                    'state:osun' => 'Osun State',
                    'state:oyo' => 'Oyo State',
                    'state:plateau' => 'Plateau State',
                    'state:rivers' => 'Rivers State',
                    'state:sokoto' => 'Sokoto State',
                    'state:taraba' => 'Taraba State',
                    'state:yobe' => 'Yobe State',
                    'state:zamfara' => 'Zamfara State',
                ])
                ->searchable(),

            TextInput::make('source')
                ->placeholder('e.g. Laws of the Federation of Nigeria 2004')
                ->maxLength(255),

            TextInput::make('year')
                ->numeric()
                ->minValue(1900)
                ->maxValue(date('Y')),

            FileUpload::make('file_path')
                ->label('PDF File')
                ->disk('local')
                ->directory('laws')
                ->acceptedFileTypes(['application/pdf'])
                ->required()
                ->maxSize(50 * 1024),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('jurisdiction')->badge()->sortable()
                    ->formatStateUsing(fn (?string $state): string => match (true) {
                        $state === null => '',
                        $state === 'federal' => 'Federal',
                        $state === 'fct' => 'FCT',
                        str_starts_with($state ?? '', 'state:') => ucwords(str_replace('_', ' ', substr($state, 6))).' State',
                        default => $state ?? '',
                    }),
                TextColumn::make('year')->sortable(),
                TextColumn::make('chunk_count')->label('Chunks'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('processed_at')->dateTime()->sortable(),
            ])
            ->actions([
                Action::make('reprocess')
                    ->label('Reprocess')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (LawDocument $record) {
                        $record->chunks()->delete();
                        $record->update(['status' => 'pending', 'chunk_count' => 0]);
                        ProcessLawDocument::dispatch($record->id);
                        Notification::make()->title('Reprocessing queued')->success()->send();
                    })
                    ->requiresConfirmation(),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLawDocuments::route('/'),
            'create' => Pages\CreateLawDocument::route('/create'),
            'edit' => Pages\EditLawDocument::route('/{record}/edit'),
        ];
    }
}

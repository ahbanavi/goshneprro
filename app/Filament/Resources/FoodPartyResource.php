<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FoodPartyResource\Pages;
use App\Models\FoodParty;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FoodPartyResource extends Resource
{
    protected static ?string $model = FoodParty::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('description')->required()->maxLength(255),
                Select::make('user_id')->relationship('user', 'name')->searchable()->preload()->visible(auth()->user()->isAdmin()),
                TextInput::make('threshold')->label('Discount Threshold')->integer()->hint('between 0 and 99')->default(0)->minValue(0)->maxValue(99)->required(),
                TextInput::make('tg_chat_id')->label('Telegram Chat ID')->integer()->hint('you can get it with https://t.me/username_to_id_bot')->required(),
                Map::make('location')->label('Location')->columnSpanFull()->default([
                    'lat' => config('goshne.default.latitude'),
                    'lng' => config('goshne.default.longitude'),
                ])->afterStateUpdated(function (Set $set, ?array $state): void {
                    $set('latitude', $state['lat']);
                    $set('longitude', $state['lng']);
                })->afterStateHydrated(function ($state, $record, Set $set): void {
                    $set('location', ['lat' => $record->latitude ?? config('goshne.default.latitude'), 'lng' => $record->longitude ?? config('goshne.default.longitude')]);
                })->extraStyles(['border-radius: 15px'])->liveLocation()->showMarker()->markerColor('#22c55eff')
                    ->showFullscreenControl()->showZoomControl()->draggable()->tilesUrl('https://tile.openstreetmap.de/{z}/{x}/{y}.png')
                    ->zoom(13)->detectRetina()->showMyLocationButton()->extraTileControl([])->extraControl(['zoomDelta' => 1, 'zoomSnap' => 2])->dehydrated(false),
                TextInput::make('latitude')->required()->readOnly(),
                TextInput::make('longitude')->required()->readOnly(),
                Placeholder::make('created_at')->hiddenOn('create')->label('Created Date')->content(fn (?FoodParty $record): string => $record?->created_at?->diffForHumans() ?? '-'),
                Placeholder::make('updated_at')->hiddenOn('create')->label('Last Modified Date')->content(fn (?FoodParty $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                Toggle::make('active')->required()->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('user.name')->visible(fn ($livewire) => $livewire->activeTab === 'all'),
                TextInputColumn::make('description')->searchable()->rules(['string', 'max:255']),
                TextInputColumn::make('threshold')->sortable()->rules(['integer', 'min:0', 'max:99']),
                TextColumn::make('tg_chat_id'),
                TextColumn::make('created_at')->dateTime()->label('Created')->sortable(),
                TextColumn::make('updated_at')->dateTime()->label('Updated')->sortable(),
                ToggleColumn::make('active'),
            ])->defaultSort('id', 'desc')
            ->filters([
                TernaryFilter::make('active')->trueLabel('Active')->falseLabel('Inactive')->placeholder('All'),
                SelectFilter::make('user')->visible(auth()->user()->isAdmin())->relationship('user', 'name')->searchable()->preload()->multiple(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListFoodParties::route('/'),
            'create' => Pages\CreateFoodParty::route('/create'),
            'edit' => Pages\EditFoodParty::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        if (auth()->user()->isAdmin()) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->whereBelongsTo(auth()->user());
    }
}

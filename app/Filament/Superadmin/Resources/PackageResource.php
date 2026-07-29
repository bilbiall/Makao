<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\PackageResource\Pages;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Billing';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('price')
                    ->label('Price (KES)')
                    ->numeric()
                    ->required()
                    ->prefix('KES'),

                Forms\Components\Select::make('billing_interval')
                    ->options([
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                    ])
                    ->default('monthly')
                    ->required(),

                Forms\Components\TextInput::make('trial_days')
                    ->numeric()
                    ->default(14)
                    ->required(),

                Forms\Components\Section::make('Limits')
                    ->description('Leave blank for unlimited.')
                    ->schema([
                        Forms\Components\TextInput::make('max_locations')->numeric()->label('Max Locations'),
                        Forms\Components\TextInput::make('max_houses')->numeric()->label('Max Houses/Units'),
                        Forms\Components\TextInput::make('max_tenants')->numeric()->label('Max Tenants'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Features')
                    ->schema([
                        Forms\Components\Toggle::make('features.sms_notifications')->label('SMS Notifications'),
                        Forms\Components\Toggle::make('features.mpesa_payments')->label('M-Pesa Payments'),
                        Forms\Components\Toggle::make('features.reports')->label('Reports & Analytics'),
                    ])
                    ->columns(3),

                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active (visible on the pricing page)')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('price')->money('KES')->sortable(),
                Tables\Columns\TextColumn::make('billing_interval')->badge(),
                Tables\Columns\TextColumn::make('max_locations')->label('Locations')->default('Unlimited'),
                Tables\Columns\TextColumn::make('max_houses')->label('Houses')->default('Unlimited'),
                Tables\Columns\TextColumn::make('max_tenants')->label('Tenants')->default('Unlimited'),
                Tables\Columns\TextColumn::make('subscriptions_count')->counts('subscriptions')->label('Landlords'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}

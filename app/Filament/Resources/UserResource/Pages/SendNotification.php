<?php

//namespace App\Filament\Resources\AdminResource\Pages;
namespace App\Filament\Resources\UserResource\Pages;


use App\Filament\Resources\AdminResource;
use Filament\Resources\Pages\Page;

use App\Filament\Resources\UserResource;

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\Page;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Models\User;


//new edit

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;


/*class SendNotification extends Page implements HasForms
{
    //protected static string $resource = AdminResource::class;
    use InteractsWithForms;

    public array $tenant_ids = [];
    public string $message = '';

    protected static string $resource = UserResource::class;
    protected static string $view = 'filament.pages.send-notification';

    protected static ?string $navigationLabel = 'Send Notification';
    protected static ?string $navigationIcon  = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Users';
    protected static ?int    $navigationSort  = 4;


    /*protected static ?string $navigationLabel = 'Send Notification';
    protected static ?string $navigationIcon  = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Users';
    protected static ?int    $navigationSort  = 4;*/


    //protected static string $view = 'filament.resources.admin-resource.pages.send-notification';

   /* protected function getFormSchema(): array
    {
        return [

            Forms\Components\Select::make('tenant_ids')
                ->label('Select Tenants')
                ->multiple()   // enables multi-select mode
                ->options(User::where('role', 'tenant')->pluck('name', 'id'))
                ->required(),


            Forms\Components\Textarea::make('message')
                ->label('Message')
                ->rows(3)
                ->required(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('send')
                ->label('Send Notification')
                ->action('send')
                ->button(),
        ];
    }

    public function send(): void
    {
        $users = User::whereIn('id', $this->tenant_ids)->get();

        foreach ($users as $user) {
            Notification::make()
                ->title('Announcement')
                ->body($this->message)
                ->sendToDatabase($user); // saves to notifications table
        }

        $this->notify('success', 'Notifications sent!');
    }
}*/
class SendNotification extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $resource = \App\Filament\Resources\UserResource::class;
    protected static string $view = 'filament.pages.send-notification';
    protected static ?string $navigationLabel = 'Send Notification';
    protected static ?string $navigationIcon = 'heroicon-o-bell';

    public array $tenant_ids = [];
    public string $message = '';

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('tenant_ids')
                ->label('Tenants')
                ->multiple()
                ->options(User::where('role', 'tenant')->pluck('name', 'id'))
                ->required(),

            Forms\Components\Textarea::make('message')
                ->label('Message')
                ->required(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('send')
                ->label('Send Notifications')
                ->color('primary')
                ->action('send')
                ->icon('heroicon-o-send'),
        ];
    }

    public function send(): void
    {
        $this->form->validate();

        foreach ($this->tenant_ids as $id) {
            $user = User::find($id);
            Notification::make()
                ->title('Message from Admin')
                ->body($this->message)
                ->sendToDatabase($user);
        }

        Notification::make()
            ->title('Notifications sent')
            ->success()
            ->send();

        $this->reset(['tenant_ids', 'message']);
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Device;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DeviceConsole extends Component
{
    public Device $device;
    public array $authData = [];
    public ?string $initError = null;


    public function mount(Device $device)
    {
        Gate::authorize('commands:run');

        $this->device = $device->load('vendor', 'credential');

        if (!$this->device->credential) {
            $this->initError = "Błąd: Brak poświadczeń (credentials) powiązanych z tym urządzeniem.";
            Log::error("Próba dostępu do konsoli dla urządzenia {$device->name} bez poświadczeń.");
            return;
        }

        if (!$this->device->vendor) {
            $this->initError = "Błąd: Brak typu vendora (vendor) powiązanego z tym urządzeniem.";
            Log::error("Próba dostępu do konsoli dla urządzenia {$device->name} bez vendora.");
            return;
        }

        $driver = $this->device->driver_override ?: $this->device->vendor->netmiko_driver;


        $this->authData = [
            'ip' => $this->device->ip_address,
            'port' => $this->device->port,
            'netmiko_driver' => $driver,
            'username' => $this->device->credential->username,
            'password' => $this->device->credential->password, 
            'secret' => $this->device->credential->secret,     
            'initiator_user_id' => Auth::id() ?? 1,
            'device_db_id' => $this->device->id
        ];
    }


    public function render()
    {
        return view('livewire.device-console')
            ->layout('layouts.app', ['header' => "Konsola SSH: {$this->device->name}"]);
    }
}
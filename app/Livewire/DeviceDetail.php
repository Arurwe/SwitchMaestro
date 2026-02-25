<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Device;
use App\Models\ConfigurationBackup;
use App\Models\DevicePort;
use App\Models\Vlan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeviceDetail extends Component
{
    public Device $device;

    public $backups;
    public $ports = [];
    public $vlans;


    public function mount(Device $device)
    {
        $this->device = $device;
        $this->loadData();
    }
    

    public function loadData()
    {
        $this->device->refresh(); 
        $this->device->load('vendor', 'credential', 'deviceGroups');

        $this->backups = ConfigurationBackup::where('device_id', $this->device->id)
                            ->orderBy('created_at', 'desc')
                            ->take(5)
                            ->get();
                            

        $this->vlans = $this->device->vlans() 
            ->with([
                'portMemberships' => function ($query) {
                    $query->whereHas('port', function ($q) {
                        $q->where('device_id', $this->device->id);
                    });
                },
                'portMemberships.port'
            ])
            ->orderBy('vlan_id')
            ->get();

        $this->ports = DevicePort::where('device_id', $this->device->id)
                            ->orderBy('name')
                            ->get();
    }
    

    public function refreshComponent()
    {
        $this->loadData();
    }


    private function buildAuthPayload(): array
    {
        $credential = $this->device->credential;
        $vendor = $this->device->vendor;

        if (!$credential || !$vendor) {
            throw new \Exception("Brak poświadczeń lub vendora dla urządzenia {$this->device->id}");
        }

        $authData = [
            'username' => $credential->username,
            'password' => $credential->password,
            'secret' => $credential->secret,     
            'netmiko_driver' => $vendor->netmiko_driver,
            'ip' => $this->device->ip_address,
            'port' => $this->device->port,
        ];
        
        return [
            'initiator_user_id' => Auth::id() ?? 1,
            'auth_data' => $authData
        ];
    }

    public function runTestConnection()
    {
        Gate::authorize('commands:run:readonly');
        Log::info("Uruchomienie 'runTestConnection' dla: {$this->device->name}");
        $apiUrl = config('services.python_api.base_url', 'http://api:8000');

        try {
            $payload = $this->buildAuthPayload();
            $payload['device_db_id'] = $this->device->id;
            
            $response = Http::timeout(20)
                ->post("{$apiUrl}/api/device/test-connection", $payload);

            if ($response->successful()) {
                $this->device->status = 'online';
                $this->device->save();
                session()->flash('message', 'Połączenie pomyślne! Status zaktualizowany.');
            } else {
                $this->device->status = 'offline';
                $this->device->save();
                session()->flash('error', 'Błąd połączenia: ' . $response->json('detail.error', $response->body()));
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
             session()->flash('error', 'Błąd: Nie można połączyć się z serwisem API.');
             Log::error('[Test Connection] Connection Exception: '. $e->getMessage());
        } catch (\Exception $e) {
            session()->flash('error', 'Wystąpił błąd lokalny: '. $e->getMessage());
            Log::error('[Test Connection] Local Exception: '. $e->getMessage());
        }
    }


    public function triggerAction(string $actionSlug)
    {
        Gate::authorize('commands:run:readonly');
        Log::info("Uruchomienie 'triggerAction' ($actionSlug) dla: {$this->device->name}");
        $apiUrl = config('services.python_api.base_url', 'http://api:8000');

        try {
            $payload = $this->buildAuthPayload();
            
            $url = "{$apiUrl}/api/device/{$this->device->id}/run-action/{$actionSlug}";
            

            if ($actionSlug === 'get_all_diagnostics') {
                 $url = "{$apiUrl}/api/device/{$this->device->id}/refresh-data";
            }
            
            $response = Http::timeout(5)->post($url, $payload);

            if ($response->successful()) {
                session()->flash('message', "Zadanie '$actionSlug' zostało pomyślnie zakolejkowane.");
            } else {
                session()->flash('error', 'Błąd API: Nie udało się zakolejkować zadania. '. $response->json('detail.error', $response->body()));
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
             session()->flash('error', 'Błąd: Nie można połączyć się z serwisem API.');
             Log::error("[Trigger Action] Connection Exception: ". $e->getMessage());
        } catch (\Exception $e) {
            session()->flash('error', 'Wystąpił błąd lokalny: '. $e->getMessage());
            Log::error("[Trigger Action] Local Exception: ". $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.device-detail')
            ->layout('layouts.app', ['header' => "Szczegóły: {$this->device->name}"]);
    }
}
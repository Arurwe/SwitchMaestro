<?php

namespace App\Livewire;

use App\Models\AuditLog;
use Livewire\Component;
use App\Models\Device;
use App\Models\Credential;
use App\Models\DeviceGroup;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class DeviceForm extends Component
{
    public $vendors;
    public $credentials;
    public $deviceGroups;

    public $name = '';
    public $ip_address = '';
    public $port = 22;
    public $description = '';
    public $vendor_id = '';
    public $credential_id = '';
    public $selectedGroups = [];
    public $driver_override = '';

    public ?Device $device = null;
    public ?int $deviceIdToUpdate = null;
    public bool $isCreating = true;


    protected function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', $this->isCreating ? 'unique:devices' : Rule::unique('devices')->ignore($this->deviceIdToUpdate)],
            'ip_address' => ['required', 'ipv4'],
            'port' => 'required|integer|min:1|max:65535',
            'description' => 'nullable|string',
            'vendor_id' => 'required|integer|exists:vendors,id',
            'credential_id' => 'required|integer|exists:credentials,id',
            'selectedGroups' => 'nullable|array',
            'selectedGroups.*' => 'integer|exists:device_groups,id',
            'driver_override' => 'nullable|string|max:255',
        ];
    }


    public function mount()
    {
        $this->vendors = Vendor::orderBy('name')->get();
        $this->credentials = Credential::orderBy('name')->get();
        $this->deviceGroups = DeviceGroup::orderBy('name')->get();
        $this->driver_override = $this->device->driver_override ?? '';

        $routeDevice = Route::current()->parameter('device');

        if ($routeDevice instanceof Device) {
            $this->isCreating = false;
            $this->device = $routeDevice;
            $this->deviceIdToUpdate = $this->device->id;

            $this->fill(
                $this->device->only([
                    'name', 'ip_address', 'port', 'description',
                    'vendor_id', 'credential_id'
                ])
            );
            $this->selectedGroups = $this->device->deviceGroups()->pluck('id')->toArray();
        }
        elseif ($routeDevice) {
             Log::error("Próbowano edytować urządzenie, ale ID '$routeDevice' nie jest poprawnym modelem Device.");
             abort(404); 
        }
        else {
            $this->isCreating = true;
            $this->device = new Device();
            $this->resetFormDefaults();
        }
    }

    private function resetFormDefaults()
    {
        $this->port = 22;
    }


    public function render()
    { 
        $headerTitle = $this->isCreating ? 'Tworzenie Nowego Urządzenia' : "Edycja: {$this->name}";

        return view('livewire.device-form')
            ->layout('layouts.app', ['header' => $headerTitle]);
    }


    public function saveDevice()
    {
        Gate::authorize('devices:manage');

        $validatedData = $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'ip_address' => $this->ip_address,
                'port' => $this->port,
                'description' => $this->description,
                'vendor_id' => $this->vendor_id,
                'credential_id' => $this->credential_id,
                'driver_override' => $this->driver_override, 
            ];

            if ($this->isCreating) {
                $device = Device::create($data);
                $this->device = $device;
                session()->flash('message', 'Urządzenie pomyślnie utworzone.');
                Log::info("Utworzono nowe urządzenie: {$device->name} (ID: {$device->id})");
            } else {
                $this->device->update($data);
                $device = $this->device;
                session()->flash('message', 'Urządzenie pomyślnie zaktualizowane.');
                Log::info("Zaktualizowano urządzenie: {$device->name} (ID: {$device->id})");
            }

            $device->deviceGroups()->sync($this->selectedGroups);

            $this->fetchInitialData($device);
            return $this->redirectRoute('devices.show', $device, navigate: true);

        } catch (\Exception $e) {
            session()->flash('error', 'Wystąpił błąd podczas zapisu: '. $e->getMessage());
            Log::error('Błąd zapisu urządzenia: '. $e->getMessage());
        }
    }

    public function fetchInitialData(Device $device)
    {
        Log::info("Wywoływanie API w celu zebrania danych dla urządzenia: {$device->name} (ID: {$device->id})");
        
        $apiUrl = config('services.python_api.base_url', 'http://api:8000');
        
        try {
            $credential = $device->credential;
            $vendor = $device->vendor;       

            if (!$credential || !$vendor) {
                Log::error("Nie można pobrać poświadczeń lub vendora dla urządzenia {$device->id}");
                return;
            }

            $authData = [
                'username' => $credential->username,
                'password' => $credential->password, 
                'secret' => $credential->secret,    
                'netmiko_driver' => $device->effectiveDriver(),
                'ip' => $device->ip_address,
                'port' => $device->port,
            ];
            

            $payload = [
                'initiator_user_id' => Auth::id() ?? 1,
                'auth_data' => $authData
            ];

            Http::timeout(5)
                ->post("{$apiUrl}/api/device/{$device->id}/refresh-data", $payload);
                
            Log::info("Wysłano żądanie odświeżenia danych dla urządzenia: {$device->id}");

        } catch (\Exception $e) {
            Log::error("Nie udało się wysłać żądania odświeżenia danych dla {$device->id}: ". $e->getMessage());
        }
    }


public function testConnection()
{
    Gate::authorize('commands:run:readonly');

    $validatedData = $this->validate([
        'ip_address' => 'required|ipv4',
        'port' => 'required|integer|min:1|max:65535',
        'vendor_id' => 'required|exists:vendors,id',
        'credential_id' => 'required|exists:credentials,id',
    ]);

    try {
        $credential = Credential::findOrFail($validatedData['credential_id']);
        $vendor = Vendor::findOrFail($validatedData['vendor_id']);

        $payload = [
            'initiator_user_id' => Auth::id() ?? 1,
            'device_db_id' => $this->deviceIdToUpdate,
            'auth_data' => [
                'ip' => $validatedData['ip_address'],
                'port' => $validatedData['port'],
                'netmiko_driver' => $vendor->netmiko_driver,
                'username' => $credential->username,
                'password' => $credential->password,
                'secret' => $credential->secret,
            ]
        ];

        $apiUrl = config('services.python_api.base_url', 'http://api:8000');

        $response = Http::timeout(20)
            ->post("{$apiUrl}/api/device/test-connection", $payload);

        if ($response->successful()) {
            $prompt = $response->json('prompt');
            session()->flash('test_message', 'Sukces: '. $response->json('message'));

            if ($this->isCreating && $prompt) {
                $cleanedName = str_replace(['<', '>'], '', $prompt);
                $cleanedName = preg_replace('/[#>].*$/', '', $cleanedName);
                $this->name = trim($cleanedName);
            }

        } else {
            $errorDetail = $response->json('detail');
            $errorMessage = is_array($errorDetail) ? $errorDetail['error'] : $errorDetail;
            session()->flash('test_error', 'Błąd: '. $errorMessage);
        }

    } catch (\Illuminate\Http\Client\ConnectionException $e) {
         session()->flash('test_error', 'Błąd: Nie można połączyć się z serwisem API.');
    } catch (\Exception $e) {
        session()->flash('test_error', 'Wystąpił błąd lokalny: '. $e->getMessage());
    }
}

}
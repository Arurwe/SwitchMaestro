<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Device;
use App\Models\Action;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BulkActionRunner extends Component
{
    
    //   Pobiera tablicę ID urządzeń z paska adresu URL.

    #[Url(keep: true)]
    public array $deviceIds = [];

    // Dane załadowane z bazy
    public Collection $devices;
    public Collection $actions;

    // Dane z formularza
    public string $selectedActionId = '';
    public string $customCommands = '';


    protected function rules()
    {
        return [
            'selectedActionId' => 'required_without:customCommands|nullable|integer|exists:actions,id',
            'customCommands' => 'required_without:selectedActionId|nullable|string|min:1',
        ];
    }

    protected $messages = [
        'selectedActionId.required_without' => 'Musisz wybrać akcję lub wpisać własne komendy.',
        'customCommands.required_without' => 'Musisz wpisać własne komendy lub wybrać akcję.',
    ];


    public function mount()
    {
        if (empty($this->deviceIds)) {
            session()->flash('bulk_error', 'Nie wybrano żadnych urządzeń. Wróć do listy i zaznacz urządzenia.');
            $this->devices = collect();
            $this->actions = collect();
            return;
        }

        $this->devices = Device::with(['vendor', 'credential'])
                            ->whereIn('id', $this->deviceIds)
                            ->orderBy('name')
                            ->get();

        $this->actions = Action::
                                orderBy('name')
                                ->get();
    }


    public function runBulkAction()
    {
        Gate::authorize('commands:run:config');
        $this->validate();

        session()->forget('bulk_error');

        //  wygenerowanie Batch ID
        $batchId = (string) Str::uuid();

        $tasks = []; 
        $actionSlug = null;
        $commandsList = [];

        // Sprawdź tryb 
        $isCustomMode = !empty($this->customCommands);
        
        if ($isCustomMode) {
            $commandsList = array_filter(explode("\n", str_replace("\r", "", $this->customCommands)));
            if (empty($commandsList)) {
                 session()->flash('bulk_error', 'Pole własnych komend nie może być puste.');
                 return;
            }
        } else {
            $action = Action::find($this->selectedActionId);
            if (!$action) {
                 session()->flash('bulk_error', 'Wybrana akcja nie istnieje.');
                 return;
            }
            $actionSlug = $action->action_slug;
        }

        //  Zbuduj listę zadań
        foreach ($this->devices as $device) {
            if (!$device->credential || !$device->vendor) {
                Log::warning("Pominięto urządzenie {$device->name} (ID: {$device->id}) z powodu braku poświadczeń lub vendora.");
                continue;
            }

            $driver = $device->driver_override ?: $device->vendor->netmiko_driver;
            
            $authData = [
                'username' => $device->credential->username,
                'password' => $device->credential->password,
                'secret' => $device->credential->secret,
                'netmiko_driver' => $driver,
                'ip' => $device->ip_address,
                'port' => $device->port,
            ];

            // dodanie zadania do listy
            $tasks[] = [
                'device_id' => $device->id,
                'auth_data' => $authData,
            ];
        }

        if (empty($tasks)) {
            session()->flash('bulk_error', 'Nie można było przygotować żadnych zadań (sprawdź poświadczenia/vendory).');
            return;
        }

        // Zbuduj finalny Payload
        $payload = [
            'batch_id' => $batchId,
            'initiator_user_id' => Auth::id() ?? 1,
            'tasks' => $tasks,
        ];

        if ($isCustomMode) {
            $payload['commands'] = $commandsList;
            $urlSlug = 'devices/bulk-run-custom-commands';
        } else {
            $payload['action_slug'] = $actionSlug;
            $urlSlug = 'devices/bulk-run-action';
        }

        $apiUrl = config('services.python_api.base_url', 'http://api:8000');

        // wyslanie do api
        try {
            $response = Http::timeout(15)
                ->post("{$apiUrl}/api/{$urlSlug}", $payload);

            if (!$response->successful()) {
                $error = $this->parseApiError($response);
                session()->flash('bulk_error', $error);
                return;
            }

            $taskCount = $response->json('tasks_created_count', count($tasks));
            session()->flash('message', "Pomyślnie zakolejkowano {$taskCount} zadań. Batch ID: {$batchId}");
            
            // Przekierowanie do monitora zadan
            return $this->redirect(route('jobs.index', ['filterBatch' => $batchId]), navigate: true);

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Obsługa błędów API
     */
    private function parseApiError($response): string
    {
        $errorDetail = $response->json('detail');
        $errorMessage = 'Błąd API';
        if (is_array($errorDetail) && isset($errorDetail['error'])) {
            $errorMessage = $errorDetail['error'];
        } elseif (is_string($errorDetail)) {
            $errorMessage = $errorDetail;
        } else {
            $errorMessage = $response->body();
        }
        Log::error('[BulkAction] API Error:', ['status' => $response->status(), 'body' => $response->body()]);
        return $errorMessage;
    }

    /**
     * Obsługa wyjątków połączenia
     */
    private function handleException(\Exception $e)
    {
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            session()->flash('bulk_error', 'Błąd: Nie można połączyć się z serwisem API.');
            Log::error('[BulkAction] Connection Exception: '. $e->getMessage());
        } else {
            session()->flash('bulk_error', 'Wystąpił błąd lokalny: '. $e->getMessage());
            Log::error('[BulkAction] Local Exception: '. $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.bulk-action-runner')
            ->layout('layouts.app', ['header' => 'Uruchom Akcję Zbiorczą']);
    }
}
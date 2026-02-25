<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Device;
use App\Models\Action;
use App\Models\TaskLog;
use App\Services\AiCommandTranslator;
use App\Models\Vendor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class RunActionOnDevice extends Component
{
    public Device $device;
    public Collection $actions;
    public Collection $allVendors;

    // Stan formularza
    public string $mode = 'predefined';
    public string $selectedActionId = '';
    public string $customCommands = '';

    // sledzenie wyniku
    public ?string $monitoringJobId = null;
    public ?TaskLog $monitoredLog = null;
    public ?string $taskError = null;

    // asysten ai
    public bool $showAiModal = false;
    public string $aiSourceCommands = '';
    public ?int $aiSourceVendorId = null;
    public bool $isTranslating = false;


    protected function rules()
    {
        if ($this->mode === 'predefined') {
            return ['selectedActionId' => 'required|integer|exists:actions,id'];
        } else {
            return ['customCommands' => 'required|string|min:1'];
        }
    }

    protected $messages = [
        'selectedActionId.required' => 'Musisz wybrać akcję z listy.',
        'customCommands.required' => 'Pole komend nie może być puste.',
    ];


    public function mount(Device $device)
    {
        $this->device = $device->load('vendor', 'credential');
        $this->actions = Action::orderBy('name')->get();
        $this->allVendors = Vendor::all();
    }


    public function runAction()
    {
        Gate::authorize('commands:run');
        $this->validate();

        $this->monitoringJobId = null;
        $this->monitoredLog = null;
        $this->taskError = null;

        if ($this->mode === 'predefined') {
            $this->runPredefinedAction();
        } else {
            $this->runCustomCommands();
        }
    }


    private function runPredefinedAction()
    {
        try {
            $apiUrl = config('services.python_api.base_url', 'http://api:8000');
            $payload = $this->buildAuthPayload();
            $action = Action::find($this->selectedActionId);
            
            $url = "{$apiUrl}/api/device/{$this->device->id}/run-action/{$action->action_slug}";

            $response = Http::timeout(5)->post($url, $payload);

            if ($response->successful()) {
                $this->monitoringJobId = $response->json('celery_task_id');
                session()->flash('message', 'Zadanie zakolejkowane. Oczekiwanie na wynik...');
            } else {
                $this->taskError = $this->parseApiError($response);
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }


    private function runCustomCommands()
    {
        try {
            $apiUrl = config('services.python_api.base_url', 'http://api:8000');
            $payload = $this->buildAuthPayload();
            
            $commandsList = array_filter(explode("\n", str_replace("\r", "", $this->customCommands)));

            if (empty($commandsList)) {
                $this->addError('customCommands', 'Musisz podać co najmniej jedną komendę.');
                return;
            }

            $payload['commands'] = $commandsList;
            
            $url = "{$apiUrl}/api/device/{$this->device->id}/run-custom-commands";

            $response = Http::timeout(5)->post($url, $payload);

            if ($response->successful()) {
                $this->monitoringJobId = $response->json('celery_task_id');
                session()->flash('message', 'Zadanie zakolejkowane. Oczekiwanie na wynik...');
            } else {
                $this->taskError = $this->parseApiError($response);
            }

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }


    public function checkTaskStatus()
    {
        if (!$this->monitoringJobId) {
            return;
        }

        $log = TaskLog::where('job_id', $this->monitoringJobId)->first();

        if ($log) {
            $this->monitoredLog = $log;

            if ($log->status === 'success' || $log->status === 'failed') {
                $this->monitoringJobId = null;
                
                if($log->status === 'success') {
                    session()->flash('message', 'Zadanie zakończone pomyślnie.');
                } else {
                    session()->flash('error', 'Zadanie zakończone błędem.');
                }
            }
        }

    }

    private function buildAuthPayload(): array
    {
        $credential = $this->device->credential;
        $vendor = $this->device->vendor;

        if (!$credential || !$vendor) {
            throw new \Exception("Brak poświadczeń lub vendora dla urządzenia {$this->device->id}");
        }

        $driver = $this->device->driver_override ?: $vendor->netmiko_driver;

        $authData = [
            'username' => $credential->username,
            'password' => $credential->password,
            'secret' => $credential->secret,
            'netmiko_driver' => $driver,
            'ip' => $this->device->ip_address,
            'port' => $this->device->port,
        ];
        
        return [
            'initiator_user_id' => Auth::id() ?? 1,
            'auth_data' => $authData
        ];
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
        
        Log::error('[RunAction] API Error:', ['status' => $response->status(), 'body' => $response->body()]);
        return $errorMessage;
    }

    /**
     * Obsługa wyjątków połączenia
     */
    private function handleException(\Exception $e)
    {
        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            $this->taskError = 'Błąd: Nie można połączyć się z serwisem API.';
            Log::error('[RunAction] Connection Exception: '. $e->getMessage());
        } else {
            $this->taskError = 'Wystąpił błąd lokalny: '. $e->getMessage();
            Log::error('[RunAction] Local Exception: '. $e->getMessage());
        }
    }

    public function openAiAssistant()
    {
        $this->aiSourceCommands = '';
        $this->aiSourceVendorId = null;
        $this->showAiModal = true;
    }

    public function closeAiModal()
    {
        $this->showAiModal = false;
    }

    public function translateCommandsWithAi(AiCommandTranslator $translator)
    {
        $this->validate([
            'aiSourceVendorId' => 'required|exists:vendors,id',
            'aiSourceCommands' => 'required|string|min:3',
        ]);

        $this->isTranslating = true;

        $sourceVendorId = (int) $this->aiSourceVendorId;
        $targetVendorId = (int) $this->device->vendor_id; 
        $userId = Auth::id() ?? 1;

        $translatedCommands = $translator->translate(
            $userId,
            $sourceVendorId, 
            $targetVendorId, 
            $this->aiSourceCommands
        );

        $this->isTranslating = false;

        if (!$translatedCommands) {
            $this->addError('aiSourceCommands', 'Nie udało się przetłumaczyć komend. Spróbuj ponownie lub sprawdź logi.');
            return;
        }

        $this->customCommands = $translatedCommands;
        
        $this->mode = 'custom';

        $this->closeAiModal();
        session()->flash('ai_success', 'AI wygenerowało komendy! Sprawdź je dokładnie przed uruchomieniem na urządzeniu.');
    }

    public function render()
    {
        return view('livewire.run-action-on-device')
            ->layout('layouts.app', ['header' => "Uruchom Akcję: {$this->device->name}"]);
    }
}
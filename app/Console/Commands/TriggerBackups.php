<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Device; // Import modelu Device
use App\Models\User;   // Import modelu User (dla ID Systemu)
use App\Models\Action; // <-- 1. IMPORTUJ MODEL ACTION
use Illuminate\Support\Str; // <-- 2. IMPORTUJ STR (dla UUID)

class TriggerBackups extends Command
{
    protected $signature = 'backup:trigger';
    protected $description = 'Kolejkuje zadania backupu dla wszystkich urządzeń przez API (metoda zbiorcza)';

    public function handle(): int
    {
        $this->info('Rozpoczynanie procesu zbiorczego backupu...');
        Log::info('Komenda backup:trigger została uruchomiona.');
        
        // --- Znajdź użytkownika "System" ---
        $systemUser = User::find(1); 
        if (!$systemUser) {
            $this->error('Nie znaleziono użytkownika systemowego (ID=1)!');
            Log::error('Nie znaleziono użytkownika systemowego do przypisania backupu.');
            return Command::FAILURE;
        }
        $systemUserId = $systemUser->id;

        // --- Znajdź akcję 'get_config_backup' ---
        $backupAction = Action::where('action_slug', 'get_config_backup')->first();
        if (!$backupAction) {
            $this->error("Krytyczny błąd: Nie można znaleźć akcji 'get_config_backup' w bazie danych.");
            Log::error("Nie można znaleźć akcji 'get_config_backup' w bazie danych. Uruchom seeder.");
            return Command::FAILURE;
        }

        try {
            // 1. Pobierz wszystkie urządzenia z wymaganymi relacjami
            $devicesToBackup = Device::with('vendor', 'credential')->get();

            if ($devicesToBackup->isEmpty()) {
                $this->info('Nie znaleziono urządzeń do backupu.');
                return Command::SUCCESS;
            }

            $this->info("Znaleziono {$devicesToBackup->count()} urządzeń do przetworzenia.");

            // 2. Przygotuj listę zadań
            $tasksPayload = [];
            foreach ($devicesToBackup as $device) {
                if (!$device->vendor || !$device->credential) {
                    Log::warning("Pominięto urządzenie {$device->id} ({$device->name}) - brak typu lub poświadczeń.");
                    continue;
                }

                // Pobieranie właściwego sterownika
                $driver = $device->driver_override ?: $device->vendor->netmiko_driver;

                $tasksPayload[] = [
                    'device_id' => $device->id,
                    'auth_data' => [
                        'username' => $device->credential->username,
                        'password' => $device->credential->password,
                        'secret' => $device->credential->secret,
                        'netmiko_driver' => $driver,
                        'ip' => $device->ip_address,
                        'port' => $device->port,
                    ]
                ];
            }

            if (empty($tasksPayload)) {
                $this->warn('Nie przygotowano zadań dla żadnego urządzenia (sprawdź relacje).');
                return Command::SUCCESS;
            }

            // 3. Wygeneruj Batch ID i zbuduj główny payload
            $batchId = (string) Str::uuid();
            $payload = [
                'batch_id' => $batchId,
                'initiator_user_id' => $systemUserId,
                'action_slug' => $backupAction->action_slug,
                'tasks' => $tasksPayload,
            ];

            $apiUrl = config('services.python_api.base_url', 'http://api:8000');
            $url = "{$apiUrl}/api/devices/bulk-run-action";

            $response = Http::timeout(30)->post($url, $payload);

            // 5. Obsłuż odpowiedź
            if ($response->successful()) {
                $count = $response->json('tasks_created_count', count($tasksPayload));
                $this->info("Sukces! Zakolejkowano {$count} zadań backupu.");
                $this->info("Batch ID: {$batchId}");
                Log::info('Wywołanie API backupu (zbiorcze) zakończone sukcesem.', $response->json());
                return Command::SUCCESS;
            } else {
                $this->error('Błąd! Serwis API zwrócił status: ' . $response->status());
                $this->error('Odpowiedź: ' . $response->body());
                Log::error('Błąd podczas wywoływania API backupu (zbiorcze).', ['status' => $response->status(), 'body' => $response->body()]);
                return Command::FAILURE;
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->error('Błąd połączenia z serwisem API: ' . $e->getMessage());
            Log::critical('Nie można połączyć się z API backupu.', ['exception' => $e->getMessage()]);
            return Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('Wystąpił nieoczekiwany błąd: ' . $e->getMessage());
            Log::error('Nieoczekiwany błąd w komendzie backup:trigger.', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }
}
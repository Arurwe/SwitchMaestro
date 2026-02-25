<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\NetworkLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TopologyReconcileCommand extends Command
{

    protected $signature = 'topology:reconcile';
    protected $description = 'Uzgadnia (reconciles) nazwy hostów sąsiadów LLDP z ID urządzeń w bazie danych.';

public function handle()
    {
        $this->info('Rozpoczynanie uzgadniania topologii...');

        // 1. Pobierz wszystkie urządzenia do szybkiej mapy (Klucz = nazwa, Wartość = ID)
        $deviceMap = Device::all()->keyBy(function ($device) {
            return strtolower($device->name);
        });

        // 2. Pobierz tylko te linki, które wymagają uzgodnienia
        $linksToReconcile = NetworkLink::whereNull('neighbor_device_id')
                                ->whereNotNull('neighbor_device_hostname')
                                ->where('neighbor_device_hostname', '!=', '-')
                                ->get();

        if ($linksToReconcile->isEmpty()) {
            $this->info('Brak linków do uzgodnienia. Zakończono.');
            return 0;
        }

        $this->info("Znaleziono {$linksToReconcile->count()} linków do przetworzenia...");
        $updatedCount = 0;

        // 3. Przejdź przez każdy link i spróbuj znaleźć dopasowanie
        foreach ($linksToReconcile as $link) {
            
            // Przygotuj nazwę sąsiada
            $neighborName = strtolower($link->neighbor_device_hostname);
            $nameParts = explode('.', $neighborName);
            $shortName = $nameParts[0];

            $foundDevice = null;
            
            if ($deviceMap->has($neighborName)) {
                $foundDevice = $deviceMap->get($neighborName);
            } elseif ($deviceMap->has($shortName)) {
                $foundDevice = $deviceMap->get($shortName);
            }

            // 4. Jeśli znaleziono, zaktualizuj link
            if ($foundDevice) {
                $link->neighbor_device_id = $foundDevice->id;
                $link->save();
                $updatedCount++;
                $this->line("  [OK] Link {$link->id} ({$link->local_port_name}) uzgodniony z {$foundDevice->name} (ID: {$foundDevice->id})");
            } else {
                $this->warn("  [Brak] Nie znaleziono urządzenia dla hosta: '{$link->neighbor_device_hostname}'");
                Log::warning("TopologyReconcile: Nie znaleziono dopasowania dla hosta '{$link->neighbor_device_hostname}' (Link ID: {$link->id})");
            }
        }

        $this->info("Zakończono. Pomyślnie zaktualizowano {$updatedCount} linków.");
        return 0;
    }
}

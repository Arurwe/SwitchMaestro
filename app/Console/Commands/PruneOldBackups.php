<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConfigurationBackup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PruneOldBackups extends Command
{
    protected $signature = 'backup:prune {--days=30 : Liczba dni, po których backupy są usuwane}';
    protected $description = 'Usuwa stare backupy konfiguracji starsze niż podana liczba dni';

    public function handle()
    {
        $days = (int) $this->option('days');
        if ($days <= 0) {
            $this->error('Liczba dni musi być większa od zera.');
            return Command::INVALID;
        }

        $this->info("Usuwanie backupów starszych niż {$days} dni...");

        try {
            // Oblicz datę graniczną
            $cutoffDate = Carbon::now()->subDays($days)->startOfDay();

            // Znajdź i usuń stare backupy
            $deletedCount = ConfigurationBackup::where('created_at', '<', $cutoffDate)->delete();

            if ($deletedCount > 0) {
                $this->info("Pomyślnie usunięto {$deletedCount} starych backupów.");
                Log::info("Backup prune successful.", ['days' => $days, 'deleted_count' => $deletedCount]);
            } else {
                $this->info('Nie znaleziono starych backupów do usunięcia.');
            }

        } catch (\Exception $e) {
            $this->error('Wystąpił błąd podczas usuwania backupów: ' . $e->getMessage());
            Log::error('Backup prune command failed.', ['exception' => $e->getMessage()]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
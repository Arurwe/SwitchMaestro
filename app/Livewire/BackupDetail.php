<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ConfigurationBackup;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage; 

class BackupDetail extends Component
{
    public ConfigurationBackup $backup;

    public function mount(ConfigurationBackup $backup)
    {
        $this->backup->loadMissing('device', 'user');
    }

    public function render()
    {
        return view('livewire.backup-detail')
            ->layout('layouts.app', [
                'header' => "Szczegóły Backupu #{$this->backup->id} dla "
                           . ($this->backup->device?->name ?? 'Usuniętego Urządzenia')
            ]);
    }

}
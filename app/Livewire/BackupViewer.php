<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ConfigurationBackup;
use App\Models\Device;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Gate; 

class BackupViewer extends Component
{
    use WithPagination;

    // Filtry
    public $deviceId = null;
    public $dateFrom = null;
    public $dateTo = null;

    public ?ConfigurationBackup $selectedBackup = null;
    public bool $isDetailModalOpen = false;


    public function updating ($field)
    {
        if (in_array($field, ['deviceId','dateFrom','dateTo'])){
            $this->resetPage();
        }

    }

    public function render()
    {
        $query = ConfigurationBackup::with('device', 'user') 
            ->orderBy('created_at', 'desc');

        if($this->deviceId){
            $query->where('device_id', $this->deviceId);
        }

        if($this->dateFrom){
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if($this->dateTo){
            $query->whereDate('created_at', '<=',$this->dateTo);
        }


        return view('livewire.backup-viewer', [
            'backups' => $query ->paginate(20),
            'devices' => Device::orderBy('name')->get()
        ])
        ->layout('layouts.app', ['header' => 'Archiwum Backupów Konfiguracji']);
    }

    public function resetFilters()
    {
        $this->deviceId = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->dispatch('resetTomSelect');
        $this->resetPage();
    }



    public function deleteBackup(ConfigurationBackup $backup)
    {
        Gate::authorize('backups:delete');

        $backup->delete();
        session()->flash('message', 'Backup pomyślnie usunięty.');
    }
}
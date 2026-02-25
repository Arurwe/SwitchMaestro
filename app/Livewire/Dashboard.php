<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Device;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public $deviceCount = 0;
    public $devicesOnline = 0;
    public $devicesOffline = 0;
    public $devicesUnknown = 0;
    public $logsCount = 0;
    public $errorsLast24h = 0; 

    
    public function mount()
    {

        $statusCounts = Device::select('status', DB::raw('count(*) as total'))
                               ->groupBy('status')
                               ->pluck('total', 'status');

        $this->devicesOnline = $statusCounts->get('online', 0); 
        $this->devicesOffline = $statusCounts->get('offline', 0); 
        $this->devicesUnknown = $statusCounts->get('unknown', 0); 
        
        $this->deviceCount = $this->devicesOnline + $this->devicesOffline + $this->devicesUnknown;

    
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app', ['header' => 'Dashboard']);
    }
}
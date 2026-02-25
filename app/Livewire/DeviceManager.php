<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Device;
use App\Models\Vendor;
use App\Models\Credential;
use App\Models\DeviceGroup;
use Illuminate\Support\Facades\Gate;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class DeviceManager extends Component
{
    use WithPagination;

    // --- Filtry ---
    #[Url(keep: true)]
    public $searchName = '';
    #[Url(keep: true)]
    public $searchIp = '';
    #[Url(keep: true)]
    public $selectedGroup = '';

    public $allDeviceGroups;

    public $selectedDevices = [];
    public $selectAllOnPage = false;


    public function mount()
    {
        $this->allDeviceGroups = DeviceGroup::orderBy('name')->get();
    }


    public function resetFilters()
    {
        $this->reset(['searchName', 'searchIp', 'selectedGroup']);
        $this->resetPage();
    }


    public function updated($propertyName)
    {
        if (in_array($propertyName, ['searchName', 'searchIp', 'selectedGroup'])) {
            $this->resetPage();
        }
        if ($propertyName === 'selectedDevices') {
            $this->selectAllOnPage = false;
        }
    }
    

    public function updatedSelectAllOnPage($value)
    {
        if ($value) {

            $this->selectedDevices = $this->buildQuery()
                                         ->paginate(20)
                                         ->pluck('id')
                                         ->map(fn ($id) => (string) $id)
                                         ->toArray();
        } else {
            $this->selectedDevices = [];
        }
    }
    private function buildQuery()
    {
        return Device::with('vendor', 'credential', 'deviceGroups')
            ->when($this->searchName, function ($q) {
                $q->where('name', 'like', '%' . $this->searchName . '%');
            })
            ->when($this->searchIp, function ($q) {
                $q->where('ip_address', 'like', '%' . $this->searchIp . '%');
            })
            ->when($this->selectedGroup, function ($q) {
                $q->whereHas('deviceGroups', function ($subQ) {
                    $subQ->where('device_groups.id', $this->selectedGroup);
                });
            })
            ->orderBy('name');
    }    

    public function render()
    {
        $query = Device::with('vendor', 'credential', 'deviceGroups')
            ->when($this->searchName, function ($q) {
                $q->where('name', 'like', '%' . $this->searchName . '%');
            })
            ->when($this->searchIp, function ($q) {
                $q->where('ip_address', 'like', '%' . $this->searchIp . '%');
            })
            ->when($this->selectedGroup, function ($q) {
                $q->whereHas('deviceGroups', function ($subQ) {
                    $subQ->where('device_groups.id', $this->selectedGroup);
                });
            })
            ->orderBy('name');

        return view('livewire.device-manager', [
            'devices' => $query->paginate(20),
        ])
        ->layout('layouts.app', ['header' => 'Zarządzanie Urządzeniami']);
    }


    public function deleteDevice(Device $device)
    {
        Gate::authorize('devices:manage');
        
        $device->delete();
        session()->flash('message', 'Urządzenie pomyślnie usunięte.');
        $this->resetPage();
    }
    
}
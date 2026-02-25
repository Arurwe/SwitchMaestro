<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DeviceGroup;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use App\Models\Device;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class DeviceGroupManager extends Component
{
    public $deviceGroups;


    public $name = '';
    public $description = '';
    public $groupIdToUpdate = null;
    public bool $isFormVisible = false; 

    public $devicesInGroup = [];
    public $deviceToAdd = null;
    public Collection $availableDevices;

    protected function rules()
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', is_null($this->groupIdToUpdate) ? 'unique:device_groups' : Rule::unique('device_groups')->ignore($this->groupIdToUpdate)],
            'description' => 'nullable|string',
        ];

        if ($this->isFormVisible && $this->groupIdToUpdate) {
             $rules['deviceToAdd'] = 'nullable|integer|exists:devices,id';
        }

        return $rules;
    }
    public function mount()
    {
        $this->availableDevices = new Collection();
    }
    public function render()
    {

        if (!$this->isFormVisible) {
            $this->deviceGroups = DeviceGroup::orderBy('name')->get();
        }

        return view('livewire.device-group-manager')
            ->layout('layouts.app', ['header' => 'Zarządzanie Grupami Urządzeń']);
    }

    public function showCreateForm()
    {
        Gate::authorize('groups:manage');
        $this->resetForm();
        $this->isFormVisible = true;
    }

    public function showEditForm($id)
    {
        Gate::authorize('groups:manage');

        $group = DeviceGroup::with('devices')->findOrFail($id);
        $this->groupIdToUpdate = $group->id;
        $this->name = $group->name;
        $this->description = $group->description;
        $this->devicesInGroup = $group->devices;

        $this->loadAvailableDevices();

        $this->isFormVisible = true;
    }

    public function hideForm()
    {
        $this->isFormVisible = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->groupIdToUpdate = null;
        $this->devicesInGroup = [];
        $this->deviceToAdd = null; 
        $this->availableDevices = new Collection();
        $this->resetValidation(); 
    }


    private function loadAvailableDevices()
    {
         if (!$this->groupIdToUpdate) {
            $this->availableDevices = new Collection();
            return;
        }
        $existingDeviceIds = DeviceGroup::find($this->groupIdToUpdate)->devices()->pluck('devices.id');
        $this->availableDevices = Device::whereNotIn('id', $existingDeviceIds)
                                      ->orderBy('name')
                                      ->get(['id', 'name', 'ip_address']);
    }


    public function saveDeviceGroup()
    {
        Gate::authorize('groups:manage');

        $isCreating = is_null($this->groupIdToUpdate);
        $this->validate([
             'name' => ['required', 'string', 'max:255', $isCreating ? 'unique:device_groups' : Rule::unique('device_groups')->ignore($this->groupIdToUpdate)],
             'description' => 'nullable|string',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
        ];
        $group = DeviceGroup::updateOrCreate(['id' => $this->groupIdToUpdate], $data);
        session()->flash('message', $isCreating ? 'Grupa pomyślnie utworzona.' : 'Grupa pomyślnie zaktualizowana.');
        $this->hideForm();
    }


    public function removeDevice($deviceId)
    {
        Gate::authorize('groups:manage');
        if ($this->groupIdToUpdate) {
            $group = DeviceGroup::find($this->groupIdToUpdate);
            $device = Device::find($deviceId);
            if ($group && $device) {
                $group->devices()->detach($deviceId); 
                $this->devicesInGroup = $group->fresh()->devices;
                $this->loadAvailableDevices();

            }
        }
    }


    public function addDevice()
    {
        Gate::authorize('groups:manage');

        $this->validate(['deviceToAdd' => 'required|integer|exists:devices,id']);

        if ($this->groupIdToUpdate && $this->deviceToAdd) {
            $group = DeviceGroup::find($this->groupIdToUpdate);
            $device = Device::find($this->deviceToAdd);
            if ($group && $device) {
                if (!$group->devices()->where('device_id', $this->deviceToAdd)->exists()) {
                    $group->devices()->attach($this->deviceToAdd);
                    $this->devicesInGroup = $group->fresh()->devices;
                    $this->loadAvailableDevices();
                    $this->deviceToAdd = null;

                    $this->dispatch('resetDeviceAddSelect');
                } else {
                     $this->addError('deviceToAdd', 'To urządzenie już jest w tej grupie.');
                }
            }
        }
    }


    public function deleteDeviceGroup($id) 
    {
        Gate::authorize('groups:manage');
        $group = DeviceGroup::findOrFail($id);
        $groupName = $group->name;

        $group->delete();
        session()->flash('message', 'Grupa usunięta.');
        if ($this->groupIdToUpdate == $id) {
            $this->hideForm();
        }
    }
}
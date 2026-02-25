<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Validation\Rule;
use App\Models\AuditLog;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;

class DeviceTypeManager extends Component
{
    public $deviceTypes;

    public $name = '';
    public $netmiko_driver = '';
    
    public $typeIdToUpdate = null;
    public $isFormVisible = false;

    protected function rules()
    {
        $isCreating = is_null($this->typeIdToUpdate);

        return [
            'name' => ['required', 'string', 'max:255', $isCreating ? 'unique:vendors' : Rule::unique('vendors')->ignore($this->typeIdToUpdate)],
            'netmiko_driver' => ['required', 'string', 'max:255', $isCreating ? 'unique:vendors' : Rule::unique('vendorsS')->ignore($this->typeIdToUpdate)],
        ];
    }

    public function render()
    {
        $this->deviceTypes = Vendor::all();
        
        return view('livewire.device-type-manager')
            ->layout('layouts.app', ['header' => 'Zarządzanie Typami Urządzeń (Vendorami)']);
    }


    public function showCreateForm()
    {
        $this->resetForm();
        $this->isFormVisible = true;
    }
    
    public function showEditForm($id)
    {
        $type = Vendor::findOrFail($id);
        $this->typeIdToUpdate = $type->id;
        $this->name = $type->name;
        $this->netmiko_driver = $type->netmiko_driver;
        
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
        $this->netmiko_driver = '';
        $this->typeIdToUpdate = null;
    }


    public function saveDeviceType()
    {
        $isCreating = is_null($this->typeIdToUpdate);

        $this->validate();

        $data = [
            'name' => $this->name,
            'netmiko_driver' => $this->netmiko_driver,
        ];

        $deviceType = Vendor::updateOrCreate(['id' => $this->typeIdToUpdate], $data);

      
        
        session()->flash('message', $isCreating ? 'Typ urządzenia pomyślnie utworzony.' : 'Typ urządzenia pomyślnie zaktualizowany.');
        $this->hideForm(); 
    }
    
    public function deleteDeviceType($id)
    {
        $deviceType = Vendor::findOrFail($id);
        $typeName = $deviceType->name;
        $typeDriver = $deviceType->netmiko_driver;

        $deviceType->delete();



        session()->flash('message', 'Typ urządzenia usunięty.');
    }
}
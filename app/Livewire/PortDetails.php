<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Device;
use App\Models\DevicePort;

class PortDetails extends Component
{
    public Device $device;
    public DevicePort $port;

    public function mount(Device $device, DevicePort $port)
    {
        $this->device = $device;
        $this->port = $port;
    }

    public function render()
    {
        $details = $this->port->details;
        if (is_string($details)) {
            $details = json_decode($details, true) ?: [];
        }


        return view('livewire.port-details', [
           'details' => $details
        ])->layout('layouts.app', ['header' => 'Port Details']); 
    }
}

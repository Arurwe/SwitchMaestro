<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Vlan;
use App\Models\Device;
use App\Models\PortVlanMembership;

class VlanExplorer extends Component
{

    public $allVlans;
    public $selectedVlanId = null;
    public $selectedVlan = null;
    public $devicesWithVlan = [];
    public $portsWithVlan = [];
    protected $queryString = ['selectedVlanId'];

    public function mount()
    {
        $this->allVlans = Vlan::orderBy('vlan_id')->get();
                if (!empty($this->selectedVlanId)) {
            
            if ($this->allVlans->contains('id', $this->selectedVlanId)) {
                $this->loadVlanDetails($this->selectedVlanId);
            } else {
                $this->selectedVlanId = null;
            }
        }
    }



        public function updatedSelectedVlanId($vlanId)
    {
        $this->loadVlanDetails($vlanId);
    }


    public function loadVlanDetails($vlanId)
    {
        if (empty($vlanId)) {
            $this->resetDetails();
            return;
        }

        $this->selectedVlan = Vlan::find($vlanId);

        if (!$this->selectedVlan) {
            $this->resetDetails();
            return;
        }

        $this->devicesWithVlan = $this->selectedVlan->devices()
            ->orderBy('name')
            ->get();

        $this->portsWithVlan = $this->selectedVlan->portMemberships()
            ->with(['port.device'])
            ->select('port_vlan_membership.*')
            ->join('device_ports', 'port_vlan_membership.device_port_id', '=', 'device_ports.id')
            ->join('devices', 'device_ports.device_id', '=', 'devices.id')
            ->orderBy('devices.name')
            ->orderBy('device_ports.name')
            ->get();
    }


    public function resetDetails()
    {
        $this->selectedVlan = null;
        $this->devicesWithVlan = [];
        $this->portsWithVlan = [];
        $this->selectedVlanId = null; 
    }

    public function render()
    {
        return view('livewire.vlan-explorer')
            ->layout('layouts.app', ['header' => 'Eksplorator VLAN-ów']);
    }
}
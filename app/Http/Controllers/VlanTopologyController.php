<?php

namespace App\Http\Controllers;


use App\Models\Device;
use App\Models\NetworkLink;
use App\Models\Vlan;
use App\Models\DevicePort;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class VlanTopologyController extends Controller
{

    public function index(): View
    {
        $vlans = Vlan::orderBy('vlan_id')->get();
        return view('topology.vlan-map-index', ['vlans' => $vlans]);
    }


    public function show(Vlan $vlan): View
    {
        $memberships = $vlan->portMemberships()->with('port.device')->get();

        // dzielenie urzadzeń na grupy
        $accessDevices = $memberships->where('membership_type', 'access')->pluck('port.device')->unique('id');
        $trunkDevices = $memberships->where('membership_type', 'trunk')->pluck('port.device')->unique('id');
        $allInvolvedDevices = $accessDevices->merge($trunkDevices)->unique('id');

        $allDevices = Device::all();
        $allDevicesKeyedByName = $allDevices->keyBy(fn($d) => strtolower($d->name));
        $allDevicesKeyedById = $allDevices->keyBy('id');

        // budowanie wezlow
        $nodes = [];
        foreach ($allInvolvedDevices as $device) {
            $isAccess = $accessDevices->contains($device);
            $isTrunk = $trunkDevices->contains($device);
            $group = 'trunk_only'; 
            if ($isAccess && $isTrunk) $group = 'hybrid';
            elseif ($isAccess && !$isTrunk) $group = 'access_only';

            $nodes[] = [
                'id' => $device->id,
                'label' => $device->name,
                'title' => "IP: {$device->ip_address}\nModel: {$device->model}\nRola: {$group}",
                'group' => $group,
            ];
        }

        $physicalLinks = NetworkLink::all();
        $trunkPortsMap = $memberships->where('membership_type', 'trunk')
                                   ->keyBy('device_port_id');


        // Mapa wszystkich portów 
        $allPortsMap = DevicePort::whereIn('device_id', $allInvolvedDevices->pluck('id'))
                                ->get()
                                ->keyBy(fn($p) => $p->device_id . ':' . $this->normalizePortName($p->name));

        $edges = [];
        $addedPairs = []; 

        foreach ($physicalLinks as $link) {
            
            $neighbor_id = $link->neighbor_device_id;
            if (!$neighbor_id && $link->neighbor_device_hostname && $link->neighbor_device_hostname !== '-') {
                $neighborName = strtolower($link->neighbor_device_hostname);
                $nameParts = explode('.', $neighborName);
                $shortName = $nameParts[0];
                $neighbor = $allDevicesKeyedByName->get($neighborName) ?? $allDevicesKeyedByName->get($shortName);
                if ($neighbor) {
                    $neighbor_id = $neighbor->id;
                }
            }

            if (!$neighbor_id || !$allDevicesKeyedById->has($link->local_device_id) || !$allDevicesKeyedById->has($neighbor_id)) {
                continue; 
            }

            $localPort = $allPortsMap->get($link->local_device_id . ':' . $this->normalizePortName($link->local_port_name));
            $neighborPort = $allPortsMap->get($neighbor_id . ':' . $this->normalizePortName($link->neighbor_port_name));

            if (!$localPort || !$neighborPort) {
                continue; 
            }

            if ($trunkPortsMap->has($localPort->id) && $trunkPortsMap->has($neighborPort->id)) {
                
                $endpoint1 = $link->local_device_id . ':' . $this->normalizePortName($link->local_port_name);
                $endpoint2 = $neighbor_id . ':' . $this->normalizePortName($link->neighbor_port_name);
                $pair = [$endpoint1, $endpoint2];
                sort($pair);
                $key = $pair[0] . '|' . $pair[1];

                if (!isset($addedPairs[$key])) {
                    $edges[] = [
                        'from' => $link->local_device_id,
                        'to' => $neighbor_id,
                        'title' => "VLAN {$vlan->vlan_id} Trunk\n{$link->local_port_name} ➔ {$link->neighbor_port_name}",
                        'label' => "VLAN {$vlan->vlan_id}",
                    ];
                    $addedPairs[$key] = true;
                }
            }
        }
        
        return view('topology.vlan-map-show', [
            'vlan' => $vlan,
            'nodes_json' => $nodes,
            'edges_json' => $edges,
        ]);
    }

    private function normalizePortName(string $name): string
    {
        $replacements = [
            'Ten-GigabitEthernet' => 'XGE',
            'FortyGigE' => 'FGE',
            'GigabitEthernet' => 'GE',
            'FastEthernet' => 'Fa',
            'TwentyFiveGigE' => 'Twe',
            'HundredGigE' => 'Hu',
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $name);
    }
}
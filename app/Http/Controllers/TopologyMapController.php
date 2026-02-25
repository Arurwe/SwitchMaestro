<?php

namespace App\Http\Controllers;
    use App\Models\Device;
    use App\Models\NetworkLink;
    use Illuminate\View\View;

class TopologyMapController extends Controller
{

    public function index(): View
    {
        $devices = Device::with('vendor')->get();
        $links = NetworkLink::all();

        // budowanie węzłów
        $nodes = [];
        foreach ($devices as $device) {
            $nodes[] = [
                'id' => $device->id,
                'label' => $device->name,
                'title' => "IP: {$device->ip_address}\nModel: {$device->model}",
            ];
        }

        $deviceMapByName = $devices->keyBy(fn($d) => strtolower($d->name));
        $deviceMapById = $devices->keyBy('id');

        // bydowanie krawedzi
        $edges = [];
        $addedPairs = []; 

        foreach ($links as $link) {

            // Wyszukiwanie sąsiada
            $neighbor_id = $link->neighbor_device_id;
            if (!$neighbor_id && $link->neighbor_device_hostname && $link->neighbor_device_hostname !== '-') {
                $neighborName = strtolower($link->neighbor_device_hostname);
                $nameParts = explode('.', $neighborName);
                $shortName = $nameParts[0];
                $neighbor = $deviceMapByName->get($neighborName) ?? $deviceMapByName->get($shortName);
                if ($neighbor) {
                    $neighbor_id = $neighbor->id;
                }
            }

            if ($neighbor_id && $deviceMapById->has($link->local_device_id)) {
                
                // normalizacja nazw portów
                $localPortName = $this->normalizePortName($link->local_port_name);
                $neighborPortName = $this->normalizePortName($link->neighbor_port_name);

                //punkty kocowe
                $endpoint1 = $link->local_device_id . ':' . $localPortName;
                $endpoint2 = $neighbor_id . ':' . $neighborPortName;

                // sortowanie punktow koncowych
                $pair = [$endpoint1, $endpoint2];
                sort($pair);
                $key = $pair[0] . '|' . $pair[1];

                
                if (!isset($addedPairs[$key])) {
                    $edges[] = [
                        'from' => $link->local_device_id,
                        'to' => $neighbor_id,
                        'title' => "{$link->local_port_name} ➔ {$link->neighbor_port_name}", 
                        'label' => $link->local_port_name,
                        'font' => ['align' => 'top'],
                    ];
                    
                    $addedPairs[$key] = true;
                }
            }
        }
        
        return view('topology.map', [
            'nodes_json' => $nodes,
            'edges_json' => $edges,
        ]);
    }


    public function indexWithUnmanaged(): View
    {
        $devices = Device::with('vendor')->get();
        $links = NetworkLink::all(); 


        $nodes = [];
        foreach ($devices as $device) {
            $nodes[] = [
                'id' => $device->id,
                'label' => $device->name,
                'title' => "IP: {$device->ip_address}\nModel: {$device->model}",
            ];
        }

        $deviceMapByName = $devices->keyBy(fn ($d) => strtolower($d->name));
        $deviceMapById = $devices->keyBy('id');

        $edges = [];
        $addedPairs = [];

        foreach ($links as $link) {

            $neighbor_id = $link->neighbor_device_id;
            if (!$neighbor_id && $link->neighbor_device_hostname && $link->neighbor_device_hostname !== '-') {
                $neighborName = strtolower($link->neighbor_device_hostname);
                $nameParts = explode('.', $neighborName);
                $shortName = $nameParts[0];
                $neighbor = $deviceMapByName->get($neighborName) ?? $deviceMapByName->get($shortName);
                if ($neighbor) {
                    $neighbor_id = $neighbor->id;
                }
            }

            if (!$deviceMapById->has($link->local_device_id)) {
                continue;
            }

            if ($neighbor_id) {
                $localPortName = $this->normalizePortName($link->local_port_name);
                $neighborPortName = $this->normalizePortName($link->neighbor_port_name);
                $endpoint1 = $link->local_device_id . ':' . $localPortName;
                $endpoint2 = $neighbor_id . ':' . $neighborPortName;
                $pair = [$endpoint1, $endpoint2];
                sort($pair);
                $key = $pair[0] . '|' . $pair[1];

                if (!isset($addedPairs[$key])) {
                    $edges[] = [
                        'from' => $link->local_device_id,
                        'to' => $neighbor_id,
                        'title' => "{$link->local_port_name} ➔ {$link->neighbor_port_name}",
                        'label' => $link->local_port_name,
                        'font' => ['align' => 'top'],
                    ];
                    $addedPairs[$key] = true;
                }
            }
            else {
 
                $unknownNodeId = "unknown_{$link->id}";
                // $originalHostname = $link->neighbor_device_hostname;
                // $displayName = $customNamesMap[$originalHostname] ?? ($originalHostname ?? 'Nieznane Urządzenie');
                $nodes[] = [
                    'id' => $unknownNodeId,
                    // 'label' => $displayName,
                    'label' => $link->neighbor_device_hostname ?? 'Nieznane Urządzenie',
                    'title' => "Niezarządzane urządzenie podłączone do:\n" .
                               $deviceMapById->get($link->local_device_id)->name . "\n" .
                               "Port: {$link->local_port_name}",
                    'shape' => 'box',
                    'color' => '#f9a8a8',
                    'font' => ['size' => 10, 'color' => '#333'],
                    'group' => 'unmanaged',
                ];

                $edges[] = [
                    'id' => "edge_{$link->id}", 
                    'from' => $link->local_device_id,
                    'to' => $unknownNodeId,
                    'title' => "{$link->local_port_name} ➔ (Nieznany)",
                    'label' => $link->local_port_name,
                    'font' => ['align' => 'top'],
                    'dashes' => true,
                    'color' => ['color' => '#c0c0c0', 'highlight' => '#c0c0c0'],
                ];
            }
        }
        
        return view('topology.map', [
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
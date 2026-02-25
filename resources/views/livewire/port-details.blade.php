<div class="bg-white shadow-md rounded-lg p-6">

<div class="mb-4">
    <a href="{{ route('devices.show', $device->id) }}" wire:navigate
       class="text-sm text-blue-600 hover:underline">
        &larr; Wróć do urządzenia
    </a>
</div>
    <h3 class="text-lg font-semibold mb-4">Szczegóły portu: {{ $port->name }}</h3>

    <div class="space-y-2 text-sm">

        <p><strong>Status:</strong> {{ $port->status ?? '-' }}</p>
        <p><strong>Protocol:</strong> {{ $port->protocol_status ?? '-' }}</p>
        <p><strong>Opis:</strong> {{ $port->description ?? '-' }}</p>
        <p><strong>Speed:</strong> {{ $port->speed ?? '-' }}</p>
        <p><strong>Duplex:</strong> {{ $port->duplex ?? '-' }}</p>


<div>
    @php
        if ($port->vlans && method_exists($port->vlans, 'partition')) {
            

            [$access_vlans, $trunk_vlans] = $port->vlans->partition(function ($vlan) {
                return $vlan->pivot->membership_type === 'access';
            });

            $access_string = $access_vlans->pluck('vlan_id')->sort()->implode(', ');
            $trunk_string = $trunk_vlans->pluck('vlan_id')->sort()->implode(', ');
            
        } else {
            $access_string = null;
            $trunk_string = null;
        }
    @endphp

    @if ($access_string)
        <p><strong>VLAN (Untagged):</strong> {{ $access_string }}</p>
    @endif
    
    @if ($trunk_string)
        <p><strong>VLAN (Tagged):</strong> {{ $trunk_string }}</p>
    @endif

    @if (!$access_string && !$trunk_string)
        <p><strong>VLAN:</strong> -</p>
    @endif
    
</div>

<div>
    @php
        $access_vlans_sorted = collect();
        $trunk_vlans_sorted = collect();

        if ($port->vlans && method_exists($port->vlans, 'partition')) {
            
            [$access_vlans, $trunk_vlans] = $port->vlans->partition(function ($vlan) {
                return $vlan->pivot->membership_type === 'access';
            });

            $access_vlans_sorted = $access_vlans->sortBy('vlan_id');
            $trunk_vlans_sorted = $trunk_vlans->sortBy('vlan_id');
        }
    @endphp

    
    @if ($access_vlans_sorted->isNotEmpty())
        <p>
            <strong>VLAN (Untagged):</strong>
            @foreach ($access_vlans_sorted as $vlan)

                <a href="{{ route('vlans.explorer', ['selectedVlanId' => $vlan->id]) }}"
                   class="text-blue-600 hover:underline">
                    {{ $vlan->vlan_id }}
                </a>
                @if (!$loop->last), @endif
            @endforeach
        </p>
    @endif
    
    @if ($trunk_vlans_sorted->isNotEmpty())
        <p>
            <strong>VLAN (Tagged):</strong>
            @foreach ($trunk_vlans_sorted as $vlan)
                <a href="{{ route('vlans.explorer', ['selectedVlanId' => $vlan->id]) }}"
                   class="text-blue-600 hover:underline">
                    {{ $vlan->vlan_id }}
                </a>
                @if (!$loop->last), @endif
            @endforeach
        </p>
    @endif

    @if ($access_vlans_sorted->isEmpty() && $trunk_vlans_sorted->isEmpty())
        <p><strong>VLAN:</strong> -</p>
    @endif
    
</div>

        @if(!empty($details))
            <h4 class="mt-4 font-semibold">Dodatkowe Szczegóły</h4>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($details as $key => $value)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $key }}</td>
                            <td class="px-4 py-2">
                                @if(is_array($value))
                                    {{ json_encode($value) }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-500 mt-2">Brak dodatkowych informacji.</p>
        @endif
    </div>

    <div class="mt-4">
        <a href="{{ route('devices.show', $device) }}"
           class="px-3 py-1 bg-gray-100 text-gray-800 rounded hover:bg-gray-200">
           Powrót do urządzenia
        </a>
    </div>
</div>

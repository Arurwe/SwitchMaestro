<div class="space-y-6">

    <div class="bg-white shadow-md rounded-lg p-6">
        <div class="max-w-md">
            <label for="vlan_select" class="block text-sm font-medium text-gray-700">
                Wybierz VLAN do analizy
            </label>
            <div class="flex items-center space-x-2 mt-1">
                <select id="vlan_select" 
                        wire:model.live="selectedVlanId" 
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    
                    <option value="">-- Wybierz VLAN --</option>
                    @foreach($allVlans as $vlan)
                        <option value="{{ $vlan->id }}">
                            {{ $vlan->vlan_id }} 
                            @if($vlan->name)
                                ({{ $vlan->name }})
                            @endif
                        </option>
                    @endforeach
                </select>

                <div wire:loading wire:target="updatedSelectedVlanId" class="text-gray-500">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                @if ($selectedVlan)
                <a href="{{ route('topology.vlan.show',$selectedVlan->id) }}">Pokaż mape</a>
                @endif
            </div>
        </div>
    </div>

    @if ($selectedVlan)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold border-b pb-2 mb-4">
                Urządzenia definiujące VLAN {{ $selectedVlan->vlan_id }}
            </h3>
            <ul class="list-disc pl-5 space-y-1 text-sm">
                @forelse ($devicesWithVlan as $device)
                    <li>
                        <a href="{{ route('devices.show', $device) }}" wire:navigate class="text-blue-600 hover:underline">
                            {{ $device->name }}
                        </a>
                        <span class="text-gray-500">({{ $device->ip_address }})</span>
                    </li>
                @empty
                    <li class="text-gray-500 list-none">
                        Brak urządzeń, na których ten VLAN jest zdefiniowany.
                    </li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold border-b pb-2 mb-4">
                Porty używające VLAN {{ $selectedVlan->vlan_id }} (Globalnie)
            </h3>
            
            <div class="overflow-x-auto max-h-96">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left">Urządzenie</th>
                            <th class="px-4 py-2 text-left">Port</th>
                            <th class="px-4 py-2 text-left">Tryb</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($portsWithVlan as $membership)
                            <tr>
                                <td class="px-4 py-2">
                                    <a href="{{ route('devices.show', $membership->port->device) }}" wire:navigate class="text-blue-600 hover:underline">
                                        {{ $membership->port->device->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 font-mono">
                                    {{ $membership->port->name }}
                                </td>
                                <td class="px-4 py-2">
                                    @if ($membership->membership_type == 'access')
                                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs">
                                            Access (Untagged)
                                        </span>
                                    @elseif ($membership->membership_type == 'trunk')
                                        <span class="px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 text-xs">
                                            Trunk (Tagged)
                                        </span>
                                    @else
                                        {{ $membership->membership_type }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-center text-gray-500">
                                    Żaden port nie używa tego VLAN-u.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
    @endif

</div>
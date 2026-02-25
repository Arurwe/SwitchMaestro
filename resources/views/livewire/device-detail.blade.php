<div wire:poll.5s="refreshComponent"> 
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="mb-4">
        <a href="{{ route('devices.index') }}" wire:navigate 
           class="text-sm text-blue-600 hover:underline">
            &larr; Wróć do listy urządzeń
        </a>
    </div>


    <div class="flex flex-wrap gap-2 mb-6">
        <x-secondary-button 
            wire:click="runTestConnection" 
            wire:loading.attr="disabled" 
            wire:loading.class="opacity-50" 
            wire:target="runTestConnection"
            title="Szybki test połączenia SSH/Telnet">
            <svg wire:loading wire:target="runTestConnection" class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Odśwież Status
        </x-secondary-button>
        <div class="relative inline-block text-left">
            <x-secondary-button @click="open = !open">Więcej…</x-secondary-button>
            <div x-show="open" @click.away="open = false" class="absolute mt-2 w-48 bg-white shadow-md rounded">
                <button wire:click="triggerAction('get_all_diagnostics')" class="w-full px-4 py-2 text-left">Odśwież Wszystko</button>
                <button wire:click="triggerAction('get_interfaces')" class="w-full px-4 py-2 text-left">Odśwież Porty</button>
                <button wire:click="triggerAction('get_interfaces_full')" class="w-full px-4 py-2 text-left">Odśwież Porty (szczegóły)</button>
                <button wire:click="triggerAction('get_vlans')" class="w-full px-4 py-2 text-left">Odśwież VLANy</button>
                <button wire:click="triggerAction('get_config_backup')" class="w-full px-4 py-2 text-left">Pobierz Backup</button>
            </div>
        </div>
        <a href="{{ route('devices.run-action', $device) }}" wire:navigate
           class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
            Uruchom Akcję
        </a>
        
            <a href="{{ route('devices.console', $device) }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                Consola ssh
            </a>
        @can('devices:manage')
            <a href="{{ route('devices.edit', $device) }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                Edytuj
            </a>
        @endcan

        <div  class="text-sm text-gray-500 flex items-center ml-auto">
            <svg class="animate-spin h-4 w-4 text-gray-400 mr-2" ...>...</svg>
            Synchronizacja...
        </div>
    </div>


    <div class="grid grid-cols-1 gap-6">
        
        <div class="lg:col-span-1 bg-white shadow-md rounded-lg p-6 h-fit">
            <h3 class="text-lg font-semibold border-b pb-2 mb-4">Informacje Ogólne</h3>
            <div class="space-y-3 text-sm">
                <p><strong>Nazwa:</strong> {{ $device->name }}</p>
                <p><strong>Adres IP:Port:</strong> <code class="font-mono">{{ $device->ip_address }}:{{ $device->port }}</code></p>
                <p><strong>Status:</strong>
                    <span @class([
                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                        'bg-green-100 text-green-800' => $device->status == 'online',
                        'bg-red-100 text-red-800' => $device->status == 'offline',
                        'bg-gray-100 text-gray-800' => $device->status == 'unknown',
                    ])>
                        {{ ucfirst($device->status) }}
                    </span>
                </p>
                <p><strong>Opis:</strong> {{ $device->description ?: '-' }}</p>
                <p><strong>Typ (Vendor):</strong> {{ $device->vendor?->name ?? 'Brak' }} (<code class="font-mono text-xs">{{ $device->vendor?->netmiko_driver }}</code>)</p>
                <p><strong>Poświadczenia:</strong> {{ $device->credential?->name ?? 'Brak' }}</p>
                <p><strong>Grupy:</strong>
                    @forelse($device->deviceGroups as $group)
                        <span class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                            {{ $group->name }}
                        </span>
                    @empty
                        <span class="text-xs text-gray-400">Brak</span>
                    @endforelse
                </p>
                <p><strong>Uptime:</strong> {{ $device->uptime }} </p>
                <p><strong>Model:</strong> {{ $device->model }} </p>
                <p><strong>Software Version:</strong> {{ $device->software_version }} </p>
                <p><strong>Serial Number:</strong> {{ $device->serial_number }} </p>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div x-data="{ activeTab: 'ports' }" class="w-full">
                
                <div class="mb-4 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                        <li class="mr-2">
                            <button @click="activeTab = 'ports'"
                                :class="{ 'border-blue-600 text-blue-600': activeTab === 'ports', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'ports' }"
                                class="inline-block p-4 border-b-2 rounded-t-lg focus:outline-none">
                                Porty
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeTab = 'vlans'"
                                :class="{ 'border-blue-600 text-blue-600': activeTab === 'vlans', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'vlans' }"
                                class="inline-block p-4 border-b-2 rounded-t-lg focus:outline-none">
                                VLANy
                            </button>
                        </li>
                        <li class="mr-2">
                            <button @click="activeTab = 'backups'"
                                :class="{ 'border-blue-600 text-blue-600': activeTab === 'backups', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'backups' }"
                                class="inline-block p-4 border-b-2 rounded-t-lg focus:outline-none">
                                Ostatnie Backupy
                            </button>
                        </li>
                    </ul>
                </div>

                <div>
                    <!-- Zakładka Porty -->
                    <div x-show="activeTab === 'ports'" class="bg-white shadow-md rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-semibold mb-4">Porty</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Port</th>
                                        <th class="px-4 py-2 text-left">Status</th>
                                        <th class="px-4 py-2 text-left">Protocol</th>
                                        <th class="px-4 py-2 text-left">Opis</th>
                                        <th class="px-4 py-2 text-left">Speed</th>
                                        <th class="px-4 py-2 text-left">Duplex</th>
                                        <th class="px-4 py-2 text-left">VLAN</th>
                                        <th class="px-4 py-2 text-left">Details</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">

                                    @forelse ($ports as $port)
                                        <tr>
                                            <td class="px-4 py-2">{{ $port->name }}</td>
                                            <td class="px-4 py-2">
                                                @php
                                                    $s = strtolower($port->status);
                                                @endphp

                                                <span class="
                                                    px-2 py-1 rounded text-xs font-semibold
                                                    @if ($s === 'up')
                                                        bg-green-100 text-green-800
                                                    @elseif (in_array($s, ['down', 'administratively down']))
                                                        bg-red-100 text-red-800
                                                    @else
                                                        bg-gray-100 text-gray-800
                                                    @endif
                                                ">
                                                    {{ $port->status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2">{{ $port->protocol_status }}</td>
                                            <td class="px-4 py-2">{{ $port->description }}</td>
                                            <td class="px-4 py-2">{{ $port->speed }}</td>
                                            <td class="px-4 py-2">{{ $port->duplex }}</td>
                                            <td class="px-4 py-2">
                                                @if (is_array($port->vlan))
                                                    {{ json_encode($port->vlan) }}
                                                @else
                                                    {{ $port->vlan }}
                                                @endif
                                            </td>
                                           
                                            <td class="px-4 py-2 text-center">
                                                @if ($port)
                                                    <a href="{{ route('ports.show', ['device' => $device->id, 'port' => $port->id]) }}"
                                                    class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded hover:bg-blue-200">
                                                        Szczegóły
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>


                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-2 text-center text-gray-500">
                                                Brak danych portów. Kliknij "Odśwież Dane", aby je pobrać.
                                            </td>
                                        </tr>
                                    @endforelse

                                </tbody>
                            </table>
                        </div>
                    </div>

                   


                    <!-- Zakładka VLANy -->
<div x-show="activeTab === 'vlans'" class="bg-white shadow-md rounded-lg p-6">
    <h3 class="text-lg font-semibold mb-4">Lista VLANów (zdefiniowane na urządzeniu)</h3>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">VLAN ID</th>
                    <th class="px-4 py-2 text-left">Nazwa (Globalna)</th>
                    
                    <th class="px-4 py-2 text-left">Porty Untagged (Access)</th>
                    <th class="px-4 py-2 text-left">Porty Tagged (Trunk)</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">

                @forelse ($vlans as $vlan)
                    @php
                        $untaggedPorts = $vlan->portMemberships
                            ->where('membership_type', 'access')
                            ->pluck('port.name');
                            
                        $taggedPorts = $vlan->portMemberships
                            ->where('membership_type', 'trunk')
                            ->pluck('port.name');
                    @endphp
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $vlan->vlan_id }}</td>
                        <td class="px-4 py-2">{{ $vlan->name ?? '-' }}</td>
                        <td class="px-4 py-2">
                            {{ $untaggedPorts->isNotEmpty() ? $untaggedPorts->implode(', ') : '-' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $taggedPorts->isNotEmpty() ? $taggedPorts->implode(', ') : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-2 text-center text-gray-500">
                            Brak danych VLAN. Uruchom akcję "Odśwież VLANy".
                        </td>
                    </tr>
                @endforelse
            
            </tbody>
        </table>
    </div>
</div>

                    <!-- Zakładka Backupy -->
                    <div x-show="activeTab === 'backups'"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="bg-white shadow-md rounded-lg p-6">
                        
                         <div class="flex justify-between items-center border-b pb-2 mb-4">
                             <h3 class="text-lg font-semibold">Ostatnie Backupy</h3>
                             <a href="{{ route('backups.index') }}?device_id={{ $device->id }}" wire:navigate class="text-sm text-blue-600 hover:underline">Pokaż wszystkie</a>
                         </div>
                         
                         <div class="overflow-x-auto">
                             <table class="min-w-full divide-y divide-gray-200">
                                 <thead class="bg-gray-50">
                                     <tr>
                                         <th class="px-4 py-2 text-left text-xs ...">Data</th>
                                         <th class="px-4 py-2 text-left text-xs ...">Inicjator</th>
                                         <th class="px-4 py-2 text-left text-xs ...">Rozmiar</th>
                                         <th class="relative px-4 py-2"><span class="sr-only">Akcje</span></th>
                                     </tr>
                                 </thead>
                                 <tbody class="bg-white divide-y divide-gray-200">
                                     @forelse ($backups as $backup)
                                         <tr>
                                             <td class="px-4 py-2 text-sm">{{ $backup->created_at->format('Y-m-d H:i') }}</td>
                                             <td class="px-4 py-2 text-sm">{{ $backup->user?->full_name ?? 'System' }}</td>
                                             <td class="px-4 py-2 text-sm">{{ number_format(strlen($backup->configuration) / 1024, 1) }} KB</td>
                                             <td class="px-4 py-2 text-right">
                                                 <a href="{{ route('backups.show', $backup) }}" wire:navigate class="text-blue-600 hover:underline text-sm">Pokaż</a>
                                             </td>
                                         </tr>
                                     @empty
                                         <tr>
                                             <td colspan="4" class="px-4 py-2 text-center text-gray-500 text-sm">Brak backupów dla tego urządzenia.</td>
                                         </tr>
                                     @endforelse
                                 </tbody>
                             </table>
                         </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
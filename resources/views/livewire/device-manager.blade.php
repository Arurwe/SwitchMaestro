<div>
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif
    
    <div classs="bg-white shadow-md rounded-lg p-4 mb-6">
        <h4 classs="text-lg font-semibold mb-4">Filtruj Urządzenia</h4>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <x-input-label for="searchName" :value="__('Nazwa')" />
                <x-text-input wire:model.live.debounce.300ms="searchName" id="searchName" class="block mt-1 w-full" type="text" placeholder="Filtruj po nazwie..." />
            </div>
            <div>
                <x-input-label for="searchIp" :value="__('Adres IP')" />
                <x-text-input wire:model.live.debounce.300ms="searchIp" id="searchIp" class="block mt-1 w-full" type="text" placeholder="Filtruj po IP..." />
            </div>
            <div>
                <x-input-label for="selectedGroup" :value="__('Grupa')" />
                <select wire:model.live="selectedGroup" id="selectedGroup" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">Wszystkie Grupy</option>
                    @foreach($allDeviceGroups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <x-secondary-button wire:click="resetFilters" class="w-full justify-center">
                    Wyczyść Filtry
                </x-secondary-button>
            </div>
        </div>
    </div>

@if ($selectedDevices)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 flex items-center justify-between">
        <div>
            <span class="font-semibold text-blue-800">
                Zaznaczono: {{ count($selectedDevices) }} urządzeń
            </span>
            <span class="text-sm text-blue-600 ml-2">
                (Aby zaznaczyć urządzenia z innych stron, zmień filtry i zaznacz je.)
            </span>
        </div>
        <div>

            <a href="{{ route('devices.bulk-action', ['deviceIds' => $selectedDevices]) }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Wykonaj akcję zbiorczą
            </a>
        </div>
    </div>
    @endif

    @can('devices:manage')
        <div class="flex justify-end my-4">
            <a href="{{ route('devices.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 ...">
                Dodaj Nowe Urządzenie
            </a>
        </div>
    @endcan

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="relative px-6 py-3">
                        <input type="checkbox" wire:model.live="selectAllOnPage" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nazwa</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adres IP</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Typ</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grupy</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Akcje</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($devices as $device)
                    <tr>
                        <td class="px-6 py-4">
                            <input type="checkbox" wire:model.live="selectedDevices" value="{{ $device->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span @class([
                                'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                'bg-green-100 text-green-800' => $device->status == 'online',
                                'bg-red-100 text-red-800' => $device->status == 'offline',
                                'bg-gray-100 text-gray-800' => $device->status == 'unknown',
                                'bg-yellow-100 text-yellow-800' => $device->status == 'testing',
                            ])>
                                {{ ucfirst($device->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $device->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $device->ip_address }}:{{$device->port}}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $device->vendor?->name ?? 'Brak' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @forelse($device->deviceGroups as $group)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    {{ $group->name }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400">Brak</span>
                            @endforelse
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="{{ route('devices.show', $device) }}" wire:navigate
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 ...">
                                Pokaż
                            </a>
                            @can('devices:manage')
                                <x-danger-button 
                                    wire:click="deleteDevice({{ $device->id }})" 
                                    wire:confirm="Czy na pewno chcesz usunąć to urządzenie?">
                                    Usuń
                                </x-danger-button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            Nie znaleziono urządzeń pasujących do filtrów.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $devices->links() }}
    </div>

</div>
<div>
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

    @if ($isFormVisible)
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                {{ $groupIdToUpdate ? 'Edytuj Grupę' : 'Stwórz Nową Grupę' }}
            </h2>

            <form wire:submit="saveDeviceGroup">
                <div>
                    <x-input-label for="name" :value="__('Nazwa Grupy (np. Core, Piętro 1)')" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div class="mt-4">
                    <x-input-label for="description" :value="__('Opis (opcjonalnie)')" />
                    <textarea wire:model="description" id="description" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
                <div class="flex justify-end mt-6">
                    <x-secondary-button type="button" wire:click="hideForm">
                        Anuluj
                    </x-secondary-button>
                    <x-primary-button class="ms-3" type="submit">
                        Zapisz
                    </x-primary-button>
                </div>
            </form>

    <div class="mt-6 pt-6 border-t">
        <h3 class="text-md font-semibold text-gray-700 mb-3">Dodaj urządzenie do grupy</h3>
        <div class="flex items-start space-x-2">

            <div class="flex-grow"
                 wire:ignore
                 x-data="{ tomSelectInstance: null }"
                 x-init="
                     tomSelectInstance = new TomSelect($refs.selectElement, {
                         options: {{ $availableDevices->map(fn($d) => ['id' => $d->id, 'text' => $d->name . ' (' . $d->ip_address . ')'])->values() }},
                         valueField: 'id',
                         labelField: 'text',
                         searchField: ['text'],
                         create: false,
                         placeholder: 'Wyszukaj urządzenie do dodania...',
                         allowEmptyOption: true,
                         onChange: (value) => {
                             @this.set('deviceToAdd', value);
                         }
                     });
                     // Nasłuchuj na reset z PHP
                     $wire.on('resetDeviceAddSelect', () => {
                         if (tomSelectInstance) {
                             tomSelectInstance.clear();
                         }
                     });
                "
            >
                <label for="device-add-select" class="sr-only">Wybierz urządzenie</label>
                <select id="device-add-select" x-ref="selectElement" wire:key="device-add-select-{{ $groupIdToUpdate }}"
                        class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                </select>
                <x-input-error :messages="$errors->get('deviceToAdd')" class="mt-2" />
            </div>


            <div>
                <x-primary-button wire:click="addDevice" type="button" wire:loading.attr="disabled" wire:target="addDevice">
                    Dodaj
                </x-primary-button>
            </div>
        </div>
    </div>
            @if ($groupIdToUpdate) 
                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-md font-semibold text-gray-700 mb-3">Urządzenia w tej grupie ({{ $devicesInGroup->count() }})</h3> 

                    @if ($devicesInGroup->isNotEmpty()) 
                        <ul class="list-none space-y-2 text-sm text-gray-600 max-h-48 overflow-y-auto border rounded p-3">
                            @foreach ($devicesInGroup as $device)
                                <li class="flex justify-between items-center group"> 
                                    <span>
                                        <a href="{{ route('devices.show', $device) }}" wire:navigate class="text-blue-600 hover:underline">
                                            {{ $device->name }}
                                        </a>
                                        <span class="text-gray-500 text-xs">({{ $device->ip_address }})</span>
                                    </span>
                                    <button wire:click="removeDevice({{ $device->id }})"
                                            wire:confirm="Czy na pewno chcesz usunąć '{{ $device->name }}' z grupy '{{ $name }}'?"
                                            type="button"
                                            class="ml-2 text-red-500 hover:text-red-700 text-xs font-semibold opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                            <i class="fa-solid fa-trash"></i>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500 italic">Brak urządzeń przypisanych do tej grupy.</p>
                    @endif
                </div>


            @endif

        </div> 

    @else
        <div>
           @can('groups:manage')
            <div class="flex justify-end mb-4">
                <x-primary-button wire:click="showCreateForm">
                    Dodaj Nową Grupę
                </x-primary-button>
            </div>
           @endcan
           <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nazwa Grupy</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opis</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Akcje</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($this->deviceGroups ?? [] as $group)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $group->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $group->description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    @can('groups:manage')
                                    <x-secondary-button wire:click="showEditForm({{ $group->id }})">Edytuj</x-secondary-button>
                                    <x-danger-button
                                        wire:click="deleteDeviceGroup({{ $group->id }})"
                                        wire:confirm="Czy na pewno chcesz usunąć grupę '{{ $group->name }}'?">
                                        Usuń
                                    </x-danger-button>
                                    @else
                                    <span class="text-xs text-gray-400 italic">Tylko odczyt</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                    Nie zdefiniowano jeszcze żadnych grup.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
           </div>
        </div>
    @endif

</div> 

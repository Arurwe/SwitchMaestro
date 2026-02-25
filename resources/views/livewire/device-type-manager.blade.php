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
                {{ $typeIdToUpdate ? 'Edytuj Typ Urządzenia' : 'Stwórz Nowy Typ Urządzenia' }}
            </h2>
            
            <form wire:submit="saveDeviceType">
                <div>
                    <x-input-label for="name" :value="__('Przyjazna Nazwa (np. Cisco IOS)')" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                
                <div class="mt-4">
                    <x-input-label for="netmiko_driver" :value="__('Sterownik Netmiko (np. cisco_ios)')" />
                    <x-text-input wire:model="netmiko_driver" id="netmiko_driver" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('netmiko_driver')" class="mt-2" />
                    <small class="text-gray-500">Musi być to dokładna nazwa sterownika z biblioteki Netmiko.</small>
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
        </div>
        
    @else

        <div>
            <div class="flex justify-end mb-4">
                <x-primary-button wire:click="showCreateForm">
                    Dodaj Nowy Typ
                </x-primary-button>
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Przyjazna Nazwa</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sterownik Netmiko</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Akcje</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($deviceTypes as $type)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $type->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $type->netmiko_driver }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <x-secondary-button wire:click="showEditForm({{ $type->id }})">Edytuj</x-secondary-button>
                                    <x-danger-button 
                                        wire:click="deleteDeviceType({{ $type->id }})" 
                                        wire:confirm="Czy na pewno chcesz usunąć ten typ?">
                                        Usuń
                                    </x-danger-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
    @endif
    
</div>
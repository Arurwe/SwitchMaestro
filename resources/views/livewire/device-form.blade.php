<div>
    @if (session()->has('test_message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative my-4" role="alert">
            <strong class="font-bold">Sukces!</strong>
            <span class="block sm:inline">{{ session('test_message') }}</span>
        </div>
    @endif
    @if (session()->has('test_error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative my-4" role="alert">
            <strong class="font-bold">Błąd!</strong>
            <span class="block sm:inline">{{ session('test_error') }}</span>
        </div>
    @endif
    
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
            {{ $isCreating ? 'Dodaj Nowe Urządzenie' : "Edytuj Urządzenie: {$device->name}" }}
        </h2>
        
        <form wire:submit="saveDevice">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div>
                        <x-input-label for="name" :value="__('Nazwa Urządzenia')" />
                        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    
                    <div class="mt-4">
                        <x-input-label for="ip_address" :value="__('Adres IP')" />
                        <x-text-input wire:model="ip_address" id="ip_address" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('ip_address')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="port" :value="__('Port SSH')" />
                        <x-text-input wire:model="port" id="port" class="block mt-1 w-full" type="number" required />
                        <x-input-error :messages="$errors->get('port')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="description" :value="__('Opis (opcjonalnie)')" />
                        <textarea wire:model="description" id="description" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>
                
                <div>
                    <div>
                        <x-input-label for="vendor_id" :value="__('Typ Urządzenia (Vendor)')" />
                        <select wire:model="vendor_id" id="vendor_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">Wybierz typ...</option>
                            @foreach($vendors as $type)
                                <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->netmiko_driver }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                    </div>
                    <div class="mt-4">
                        <label for="driver_override" class="block text-sm font-medium">Driver override (opcjonalnie)</label>
                        
                        <select id="driver_override"
                                wire:model="driver_override"
                                class="mt-1 block w-full rounded border-gray-300">
                            
                            <option value="">Wybierz (BRAK)</option>
                            
                            <option value="hp_comware_buggy">hp_comware_buggy</option>                   
                        </select>
                        
                        <p class="text-xs text-gray-500">Wybierz sterownik, jeśli automatyczne wykrywanie zawodzi.</p>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="credential_id" :value="__('Poświadczenia')" />
                        <select wire:model="credential_id" id="credential_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            <option value="">Wybierz poświadczenia...</option>
                            @foreach($credentials as $credential)
                                <option value="{{ $credential->id }}">{{ $credential->name }} (user: {{ $credential->username }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('credential_id')" class="mt-2" />
                    </div>
                    
                    <div class="mt-4">
                        <x-input-label for="selectedGroups" :value="__('Grupy (trzymaj Ctrl/Cmd aby zaznaczyć wiele)')" />
                        <select wire:model="selectedGroups" id="selectedGroups" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" multiple size="8">
                            @foreach($deviceGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('selectedGroups')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end mt-6 border-t pt-6 space-x-3">
                
                <x-primary-button type="button" wire:click="testConnection" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700">
                    <svg wire:loading wire:target="testConnection" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" ...> ... </svg>
                    <span wire:loading.remove wire:target="testConnection">Testuj Połączenie</span>
                    <span wire:loading wire:target="testConnection">Testowanie...</span>
                </x-primary-button>
                
                <a href="{{ $isCreating ? route('devices.index') : route('devices.show', $device) }}" wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 ...">
                    Anuluj
                </a>

                <x-primary-button type="submit">
                    {{ $isCreating ? 'Zapisz Urządzenie' : 'Zapisz Zmiany' }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
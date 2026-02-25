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
                {{ $credentialIdToUpdate ? 'Edytuj Poświadczenia' : 'Stwórz Nowe Poświadczenia' }}
            </h2>
            
            <form wire:submit="saveCredential">
                <div>
                    <x-input-label for="name" :value="__('Nazwa')" />
                    <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                
                <div class="mt-4">
                    <x-input-label for="username" :value="__('Login')" />
                    <x-text-input wire:model="username" id="username" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password" :value="__('Hasło dostępowe')" />
                    <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" />
                    @if ($credentialIdToUpdate)
                        <small class="text-gray-500">Wypełnij tylko jeśli chcesz zmienić hasło.</small>
                    @endif
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                
                <div class="mt-4">
                    <x-input-label for="secret" :value="__('Hasło \'Enable\' (secret) (opcjonalne)')" />
                    <x-text-input wire:model="secret" id="secret" class="block mt-1 w-full" type="password" />
                     @if ($credentialIdToUpdate)
                        <small class="text-gray-500">Wypełnij tylko jeśli chcesz zmienić secret.</small>
                    @endif
                    <x-input-error :messages="$errors->get('secret')" class="mt-2" />
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
                    Dodaj Nowe Poświadczenia
                </x-primary-button>
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Przyjazna Nazwa</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nazwa użytkownika</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Akcje</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($credentials as $credential)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $credential->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">{{ $credential->username }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <x-secondary-button wire:click="showEditForm({{ $credential->id }})">Edytuj</x-secondary-button>
                                    <x-danger-button 
                                        wire:click="deleteCredential({{ $credential->id }})" 
                                        wire:confirm="Czy na pewno chcesz usunąć te poświadczenia?">
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
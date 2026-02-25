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
                {{ $userIdToUpdate ? 'Edytuj Użytkownika' : 'Stwórz Nowego Użytkownika' }}
            </h2>
            
            <form wire:submit="saveUser">
                <div>
                    <x-input-label for="full_name" :value="__('Pełna Nazwa')" />
                    <x-text-input wire:model="full_name" id="full_name" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                </div>
                
                <div class="mt-4">
                    <x-input-label for="username" :value="__('Nazwa Użytkownika (login)')" />
                    <x-text-input wire:model="username" id="username" class="block mt-1 w-full" type="text" required />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="selectedRole" :value="__('Rola')" />
                    <select wire:model="selectedRole" id="selectedRole" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        <option value="">Wybierz rolę...</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('selectedRole')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password" :value="__('Hasło')" />
                    <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" />
                    @if ($userIdToUpdate)
                        <small class="text-gray-500">Wypełnij tylko jeśli chcesz zmienić hasło.</small>
                    @endif
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password_confirmation" :value="__('Potwierdź Hasło')" />
                    <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full" type="password" />
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
                    Dodaj Użytkownika
                </x-primary-button>
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pełna Nazwa</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nazwa Użytkownika</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rola</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Akcje</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->full_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->username }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $user->getRoleNames()->first() ?? 'Brak Roli' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <x-secondary-button wire:click="showEditForm({{ $user->id }})">Edytuj</x-secondary-button>
                                    <x-danger-button 
                                        wire:click="deleteUser({{ $user->id }})" 
                                        wire:confirm="Czy na pewno chcesz usunąć tego użytkownika? Ta akcja jest nieodwracalna.">
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
<div>
    <div class="mb-4">
        <a href="{{ route('devices.index') }}" wire:navigate 
           class="text-sm text-blue-600 hover:underline">
            &larr; Wróć do listy urządzeń
        </a>
    </div>

    @if (session()->has('bulk_error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Błąd!</strong>
            <span class="block sm:inline">{{ session('bulk_error') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
        <form wire:submit="runBulkAction">
            
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Uruchom Akcję Zbiorczą</h2>
                <p class="text-sm text-gray-600 mt-1">Wybrana akcja zostanie zakolejkowana dla <strong>{{ count($devices) }}</strong> wybranych urządzeń. Będziesz mógł śledzić postęp w Monitorze Zadań.</p>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="space-y-4">
                    <div>
                        <x-input-label for="selectedActionId" :value="__('Wybierz akcję do wykonania')" />
                        <select wire:model="selectedActionId" id="selectedActionId" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Wybierz akcję...</option>
                            @foreach($actions as $action)
                                <option value="{{ $action->id }}">{{ $action->name }} ({{ $action->action_slug }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('selectedActionId')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="customCommands" :value="__('Lub wprowadź własne komendy (jedna na linię)')" />
                        <textarea wire:model="customCommands" id="customCommands" rows="10" 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono bg-gray-900 text-green-300" 
                                  :disabled="$selectedActionId"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Uwaga: Wypełnienie tego pola nadpisze wybór akcji. Komendy zostaną wysłane do wszystkich wybranych urządzeń.</p>
                        <x-input-error :messages="$errors->get('customCommands')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label :value="__('Wybrane urządzenia')" />
                    <div class="mt-1 w-full h-80 overflow-y-auto border border-gray-300 rounded-md bg-gray-50 p-3 text-sm space-y-2">
                        @forelse ($devices as $device)
                            <div class="flex justify-between items-center bg-white p-2 rounded border">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $device->name }}</p>
                                    <p class="text-gray-500 font-mono text-xs">{{ $device->ip_address }}</p>
                                </div>
                                <span class="text-xs text-gray-600">{{ $device->vendor->name }}</span>
                            </div>
                        @empty
                            <p class="text-gray-500">Brak wybranych urządzeń. Wróć do listy.</p>
                        @endforelse
                    </div>
                    <x-input-error :messages="$errors->get('deviceIds')" class="mt-2" />
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end items-center space-x-3">
                <span wire:loading wire:target="runBulkAction" class="text-sm text-gray-500">
                    Kolejkowanie zadań...
                </span>
                
                <x-primary-button type="submit" wire:loading.attr="disabled">
                    <svg wire:loading wire:target="runBulkAction" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Uruchom akcję na {{ count($devices) }} urządzeniach
                </x-primary-button>
            </div>

        </form>
    </div>
</div>
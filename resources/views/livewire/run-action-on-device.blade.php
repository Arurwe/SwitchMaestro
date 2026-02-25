<div wire:poll.2s="checkTaskStatus">
    <div class="mb-4">
        <a href="{{ route('devices.show', $device) }}" wire:navigate 
           class="text-sm text-blue-600 hover:underline">
            &larr; Wróć do szczegółów urządzenia ({{ $device->name }})
        </a>
    </div>

    <div class="max-w-3xl mx-auto">
        
        <!-- Główny formularz -->
        <form wire:submit="runAction">
            <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
                
                <div x-data="{ mode: @entangle('mode') }" class="p-6">
                    <div class="mb-4">
                        <label class="text-base font-medium text-gray-900">Wybierz tryb wykonania</label>
                        <p class="text-sm text-gray-500">Wybierz predefiniowaną akcję  lub wprowadź własne komendy.</p>
                    </div>
                    <fieldset class="mt-4">
                        <legend class="sr-only">Tryb wykonania</legend>
                        <div class="flex items-center space-x-4">
                            <label 
                                :class="{ 'bg-indigo-600 text-white': mode === 'predefined', 'bg-white text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50': mode !== 'predefined' }"
                                class="cursor-pointer rounded-md px-3 py-2 text-sm font-semibold transition-all duration-150">
                                <input type="radio" wire:model="mode" value="predefined" class="sr-only">
                                Akcje Predefiniowane
                            </label>
                            <label 
                                :class="{ 'bg-indigo-600 text-white': mode === 'custom', 'bg-white text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50': mode !== 'custom' }"
                                class="cursor-pointer rounded-md px-3 py-2 text-sm font-semibold transition-all duration-150">
                                <input type="radio" wire:model="mode" value="custom" class="sr-only">
                                Własne Komendy
                            </label>
                        </div>
                    </fieldset>

                    <!-- Akcje Predefiniowane -->
                    <div x-show="mode === 'predefined'" class="mt-6 space-y-4">
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
                    </div>

                    <!-- : Własne Komendy -->
                    <div x-show="mode === 'custom'" class="mt-6 space-y-4">
                        <div>
                            <x-input-label for="customCommands" :value="__('Własne komendy (jedna na linię)')" />
                            <p class="text-xs text-gray-500 mt-1 mb-2">Uwaga: Te komendy zostaną wykonane z uprawnieniami `enable` (jeśli skonfigurowano `secret`).</p>
                            <textarea wire:model="customCommands" id="customCommands" rows="8" 
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono bg-gray-900 text-green-300" 
                                      placeholder=""></textarea>
                            <x-input-error :messages="$errors->get('customCommands')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                                <button type="button" wire:click="openAiAssistant({{ $action->id }})"
                                        class="rounded-md bg-purple-600 mr-4 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700 flex items-center gap-1"
                                        title="Przetłumacz z innego vendora przy użyciu AI">
                                    <span>Tłumacz AI</span>
                                </button>
                    <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="runAction" :disabled="$monitoringJobId">
                        <svg wire:loading wire:target="runAction" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="runAction">
                            @if ($monitoringJobId)
                                Oczekiwanie...
                            @else
                                Uruchom
                            @endif
                        </span>
                        <span wire:loading wire:target="runAction">Wysyłanie...</span>
                    </x-primary-button>
                </div>
            </div>
        </form>

        <!-- Sekcja Wyników ) -->
        <div class="mt-6">
            <!-- Komunikat o błędzie API -->
            @if ($taskError)
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Błąd!</strong>
                    <span class="block sm:inline">{{ $taskError }}</span>
                </div>
            @endif

            <!-- Informacja o monitorowaniu -->
            @if ($monitoringJobId && !$monitoredLog)
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">Wysłano zadanie. Oczekiwanie na odebranie przez workera... (ID: {{ $monitoringJobId }})</span>
                </div>
            @endif

            <!-- Wynik  -->
            @if ($monitoredLog)
                <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Wynik Wykonania Zadania</h3>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                            <div>
                                <dt class="text-gray-500">Job ID</dt>
                                <dd class="font-mono">{{ $monitoredLog->job_id ?? 'Brak' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Status</dt>
                                <dd>
                                    @if ($monitoredLog->status == 'success')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Sukces</span>
                                    @elseif ($monitoredLog->status == 'failed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Błąd</span>
                                    @elseif ($monitoredLog->status == 'RUNNING')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-yellow-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Uruchomione
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </div>

                        <!-- Wysłane komendy -->
                        <div class="mb-4">
                            <dt class="text-sm font-medium text-gray-700">Wysłane komendy:</dt>
                            <dd class="mt-1">
                                <pre class="bg-gray-900 text-white text-sm font-mono p-3 rounded-md overflow-x-auto">{{ $monitoredLog->command_sent ?? 'Brak' }}</pre>
                            </dd>
                        </div>
                        
                        <!-- Błąd ) -->
                        @if ($monitoredLog->error_message)
                        <div class="mb-4">
                            <dt class="text-sm font-medium text-red-700">Komunikat błędu:</dt>
                            <dd class="mt-1">
                                <pre class="bg-red-50 text-red-900 text-sm font-mono p-3 rounded-md overflow-x-auto whitespace-pre-wrap">{{ $monitoredLog->error_message }}</pre>
                            </dd>
                        </div>
                        @endif

                        <!-- Surowy wynik -->
                        @if ($monitoredLog->raw_output)
                        <div class="mb-4">
                            <dt class="text-sm font-medium text-gray-700">Surowy wynik:</dt>
                            <dd class="mt-1">
                                <pre class="bg-gray-100 text-gray-800 text-sm font-mono p-3 rounded-md overflow-x-auto whitespace-pre-wrap">{{ $monitoredLog->raw_output }}</pre>
                            </dd>
                        </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
    {{-- MODAL ASYSTENTA AI --}}
@if ($showAiModal)
<div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full overflow-hidden transform transition-all">
        
        {{-- Nagłówek Modala --}}
        <div class="bg-purple-600 px-4 py-3 flex justify-between items-center">
            <h3 class="text-lg font-medium text-white flex items-center gap-2">
                 Asystent Translacji AI
            </h3>
            <button wire:click="closeAiModal" class="text-white hover:text-gray-200 text-xl">&times;</button>
        </div>
        
        <div class="p-6">
            <div class="mb-4 bg-purple-50 p-3 rounded-md text-sm text-purple-800 border border-purple-200">
                <p><strong>Jak to działa?</strong></p>
                <p>Wklej komendy z innego systemu. AI przetłumaczy je na format zgodny z obecnym urządzeniem: <strong class="uppercase">{{ $device->vendor->name }}</strong>.</p>
            </div>

            <div class="space-y-4">
                {{-- Wybór źródła --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">1. Znam komendy dla (Źródło):</label>
                    <select wire:model.defer="aiSourceVendorId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        <option value="">Wybierz źródło...</option>
                        @foreach ($allVendors as $v)
                            @if($v->id !== $device->vendor_id)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('aiSourceVendorId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Komendy --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">2. Wklej komendy źródłowe:</label>
                    <textarea wire:model.defer="aiSourceCommands" rows="6" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm font-mono text-sm bg-gray-50 focus:border-purple-500 focus:ring-purple-500"
                              placeholder="np. interface GigabitEthernet1/0/1&#10; description Uplink&#10; switchport mode trunk"></textarea>
                    @error('aiSourceCommands') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Stopka Modala --}}
        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse">
            <button type="button" wire:click="translateCommandsWithAi" wire:loading.attr="disabled"
                    class="w-full inline-flex justify-center rounded-md border border-transparent bg-purple-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-purple-700 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-wait">
                
                <span wire:loading.remove wire:target="translateCommandsWithAi">
                    Tłumacz i Wstaw
                </span>
                <span wire:loading wire:target="translateCommandsWithAi" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Przetwarzanie AI...
                </span>
            </button>
            <button type="button" wire:click="closeAiModal"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                Anuluj
            </button>
        </div>
    </div>
</div>
@endif
</div>


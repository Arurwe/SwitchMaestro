<div>
    @if (session()->has('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4 shadow-sm border border-green-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="mb-6 rounded-lg bg-white shadow-lg border border-gray-200 overflow-hidden">
        <div class="flex justify-between items-center p-4 cursor-pointer @if(!$showCreateForm) hover:bg-gray-50 @endif" wire:click="toggleCreateForm">
            <h2 class="text-lg font-semibold text-gray-800">
                @if ($showCreateForm)
                    Formularz Nowej Akcji
                @else
                    + Dodaj Nową Akcję
                @endif
            </h2>
            <button class="text-indigo-600 hover:text-indigo-800 text-2xl font-light">
                @if ($showCreateForm) &times; @else + @endif
            </button>
        </div>


        @if ($showCreateForm)
        <div class="p-6 border-t border-gray-200 bg-gray-50">
            <form wire:submit.prevent="storeNewAction" class="space-y-4">
                <div>
                    <label for="newActionName" class="block text-sm font-medium text-gray-700">Nazwa Akcji</label>
                    <input type="text" wire:model.defer="newActionName" id="newActionName" placeholder="Pobierz backup konfiguracji" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('newActionName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="newActionSlug" class="block text-sm font-medium text-gray-700">Slug Akcji (identyfikator)</label>
                    <input type="text" wire:model.defer="newActionSlug" id="newActionSlug" placeholder="get_config_backup" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <p class="text-xs text-gray-500 mt-1">Używaj tylko małych liter, cyfr i myślników (np. `get-vlans`).</p>
                    @error('newActionSlug') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="newActionDescription" class="block text-sm font-medium text-gray-700">Opis (opcjonalnie)</label>
                    <textarea wire:model.defer="newActionDescription" id="newActionDescription" rows="3" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" wire:loading.attr="disabled" 
                            class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50">
                        <span wire:loading.remove wire:target="storeNewAction">Zapisz Akcję</span>
                        <span wire:loading wire:target="storeNewAction">Zapisywanie...</span>
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>

    <div class="space-y-4">
        @forelse ($actions as $action)
            @php
                $isExpanded = ($expandedActionId === $action->id);
            @endphp
            
            <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
                

<div class="flex justify-between items-center p-6 hover:bg-gray-50">
    <div class="flex-1 cursor-pointer" wire:click="toggleExpand({{ $action->id }})">
        <h3 class="text-xl font-bold text-indigo-700">{{ $action->name }}</h3>
        <p class="text-sm text-gray-500 mt-1">{{ $action->description }}</p>
    </div>
    <div class="flex items-center space-x-2">
        <!-- Edytuj -->
        <button wire:click="startEditing({{ $action->id }})" 
                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
             Edytuj
        </button>
        <!-- Usuń -->
        <button wire:click="deleteAction({{ $action->id }})"
                wire:confirm="Na pewno chcesz usunąć tę akcję?" 
                class="text-red-600 hover:text-red-800 text-sm font-medium">
             Usuń
        </button>
        <span class="ml-3 text-indigo-600 cursor-pointer" wire:click="toggleExpand({{ $action->id }})">
            @if ($isExpanded) ▼ @else ► @endif
        </span>
    </div>
</div>
        @if ($editActionId === $action->id)
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <form wire:submit.prevent="updateAction" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nazwa Akcji</label>
                        <input type="text" wire:model.defer="editActionName" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('editActionName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Slug Akcji</label>
                        <input type="text" wire:model.defer="editActionSlug" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('editActionSlug') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Opis</label>
                        <textarea wire:model.defer="editActionDescription" rows="3" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="cancelEditing"
                                class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Anuluj
                        </button>
                        <button type="submit" 
                                class="rounded-md bg-indigo-600 py-2 px-4 text-sm font-medium text-white hover:bg-indigo-700">
                            Zapisz zmiany
                        </button>
                    </div>
                </form>
            </div>
        @endif
                @if ($isExpanded)
                <div class="bg-gray-50 p-6 border-t border-gray-200">
                    <h4 class="text-md font-semibold mb-4 text-gray-800">Dostępne implementacje:</h4>
                    
                    @if ($action->commands->isEmpty())
                        <p class="text-gray-500 italic text-sm">Brak implementacji dla tej akcji.</p>
                    @else
                        <div class="space-y-3 mb-6">
                            @foreach ($action->commands as $command)
                                <div class="flex justify-between items-center bg-white p-3 rounded-md shadow-sm border">
                                    <div>
                                        <span class="font-medium text-gray-900">{{ $command->vendor->name }}</span>
                                        <span class="text-gray-500 text-sm ml-2 font-mono">({{ $command->vendor->netmiko_driver }})</span>
                                    </div>
                                    <a href="{{ route('commands.edit', $command) }}" 
                                       class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                        Edytuj Komendy
                                        <span class="ml-1">&rarr;</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <hr class="my-6">

                    <form wire:submit.prevent="addImplementation({{ $action->id }})">
                        <h5 class="text-md font-semibold mb-2">Dodaj nową implementację</h5>
                        
                        @php
                            $existingVendorIds = $action->commands->pluck('vendor_id');
                            $availableVendors = $allVendors->whereNotIn('id', $existingVendorIds);
                        @endphp

                        @if ($availableVendors->isEmpty())
                            <p class="text-sm text-gray-500">Wszyscy dostępni vendorzy zostali już dodani do tej akcji.</p>
                        @else
                            <div class="flex items-start space-x-2">
                                <div class="flex-grow">
                                    <select wire:model.lazy="newVendorId.{{ $action->id }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Wybierz vendora...</option>
                                        @foreach ($availableVendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }} ({{ $vendor->netmiko_driver }})</option>
                                        @endforeach
                                    </select>
                                    @error('newVendorId.'.$action->id) <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                                <button type="submit" wire:loading.attr="disabled"
                                        class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-700 disabled:opacity-50">
                                    Stwórz
                                </button>
                                <button type="button" wire:click="openAiAssistant({{ $action->id }})"
                                        class="rounded-md bg-purple-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700 flex items-center gap-1"
                                        title="Przetłumacz z innego vendora przy użyciu AI">
                                    <span>AI</span>
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
                @endif

            </div> @empty
            <div class="text-center bg-white p-12 rounded-lg shadow-md border">
                <h3 class="text-lg font-medium text-gray-900">Brak Akcji</h3>
                <p class="text-gray-500 mt-2">Nie zdefiniowano jeszcze żadnych akcji. Zacznij od dodania nowej, używając formularza powyżej.</p>
            </div>
        @endforelse
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
                <p>System spróbuje przetłumaczyć komendy z formatu, który znasz, na format vendora wybranego w głównym oknie.</p>
            </div>

            <div class="space-y-4">
                {{-- Wybór źródła --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">1. Znam komendy dla (Źródło):</label>
                    <select wire:model.defer="aiSourceVendorId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        <option value="">Wybierz źródło...</option>
                        @foreach ($allVendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
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
                
                {{-- Błędy ogólne (np. brak vendora docelowego) --}}
                @error('newVendorId.'.$aiTargetActionId) 
                    <div class="text-red-600 text-sm font-bold bg-red-50 p-2 rounded">
                        ⚠️ {{ $message }}
                    </div>
                @enderror
            </div>
        </div>

        {{-- Stopka Modala --}}
        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse">
            <button type="button" wire:click="generateImplementationWithAi" wire:loading.attr="disabled"
                    class="w-full inline-flex justify-center rounded-md border border-transparent bg-purple-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-purple-700 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-wait">
                
                <span wire:loading.remove wire:target="generateImplementationWithAi">
                    Tłumacz i Utwórz
                </span>
                <span wire:loading wire:target="generateImplementationWithAi" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="[http://www.w3.org/2000/svg](http://www.w3.org/2000/svg)" fill="none" viewBox="0 0 24 24">
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


<div>
    <div class="bg-white shadow-md rounded-lg p-4 mb-6">
        <h4 class="text-lg font-semibold mb-4">Filtry Dziennika Zdarzeń</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Wyszukaj w opisie</label>
                <x-text-input wire:model.live.debounce.300ms="search" id="search" class="block mt-1 w-full" type="text" />
            </div>

            <div wire:ignore>
                <label for="user-select" class="block text-sm font-medium text-gray-700">Użytkownik</label>
                <select id="user-select" class="block mt-1 w-full" placeholder="Wybierz użytkownika..."></select>
            </div>
            
            <div>
                <label for="dateFrom" class="block text-sm font-medium text-gray-700">Data od</label>
                <x-text-input wire:model.live="dateFrom" id="dateFrom" class="block mt-1 w-full" type="date" />
            </div>
            
            <div>
                <label for="dateTo" class="block text-sm font-medium text-gray-700">Data do</label>
                <x-text-input wire:model.live="dateTo" id="dateTo" class="block mt-1 w-full" type="date" />
            </div>

            <div class="flex items-end">
                <x-secondary-button wire:click="resetFilters" class="w-full justify-center">
                    Wyczyść Filtry
                </x-secondary-button>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Czas</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Użytkownik (Kto)</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opis Zdarzenia (Co)</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Szczegóły (Zmiany)</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 align-top">
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 align-top">
                            {{ $log->causer?->full_name ?? 'System' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 align-top">
                            {{ $log->description }}
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500 font-mono align-top">
                            @php
                                $properties = $log->properties;
                                
                                $old = $properties->get('old', []);
                                $attributes = $properties->get('attributes', []);
                                
                                $customChanges = $properties->except(['old', 'attributes']);
                            @endphp

                            @if (!empty($old) || !empty($attributes) || $customChanges->isNotEmpty())
                                <ul class="list-disc list-inside">

                                    @if ($log->event === 'created' && !empty($attributes))
                                        <li class="font-semibold text-green-700">Utworzono z:</li>
                                        @foreach ($attributes as $key => $value)
                                            <li class="ml-4">
                                                <span class="text-gray-900">{{ $key }}</span>: 
                                                <span class="text-green-600 break-all">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                            </li>
                                        @endforeach
                                    @endif

                                    @if ($log->event === 'updated')
                                        <li class="font-semibold text-blue-700">Zaktualizowano:</li>
                                        
                                        @foreach ($old as $key => $value)
                                            <li class="ml-4">
                                                <span class="text-gray-900">{{ $key }}</span>: 
                                                <span class="text-red-600 line-through">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                &rarr;
                                                <span class="text-green-600">{{ is_array($attributes[$key]) ? json_encode($attributes[$key]) : $attributes[$key] }}</span>
                                            </li>
                                        @endforeach

                                        @foreach ($customChanges as $key => $message)
                                            <li class="ml-4">
                                                <span class="text-gray-900">{{ $key }}</span>: 
                                                <span class="text-blue-600 font-medium">{{ $message }}</span>
                                            </li>
                                        @endforeach
                                    @endif
                                </ul>
                                
                            @elseif ($log->event === 'deleted')
                                <span class="font-semibold text-red-700">Usunięto obiekt</span>
                            @else
                                <span class="italic">Brak szczegółów zmian</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            Brak wpisów w dzienniku zdarzeń systemu.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:navigated', () => {
            if (window.initTomSelect) {
                window.initTomSelect('user-select', 'selectedUser', @json($allUsers), 'full_name');
            }
        });
    </script>
    @endpush
</div>
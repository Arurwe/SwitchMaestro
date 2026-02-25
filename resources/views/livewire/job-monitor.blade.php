<div wire:poll.5s="refreshLogs">


    @if ($selectedLog)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @keydown.escape.window="$wire.closeModal()">
        
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
            
            <div class="flex justify-between items-center p-6 border-b">
                 <h2 class="text-xl font-semibold text-gray-900">
                    Szczegóły logu (ID: {{ $selectedLog->id }})
                </h2>
                <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 text-3xl">&times;</button>
            </div>

            <div class="p-6 overflow-y-auto space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Job ID</dt>
                        <dd class="font-mono text-gray-900">{{ $selectedLog->job_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Batch ID</dt>
                        <dd class="font-mono text-gray-900">{{ $selectedLog->batch_id ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Urządzenie</dt>
                        <dd class="text-gray-900">{{ optional($selectedLog->device)->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Akcja</dt>
                        <dd class="text-gray-900 font-mono">{{ optional($selectedLog->action)->name ?? $selectedLog->action }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd class="text-gray-900">{{ $selectedLog->status }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Data</dt>
                        <dd class="text-gray-900">{{ $selectedLog->created_at }}</dd>
                    </div>
                </div>

                @if ($selectedLog->intention_prompt)
                    <h3 class="font-semibold text-gray-800">Prompt (Intencja):</h3>
                    <pre class="bg-gray-100 p-3 rounded mb-4 text-xs whitespace-pre-wrap max-h-40 overflow-auto">{{ $selectedLog->intention_prompt }}</pre>
                @endif

                @if ($selectedLog->command_sent)
                    <h3 class="font-semibold text-gray-800">Wysłana komenda:</h3>
                    <pre class="bg-gray-100 p-3 rounded mb-4 text-xs whitespace-pre-wrap max-h-40 overflow-auto">{{ $selectedLog->command_sent }}</pre>
                @endif

                @if ($selectedLog->error_message)
                    <h3 class="font-semibold text-red-600">Błąd:</h3>
                    <pre class="bg-red-50 p-3 rounded mb-4 text-xs whitespace-pre-wrap max-h-60 overflow-auto text-red-800">{{ $selectedLog->error_message }}</pre>
                @endif

                @if ($selectedLog->system_info)
                    <h3 class="font-semibold text-gray-800">Informacja:</h3>
                    <pre class="bg-gray-100 p-3 rounded mb-4 text-xs whitespace-pre-wrap max-h-40 overflow-auto">{{ $selectedLog->system_info }}</pre>
                @endif
                
                @if ($selectedLog->raw_output)
                    <h3 class="font-semibold text-gray-800">Surowy output:</h3>
                    <pre class="bg-gray-900 text-green-300 p-3 rounded mb-4 text-xs whitespace-pre-wrap max-h-80 overflow-auto">{{ $formattedOutput }}</pre>
                @endif
            </div>

            <div class="text-right bg-gray-50 p-4 border-t">
                <x-secondary-button wire:click="closeModal">
                    Zamknij
                </x-secondary-button>
            </div>
        </div>
    </div>
    @endif

    <div class="mb-4 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'all')"
                        class="inline-block p-4 border-b-2 rounded-t-lg focus:outline-none
                        {{ $activeTab === 'all' 
                            ? 'border-blue-600 text-blue-600' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Wszystkie Zadania
                </button>
            </li>
            <li class="mr-2">
                <button wire:click="$set('activeTab', 'grouped')"
                        class="inline-block p-4 border-b-2 rounded-t-lg focus:outline-none
                        {{ $activeTab === 'grouped' 
                            ? 'border-blue-600 text-blue-600' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Zadania Zbiorcze (Batch)
                </button>
            </li>
        </ul>
    </div>

    <div class="bg-white shadow-md rounded-lg p-4 mb-6">
        <h4 class="text-lg font-semibold mb-3">Filtry</h4>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <x-input-label for="filterBatch" :value="__('Batch ID')" />
                <x-text-input wire:model.lazy="filterBatch" id="filterBatch" class="block mt-1 w-full" type="text" placeholder="Filtruj po Batch ID..." />
            </div>

            @if ($activeTab === 'all')
                <div>
                    <x-input-label for="filterDevice" :value="__('Nazwa urządzenia')" />
                    <x-text-input wire:model.lazy="filterDevice" id="filterDevice" class="block mt-1 w-full" type="text" placeholder="Filtruj po nazwie..." />
                </div>
                <div>
                    <x-input-label for="filterUser" :value="__('Użytkownik')" />
                    <x-text-input wire:model.lazy="filterUser" id="filterUser" class="block mt-1 w-full" type="text" placeholder="Filtruj po nazwie..." />
                </div>
                <div>
                    <x-input-label for="filterStatus" :value="__('Status')" />
                    <select wire:model="filterStatus" id="filterStatus" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">Wszystkie</option>
                        <option value="RUNNING">Uruchomione (RUNNING)</option>
                        <option value="success">Sukces (success)</option>
                        <option value="failed">Błąd (failed)</option>
                    </select>
                </div>
            @else
                <div class="hidden md:block"></div>
                <div class="hidden md:block"></div>
            @endif
            
            <div class="flex items-end">
                <x-secondary-button wire:click="resetFilters" class="mt-1 w-full justify-center">
                    Resetuj Filtry
                </x-secondary-button>
            </div>
        </div>
    </div>
    
    @if ($activeTab === 'all')
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Wszystkie Zadania (Odświeżanie co 5s)</h3>
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Czas</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Użytkownik</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Urządzenie</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcja</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Szczegóły / Błąd</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job / Batch ID</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Akcje</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ optional($log->user)->full_name ?? 'System' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ optional($log->device)->name ?? '-' }}</td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                            {{ optional($log->action)->name ?? $log->action }}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if ($log->status == 'success')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Sukces
                                </span>
                            @elseif ($log->status == 'failed')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Błąd
                                </span>
                            @elseif ($log->status == 'RUNNING')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Uruchomione
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    {{ $log->status }}
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-xs">
                            @if ($log->error_message)
                                <code class="block whitespace-nowrap overflow-hidden text-ellipsis max-w-xs text-red-600 font-semibold" title="{{ $log->error_message }}">
                                    {{ Str::limit($log->error_message, 50) }}
                                </code>
                            @else
                                <code class="block whitespace-nowrap overflow-hidden text-ellipsis max-w-xs text-gray-500" title="{{ $log->command_sent }}">
                                    {{ Str::limit($log->command_sent, 50) }}
                                </code>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap font-mono text-xs">
                            @if ($log->batch_id)
                                <a href="#" wire:click.prevent="$set('filterBatch', '{{ $log->batch_id }}')" 
                                   class="text-blue-600 hover:underline" 
                                   title="Filtruj po Batch ID: {{ $log->batch_id }}">
                                    Batch: {{ Str::limit($log->batch_id, 8, '...') }}
                                </a>
                            @else
                                <a href="#" wire:click.prevent="openModal({{ $log->id }})" 
                                   class="text-gray-500 hover:underline"
                                   title="Pokaż szczegóły Job ID: {{ $log->job_id }}">
                                   {{ $log->job_id ? Str::limit($log->job_id, 8, '...') : '-' }}
                                </a>
                            @endif
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="openModal({{ $log->id }})"
                                class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                Szczegóły
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            Brak zadań pasujących do filtrów.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $logs->links() }}
    </div>

    @elseif ($activeTab === 'grouped')
    <h3 class="text-lg font-semibold text-gray-700 mb-4">Zadania Zbiorcze (Odświeżanie co 5s)</h3>
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Postęp (Ukończone / Uruchomione / Błędy / Razem)</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ostatnia Aktywność</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Akcje</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($batches as $batch)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                            <a href="#" wire:click.prevent="$set('filterBatch', '{{ $batch->batch_id }}'); $set('activeTab', 'all')" 
                               class="text-blue-600 hover:underline" 
                               title="Pokaż zadania z tego batcha">
                                {{ $batch->batch_id }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <span class="text-green-600 font-semibold">{{ $batch->success_tasks }}</span> /
                            <span class="text-yellow-600 font-semibold">{{ $batch->running_tasks }}</span> /
                            <span class="text-red-600 font-semibold">{{ $batch->failed_tasks }}</span> /
                            <span class="font-bold">{{ $batch->total_tasks }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($batch->last_activity)->diffForHumans() }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button 
                                wire:click="showBatchTasks('{{ $filterBatch }}')" 
                                class="inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs
                                text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                Pokaż zadania
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            Brak zadań zbiorczych pasujących do filtrów.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $batches->links() }}
    </div>
    @endif
    
</div>
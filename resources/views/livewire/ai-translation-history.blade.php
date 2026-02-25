<div>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        
        <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inicjator</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kierunek Translacji</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model AI</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($translations as $translation)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $translation->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $translation->user->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-medium text-indigo-600">{{ $translation->sourceVendor->name ?? 'Nieznany' }}</span>
                                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                        <span class="font-medium text-purple-600">{{ $translation->targetVendor->name ?? 'Nieznany' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $translation->model_name ?? 'Brak danych' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if ($translation->error_message)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Błąd
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Sukces
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="viewDetails({{ $translation->id }})" class="text-indigo-600 hover:text-indigo-900">
                                        Szczegóły
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    Brak historii translacji AI w bazie danych.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($translations->hasPages())
                <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                    {{ $translations->links() }}
                </div>
            @endif
        </div>
    </div>

    @if ($showModal && $selectedTranslation)
    <div class="fixed inset-0  bg-gray-600 bg-opacity-75 transition-opacity z-50 flex justify-center items-center px-4 sm:px-0">
        
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
            
            <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center shrink-0">
                <h3 class="text-lg font-medium text-white">
                    Szczegóły translacji #{{ $selectedTranslation->id }}
                </h3>
                <button wire:click="closeModal" class="text-white hover:text-gray-200 text-2xl leading-none">&times;</button>
            </div>
            
            <div class="p-6 overflow-y-auto grow">
                
                @if ($selectedTranslation->error_message)
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Komunikat błędu</h3>
                                <div class="mt-2 text-sm text-red-700 font-mono whitespace-pre-wrap">
                                    {{ $selectedTranslation->error_message }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2 uppercase tracking-wide">
                            Wejście ({{ $selectedTranslation->sourceVendor->name ?? 'Nieznany' }})
                        </h4>
                        <pre class="bg-gray-100 border border-gray-300 rounded-md p-4 text-sm font-mono text-gray-800 whitespace-pre-wrap h-64 overflow-y-auto">{{ $selectedTranslation->input_commands }}</pre>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2 uppercase tracking-wide flex items-center justify-between">
                            <span>Wyjście ({{ $selectedTranslation->targetVendor->name ?? 'Nieznany' }})</span>
                            <span class="text-xs font-normal text-gray-500 normal-case">Model: {{ $selectedTranslation->model_name }}</span>
                        </h4>
                        <pre class="bg-gray-900 border border-gray-700 rounded-md p-4 text-sm font-mono text-green-400 whitespace-pre-wrap h-64 overflow-y-auto">{{ $selectedTranslation->translated_commands ?? 'Brak przetłumaczonych komend.' }}</pre>
                    </div>
                </div>

            </div>

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end shrink-0">
                <button wire:click="closeModal" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Zamknij
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
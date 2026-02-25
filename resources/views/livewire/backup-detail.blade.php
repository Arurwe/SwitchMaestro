<div>
    <div class="bg-white shadow-md rounded-lg p-6">

        {{-- Przycisk powrotu --}}
        <div class="mb-6 pb-4 border-b">
            <a href="{{ route('backups.index') }}" wire:navigate
               class="text-sm text-blue-600 hover:underline">
                &larr; Wróć do listy backupów
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-sm">
            <div>
                <span class="font-semibold text-gray-600">ID Backupu:</span>
                <span class="text-gray-800">{{ $backup->id }}</span>
            </div>
            <div>
                <span class="font-semibold text-gray-600">Data Utworzenia:</span>
                <span class="text-gray-800">{{ $backup->created_at->format('Y-m-d H:i:s') }}</span>
            </div>
            <div>
                <span class="font-semibold text-gray-600">Urządzenie:</span>

                @if($backup->device)

                    <a href="{{ route('devices.show', $backup->device) }}" 
                    class="text-blue-600 hover:text-blue-800 hover:underline"
                    title="Przejdź do szczegółów urządzenia">
                        
                        <span class="text-gray-800">{{ $backup->device->name }}</span>
                        <span class="text-gray-500">({{ $backup->device->ip_address }})</span>
                    </a>
                @else
                    <span class="text-gray-800">Usunięto</span>
                @endif
            </div>
            <div>
                <span class="font-semibold text-gray-600">Inicjator:</span>
                <span class="text-gray-800">{{ optional($backup->user)->full_name ?? 'System' }}</span>
            </div>
             <div>
                <span class="font-semibold text-gray-600">Rozmiar:</span>
                <span class="text-gray-800">{{ number_format(strlen($backup->configuration) / 1024, 1) }} KB</span>
            </div>
        </div>

        {{-- Konfiguracja --}}
        <div>
            <h4 class="text-md font-semibold text-gray-700 mb-2">Zawartość Konfiguracji:</h4>
            <pre class="bg-gray-900 text-gray-100 p-4 rounded-md text-xs overflow-auto max-h-[70vh] font-mono">{{ $backup->configuration }}</pre>
        </div>

        <div class="mt-6 pt-4 border-t flex justify-between">

        </div>

    </div>
</div>
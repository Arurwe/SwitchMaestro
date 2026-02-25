<div>
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-white shadow-md rounded-lg overflow-hidden">

        <div class="flex gap-4 mb-4 items-end">

    <div class="w-64">
        <label for="device-select" class="block text-sm font-medium text-gray-700">Urządzenie</label>
        <select id="device-select" wire:model.live="deviceId" class="mt-1 block w-full">
            <option value="">— Wszystkie —</option>
            @foreach($devices as $device)
                <option value="{{ $device->id }}">{{ $device->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Data od</label>
        <input type="date" wire:model.live="dateFrom"
               class="mt-1 block w-full border-gray-300 rounded-md" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Data do</label>
        <input type="date" wire:model.live="dateTo"
               class="mt-1 block w-full border-gray-300 rounded-md" />
    </div>

    <div>
        <button
            wire:click="resetFilters"
            class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 border border-gray-300 rounded-md text-sm font-medium text-gray-700 transition">
            Resetuj filtry
        </button>
    </div>

</div>


        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Utworzenia</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Urządzenie</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inicjator</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rozmiar</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Akcje</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($backups as $backup)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $backup->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ optional($backup->device)->name ?? 'Usunięto' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ optional($backup->user)->full_name ?? 'System' }}
                        </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ number_format(strlen($backup->configuration) / 1024, 1) }} KB
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="{{ route('backups.show', $backup) }}" wire:navigate
                                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                        Pokaż
                                    </a>

                            @can('backups:delete')
                                <x-danger-button
                                    wire:click="deleteBackup({{ $backup->id }})"
                                    wire:confirm="Czy na pewno chcesz usunąć ten backup? Ta akcja jest nieodwracalna.">
                                    Usuń
                                </x-danger-button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Nie znaleziono żadnych backupów konfiguracji.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $backups->links() }}
    </div>

@push('scripts')
<script>
    document.addEventListener('livewire:navigated', () => {
        if (window.initTomSelect) {
            window.initTomSelect(
                'device-select',
                'deviceId',
                @json($devices),
                'name'
            );
        }
    });
</script>
@endpush


</div>
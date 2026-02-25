@component('layouts.app', ['header' => 'Edytuj Implementację Komend'])
    <div class="w-full max-w-7xl mx-auto bg-white p-6 rounded-2xl shadow-md border border-gray-200">

        <div class="mb-8 border-b border-gray-200 pb-4">
            <h2 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
                <span class="text-indigo-600">{{ $command->action->name }}</span>
                <span class="text-gray-400">/</span>
                <span class="text-green-600">{{ $command->vendor->name }}</span>
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Edytuj zestaw komend wykonywanych dla wybranego producenta.
            </p>
        </div>

        <form action="{{ route('commands.update', $command) }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')
            <div>
                <label for="commands" class="block text-sm font-semibold text-gray-800 mb-1">Komendy</label>
                <p class="text-xs text-gray-500 mb-2">
                    Każdą komendę wpisz w nowej linii. Zostaną wykonane w podanej kolejności.
                </p>

                <textarea 
                    name="commands" 
                    id="commands" 
                    rows="12"
                    class="w-full rounded-md border-gray-300 bg-gray-900 text-green-600 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('commands') border-red-500 @enderror"
                >{{ old('commands', implode("\n", $command->commands)) }}</textarea>

                @error('commands')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-semibold text-gray-800 mb-1">Opis implementacji</label>
                <textarea 
                    name="description" 
                    id="description" 
                    rows="3"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >{{ old('description', $command->description) }}</textarea>

                <p class="text-xs text-gray-500 mt-1">
                    (Opcjonalnie) Opisz, co robią te komendy.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-t border-gray-200 pt-6 mt-8 space-y-3 sm:space-y-0">
                <p class="text-xs text-gray-500">
                    Ostatnia edycja: 
                    <span class="font-medium text-gray-700">{{ $command->updated_at->diffForHumans() }}</span> 
                    przez <span class="font-medium text-gray-700">{{ $command->user->full_name ?? 'System' }}</span>
                </p>

                <div class="flex gap-3">
                    <a 
                        href="{{ route('actions.index') }}" 
                        class="px-4 py-2 bg-white text-gray-700 rounded-md shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 transition"
                    >
                        Wróć
                    </a>

                    <button 
                        type="submit" 
                        class="px-5 py-2 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700 transition"
                    >
                        Zapisz zmiany
                    </button>
                </div>
            </div>
        </form>
    </div>
@endcomponent

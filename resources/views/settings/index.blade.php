@component('layouts.app', ['header' => 'Ustawienia Aplikacji'])

    <div class="max-w-4xl mx-auto">
        
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">Błąd!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="bg-white shadow-lg rounded-xl border border-gray-200 overflow-hidden">
            
            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                <div class="p-6 space-y-6">
                    @forelse ($settings as $setting)
                        <div>

                            <x-input-label 
                                for="setting-{{ $setting->key }}" 
                                :value="__(Str::title(str_replace('_', ' ', $setting->key)))" 
                            />
                            
                            <x-text-input 
                                type="text"
                                id="setting-{{ $setting->key }}"
                                name="settings[{{ $setting->key }}]"
                                class="block mt-1 w-full" 
                                value="{{ old('settings.' . $setting->key, $setting->value) }}"
                            />
                            
                            <x-input-error :messages="$errors->get('settings.' . $setting->key)" class="mt-2" />
                            
                            @if ($setting->key === 'backup_schedule_time')
                                <p class="mt-2 text-sm text-gray-500">
                                    Podaj godzinę w formacie HH:MM (np. 03:00)
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-500">Nie znaleziono żadnych ustawień w bazie danych.</p>
                    @endforelse
                </div>

                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end">
                    <x-primary-button type="submit">
                        Zapisz Ustawienia
                    </x-primary-button>
                </div>
            </form>

        </div>
    </div>

@endcomponent
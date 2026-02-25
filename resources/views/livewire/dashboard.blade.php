<div>
    <h3 class="text-lg font-semibold text-gray-600 mb-6">Witaj, {{ auth()->user()->full_name }}!</h3>

    <div class="grid gap-6 mb-8 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">


        <a href="{{ route('devices.index') }}" wire:navigate class="flex items-center p-4 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-150">
            <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full">
                <i class="fa-solid fa-server fa-xl"></i>
            </div>
            <div>
                <p class="mb-1 text-sm font-medium text-gray-600">
                    Wszystkie Urządzenia
                </p>
                <p class="text-3xl font-bold text-gray-700">
                    {{ $deviceCount }}
                </p>
            </div>
        </a>

        <div class="flex items-center p-4 bg-white rounded-lg shadow-lg">
             <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full">
                 <i class="fa-solid fa-circle-check fa-xl"></i> 
            </div>
            <div>
                <p class="mb-1 text-sm font-medium text-gray-600">
                    Online
                </p>
                <p class="text-3xl font-bold text-green-600"> 
                    {{ $devicesOnline }} 
                </p>
            </div>
        </div>

        <div class="flex items-center p-4 bg-white rounded-lg shadow-lg">
            <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full">
                 <i class="fa-solid fa-circle-exclamation fa-xl"></i> 
            </div>
            <div>
                <p class="mb-1 text-sm font-medium text-gray-600">
                    Offline
                </p>
                <p class="text-3xl font-bold text-orange-600"> 
                    {{ $devicesOffline }} 
                </p>
            </div>
        </div>
        
        <div class="flex items-center p-4 bg-white rounded-lg shadow-lg">
            <div class="p-3 mr-4 text-gray-500 bg-gray-100 rounded-full">
                <i class="fa-solid fa-circle-question fa-xl"></i> 
            </div>
            <div>
                <p class="mb-1 text-sm font-medium text-gray-600">
                    Status Nieznany
                </p>
                <p class="text-3xl font-bold text-gray-500"> 
                    {{ $devicesUnknown }} 
                </p>
            </div>
        </div>

        <div class="flex items-center p-4 bg-white rounded-lg shadow-lg">
             <div class="p-3 mr-4 text-red-500 bg-red-100 rounded-full">
                 <i class="fa-solid fa-triangle-exclamation fa-xl"></i> 
            </div>
            <div>
                <p class="mb-1 text-sm font-medium text-gray-600">
                    Błędy (24h)
                </p>
                <p class="text-3xl font-bold text-red-600"> 
                    {{ $errorsLast24h }} 
                </p>
            </div>
        </div>


        <a href="{{ route('audit-log.index') }}" wire:navigate class="flex items-center p-4 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-150">
            <div class="p-3 mr-4 text-indigo-500 bg-indigo-100 rounded-full">
                <i class="fa-solid fa-clipboard-list fa-xl"></i>
            </div>
            <div>
                <p class="mb-1 text-sm font-medium text-gray-600">
                    Wszystkich Zdarzeń
                </p>
                <p class="text-3xl font-bold text-gray-700">
                    {{ $logsCount }}
                </p>
            </div>
        </a>

    </div>

    <div class="p-6 bg-white rounded-lg shadow-lg">
        <h4 class="text-lg font-semibold text-gray-700 mb-4">Ostatnia Aktywność</h4>
        <p class="text-gray-500 text-sm">Wkrótce pojawi się tutaj lista ostatnich zdarzeń z dziennika audytowego...</p>
    </div>

</div>
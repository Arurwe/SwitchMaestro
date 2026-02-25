<aside class="z-20 flex-shrink-0 hidden w-64 overflow-y-auto bg-white shadow-lg lg:block">
    <div class="py-4 text-gray-500">

        <a class="ml-6 text-lg font-bold text-gray-800" href="{{ route('dashboard') }}">
            SwitchMaestro 🎶
        </a>

        <ul class="mt-6">

            <li class="relative px-6 py-3">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="inline-flex items-center w-full">
                    <span class="ml-4">Dashboard</span>
                </x-nav-link>
            </li>

            @canany(['devices:view', 'groups:view'])
            <li class="relative px-6 py-2 mt-4">
                <span class="ml-2 text-xs font-semibold text-gray-400 uppercase">Zarządzanie Flotą</span>
            </li>
            @can('devices:view')
            <li class="relative px-6 py-3">
                <x-nav-link :href="route('devices.index')" :active="request()->routeIs('devices.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Urządzenia</span>
                </x-nav-link>
            </li>
            @endcan
            @can('groups:view')
            <li class="relative px-6 py-3">
                <x-nav-link :href="route('device-groups.index')" :active="request()->routeIs('device-groups.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Grupy Urządzeń</span>
                </x-nav-link>
            </li>
            @endcan
            
            <li class="relative px-6 py-3">
                <x-nav-link :href="route('vlans.explorer')" :active="request()->routeIs('vlans.explorer')" class="inline-flex items-center w-full">
                    <span class="ml-4">VLANy</span>
                </x-nav-link>
            </li>
            @endcanany 

            <li class="relative px-6 py-3">
                <x-nav-link :href="route('topology.map')" :active="request()->routeIs('topology.map')" class="inline-flex items-center w-full">
                    <span class="ml-4">Topologia sieci</span>
                </x-nav-link>
            </li>
            <li class="relative px-6 py-3">
                <x-nav-link :href="route('topology.vlan.index')" :active="request()->routeIs('topology.vlan.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Topologia sieci - vlany</span>
                </x-nav-link>
            </li>
            @canany(['backups:view', 'auditlog:view', 'commands:run:readonly'])
             <li class="relative px-6 py-2 mt-4">
                <span class="ml-2 text-xs font-semibold text-gray-400 uppercase">Operacje</span>
            </li>
            @can('backups:view')
            <li class="relative px-6 py-3">
                <x-nav-link :href="route('backups.index')" :active="request()->routeIs('backups.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Backupy Konfiguracji</span>
                </x-nav-link>
            </li>
            @endcan

            @endcanany


            @canany(['credentials:manage', 'devicetypes:manage'])
            <li class="relative px-6 py-2 mt-4">
                <span class="ml-2 text-xs font-semibold text-gray-400 uppercase">Konfiguracja Systemu</span>
            </li>
            @can('credentials:manage')
            <li class="relative px-6 py-3">
                <x-nav-link :href="route('credentials.index')" :active="request()->routeIs('credentials.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Poświadczenia</span>
                </x-nav-link>
            </li>
            @endcan
            @can('devicetypes:manage') 
            <li class="relative px-6 py-3">
                <x-nav-link :href="route('device-types.index')" :active="request()->routeIs('device-types.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Typy Urządzeń</span>
                </x-nav-link>
            </li>
            @endcan
            @endcanany
            @can('auditlog:view')
            <li class="relative px-6 py-3">
                <x-nav-link :href="route('audit-log.index')" :active="request()->routeIs('audit-log.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Dziennik Zdarzeń</span>
                </x-nav-link>
            </li>
            <li class="relative px-6 py-3">
                <x-nav-link :href="route('jobs.index')" :active="request()->routeIs('jobs.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Monitor Zadań</span>
                </x-nav-link>
            </li>
            @endcan
            @can('users:manage')
            <li class="relative px-6 py-2 mt-4">
                <span class="ml-2 text-xs font-semibold text-gray-400 uppercase">Administracja</span>
            </li>
            <li class="relative px-6 py-3">
                 <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Użytkownicy</span>
                </x-nav-link>
            </li>

            <li class="relative px-6 py-3">
                 <x-nav-link :href="route('actions.index')" :active="request()->routeIs('actions.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Akcje</span>
                </x-nav-link>
            </li>

            <li class="relative px-6 py-3">
                 <x-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.index')" class="inline-flex items-center w-full">
                    <span class="ml-4">Ustawienia</span>
                </x-nav-link>
            </li>
            @endcan

        </ul>
    </div>
</aside>
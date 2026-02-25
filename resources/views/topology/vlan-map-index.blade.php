<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Eksplorator Topologii VLAN</h2>
                <p class="mb-4 text-gray-600">
                    Wybierz VLAN, aby zobaczyć jego logiczną topologię (połączenia trunk)
                    oraz urządzenia, które posiadają w nim porty dostępowe (access).
                </p>

                <form id="vlanSelectForm" method="GET" action="#">
                    <div class="max-w-md">
                        <label for="vlan_select" class="block text-sm font-medium text-gray-700">Wybierz VLAN:</label>
                        <select id="vlan_select" name="vlan_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">-- Wybierz --</option>
                            @foreach($vlans as $vlan)
                                <option value="{{ $vlan->id }}">
                                    {{ $vlan->vlan_id }} ({{ $vlan->name ?? 'Brak nazwy' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button class="mt-4">Pokaż Mapę</x-primary-button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('vlanSelectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const vlanId = document.getElementById('vlan_select').value;
            if (vlanId) {
                const url = `/topology/vlan/${vlanId}`;
                window.location.href = url;
            }
        });
    </script>
    @endpush
</x-app-layout>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Topologia dla VLAN: {{ $vlan->vlan_id }} ({{ $vlan->name }})
        </h2>
    </x-slot>

    <script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <style type="text/css">
        #network-map {
            width: 100%;
            height: 75vh;
            border: 1px solid #d1d5db;
            background-color: #f9fafb;
            border-radius: 0.5rem;
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('topology.vlan.index') }}" 
                   class="text-sm text-blue-600 hover:underline">
                   &larr; Wróć do wyboru VLAN
                </a>
            </div>
            
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div id="network-map"></div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            const nodes = new vis.DataSet(@json($nodes_json));
            const edges = new vis.DataSet(@json($edges_json));
            const container = document.getElementById('network-map');
            const data = { nodes: nodes, edges: edges };

            const options = {
                groups: {
                    trunk_only: {
                        color: { background: '#DB2777', border: '#831843' },
                        shape: 'dot',
                        size: 25,
                    },
                    access_only: {
                        color: { background: '#2563EB', border: '#1E40AF' },
                        shape: 'square',
                        size: 20,
                    },
                    hybrid: {
                        color: { background: '#F59E0B', border: '#B45309' },
                        shape: 'dot',
                        size: 25,
                    }
                },
                
                nodes: {
                    font: { size: 14, color: '#1f2937' },
                    borderWidth: 2
                },
                edges: {
                    arrows: { to: { enabled: false } },
                    color: { color: '#6b7280', highlight: '#4F46E5', hover: '#4F46E5' },
                    font: { size: 10, align: 'top' },
                    smooth: { type: 'dynamic' }
                }
            };

            const network = new vis.Network(container, data, options);
            
            network.on("stabilizationIterationsDone", function () {
                network.setOptions({ physics: false });
                network.fit();
            });
        });
    </script>
    @endpush
</x-app-layout>
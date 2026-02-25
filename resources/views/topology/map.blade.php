<x-app-layout>
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

    <div class="mb-4">
        <a href="{{ route('topology.map') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase hover:bg-gray-700 active:bg-gray-900">
            Odśwież Mapę
        </a>

        <button id="saveJson"
            class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            Eksportuj JSON
        </button>

        <button id="savePdf"
            class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700">
            Zapisz jako PDF
        </button>
        <button id="saveImage"
            class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Zapisz mapę jako obraz
        </button>
            @if(Request::is('topology/map-full') || Request::is('topology/map-full/*'))
                <a href="{{ route('topology.map') }}" 
                   class="mt-3 sm:mt-0 w-full sm:w-auto text-center px-4 py-2 bg-blue-600 text-white rounded-lg shadow-md hover:bg-blue-700 transition duration-300 ease-in-out">
                    Pokaż tylko zarządzane
                </a>
            @else
                <a href="{{ route('topology.map.full') }}" 
                   class="mt-3 sm:mt-0 w-full sm:w-auto text-center px-4 py-2 bg-green-600 text-white rounded-lg shadow-md hover:bg-green-700 transition duration-300 ease-in-out">
                    Pokaż pełną mapę (z nieznanymi)
                </a>
            @endif
    </div>

    <div id="network-map"></div>
@push('scripts')
<script type="text/javascript">
    let network, nodes, edges;

    document.addEventListener('DOMContentLoaded', function () {

        nodes = new vis.DataSet(@json($nodes_json));
        edges = new vis.DataSet(@json($edges_json));

        const container = document.getElementById('network-map');
        const data = { nodes: nodes, edges: edges };

        const options = {
            layout: { hierarchical: false },
            edges: {
                arrows: { to: { enabled: true, scaleFactor: 0.7 } },
                color: { color: '#6b7280', highlight: '#4F46E5', hover: '#4F46E5' },
                font: { size: 10, align: 'top' },
                smooth: { type: 'dynamic' }
            },
            nodes: {
                shape: 'dot',
                size: 20,
                font: { size: 14, color: '#1f2937' },
                borderWidth: 2
            },
            physics: {
                forceAtlas2Based: {
                    gravitationalConstant: -50,
                    centralGravity: 0.01,
                    springLength: 100,
                    springConstant: 0.08
                },
                maxVelocity: 50,
                solver: 'forceAtlas2Based',
                timestep: 0.5,
                stabilization: { iterations: 150 }
            },
            interaction: { hover: true, tooltipDelay: 200 }
        };

        network = new vis.Network(container, data, options);

        network.on("stabilizationIterationsDone", function () {
            network.setOptions({ physics: false });
            network.fit();
        });
    });


    document.addEventListener('click', function (e) {
    if (e.target.id === 'saveImage') {

        const srcCanvas = network.canvas.frame.canvas;

        const exportCanvas = document.createElement("canvas");
        exportCanvas.width = srcCanvas.width;
        exportCanvas.height = srcCanvas.height;

        const ctx = exportCanvas.getContext("2d");

        ctx.fillStyle = "#FFFFFF";
        ctx.fillRect(0, 0, exportCanvas.width, exportCanvas.height);

        ctx.drawImage(srcCanvas, 0, 0);

        const dataURL = exportCanvas.toDataURL("image/png");

        const link = document.createElement('a');
        link.href = dataURL;
        link.download = 'topologia.png';
        link.click();
    }
});

document.addEventListener('click', function (e) {
    if (e.target.id === 'savePdf') {

        if (!window.jspdf) {
            alert("jsPDF nie został załadowany");
            return;
        }

        const { jsPDF } = window.jspdf;
        const srcCanvas = network.canvas.frame.canvas;

        const exportCanvas = document.createElement("canvas");
        exportCanvas.width = srcCanvas.width;
        exportCanvas.height = srcCanvas.height;

        const ctx = exportCanvas.getContext("2d");
        ctx.fillStyle = "#FFFFFF";
        ctx.fillRect(0, 0, exportCanvas.width, exportCanvas.height);
        ctx.drawImage(srcCanvas, 0, 0);

        const imgData = exportCanvas.toDataURL("image/png");

        const pdf = new jsPDF({
            orientation: 'landscape',
            unit: 'px',
            format: 'a4'
        });

        pdf.addImage(imgData, 'PNG', 10, 10, 820, 580);
        pdf.save('topologia.pdf');
    }
});


    document.addEventListener('click', function (e) {
        if (e.target.id === 'saveJson') {
            const exportData = {
                nodes: nodes.get(),
                edges: edges.get()
            };

            const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: "application/json" });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'topologia.json';
            link.click();
        }
    });
</script>
@endpush


</x-app-layout>

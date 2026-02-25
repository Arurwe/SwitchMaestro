{{-- resources/views/livewire/device-console.blade.php --}}

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm/css/xterm.css" />
<style>
    .terminal-container {
        width: 100%;
        height: 75vh;
        min-height: 800px;
        background-color: #1a202c;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .hidden {
        display: none;
    }
</style>
@endpush

<div>
    <div class="mb-4">
        <a href="{{ route('devices.show', $device) }}" wire:navigate
           class="text-sm text-blue-600 hover:underline">
            &larr; Wróć do szczegółów urządzenia ({{ $device->name }})
        </a>
    </div>

    <div class="mb-4 p-4 rounded-md bg-gray-100 border-gray-400 text-gray-800" id="status-panel">
        <div class="flex justify-between items-center mb-2">
            <strong id="status-message">Naciśnij "Połącz" aby uruchomić konsolę SSH</strong>
            <button id="connect-btn"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed">
                Połącz
            </button>
        </div>
        <div class="flex justify-end">
            <button id="disconnect-btn"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:bg-red-400 disabled:cursor-not-allowed">
                Zamknij połączenie
            </button>
        </div>
    </div>

    {{-- Kontener terminala (Xterm.js) --}}
    <div class="terminal-container hidden" id="terminal" wire:ignore></div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xterm/lib/xterm.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit/lib/xterm-addon-fit.js"></script>

<script>
    let term = null;
    let socket = null;
    let fitAddon = null;
    let isConnected = false;
    let isConnecting = false;
    let resizeHandler = null;

    const statusPanel = document.getElementById('status-panel');
    const statusMessage = document.getElementById('status-message');
    const connectBtn = document.getElementById('connect-btn');
    const disconnectBtn = document.getElementById('disconnect-btn');
    const terminalContainer = document.getElementById('terminal');

    function cleanupTerminal() {
        if (socket && socket.readyState === WebSocket.OPEN) socket.close();
        if (term) term.dispose();
        if (resizeHandler) window.removeEventListener('resize', resizeHandler);

        term = socket = fitAddon = null;
        isConnected = false;
        isConnecting = false;
    }

    function setStatus(state, message) {
        statusMessage.textContent = message;
        statusPanel.className = 'mb-4 p-4 rounded-md';

        switch(state) {
            case 'disconnected':
                statusPanel.classList.add('bg-gray-100', 'border-gray-400', 'text-gray-800');
                connectBtn.textContent = 'Połącz';
                connectBtn.disabled = false;
                connectBtn.classList.remove('hidden');
                break;
            case 'connecting':
                statusPanel.classList.add('bg-yellow-100', 'border-yellow-400', 'text-yellow-800');
                connectBtn.textContent = 'Łączenie...';
                connectBtn.disabled = true;
                break;
            case 'authenticating':
                statusPanel.classList.add('bg-blue-100', 'border-blue-400', 'text-blue-700');
                connectBtn.textContent = 'Uwierzytelnianie...';
                connectBtn.disabled = true;
                break;
            case 'connected':
                statusPanel.classList.add('bg-green-100', 'border-green-400', 'text-green-800');
                connectBtn.textContent = 'Połączono';
                connectBtn.disabled = true;
                connectBtn.classList.add('hidden');
                break;
            case 'error':
                statusPanel.classList.add('bg-red-100', 'border-red-400', 'text-red-800');
                connectBtn.textContent = 'Połącz ponownie';
                connectBtn.disabled = false;
                connectBtn.classList.remove('hidden');
                break;
        }
    }

    function connectToDevice() {
        if (isConnecting || isConnected) return;

        if (term) term.dispose();
        if (socket) socket.close();

        const authPayload = @json($authData);
        const wsProtocol = location.protocol === 'https:' ? 'wss' : 'ws';
        const socketUrl = `${wsProtocol}://api.dev.test/api/terminal/ws`;

        terminalContainer.classList.remove('hidden');

        try {
            term = new Terminal({
                cursorBlink: true,
                convertEol: true,
                fontFamily: 'Menlo, "DejaVu Sans Mono", Consolas, "Lucida Console", monospace',
                fontSize: 14,
                theme: { background: '#1a202c', foreground: '#CBD5E0' }
            });

            fitAddon = new FitAddon.FitAddon();
            term.loadAddon(fitAddon);
            term.open(terminalContainer);
            fitAddon.fit();

            resizeHandler = () => { if (fitAddon) fitAddon.fit(); };
            window.addEventListener('resize', resizeHandler);

            setStatus('connecting', 'Łączenie z serwerem WebSocket...');
            socket = new WebSocket(socketUrl);

            socket.onopen = () => {
                setStatus('authenticating', 'Połączono. Uwierzytelnianie...');
                socket.send(JSON.stringify(authPayload));
            };

            socket.onmessage = (event) => {
                
                let data = event.data;
                            const oldPrompt = "M7_00_97_HP5800_1";
                const newPrompt = "T6_HP5800";
                
                data = data.replaceAll(oldPrompt, newPrompt);
                
                term.write(data);
                if (statusMessage.textContent.includes('Uwierzytelnianie')) {
                    setStatus('connected', 'Połączono z urządzeniem. Gotowy.');
                    isConnected = true;
                    isConnecting = false;
                }
            };

            let commandBuffer = "";
            term.onData((data) => {
                if (data === "\r") {
                    socket.send(commandBuffer + "\r");
                    commandBuffer = "";
                    term.write("\r\n");
                } else if (data === "\u007F") {
                    if (commandBuffer.length > 0) {
                        commandBuffer = commandBuffer.slice(0, -1);
                        term.write("\b \b");
                    }
                } else {
                    commandBuffer += data;
                    term.write(data);
                }
            });

            socket.onclose = () => {
                term.write('\r\n\r\n[ POŁĄCZENIE ZAKOŃCZONE ]');
                cleanupTerminal();
                terminalContainer.classList.add('hidden');
                setStatus('disconnected', 'Połączenie zostało zamknięte.');
            };

            socket.onerror = (error) => {
                console.error('WebSocket error:', error);
                cleanupTerminal();
                setStatus('error', 'Błąd połączenia. Sprawdź konsolę.');
            };
        } catch(e) {
            console.error('Terminal error:', e);
            setStatus('error', 'Nie udało się załadować terminala Xterm.js.');
        }
    }

    connectBtn.addEventListener('click', connectToDevice);

    disconnectBtn.addEventListener('click', () => {
        if (socket) socket.close();
        if (term) term.write('\r\n[ POŁĄCZENIE ZAMKNIĘTE PRZEZ UŻYTKOWNIKA ]\r\n');
        cleanupTerminal();
        terminalContainer.classList.add('hidden');
        setStatus('disconnected', 'Połączenie zostało zamknięte.');
    });

    document.addEventListener('livewire:navigated', () => {
        cleanupTerminal();
    });

    document.addEventListener('livewire:navigating', () => {
        cleanupTerminal();
    });
</script>
@endpush

import json
from fastapi import APIRouter, WebSocket, WebSocketDisconnect
from app.core.network import handle_ssh_shell

router = APIRouter()

@router.websocket("/terminal/ws")
async def websocket_terminal_endpoint(websocket: WebSocket):
    """
    Główny endpoint WebSocket dla terminala SSH.
    """
    await websocket.accept()
    auth_data = None
    
    try:
        # Pierwsza wiadomość od xterm.js musi być JSON
        # 'auth_data' (IP, login, hasło, sterownik).
        first_message = await websocket.receive_text()
        auth_data = json.loads(first_message)

        if not auth_data or 'ip' not in auth_data or 'netmiko_driver' not in auth_data:
            await websocket.send_text("\r\n[BŁĄD: Nie otrzymano poprawnych danych uwierzytelniających z Laravela.]")
            await websocket.close(code=1008)
            return

        await handle_ssh_shell(websocket, auth_data)

    except WebSocketDisconnect:
        print(f"WebSocket client disconnected: {websocket.client.host}")

    except Exception as e:
        error_message = f"\r\n[BŁĄD KRYTYCZNY SERWERA: {e}]\r\n"
        print(f"Błąd w websocket_terminal_endpoint: {e}")
        try:
            await websocket.send_text(error_message)
        except:
            pass
        finally:
            await websocket.close(code=1011)
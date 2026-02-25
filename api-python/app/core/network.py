import time
from typing import Callable, List, Optional
from netmiko import ConnectHandler
from netmiko.exceptions import NetmikoTimeoutException, NetmikoAuthenticationException
from app.core import network_special
import asyncio
from fastapi import WebSocket, WebSocketDisconnect

def test_device_connection(
    driver: str, 
    ip: str, 
    port: int,
    username: str, 
    password: str, 
    secret: str | None = None
) -> str: 
    """
    Nawiązuje połączenie, pobiera znak zachęty i zamyka połączenie.
    """
    device = {
        "device_type": driver,
        "host": ip,
        "port": port,
        "username": username,
        "password": password,
        "secret": secret,
        "fast_cli": False,
    }

    try:
        with ConnectHandler(**device) as net_connect:
            prompt = net_connect.find_prompt()
            
        return prompt
            
    except NetmikoAuthenticationException as e:
        raise PermissionError(f"Błąd uwierzytelniania: {e}")
    except NetmikoTimeoutException as e:
        raise ConnectionError(f"Błąd timeoutu połączenia: {e}")
    except Exception as e:
        raise ConnectionError(f"Nieoczekiwany błąd połączenia: {e}")

    
def dispatch_network_command_execution(
    driver: str,
    ip: str,
    port: int,
    username: str,
    password: str,
    secret: Optional[str],
    commands: List[str],
    auth_data: Optional[dict] = None
) -> str:
    """
    Router: wybiera właściwy executor dla danego 'driver'.
    - Jeśli istnieje specjalny handler (np. 'hp_comware_buggy'), wywołuje go.
    - W przeciwnym razie używa standardowego run_commands_on_device.
    """

    if driver == "hp_comware_buggy":
        device = {
            "device_type": "hp_comware",
            "host": ip,
            "port": port,
            "username": username,
            "password": password,
            "secret": secret,
        }
        return network_special.run_commands_hp_comware_buggy(device=device, commands=commands)

    return run_commands_on_device(
        driver=driver,
        ip=ip,
        port=port,
        username=username,
        password=password,
        secret=secret,
        commands=commands
    )

def run_commands_on_device(
    driver: str, 
    ip: str, 
    port: int, 
    username: str, 
    password: str, 
    secret: str | None,
    commands: list[str]
) -> str:
    """
    Nawiązuje połączenie z urządzeniem i wykonuje listę komend.
    Zwraca SUROWY output kompatybilny z NTC Templates.
    """
    
    device = {
        "device_type": driver,
        "host": ip,
        "port": port,
        "username": username,
        "password": password,
        "secret": secret,
    }

    final_output = ""

    try:
        with ConnectHandler(**device) as net_connect:
            if secret:
                net_connect.enable()

            for command in commands:
                output = net_connect.send_command(
                    command,
                    strip_command=True,
                    strip_prompt=True
                )

                final_output += output + "\n"

            net_connect.disconnect()

        return final_output.strip()

    except (NetmikoAuthenticationException, NetmikoTimeoutException) as e:
        raise ConnectionError(f"Błąd połączenia podczas wykonywania komend: {e}")
    except Exception as e:
        raise RuntimeError(f"Błąd wykonania komendy: {e}")



async def _websocket_to_ssh(websocket: WebSocket, ssh_connection, driver):
    buffer = ""
    try:
        while True:
            data = await websocket.receive_text()

            if driver == "hp_comware_buggy":
                if data == "\r":  # Enter
                    await websocket.send_text("\r\n")
                    output = network_special.run_commands_hp_comware_buggy(
                        device=ssh_connection, commands=[buffer.strip()]
                    )
                    await websocket.send_text(output + "\r\n")
                    buffer = ""
                elif data in ("\x7f", "\x08"):  # Backspace
                    buffer = buffer[:-1]
                    await websocket.send_text("\b \b")
                else:
                    buffer += data
                    await websocket.send_text(data)
            else:
                ssh_connection.remote_conn.send(data)
                
    except Exception:
        pass


async def _ssh_to_websocket(websocket: WebSocket, ssh_connection, driver):
    """
    Ciągle czyta surowe dane z SSH i przesyła do przeglądarki.
    """
    if driver == "hp_comware_buggy":
        return # 

    try:
        remote_conn = ssh_connection.remote_conn
        remote_conn.setblocking(0)

        while True:
            if remote_conn.recv_ready():
                data = remote_conn.recv(4096).decode('utf-8', 'ignore')
                if data:
                    await websocket.send_text(data)
            await asyncio.sleep(0.05)
    except Exception as e:
        print(f"SSH Output Error: {e}")

async def handle_ssh_shell(websocket: WebSocket, auth_data: dict):
    """
    Główny handler WebSocket <-> SSH dla terminala.
    """
    driver = auth_data.get("netmiko_driver")
    host = auth_data["ip"]
    port = auth_data.get("port", 22)
    username = auth_data["username"]
    password = auth_data["password"]
    secret = auth_data.get("secret")

    ssh_connection = None
    try:
        if driver == "hp_comware_buggy":
            ssh_connection = {
                "device_type": "hp_comware",
                "host": host,
                "port": port,
                "username": username,
                "password": password,
                "secret": secret,
            }
        else:
            device_config = {
                "device_type": driver,
                "host": host,
                "port": port,
                "username": username,
                "password": password,
                "secret": secret,
                "global_delay_factor": 0.5,
            }
            ssh_connection = ConnectHandler(**device_config)
            if secret:
                ssh_connection.enable()
            ssh_connection.remote_conn.setblocking(0)
        await websocket.send_text(f"Połączono z {host}.\n<>")

        task_ws_to_ssh = asyncio.create_task(_websocket_to_ssh(websocket, ssh_connection, driver))
        task_ssh_to_ws = asyncio.create_task(_ssh_to_websocket(websocket, ssh_connection, driver))

        await asyncio.wait(
            [task_ws_to_ssh, task_ssh_to_ws],
            return_when=asyncio.FIRST_COMPLETED
        )

    except Exception as e:
        error_message = f"\r\n[BŁĄD KRYTYCZNY SSH: {e}]\r\n"
        print(error_message)
        if websocket.client_state == 'CONNECTED':
            await websocket.send_text(error_message)

    finally:
        if driver != "hp_comware_buggy" and ssh_connection:
            ssh_connection.disconnect()
        print(f"Sesja SSH dla {host} została zamknięta.")
        if websocket.client_state != 'DISCONNECTED':
            await websocket.close()
        print(f"Połączenie WebSocket dla {host} zostało zamknięte.")



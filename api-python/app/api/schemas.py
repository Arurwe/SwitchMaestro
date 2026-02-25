from pydantic import BaseModel, Field
from typing import Optional, Any, List, Dict


class AuthData(BaseModel):
    """
    Ustandaryzowany model dla odszyfrowanych danych logowania.
    Wysyłany przez Laravela.
    """
    netmiko_driver: str = Field(..., description="Sterownik Netmiko, np. 'cisco_ios'")
    ip: str = Field(..., description="Adres IP urządzenia")
    port: int = Field(..., description="Port SSH/Telnet")
    username: str
    password: str
    secret: Optional[str] = None



class TestConnectionRequest(BaseModel):
    """
    Payload dla synchronicznego endpointu /test-connection.
    """
    initiator_user_id: int
    device_db_id: Optional[int] = None
    auth_data: AuthData

class RefreshDataRequest(BaseModel):
    """
    Payload dla asynchronicznego endpointu /refresh-data.
    """
    initiator_user_id: int
    auth_data: AuthData

class RunActionRequest(BaseModel):
    """
    Payload dla asynchronicznego endpointu /run-action.
    """
    initiator_user_id: int
    auth_data: AuthData

# --- Schematy Odpowiedzi ---

class TaskResponse(BaseModel):
    """
    Standardowa odpowiedź po pomyślnym zakolejkowaniu zadania Celery.
    """
    message: str
    celery_task_id: str

class TestConnectionResponse(BaseModel):
    """
    Odpowiedź dla synchronicznego endpointu /test-connection.
    """
    status: str
    message: str
    prompt: Optional[str] = None

class RunCustomCommandsRequest(BaseModel):
    """
    Payload dla endpointu /run-custom-commands.
    """
    initiator_user_id: int
    auth_data: AuthData
    commands: list[str] = Field(..., min_length=1)

class BulkTaskItem(BaseModel):
    """
    Definiuje pojedyncze zadanie w paczce zbiorczej.
    Zawiera ID urządzenia i jego dane logowania.
    """
    device_id: int
    auth_data: AuthData

class BulkRequestBase(BaseModel):
    """
    Wspólna podstawa dla obu typów żądań zbiorczych.
    """
    batch_id: str
    initiator_user_id: Optional[int] = 1
    tasks: List[BulkTaskItem]

class BulkActionRequest(BulkRequestBase):
    """
    Payload dla /devices/bulk-run-action
    Wysyła predefiniowany action_slug.
    """
    action_slug: str

class BulkCustomCommandsRequest(BulkRequestBase):
    """
    Payload dla /devices/bulk-run-custom-commands
    Wysyła listę własnych komend.
    """
    commands: List[str]

class BulkTaskResponse(BaseModel):
    """
    Standardowa odpowiedź po zakolejkowaniu paczki zadań.
    """
    message: str
    batch_id: str
    tasks_created_count: int
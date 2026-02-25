from fastapi import APIRouter, HTTPException, Depends, Body
from sqlalchemy.orm import Session
from app.db.session import get_db
from app.db.models import Device, Action, User, Credential
import uuid
from app.tasks.ssh_tasks import run_action_on_device,run_custom_commands,run_custom_commands_on_device
from app.core.network import test_device_connection  
from app.api.schemas import (
    TestConnectionRequest, RefreshDataRequest, RunActionRequest,
    TaskResponse, TestConnectionResponse,RunCustomCommandsRequest,
    BulkActionRequest, 
    BulkCustomCommandsRequest, BulkTaskResponse
)

router = APIRouter()


@router.post("/device/test-connection", response_model=TestConnectionResponse)
def test_connection_with_payload(
    request_data: TestConnectionRequest = Body(...),
    db: Session = Depends(get_db)
):
    """
    Testowanie polaczenia synchro
    """
    try:
        auth = request_data.auth_data

        prompt = test_device_connection(
            driver=auth.netmiko_driver,
            ip=auth.ip,
            port=auth.port,
            username=auth.username,
            password=auth.password,
            secret=auth.secret
        )
        
        return TestConnectionResponse(
            status="success", 
            message=f"Połączenie pomyślne! Znak zachęty: {prompt}",
            prompt=prompt
        )
        
    except (ConnectionError, PermissionError) as e:
        raise HTTPException(status_code=400, detail={"error": str(e)})
    except Exception as e:
        raise HTTPException(status_code=500, detail={"error": f"Błąd serwera: {e}"})


@router.post("/device/{device_id}/refresh-data", response_model=TaskResponse)
def trigger_refresh_data(
    device_id: int,
    request_data: RefreshDataRequest = Body(...),
    db: Session = Depends(get_db)
):
    """
     endpoint do pobierania wszystkich danych po utworzeniu urządzenia.
    """
    device = db.query(Device).get(device_id)
    if not device:
        raise HTTPException(status_code=404, detail="Nie znaleziono urządzenia")

    action = db.query(Action).filter(Action.action_slug == 'get_all_diagnostics').first()
    if not action:
        raise HTTPException(status_code=404, detail="Krytyczny błąd: Nie znaleziono akcji 'get_all_diagnostics'. Uruchom seeder.")
    driver = device.driver_override or device.vendor.netmiko_driver
    request_data.auth_data.netmiko_driver = driver
    try:
        task = run_action_on_device.delay(
            device_id=device.id,
            action_id=action.id,
            user_id=request_data.initiator_user_id,
            auth_data=request_data.auth_data.model_dump() 
        )
        return TaskResponse(
            message="Zadanie odświeżania danych zostało zakolejkowane.",
            celery_task_id=task.id
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Błąd kolejkowania zadania Celery: {e}")


@router.post("/device/{device_id}/run-action/{action_slug}", response_model=TaskResponse)
def trigger_run_action(
    device_id: int,
    action_slug: str,
    request_data: RunActionRequest = Body(...),
    db: Session = Depends(get_db)
):
    """
     endpoint do uruchamiania dowolnej akcji.
    """
    device = db.query(Device).get(device_id)
    if not device:
        raise HTTPException(status_code=404, detail="Nie znaleziono urządzenia")

    action = db.query(Action).filter(Action.action_slug == action_slug).first()
    if not action:
        raise HTTPException(status_code=404, detail=f"Nie znaleziono akcji o nazwie '{action_slug}'.")
    driver = device.driver_override or device.vendor.netmiko_driver
    request_data.auth_data.netmiko_driver = driver
    try:
        task = run_action_on_device.delay(
            device_id=device.id,
            action_id=action.id,
            user_id=request_data.initiator_user_id,
            auth_data=request_data.auth_data.model_dump()
        )
        return TaskResponse(
            message=f"Zadanie '{action.name}' zostało zakolejkowane.",
            celery_task_id=task.id
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Błąd kolejkowania zadania Celery: {e}")
    
    
@router.post("/device/{device_id}/run-custom-commands", response_model=TaskResponse)
def trigger_run_custom_commands(
    device_id: int,
    request_data: RunCustomCommandsRequest = Body(...),
    db: Session = Depends(get_db)
):
    """
     endpoint do uruchamiania listy wlasnych komend
    """
    device = db.query(Device).get(device_id)
    if not device:
        raise HTTPException(status_code=404, detail="Nie znaleziono urządzenia")

    try:
        task = run_custom_commands.delay(
            device_id=device.id,
            user_id=request_data.initiator_user_id,
            auth_data=request_data.auth_data.model_dump(),
            commands_list=request_data.commands
        )
        return TaskResponse(
            message=f"Zadanie z własnymi komendami zostało zakolejkowane.",
            celery_task_id=task.id
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Błąd kolejkowania zadania Celery: {e}")
    

@router.post("/devices/bulk-run-action", response_model=BulkTaskResponse)
def trigger_bulk_run_action(
    request_data: BulkActionRequest = Body(...),
    db: Session = Depends(get_db)
):
    """
     endpoint do uruchamiania jednej akcji w bulk
    """
    action = db.query(Action).filter(Action.action_slug == request_data.action_slug).first()
    if not action:
        raise HTTPException(status_code=404, detail=f"Nie znaleziono akcji o nazwie '{request_data.action_slug}'.")
    device_ids = [t.device_id for t in request_data.tasks]
    if not device_ids:
        raise HTTPException(status_code=400, detail="Lista zadań (tasks) jest pusta.")

    existing_devices = db.query(Device.id).filter(Device.id.in_(device_ids)).all()
    existing_ids = {row[0] for row in existing_devices}
    missing_ids = sorted(set(device_ids) - existing_ids)

    if missing_ids:
        raise HTTPException(
            status_code=404,
            detail=f"Nie znaleziono urządzeń o ID: {missing_ids}"
        )
    
    tasks_created_count = 0
    try:
        for task_item in request_data.tasks:
            run_action_on_device.delay(
                device_id=task_item.device_id,
                action_id=action.id,
                user_id=request_data.initiator_user_id,
                auth_data=task_item.auth_data.model_dump(),
                batch_id=request_data.batch_id
            )
            tasks_created_count += 1
            
        return BulkTaskResponse(
            message=f"Pomyślnie zakolejkowano {tasks_created_count} zadań.",
            batch_id=request_data.batch_id,
            tasks_created_count=tasks_created_count
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Błąd kolejkowania zadań zbiorczych: {e}")



@router.post("/devices/bulk-run-custom-commands", response_model=BulkTaskResponse)
def trigger_bulk_run_custom_commands(
    request_data: BulkCustomCommandsRequest = Body(...),
    db: Session = Depends(get_db)
):
    """
     endpoint do uruchamiania komend w bulk
    """
    device_ids = [t.device_id for t in request_data.tasks]
    if not device_ids:
        raise HTTPException(status_code=400, detail="Lista zadań (tasks) jest pusta.")

    existing_devices = db.query(Device.id).filter(Device.id.in_(device_ids)).all()
    existing_ids = {row[0] for row in existing_devices}
    missing_ids = sorted(set(device_ids) - existing_ids)
    if missing_ids:
        raise HTTPException(
            status_code=404,
            detail=f"Nie znaleziono urządzeń o ID: {missing_ids}"
        )
    
    tasks_created_count = 0
    try:
        for task_item in request_data.tasks:
            run_custom_commands_on_device.delay(
                device_id=task_item.device_id,
                user_id=request_data.initiator_user_id,
                auth_data=task_item.auth_data.model_dump(),
                commands=request_data.commands,
                batch_id=request_data.batch_id
            )
            tasks_created_count += 1
            
        return BulkTaskResponse(
            message=f"Pomyślnie zakolejkowano {tasks_created_count} zadań (własne komendy).",
            batch_id=request_data.batch_id,
            tasks_created_count=tasks_created_count
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Błąd kolejkowania zadań zbiorczych: {e}")
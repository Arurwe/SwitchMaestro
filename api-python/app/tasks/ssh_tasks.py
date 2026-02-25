
import json
import re
from celery import shared_task
from sqlalchemy import func
from sqlalchemy.orm import Session
from app.db.session import SessionLocal
from app.db.models import (
    Device, Action, Command, TaskLog, ConfigurationBackup, 
    DevicePort, User, NetworkLink, Vlan, DeviceVlan, PortVlanMembership
)
from app.core.parsing import parse_command_output,parse_device_details
from app.core.network import dispatch_network_command_execution
from netmiko.exceptions import NetmikoAuthenticationException, NetmikoTimeoutException


#  GŁÓWNe FUNKCJE

@shared_task(name="tasks.run_custom_commands", bind=True)
def run_custom_commands(self, device_id: int, user_id: int, auth_data: dict, commands_list: list[str]):
    """
    Zadanie Celery do wykonywania wlasnych komend na urządzeniu.
    """
    db: Session = SessionLocal()
    log_entry = None
    device = None
    
    try:
        device = db.query(Device).get(device_id)
        if not device:
            raise ValueError("Nie znaleziono urządzenia w bazie danych.")

        log_entry = TaskLog(
            job_id=self.request.id,
            device_id=device.id,
            user_id=user_id,
            action_id=None,
            action="device:run-custom-commands",
            status='RUNNING',
            intention_prompt=f"Uruchomiono wlasne komendy na {device.name}"
        )
        db.add(log_entry)
        db.commit()

        raw_output = _execute_ssh_commands(auth_data, commands_list, log_entry)

        log_entry.status = 'success'
        log_entry.raw_output = raw_output
        device.status = 'online'
        db.commit()
        
        return f"Sukces: Wlasne komendy zostały wykonane."

    except (ConnectionError, NetmikoAuthenticationException, NetmikoTimeoutException, IOError, TimeoutError) as e:
        error_msg = f"Błąd połączenia: {str(e)}"
        if log_entry:
            log_entry.status = 'failed'
            log_entry.error_message = error_msg
            db.commit()
        if device:
            device.status = 'offline'
            db.commit()
        raise RuntimeError(error_msg) 
        
    except Exception as e:
        error_msg = f"Błąd: {str(e)}"
        if log_entry:
            log_entry.status = 'failed'
            log_entry.error_message = error_msg
            db.commit()
        raise RuntimeError(error_msg)
        
    finally:
        db.close()

@shared_task(name="tasks.run_custom_commands_on_device", bind=True)
def run_custom_commands_on_device(self, device_id: int, user_id: int, auth_data: dict, commands: list[str], batch_id: str | None = None):
    """
    Zadanie Celery do wykonywania wlasnych komend na urządzeniu.
    """
    db: Session = SessionLocal()
    log_entry = None
    device = None
    
    try:
        device = db.query(Device).get(device_id)
        if not device:
            raise ValueError("Nie znaleziono urządzenia w bazie danych.")

        log_entry = TaskLog(
            job_id=self.request.id,
            batch_id=batch_id,
            device_id=device.id,
            user_id=user_id,
            action_id=None,
            action="device:run-custom-commands",
            status='RUNNING',
            intention_prompt=f"Uruchomiono własne komendy na {device.name}"
        )
        db.add(log_entry)
        db.commit()

        raw_output = _execute_ssh_commands(auth_data, commands, log_entry)

        log_entry.status = 'success'
        log_entry.raw_output = raw_output
        device.status = 'online'
        db.commit()
        
        return f"Sukces: Własne komendy zostały wykonane."

    except (ConnectionError, NetmikoAuthenticationException, NetmikoTimeoutException, IOError, TimeoutError) as e:
        error_msg = f"Błąd połączenia: {str(e)}"
        if log_entry:
            log_entry.status = 'failed'
            log_entry.error_message = error_msg
            db.commit()
        if device:
            device.status = 'offline'
            db.commit()
        raise RuntimeError(error_msg) 
        
    except Exception as e:
        error_msg = f"Błąd: {str(e)}"
        if log_entry:
            log_entry.status = 'failed'
            log_entry.error_message = error_msg
            db.commit()
        raise RuntimeError(error_msg)
        
    finally:
        db.close()

@shared_task(name="tasks.run_action_on_device", bind=True)
def run_action_on_device(self, device_id: int, action_id: int, user_id: int, auth_data: dict, batch_id: str | None = None):
    """
    zadanie Celery do wykonywania akcji na urządzeniu
    """
    db: Session = SessionLocal()
    log_entry = None
    device = None
    
    try:
        # urzadzenei i akcja z bazy
        device = db.query(Device).get(device_id)
        action = db.query(Action).get(action_id)
        if not device or not action:
            raise ValueError("Nie znaleziono urządzenia lub akcji w bazie danych.")

        # runnig jako status w bazie
        log_entry = TaskLog(
            job_id=self.request.id,
            batch_id=batch_id,
            device_id=device.id,
            user_id=user_id,
            action_id=action.id,
            action=action.action_slug,
            status='RUNNING',
            intention_prompt=f"Uruchomiono akcję '{action.name}' na {device.name}"
        )
        db.add(log_entry)
        db.commit()

        # 3routing akcji
        result_message = ""
        action_slug = action.action_slug

        if action_slug == 'get_config_backup':
            result_message = _perform_backup(db, device, action, log_entry, auth_data)
        elif action_slug == 'get_interfaces':
            result_message = _perform_interface_sync(db, device, action, log_entry, auth_data)
        elif action_slug == 'get_vlans':
            result_message = _perform_vlan_sync(db, device, action, log_entry, auth_data)
        elif action_slug == 'get_lldp_neighbors':
            result_message = _perform_lldp_sync(db, device, action, log_entry, auth_data)
        elif action_slug == 'get_device_details':
            result_message = _perform_details_sync(db, device, action, log_entry, auth_data)
        elif action_slug == 'get_interfaces_full':
            result_message = _perform_interface_full_sync(db, device, action, log_entry, auth_data)        
        elif action_slug == 'get_all_diagnostics':
            # wszystkie akcje inicializujace urzadzenie
            _perform_backup(db, device, db.query(Action).filter(Action.action_slug == 'get_config_backup').first(), log_entry, auth_data)
            _perform_interface_sync(db, device, db.query(Action).filter(Action.action_slug == 'get_interfaces').first(), log_entry, auth_data)
            _perform_vlan_sync(db, device, db.query(Action).filter(Action.action_slug == 'get_vlans').first(), log_entry, auth_data)
            _perform_details_sync(db, device, db.query(Action).filter(Action.action_slug == 'get_device_details').first(), log_entry, auth_data)
            _perform_lldp_sync(db, device, db.query(Action).filter(Action.action_slug == 'get_lldp_neighbors').first(), log_entry, auth_data)
            _perform_interface_full_sync(db, device, db.query(Action).filter(Action.action_slug == 'get_interfaces_full').first(), log_entry, auth_data)

            result_message = "Pełna synchronizacja zakończona."
        else:
            result_message = _perform_generic_action(db, device, action, log_entry, auth_data)

        #   log  sukces
        log_entry.status = 'success'
        if  result_message:
             log_entry.system_info = result_message
        
        device.status = 'online'
        db.commit()
        
        return f"Sukces: {result_message}"

    except (ConnectionError, NetmikoAuthenticationException, NetmikoTimeoutException, IOError, TimeoutError) as e:
        error_msg = f"Błąd połączenia: {str(e)}"
        if log_entry:
            log_entry.status = 'failed'
            log_entry.error_message = error_msg
            db.commit()
        
        if device:
            device.status = 'offline'
            db.commit()
            
        raise RuntimeError(error_msg)
        
    except Exception as e:

        error_msg = f"Błąd: {str(e)}"
        if log_entry:
            log_entry.status = 'failed'
            log_entry.error_message = error_msg
            db.commit()
        raise RuntimeError(error_msg)
        
    finally:
        db.close()

#   FUNKCJE POMOCNICZE 

def _get_device_data_for_task(db: Session, device: Device, action: Action, auth_data: dict) -> tuple[dict, list[str]]:
    """
    pobieranie listy komend
    """
    
    #  Znajdź listę komend
    command_obj = db.query(Command).filter(
        Command.vendor_id == device.vendor_id,
        Command.action_id == action.id
    ).first()
    
    if not command_obj or not command_obj.commands:
        raise ValueError(f"Nie znaleziono komend dla akcji '{action.action_slug}' i vendora '{device.vendor.name}'.")
    
    return auth_data, command_obj.commands



def _execute_ssh_commands(auth_data: dict, commands_list: list[str], log_entry: TaskLog) -> str:
    """
    Wykonuje komendy SSH. Pobiera dane logowania z auth_data.
    """
    # Log – co poszlo na switcha
    log_entry.command_sent = "\n".join(commands_list)


    #  wykonanie poleceń routerowi vendorów
    raw_output = dispatch_network_command_execution(
        driver=auth_data["netmiko_driver"],
        ip=auth_data["ip"],
        port=auth_data["port"],
        username=auth_data["username"],
        password=auth_data["password"],
        secret=auth_data["secret"],
        commands=commands_list,
        auth_data=auth_data      
    )

    return raw_output

# --- FUNKCJE SPECJALISTYCZNE ---

def _perform_generic_action(db: Session, device: Device, action: Action, log_entry: TaskLog, auth_data: dict) -> str:
    """Obsługuje generyczne akcje, które tylko zapisują wynik do logów."""
    
    auth_data_with_creds, commands_list = _get_device_data_for_task(db, device, action, auth_data)
    raw_output = _execute_ssh_commands(auth_data_with_creds, commands_list, log_entry)
    parsed_output = parse_command_output(
        auth_data_with_creds['netmiko_driver'], 
        action.action_slug, 
        raw_output
    )
    print("PRASED:",parsed_output)
    
    # Zapisanie sparsowanego wynik do logów
    if isinstance(parsed_output, (dict, list)):
        log_entry.raw_output = json.dumps(parsed_output, indent=2, ensure_ascii=False)
    else:
        log_entry.raw_output = str(raw_output)
    

    db.commit()
    return f"Akcja '{action.name}' wykonana, wynik zapisany w logach."


def _perform_backup(db: Session, device: Device, action: Action, log_entry: TaskLog, auth_data: dict) -> str:
    """Pobiera backup i zapisuje go w tabeli 'configuration_backups'."""
    
    auth_data_with_creds, commands_list = _get_device_data_for_task(db, device, action, auth_data)
    raw_output = _execute_ssh_commands(auth_data_with_creds, commands_list, log_entry)

    # Zapisz backup
    new_backup = ConfigurationBackup(
        device_id=device.id,
        user_id=log_entry.user_id,
        configuration=raw_output
    )

    log_entry.raw_output = str(raw_output)
    db.add(new_backup)
    db.commit()
    return f"Konfiguracja dla '{device.name}' została pomyślnie zapisana."


def _perform_interface_sync(db: Session, device: Device, action: Action, log_entry: TaskLog, auth_data: dict) -> str:
    """Pobiera interfejsy, parsuje je i aktualizuje tabelę 'device_ports'."""
    
    auth_data_with_creds, commands_list = _get_device_data_for_task(db, device, action, auth_data)
    raw_output = _execute_ssh_commands(auth_data_with_creds, commands_list, log_entry)

    # Parsowanie
    parsed_data = parse_command_output(auth_data_with_creds['netmiko_driver'], action.action_slug, raw_output)
    
    if not isinstance(parsed_data, list):
        raise ValueError("Parsowanie interfejsów nie zwróciło listy.")

    # Synchronizacja z bazą
    updated_count = 0
    # Usuń stare porty dla tego urządzenia
    db.query(DevicePort).filter(DevicePort.device_id == device.id).delete(synchronize_session=False)

    for port_data in parsed_data:
        # Próba znalezienia uniwersalnego klucza dla nazwy portu
        port_name = port_data.get('interface')
        if not port_name:
            continue

        # Stwórz nowe porty
        new_port = DevicePort(
            device_id=device.id,
            name=port_name,
            status=port_data.get('link'),
            protocol_status=port_data.get('protocol'),
            description=port_data.get('description'),
            speed=port_data.get('speed'),
            duplex=port_data.get('duplex'),
        )
        db.add(new_port)
        updated_count += 1
    log_entry.raw_output = str(raw_output)        
    db.commit()
    return f"Zsynchronizowano {updated_count} portów dla '{device.name}'."

def _perform_interface_full_sync(db: Session, device: Device, action: Action, log_entry: TaskLog, auth_data: dict) -> str:
    """
    Pobiera pełne informacje o interfejsach i aktualizuje 'details'
    ORAZ synchronizuje członkostwo w VLAN-ach ('port_vlan_membership').
    """

    auth_data_with_creds, commands_list = _get_device_data_for_task(db, device, action, auth_data)
    raw_output = _execute_ssh_commands(auth_data_with_creds, commands_list, log_entry)
    log_entry.raw_output = str(raw_output)

    # Parsowanie
    parsed_data = parse_command_output(auth_data_with_creds['netmiko_driver'], action.action_slug, raw_output)
    
    if not isinstance(parsed_data, list):
        raise ValueError("Parsowanie pełnych interfejsów nie zwróciło listy.")

    global_vlan_cache = {v.vlan_id: v for v in db.query(Vlan).all()}
    updated_count = 0

    for port_data in parsed_data:
        port_name_short = normalize_interface_name(port_data.get('interface', ''))
        if not port_name_short:
            continue

        existing_port = db.query(DevicePort).filter(
            DevicePort.device_id == device.id,
            DevicePort.name == port_name_short
        ).first()

        if existing_port:
            # Aktualizacja 'details' (surowe dane z NTC)
            existing_port.details = port_data.get('details')
            
            vlan_info_from_parser = port_data.get('vlan_info') 
            
            # synchro portu
            _sync_port_vlans(db, existing_port, vlan_info_from_parser, global_vlan_cache)
            
            updated_count += 1

    db.commit()
    return f"Zaktualizowano szczegóły i VLAN-y {updated_count} portów dla '{device.name}'."

def _perform_vlan_sync(db: Session, device: Device, action: Action, log_entry: TaskLog, auth_data: dict) -> str:
    """
    Pobiera VLANy, parsuje je i aktualizuje:
    1. Globalną tabelę `vlans`.
    2. Tabelę pivot `device_vlan` (powiązanie Device <-> Vlan) wraz z 'type' i 'route_interface'.
    """
    
    auth_data_with_creds, commands_list = _get_device_data_for_task(db, device, action, auth_data)
    raw_output = _execute_ssh_commands(auth_data_with_creds, commands_list, log_entry)
    log_entry.raw_output = str(raw_output) 
    
    parsed_data = parse_command_output(auth_data_with_creds['netmiko_driver'], action.action_slug, raw_output)
    
    if not isinstance(parsed_data, list):
        raise ValueError("Parsowanie VLANów nie zwróciło listy.")

    global_vlan_cache = {v.vlan_id: v for v in db.query(Vlan).all()}
    
    old_device_vlan_links = db.query(DeviceVlan).filter(DeviceVlan.device_id == device.id).all()
    old_links_map = {link.vlan.vlan_id: link for link in old_device_vlan_links if link.vlan}
    
    seen_vlan_ids_from_parser = set()
    
    for vlan_data in parsed_data:
        vlan_id_str = vlan_data.get('vlan_id') 
        if not vlan_id_str:
            continue
            
        vlan_id = int(vlan_id_str)
        seen_vlan_ids_from_parser.add(vlan_id)
        
        vlan_obj = _get_or_create_vlan_cached(db, vlan_id, global_vlan_cache)
        
        new_name = vlan_data.get('vlan_name') 
        if new_name and vlan_obj.name != new_name:
            vlan_obj.name = new_name
        
        new_desc = vlan_data.get('description')
        if new_desc and vlan_obj.description != new_desc:
            vlan_obj.description = new_desc

        if vlan_id in old_links_map:
            existing_link = old_links_map[vlan_id]
            existing_link.type = vlan_data.get('type')
            existing_link.route_interface = vlan_data.get('route_interface')
        else:
            new_link = DeviceVlan(
                device_id=device.id,
                vlan_id=vlan_obj.id,
                type=vlan_data.get('type'),
                route_interface=vlan_data.get('route_interface')
            )
            db.add(new_link)
    
    stale_vlan_ids = set(old_links_map.keys()) - seen_vlan_ids_from_parser
    for stale_id in stale_vlan_ids:
        db.delete(old_links_map[stale_id])

    db.commit()
    
    return f"Zsynchronizowano {len(parsed_data)} definicji VLAN dla '{device.name}'."

def _perform_details_sync(db: Session, device: Device, action: Action, log_entry: TaskLog, auth_data: dict) -> str:
    """
    Pobiera i parsuje szczegóły urządzenia
    """
    
    # lista komend
    auth_data_with_creds, commands_list = _get_device_data_for_task(db, device, action, auth_data)
    
    # wykonanie komend i budowa slownika
    command_outputs = {}
    for cmd in commands_list:
        output = _execute_ssh_commands(auth_data_with_creds, [cmd], log_entry)
        command_outputs[cmd] = output
        print('asasasasa',output)
    

    driver = auth_data['netmiko_driver']
    parsed_details = parse_device_details(driver, command_outputs)
    print(parsed_details)
    # aktualizacja bazy
    updated_fields = []
    if parsed_details.get('model'):
        device.model = parsed_details['model']
        updated_fields.append('Model')
    if parsed_details.get('serial_number'):
        device.serial_number = parsed_details['serial_number']
        updated_fields.append('Serial')
    if parsed_details.get('software_version'):
        device.software_version = parsed_details['software_version']
        updated_fields.append('Wersja')
    if parsed_details.get('uptime'):
        device.uptime = parsed_details['uptime']
        updated_fields.append('Uptime')

    if not updated_fields:
        raise ValueError("Parsowanie szczegółów nie zwróciło żadnych danych.")
        
    return f"Zsynchronizowano: {', '.join(updated_fields)}."


def _perform_lldp_sync(db: Session, device: Device, action: Action, log_entry: TaskLog, auth_data: dict) -> str:
    """
    Pobiera sąsiadów LLDP, parsuje ich i aktualizuje tabelę 'network_links'.
    """
    
    auth_data_with_creds, commands_list = _get_device_data_for_task(db, device, action, auth_data)
    raw_output = _execute_ssh_commands(auth_data_with_creds, commands_list, log_entry)
    print(raw_output)

    # Parsowanie
    parsed_data = parse_command_output(auth_data['netmiko_driver'], action.action_slug, raw_output)
    print(parsed_data)
    if parsed_data is None or not isinstance(parsed_data, list):
        raise ValueError("Parsowanie LLDP nie zwróciło listy (błąd szablonu lub komendy).")

    #  czyszczenie starych wpisy dla tego urządzenia
    db.query(NetworkLink).filter(NetworkLink.local_device_id == device.id).delete(synchronize_session=False)
    
    link_count = 0
    for neighbor in parsed_data:
        local_port = neighbor.get('local_interface')
        neighbor_host = neighbor.get('neighbor_name')
        neighbor_port = neighbor.get('neighbor_interface')
        
        if not local_port or not neighbor_host or not neighbor_port:
            continue

        new_link = NetworkLink(
            local_device_id=device.id,
            local_port_name=local_port,
            neighbor_device_hostname=neighbor_host,
            neighbor_port_name=neighbor_port,
            discovered_at=func.now()
        )
        db.add(new_link)
        link_count += 1
    log_entry.raw_output = str(raw_output)         
    return f"Zsynchronizowano {link_count} połączeń LLDP dla '{device.name}'."


# po _execute_ssh_commands 

def _get_or_create_vlan_cached(db: Session, vlan_id: int, cache: dict[int, Vlan]) -> Vlan:
    """
    Sprawdza w cache czy globalny VLAN o danym ID już istnieje.
    Jeśli tak, zwraca go. Jeśli nie, tworzy go, dodaje do bazy i do cache.
    """
    if vlan_id not in cache:
        vlan_obj = db.query(Vlan).filter(Vlan.vlan_id == vlan_id).first()
        
        if not vlan_obj:
            vlan_obj = Vlan(vlan_id=vlan_id, name=f"VLAN_{vlan_id}")
            db.add(vlan_obj)
            db.flush() 
            
        cache[vlan_id] = vlan_obj
    
    return cache[vlan_id]


def _sync_port_vlans(db: Session, port: DevicePort, vlan_info: dict | None, vlan_cache: dict[int, Vlan]):
    """
    Synchronizuje członkostwo VLAN dla jednego portu na podstawie
    danych z parsera. Usuwa stare, dodaje nowe, aktualizuje istniejące.
    """
    if not vlan_info or not isinstance(vlan_info, dict):
        db.query(PortVlanMembership).filter(PortVlanMembership.device_port_id == port.id).delete(synchronize_session=False)
        return

    new_memberships_map = {}
    mode = vlan_info.get('mode')

    if mode == 'access':
        vlan_id = vlan_info.get('access_vlan')
        if vlan_id:
            vlan_obj = _get_or_create_vlan_cached(db, vlan_id, vlan_cache)
            new_memberships_map[vlan_obj.id] = 'access'

    elif mode == 'trunk':
        tagged_vlans = vlan_info.get('tagged_vlans', [])
        for vlan_id in tagged_vlans:
            vlan_obj = _get_or_create_vlan_cached(db, vlan_id, vlan_cache)
            new_memberships_map[vlan_obj.id] = 'trunk'
        
        untagged_vlan_id = vlan_info.get('untagged_vlan')
        if untagged_vlan_id:
            vlan_obj = _get_or_create_vlan_cached(db, untagged_vlan_id, vlan_cache)
            new_memberships_map[vlan_obj.id] = 'access'
    
    old_memberships = db.query(PortVlanMembership).filter(PortVlanMembership.device_port_id == port.id).all()
    old_map = {m.vlan_id: m for m in old_memberships}
    
    seen_vlan_db_ids = set()

    for vlan_db_id, new_type in new_memberships_map.items():
        seen_vlan_db_ids.add(vlan_db_id)
        
        if vlan_db_id not in old_map:
            new_membership = PortVlanMembership(
                device_port_id=port.id,
                vlan_id=vlan_db_id,
                membership_type=new_type
            )
            db.add(new_membership)
        elif old_map[vlan_db_id].membership_type != new_type:
            old_map[vlan_db_id].membership_type = new_type

    for old_vlan_db_id, old_membership_obj in old_map.items():
        if old_vlan_db_id not in seen_vlan_db_ids:
            db.delete(old_membership_obj)

def normalize_interface_name(name: str) -> str:
    """Konwertuje pełne nazwy portów na standardowy skrót."""
    mapping = {
        "Ten-GigabitEthernet": "XGE",
        "FortyGigE": "FGE",
        "GigabitEthernet": "GE",
        "FastEthernet": "Fa",
        "TwentyFiveGigE": "Twe",
        "HundredGigE": "Hu",
    }

    for full_prefix, short_prefix in mapping.items():
        if name.startswith(full_prefix):
            return short_prefix + name[len(full_prefix):]
    return name

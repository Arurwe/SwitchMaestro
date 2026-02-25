import re
import os
from ntc_templates.parse import parse_output
from typing import Dict, Any, Callable
from app.core.parsers import hp_comware


CUSTOM_TEMPLATES_DIR = "app/custom_ntc_templates"

DETAILS_PARSER_STRATEGY_MAP: Dict[str, Callable] = {
    'hp_comware': hp_comware.parse_details,
    'hp_comware_buggy': hp_comware.parse_details,
}

def parse_command_output(driver: str, action_slug: str, raw_output: str) -> list | dict | None:
    SLUG_TO_TEMPLATE_MAP = {
        'get_interfaces': 'display interface brief',
        'get_interfaces_full': 'display interface',
        'get_vlans': 'display vlan all',
        'get_lldp_neighbors': 'display lldp neighbor-information list',
        'get_config_backup': None,
        'get_all_diagnostics': None,
        'get_device_details': None,
    }

    DRIVER_TO_PLATFORM_MAP = {
        'hp_comware': 'hp_comware',
        'hp_comware_buggy': 'hp_comware',
        'cisco_ios': 'cisco_ios',
        'juniper_junos': 'juniper_junos',
    }

    template_name = SLUG_TO_TEMPLATE_MAP.get(action_slug)
    platform = DRIVER_TO_PLATFORM_MAP.get(driver)
    
    if not template_name or not platform:
        print(f"Brak szablonu lub platformy dla {driver}/{action_slug}")
        return None

    if driver in ['hp_comware', 'hp_comware_buggy'] and action_slug == 'get_interfaces_full':
        try:
            from app.core.parsers.hp_comware_display_interface_parser import parse_hp_comware_display_interface
            parsed_data = parse_hp_comware_display_interface(raw_output)
            

            processed_data = []
            for port_data in parsed_data:
                port_data['details'] = port_data.copy()
                port_data['vlan_info'] = _build_vlan_info_from_dedicated_parser(port_data)
                processed_data.append(port_data)
            
            return processed_data
        except Exception as e:
            return _fallback_to_textfsm(platform, template_name, raw_output, action_slug)
    
    return _fallback_to_textfsm(platform, template_name, raw_output, action_slug)


def _fallback_to_textfsm(platform: str, template_name: str, raw_output: str, action_slug: str) -> list | dict | None:
    """
    Fallback do standardowego TextFSM z logiką custom/default.
    """
    original_ntc_dir = os.environ.get("NTC_TEMPLATES_DIR")
    parsed_data = None

    try:
        os.environ["NTC_TEMPLATES_DIR"] = CUSTOM_TEMPLATES_DIR
        parsed_data = parse_output(platform=platform, command=template_name, data=raw_output)
        
        if parsed_data:
            print("SUCCESS: Użyto CUSTOM template!")
        else:
            print("CUSTOM template zwrócił puste dane")

    except Exception as e:
        print(f"CUSTOM template ERROR: {e}")
    finally:
        if original_ntc_dir:
            os.environ["NTC_TEMPLATES_DIR"] = original_ntc_dir
        else:
            os.environ.pop("NTC_TEMPLATES_DIR", None)

    # domyślny template jesli custom zawiódl
    if not parsed_data:
        try:
            print("Używam DOMYŚLNEGO template")
            if original_ntc_dir:
                os.environ["NTC_TEMPLATES_DIR"] = original_ntc_dir
            else:
                os.environ.pop("NTC_TEMPLATES_DIR", None)
                
            parsed_data = parse_output(platform=platform, command=template_name, data=raw_output)
            
            if parsed_data:
                print("SUCCESS: Użyto DOMYŚLNEGO template")
            else:
                print("DOMYŚLNY template zwrócił puste dane")
        except Exception as e:
            print(f"KRYTYCZNY BŁĄD PARSOWANIA NTC dla {platform}/{template_name}: {e}")
            return None

# post procesing
    if parsed_data and action_slug == 'get_interfaces_full':
        processed_data = []
        for port_data in parsed_data:
            port_data['details'] = port_data.copy() 
            port_data['vlan_info'] = _build_vlan_info(port_data)
            processed_data.append(port_data)
        return processed_data
    
    return parsed_data or None



def _build_vlan_info_from_dedicated_parser(port_data: dict) -> dict | None:
    """
    Buduje vlan_info z danych z dedykowanego parsera.
    """
    link_type = port_data.get('port_link_type', '').lower()


    if link_type == 'access':
        vlan_id_str = port_data.get('untagged_vlan_id') or port_data.get('vlan_native')
        if vlan_id_str and vlan_id_str.isdigit():
            return {
                'mode': 'access',
                'access_vlan': int(vlan_id_str)
            }
        return None
            
    elif link_type in ['trunk', 'hybrid']:
        tagged_list = port_data.get('tagged_vlans', [])
        
        untagged_vlan = None
        untagged_vlan_str = port_data.get('vlan_native') or port_data.get('untagged_vlan_id')
        
        if untagged_vlan_str and untagged_vlan_str.isdigit() and untagged_vlan_str.lower() != 'none':
            untagged_vlan = int(untagged_vlan_str)
        
        return {
            'mode': 'trunk',
            'tagged_vlans': tagged_list,
            'untagged_vlan': untagged_vlan
        }

    return None

def parse_device_details(driver: str, command_outputs: Dict[str, str]) -> Dict[str, Any]:
    """
     dla parsowania 'get_device_details'.
    """
    parser_function = DETAILS_PARSER_STRATEGY_MAP.get(driver)
    
    if not parser_function:
        raise NotImplementedError(f"Brak strategii parsowania szczegółów dla sterownika: '{driver}'")
        
    try:
        return parser_function(driver, command_outputs)
    except Exception as e:
        raise ValueError(f"Parsowanie szczegółów dla {driver} nie powiodło się: {e}")
    
def _parse_vlan_string(vlan_data: list[str] | str) -> list[int]:
    if not vlan_data:
        return []
    
    if isinstance(vlan_data, list):
        filtered_data = [str(item).strip() for item in vlan_data 
                         if item and str(item).strip().lower() != 'none']
        vlan_data = ", ".join(filtered_data)
    
    if not vlan_data or vlan_data.lower() in ['none', '']:
        return []
    
    vlans = set()
    cleaned = re.sub(r'\([^)]*\)', '', vlan_data)
    cleaned = re.sub(r'\s+', ' ', cleaned.strip())
    parts = [part.strip() for part in cleaned.split(',') if part.strip()]
    
    for part in parts:
        if not part or part.lower() == 'none':
            continue
            
        if '-' in part:
            try:
                start_end = part.split('-')
                if len(start_end) == 2 and start_end[0].isdigit() and start_end[1].isdigit():
                    start, end = int(start_end[0]), int(start_end[1])
                    if 1 <= start <= 4094 and 1 <= end <= 4094 and start <= end:
                        vlans.update(range(start, end + 1))
            except ValueError:
                print(f" Błąd parsowania zakresu VLAN: {part}")
        elif part.isdigit():
            vlan_num = int(part)
            if 1 <= vlan_num <= 4094:
                vlans.add(vlan_num)
        elif part.lower() != 'none':
            print(f" Nieznany format VLAN: {part}")
    
    result = sorted(list(vlans))
    return result

def _build_vlan_info(port_data: dict) -> dict | None:

    link_type = port_data.get('port_link_type', '').lower()


    if link_type == 'access':
        vlan_id_str = port_data.get('untagged_vlan_id') or port_data.get('vlan_native')
        if vlan_id_str and vlan_id_str.isdigit():
            return {
                'mode': 'access',
                'access_vlan': int(vlan_id_str)
            }
        return None
            
    elif link_type in ['trunk', 'hybrid']:
        all_tagged_vlans = []
        
        # przetwarzanie pol vlan
        for field in ['vlan_tagged', 'vlan_passing', 'vlan_permitted']:
            field_data = port_data.get(field)
            if field_data:
                parsed = _parse_vlan_string(field_data)
                if parsed:
                    all_tagged_vlans.extend(parsed)
        
        tagged_list = sorted(list(set(all_tagged_vlans)))
        
        untagged_vlan = None
        untagged_vlan_str = port_data.get('vlan_native') or port_data.get('untagged_vlan_id')
        
        if untagged_vlan_str and untagged_vlan_str.isdigit():
            untagged_vlan = int(untagged_vlan_str)
        
        return {
            'mode': 'trunk',
            'tagged_vlans': tagged_list,
            'untagged_vlan': untagged_vlan
        }
    return None
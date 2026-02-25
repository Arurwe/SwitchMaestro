import re
from ntc_templates.parse import parse_output
from typing import Dict, Any



def _parse_hp_display_manuinfo_regex(raw_output: str) -> dict:
    """
    Ręczny parser Regex dla 'display device manuinfo'.
    """
    info = {}
    
    blocks = raw_output.split('\n\n')
    
    if not blocks:
        return info

    main_block = blocks[0]

    model_match = re.search(r"DEVICE_NAME\s+:\s+(.*)", main_block, re.IGNORECASE)
    if model_match:
        info['model'] = model_match.group(1).strip()

    serial_match = re.search(r"DEVICE_SERIAL_NUMBER\s+:\s+(\S+)", main_block, re.IGNORECASE)
    if serial_match:
        info['serial_number'] = serial_match.group(1).strip()

    return info

def _parse_hp_display_version_regex(raw_output: str) -> dict:
    """
    Ręczny parser dla 'display version' na HP Comware.
    """
    version_info = {}
    
    version_match = re.search(
        r"Comware Software,\s+Version\s+([\w\.]+),\s+Release\s+(\S+)", 
        raw_output, 
        re.IGNORECASE
    )
    if version_match:
        version_info['software_version'] = f"{version_match.group(1)}, R{version_match.group(2)}"

    uptime_match = re.search(r"uptime is\s+(.*)", raw_output, re.IGNORECASE)
    if uptime_match:
        version_info['uptime'] = uptime_match.group(1).strip()
    return version_info



def parse_details(driver: str, command_outputs: Dict[str, str]) -> Dict[str, Any]:
    """
    Implementacja strategii parsowania dla 'get_device_details' na HP Comware.
    """
    
    details = {
        'model': None,
        'serial_number': None,
        'software_version': None,
        'uptime': None
    }
    
    #  Parsowanie 'display device manuinfo'
    manuinfo_output = command_outputs.get('display device manuinfo')
    if manuinfo_output:
        try:
            manu_info = _parse_hp_display_manuinfo_regex(manuinfo_output)
            details.update(manu_info)
        except Exception as e:
            print(f"Błąd parsowania Regex (manuinfo) dla HP Comware: {e}")

    # Parsowanie 'display version'
    version_output = command_outputs.get('display version')
    print('aaaa',version_output)
    if version_output:
        try:
            version_info = _parse_hp_display_version_regex(version_output)
            details.update(version_info) 
        except Exception as e:
            print(f"Błąd parsowania Regex (version) dla HP Comware: {e}")

    return details




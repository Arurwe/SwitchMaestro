import re
from typing import Dict, List, Any, Optional

def _is_valid_interface(interface_name: str) -> bool:
    prefixes = [
        'GigabitEthernet', 'Ten-GigabitEthernet', 'FortyGigE', 
        'M-GigabitEthernet', 'Bridge-Aggregation', 'Vlan-interface', 
        'InLoopBack', 'NULL', 'Register-Tunnel'
    ]
    pattern = r'^(?:' + '|'.join(prefixes) + r')\d+([/\.]\d+)*$'
    return bool(re.match(pattern, interface_name.strip(), re.IGNORECASE))


def parse_hp_comware_display_interface(raw_output: str) -> List[Dict[str, Any]]:
    """
    Dedytkowany parser dla 'display interface' z HP Comware 5/7.
    Parsuje całe wyjście bez użycia TextFSM.
    """
    interfaces = []
    current_interface = None
    lines = raw_output.split('\n')
    
    i = 0
    while i < len(lines):
        line_raw = lines[i].rstrip()
        line = line_raw.strip() 
        # pominiecie pustych linii
        if not line:
            i += 1
            continue

        # Wykrywanie czy linia to nowy interfejs
        potential_name_match = re.match(r'^(\S+)', line)
        if potential_name_match:
            potential_name = potential_name_match.group(1)
            
            #  sprawdzenie czy to interfejs
            if _is_valid_interface(potential_name):
                if current_interface:
                    interfaces.append(current_interface)
                
                # Rozpocznij nowy interfejs
                current_interface = {
                    'interface': potential_name,
                    'line_status': '',
                    'protocol_status': '',
                    'description': '',
                    'port_link_type': '',
                    'vlan_native': '',
                    'untagged_vlan_id': '',
                    'tagged_vlans': [],
                    'hw_address': [],
                    'ip_address': [],
                    'mtu': '',
                    'l2mtu': '',
                    'speed': '',
                    'duplex': '',
                    'bandwidth': ''
                }
                
                status_match = re.search(r'current state:\s*(\S.*)$', line, re.IGNORECASE)
                if status_match:
                    current_interface['line_status'] = status_match.group(1).strip()
                
                i += 1
                continue #

        # Jeśli to nie jest nowy interfejs
        if current_interface:
            
            if not current_interface['line_status'] and 'current state:' in line:
                status_match = re.search(r'current state:\s*(\S.*)$', line, re.IGNORECASE)
                if status_match:
                    current_interface['line_status'] = status_match.group(1).strip()
            
            elif 'Line protocol state:' in line or 'Line protocol current state:' in line:
                protocol_match = re.search(r'Line protocol\s+(?:current\s+)?state:\s*(\S.*)$', line, re.IGNORECASE)
                if protocol_match:
                    current_interface['protocol_status'] = protocol_match.group(1).strip()
            
            elif line.startswith('Description:'):
                current_interface['description'] = line.replace('Description:', '').strip()
            
            elif line.startswith('Bandwidth:'):
                current_interface['bandwidth'] = line.replace('Bandwidth:', '').strip()
            
            elif 'hardware address:' in line.lower():
                hw_match = re.search(r'hardware address:\s*([a-fA-F0-9-\.]+)', line, re.IGNORECASE)
                if hw_match:
                    current_interface['hw_address'].append(hw_match.group(1))
            
            elif 'Internet address:' in line or 'Internet address is' in line:
                ip_match = re.search(r'(\d+\.\d+\.\d+\.\d+/\d+)', line)
                if ip_match:
                    current_interface['ip_address'].append(ip_match.group(1))
            
            elif re.search(r'(Maximum Transmit Unit|Maximum transmission unit)', line, re.IGNORECASE):
                mtu_match = re.search(r'(\d+)', line)
                if mtu_match:
                    current_interface['mtu'] = mtu_match.group(1)
            
            elif re.search(r'(Maximum frame length|The maximum frame length is)', line, re.IGNORECASE):
                l2mtu_match = re.search(r'(\d+)', line)
                if l2mtu_match:
                    current_interface['l2mtu'] = l2mtu_match.group(1)
            
            elif re.search(r'\d+G?bps-speed mode', line, re.IGNORECASE) or re.search(r'\d+G?bps,.*duplex', line, re.IGNORECASE):
                speed_match = re.search(r'(\d+G?bps)', line, re.IGNORECASE)
                if speed_match:
                    current_interface['speed'] = speed_match.group(1)
                
                duplex_match = re.search(r'(\S+)-duplex mode|,\s*(\S+)\s+mode', line, re.IGNORECASE)
                if duplex_match:
                    duplex_val = (duplex_match.group(1) or duplex_match.group(2) or "").lower()
                    if duplex_val in ['full', 'half', 'unknown']:
                        current_interface['duplex'] = duplex_val
            
            elif line.startswith('PVID:'):
                pvid_match = re.search(r'PVID:\s*(\d+|none)', line, re.IGNORECASE)
                if pvid_match:
                    current_interface['vlan_native'] = pvid_match.group(1)
            
            elif line.startswith('Port link-type:'):
                link_type_match = re.search(r'Port link-type:\s*(\S+)', line)
                if link_type_match:
                    current_interface['port_link_type'] = link_type_match.group(1)
            
            elif re.search(r'^(Untagged\s+VLAN\s+ID|Untagged\s+VLANs)\s*:', line, re.IGNORECASE):
                untagged_match = re.search(r':\s*(.+)', line, re.IGNORECASE)
                if untagged_match:
                    untagged_data = untagged_match.group(1).strip()
                    if untagged_data.isdigit():
                        current_interface['untagged_vlan_id'] = untagged_data
                    elif untagged_data.lower() == 'none':
                        current_interface['untagged_vlan_id'] = ''
                    else:
                        current_interface['untagged_vlan_id'] = untagged_data 
            
            elif re.search(r'^(Tagged\s+VLAN\s+ID|Tagged\s+VLANs)\s*:', line, re.IGNORECASE):
                tagged_match = re.search(r':\s*(.+)', line, re.IGNORECASE)
                tagged_data = tagged_match.group(1).strip() if tagged_match else ""
                
                j = i + 1
                # Sprawdź czy następna linia istnieje I czy ma wciecia
                while j < len(lines) and lines[j].rstrip() and lines[j].startswith(' '):
                    continuation_line = lines[j].strip()
                    
                    # stop gdy nowa sekcja
                    if re.match(r'^(Untagged|Tagged|PVID|Port link-type)', continuation_line, re.IGNORECASE):
                        break
                        
                    tagged_data += ' ' + continuation_line
                    j += 1
                
                i = j - 1 
                
                # parsowanie zebranych danych VLAN
                vlan_list = _parse_vlan_string_from_raw(tagged_data)
                current_interface['tagged_vlans'] = vlan_list
        
        i += 1 # Przejdź do następnej linii
    
    # Dodaj ostatni interfejs
    if current_interface:
        interfaces.append(current_interface)
    
    return interfaces

def _parse_vlan_string_from_raw(vlan_data: str) -> List[int]:
    """
    Parsuje surowe dane VLAN z wyjścia HP Comware.
    """
    if not vlan_data or vlan_data.lower() == 'none':
        return []
    
    vlans = set()
    
    #  usuwanie przecinkow na końcu
    cleaned = re.sub(r',\s*$', '', vlan_data.strip()) 
    
    cleaned = re.sub(r'\s+', ',', cleaned)
    cleaned = re.sub(r',,+', ',', cleaned)
    
    parts = re.split(r',', cleaned)
    
    for part in parts:
        part = part.strip()
        if not part or part.lower() == 'none':
            continue
            
        if '-' in part:
            # Obsługa zakresów
            try:
                start_end = part.split('-')
                if len(start_end) == 2 and start_end[0].isdigit() and start_end[1].isdigit():
                    start, end = int(start_end[0]), int(start_end[1])
                    if 1 <= start <= 4094 and 1 <= end <= 4094 and start <= end:
                        vlans.update(range(start, end + 1))
            except ValueError:
                print(f"[PARSER] Błąd parsowania zakresu: {part}")
        elif part.isdigit():
            # Pojedynczy VLAN
            vlan_num = int(part)
            if 1 <= vlan_num <= 4094:
                vlans.add(vlan_num)
    
    result = sorted(list(vlans))
    return result
import time
import re
from netmiko import ConnectHandler
from netmiko.exceptions import NetmikoAuthenticationException, NetmikoTimeoutException

def run_commands_hp_comware_buggy(device: dict, commands: list[str]) -> str:
    """
    Specjalne wykonanie komend na HP Comware z problematyczną paginacją.
    yzycie read_channel/write_channel do ręcznej obsługi paginacji.
    """
    print(f"DEBUG network_special.py: Uruchomiono tryb 'hp_comware_buggy' dla {device.get('host')}")
    

    device["device_type"] = "hp_comware"
    device["global_delay_factor"] = 2

    all_cleaned_outputs = []

    try:
        with ConnectHandler(**device) as net_connect:
            if device.get('secret'):
                net_connect.enable()
            
            # pobranie znaku zachety
            final_prompt_raw = net_connect.find_prompt()
            # czyszczenie z kodow ansi
            final_prompt = re.sub(r'\x1b\[[0-9;]*[a-zA-Z]', '', final_prompt_raw).strip()
            
            print(f" Wykryto znak zachęty: [{final_prompt}]")

            paging_pattern = r"---- More ----"
            
            # --- Pętla po wszystkich komendach ---
            for command in commands:
                print(f"DEBUG network_special.py: Wykonywanie komendy: {command}")
                net_connect.write_channel(command + "\n")
                
                current_command_output = ""
                read_attempts = 0
                max_attempts = 200

                # --- Pętla odczytu dla JEDNEJ komendy ---
                while read_attempts < max_attempts:
                    read_attempts += 1
                    time.sleep(0.5)
                    try:
                        chunk = net_connect.read_channel()
                    except Exception as read_e:
                        raise IOError(f"Błąd podczas read_channel: {read_e}")

                    if chunk:
                        current_command_output += chunk
                        if final_prompt in current_command_output:
                            break 

                        # sprawdzenie czy hest paginacja
                        if re.search(paging_pattern, chunk):
                            print(f"DEBUG network_special.py: Wykryto paginację, wysyłanie spacji.")
                            # Usuń marker paginacji z wyjścia
                            current_command_output = re.sub(paging_pattern, "", current_command_output)
                            net_connect.write_channel(" ")
                            time.sleep(1)
                    
                    else:
                        # Jeśli kanał jest pusty przez 5 prób z rzędu, załóż, że to koniec
                        if read_attempts > 5 and final_prompt in net_connect.find_prompt(delay_factor=1):
                             break

                if read_attempts >= max_attempts:
                    raise TimeoutError(f"Przekroczono limit prób ({max_attempts}) dla komendy: {command}")
                
                # czyszczenie wyniku dla komendy
                cleaned_output = _clean_hp_output(current_command_output, command, final_prompt)
                all_cleaned_outputs.append(cleaned_output)
            
            net_connect.disconnect()
            return "\n".join(all_cleaned_outputs)

    except (NetmikoAuthenticationException, NetmikoTimeoutException) as e:
        raise ConnectionError(f"Błąd połączenia (HP Buggy): {e}")
    except Exception as e:
        raise RuntimeError(f"Błąd wykonania (HP Buggy): {e}")


def _clean_hp_output(output: str, command: str, prompt: str) -> str:
    """
    Czyści surowy output z echo komendy, promptu i kodów ANSI.
    """
    # Usuń kody ANSI
    cleaned = re.sub(r'\x1b\[[0-9;]*[a-zA-Z]', '', output)
    
    # Usuń paginację
    cleaned = re.sub(r"---- More ----", "", cleaned)
    
    lines = cleaned.splitlines()
    cleaned_lines = []
    command_seen = False

    for line in lines:
        strip_line = line.strip()

        #  Usuń echo komendy
        if not command_seen and command in strip_line:
            command_seen = True
            continue
            
        # Usuń ostateczny znak zachęty
        if prompt in strip_line:
            # Usuń prompt i wszystko po nim, a resztę zachowaj
            line_before_prompt = strip_line.split(prompt)[0].rstrip()
            if line_before_prompt:
                cleaned_lines.append(line_before_prompt)
            continue
            
        # dodanie czystej linii
        cleaned_lines.append(line)

    return "\n".join(cleaned_lines).strip()
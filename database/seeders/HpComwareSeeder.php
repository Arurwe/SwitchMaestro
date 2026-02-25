<?php

namespace Database\Seeders;

// PAMIĘTAJ O DODANIU MODELU ACTION!
use App\Models\Action; 
use App\Models\Vendor;
use App\Models\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class HpComwareSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            
            $hp = Vendor::firstOrCreate(
                ['netmiko_driver' => 'hp_comware'], 
                ['name' => 'HP Comware'] 
            );


            $actionConfig = Action::firstOrCreate(
                ['action_slug' => 'get_config_backup'],
                [
                    'name' => 'Pobierz backup konfiguracji',
                    'description' => 'Pobiera pełną bieżącą konfigurację urządzenia.'
                ]
            );

            $actionVlan = Action::firstOrCreate(
                ['action_slug' => 'get_vlans'],
                [
                    'name' => 'Wyświetl VLANy',
                    'description' => 'Pokazuje listę wszystkich skonfigurowanych VLANów.'
                ]
            );

            $actionInterface = Action::firstOrCreate(
                ['action_slug' => 'get_interfaces'],
                [
                    'name' => 'Wyświetl interfejsy',
                    'description' => 'Pokazuje skrócony status wszystkich interfejsów.'
                ]
            );

            $actionInterfaceFull = Action::firstOrCreate(
                ['action_slug' => 'get_interfaces_full'],
                [
                    'name' => 'Wyświetl interfejsy (szczegóły)',
                    'description' => 'Pokazuje skrócony status wszystkich interfejsów.'
                ]
            );

            $actionDiagnostics = Action::firstOrCreate(
                ['action_slug' => 'get_all_diagnostics'],
                [
                    'name' => 'Pełna diagnostyka',
                    'description' => 'Wykonuje zestaw komend (config, vlany, interfejsy) do szybkiej diagnostyki.'
                ]
            );

            $actionDetail = Action::firstOrCreate(
                ['action_slug' => 'get_device_details'],
                [
                    'name' => 'Detale',
                    'description' => 'Pobiera detale'
                ]
            );

            $actionLldp = Action::firstOrCreate(
                ['action_slug' => 'get_lldp_neighbors'],
                [
                    'name' => 'Pobierz sąsiadów LLDP', 
                    'description' => 'Pobiera listę sąsiadów LLDP.'
                ]
            );

            $configCmd = ['display current-configuration'];
            $vlanCmd = ['display vlan all'];
            $interfaceCmd = ['display interface brief'];
            $interfaceFullCmd = ['display interface'];
            $versionCMD = ['display device manuinfo','display version'];
            $lldCMD = ['display lldp neighbor-information list'];
            

            Command::updateOrCreate(
                ['vendor_id' => $hp->id, 'action_id' => $actionConfig->id],
                [
                    'commands' => $configCmd,
                    'description' => 'Domyślna komenda backupu dla HP Comware.'
                ]
            );

            Command::updateOrCreate(
                ['vendor_id' => $hp->id, 'action_id' => $actionVlan->id],
                [
                    'commands' => $vlanCmd
                ]
            );

            Command::updateOrCreate(
                ['vendor_id' => $hp->id, 'action_id' => $actionInterface->id],
                [
                    'commands' => $interfaceCmd
                ]
            );

            Command::updateOrCreate(
                ['vendor_id' => $hp->id, 'action_id' => $actionDiagnostics->id],
                [
                    'commands' => array_merge($configCmd, $vlanCmd, $interfaceCmd,$interfaceFullCmd,$lldCMD)
                ]
            );

            Command::updateOrCreate(
                ['vendor_id' => $hp->id, 'action_id' => $actionDetail->id],
                [
                    'commands' => $versionCMD
                ]
            );

            Command::updateOrCreate(
                ['vendor_id' => $hp->id, 'action_id' => $actionLldp->id],
                ['commands' => $lldCMD]
            );

            
            Command::updateOrCreate(
                ['vendor_id' => $hp->id, 'action_id' => $actionInterfaceFull->id],
                ['commands' => $interfaceFullCmd]
            );
        });


    }
}
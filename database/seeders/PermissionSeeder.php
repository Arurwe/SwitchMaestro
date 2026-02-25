<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
$permissions = [

    //Administracja
        'users:manage',
        'roles:manage',
        
        // Konfiguracja Systemu
        'credentials:view',
        'credentials:manage',
        'devicetypes:view',   
        'devicetypes:manage', 
        'actions:view',      
        'actions:manage',     
        'groups:view',        
        'groups:manage',      
        
        // Operacje na Urządzeniach
        'devices:view',
        'devices:manage',
        'backups:view',
        'backups:create',
        'backups:delete',
        'backups:restore',   
        
        // Monitoring i Logi
        'auditlog:view',
        'jobs:view',          
        
        // Wykonywanie Komend 
        'commands:run:readonly', // Test połączenia, odświeżanie danych (get_...)
        'commands:run',
        'commands:run:config',   // Uruchomienie akcji konfiguracyjnej, własne komendy

        // Funkcje AI
        'llm:use'
        ];

        foreach ($permissions as $perm)
        {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $roleAuditor = Role::firstOrCreate(['name' => 'Auditor']);
        $roleAuditor->syncPermissions([

        ]);

        $roleTech = Role::firstOrCreate(['name' => 'Tech']);
        $roleTech->syncPermissions([

        ]);

        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);
        $roleAdmin->syncPermissions(Permission::all());

        $systemUser = User::firstOrCreate(
            ['username' => 'sys'],
            [
                'username' => 'sys',
                'full_name' => 'System',
                'email' => 'sys@test.com',
                'password' => Hash::make('password')
            ]
        );
        $systemUser->assignRole($roleAdmin);

        
        $adminUser = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'username' => 'admin',
                'full_name' => 'Administrator',
                'email' => 'admin@test.com',
                'password' => Hash::make('password')
            ]
        );
        $adminUser->assignRole($roleAdmin);

        // $techUser = User::firstOrCreate(
        //     ['username' => 'tech'],
        //     [
        //         'username' => 'tech',
        //         'full_name' => 'Technik',
        //         'email' => 'tech@test.com',
        //         'password' => Hash::make('password')
        //     ]
        // );
        // $techUser->assignRole($roleTech);

        // $auditorUser = User::firstOrCreate(
        //     ['username' => 'auditor'],
        //     [
        //         'username' => 'auditor',
        //         'full_name' => 'Auditor',
        //         'email' => 'auditor@test.com',
        //         'password' => Hash::make('password')
        //     ]
        // );
        // $auditorUser->assignRole($roleAuditor);

    }
}

<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'backup_schedule_time',
                'value' => '03:00',
            ],
            [
                'key' => 'prune_old_backup_schedule_time',
                'value' => '04:00',
            ],
            [
                'key' => 'backup_retention_days',
                'value' => '30',
            ],
            [
                'key' => 'OpenAI_Key',
                'value' => '',
            ],
            [
                'key' => 'OpenAI_model',
                'value' => 'gpt-4o',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}

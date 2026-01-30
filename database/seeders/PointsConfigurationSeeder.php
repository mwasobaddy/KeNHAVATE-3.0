<?php

namespace Database\Seeders;

use App\Models\PointsConfiguration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PointsConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PointsConfiguration::create([
            'event' => 'first_login',
            'points' => 50,
            'set_by' => 1, // Assume admin user id 1
        ]);
    }
}

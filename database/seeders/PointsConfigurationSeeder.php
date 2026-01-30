<?php

namespace Database\Seeders;

use App\Models\PointsConfiguration;
use Illuminate\Database\Seeder;

class PointsConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = [
            ['event' => 'idea_created', 'points' => 25],
            ['event' => 'idea_submitted', 'points' => 50],
            ['event' => 'idea_approved', 'points' => 100],
            ['event' => 'idea_implemented', 'points' => 200],
            ['event' => 'suggestion_created', 'points' => 10],
            ['event' => 'suggestion_accepted', 'points' => 25],
            ['event' => 'idea_upvoted', 'points' => 5],
            ['event' => 'collaboration_joined', 'points' => 15],
            ['event' => 'merge_performed', 'points' => 30],
            ['event' => 'conflict_resolved', 'points' => 20],
            ['event' => 'first_login', 'points' => 50],
            ['event' => 'profile_completed', 'points' => 25],
            ['event' => 'weekly_active', 'points' => 10],
            ['event' => 'monthly_active', 'points' => 50],
        ];

        foreach ($configurations as $config) {
            PointsConfiguration::updateOrCreate(
                ['event' => $config['event']],
                [
                    'points' => $config['points'],
                    'set_by' => 1, // Assume admin user id 1
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class IdeaCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Road Construction Materials',
                'slug' => 'road-construction-materials',
                'description' => 'Innovations in materials used for road construction',
            ],
            [
                'name' => 'Climate Resilience',
                'slug' => 'climate-resilience',
                'description' => 'Solutions for climate-resilient infrastructure',
            ],
            [
                'name' => 'Revenue Generation',
                'slug' => 'revenue-generation',
                'description' => 'Ideas for generating revenue through infrastructure',
            ],
            [
                'name' => 'Customer Delivery Service',
                'slug' => 'customer-delivery-service',
                'description' => 'Improving customer service and delivery',
            ],
            [
                'name' => 'Road Construction Technologies',
                'slug' => 'road-construction-technologies',
                'description' => 'Technological innovations in road construction',
            ],
            [
                'name' => 'Value for Money',
                'slug' => 'value-for-money',
                'description' => 'Maximizing value and efficiency in projects',
            ],
            [
                'name' => 'Quality and Safety',
                'slug' => 'quality-and-safety',
                'description' => 'Ensuring quality and safety in infrastructure',
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Other infrastructure-related ideas',
            ],
        ];

        foreach ($categories as $category) {
            \App\Models\IdeaCategory::create($category);
        }
    }
}

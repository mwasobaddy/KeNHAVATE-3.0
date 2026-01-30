<?php

namespace App\Console\Commands;

use App\Models\Idea;
use App\Models\User;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PopulateAnalyticsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:populate {--days=30 : Number of days of historical data to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate analytics data with sample events and metrics for testing';

    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');

        $this->info("Populating analytics data for the last {$days} days...");

        // Create sample analytics events
        $this->createSampleEvents($days);

        // Update user engagement metrics
        $this->updateUserMetrics($days);

        // Update idea lifecycle analytics
        $this->updateIdeaAnalytics();

        $this->info('Analytics data populated successfully!');
    }

    /**
     * Create sample analytics events.
     */
    private function createSampleEvents(int $days): void
    {
        $users = User::all();
        $eventTypes = [
            'user_login',
            'idea_created',
            'suggestion_submitted',
            'idea_upvoted',
            'collaboration_joined',
            'idea_submitted',
            'suggestion_accepted',
            'suggestion_rejected',
        ];

        $this->info('Creating sample analytics events...');

        $bar = $this->output->createProgressBar($days * 10);
        $bar->start();

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($i);

            // Create 10 events per day
            for ($j = 0; $j < 10; $j++) {
                $user = $users->random();
                $eventType = collect($eventTypes)->random();

                $this->analyticsService->trackEvent(
                    $eventType,
                    'user_engagement',
                    $user,
                    [
                        'session_id' => 'sample_session_'.rand(1000, 9999),
                        'user_agent' => 'Sample Browser/1.0',
                    ],
                    '127.0.0.1',
                    'Sample Browser/1.0',
                    'sample_session_'.rand(1000, 9999)
                );

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Update user engagement metrics.
     */
    private function updateUserMetrics(int $days): void
    {
        $users = User::all();

        $this->info('Updating user engagement metrics...');

        $bar = $this->output->createProgressBar($users->count() * $days);
        $bar->start();

        foreach ($users as $user) {
            for ($i = 0; $i < $days; $i++) {
                $date = Carbon::now()->subDays($i)->toDateString();
                $this->analyticsService->updateUserEngagementMetrics($user, $date);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Update idea lifecycle analytics.
     */
    private function updateIdeaAnalytics(): void
    {
        $ideas = Idea::all();

        $this->info('Updating idea lifecycle analytics...');

        $bar = $this->output->createProgressBar($ideas->count());
        $bar->start();

        foreach ($ideas as $idea) {
            $this->analyticsService->updateIdeaLifecycleAnalytics($idea);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}

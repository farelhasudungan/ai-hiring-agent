<?php

namespace App\Providers;

use App\Models\CandidateScoringAgent;
use App\Models\HiringRecommendationAgent;
use App\Models\ResumeParserAgent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'resume_parser' => ResumeParserAgent::class,
            'candidate_scoring' => CandidateScoringAgent::class,
            'hiring_recommendation' => HiringRecommendationAgent::class,
        ]);

        Model::preventLazyLoading(! $this->app->isProduction());
    }
}

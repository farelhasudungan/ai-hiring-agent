<?php

namespace App\Models;

use App\Models\Concerns\HasToolExecutionLogs;
use Database\Factories\HiringRecommendationAgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'description'])]
class HiringRecommendationAgent extends Model
{
    /** @use HasFactory<HiringRecommendationAgentFactory> */
    use HasFactory, HasToolExecutionLogs;
}

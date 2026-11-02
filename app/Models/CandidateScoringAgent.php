<?php

namespace App\Models;

use App\Models\Concerns\HasToolExecutionLogs;
use Database\Factories\CandidateScoringAgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'description'])]
class CandidateScoringAgent extends Model
{
    /** @use HasFactory<CandidateScoringAgentFactory> */
    use HasFactory, HasToolExecutionLogs;
}

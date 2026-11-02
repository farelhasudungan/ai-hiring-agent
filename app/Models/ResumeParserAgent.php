<?php

namespace App\Models;

use App\Models\Concerns\HasToolExecutionLogs;
use Database\Factories\ResumeParserAgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'description'])]
class ResumeParserAgent extends Model
{
    /** @use HasFactory<ResumeParserAgentFactory> */
    use HasFactory, HasToolExecutionLogs;
}

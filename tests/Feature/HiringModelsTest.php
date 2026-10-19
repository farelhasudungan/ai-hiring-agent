<?php

use App\Models\ActivityLog;
use App\Models\AiScreening;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\Job;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::connection()->beforeExecuting(function (): never {
        throw new LogicException('These tests must not execute SQL or run migrations.');
    });
});

test('belongs-to relations assign the expected parent foreign key without database access', function (
    string $modelClass,
    string $relationName,
    string $parentClass,
    string $foreignKey,
) {
    $model = new $modelClass;
    $parent = (new $parentClass)->forceFill(['id' => 42]);

    $model->{$relationName}()->associate($parent);

    expect($model->getAttribute($foreignKey))->toBe(42);
    expect($model->getRelation($relationName))->toBe($parent);
})->with([
    'user company' => [User::class, 'company', Company::class, 'company_id'],
    'job company' => [Job::class, 'company', Company::class, 'company_id'],
    'candidate company' => [Candidate::class, 'company', Company::class, 'company_id'],
    'application company' => [Application::class, 'company', Company::class, 'company_id'],
    'application candidate' => [Application::class, 'candidate', Candidate::class, 'candidate_id'],
    'application job' => [Application::class, 'job', Job::class, 'job_id'],
    'application reviewer' => [Application::class, 'reviewer', User::class, 'reviewed_by'],
    'document application' => [ApplicationDocument::class, 'application', Application::class, 'application_id'],
    'screening application' => [AiScreening::class, 'application', Application::class, 'application_id'],
    'screening job' => [AiScreening::class, 'job', Job::class, 'job_id'],
    'screening document' => [AiScreening::class, 'document', ApplicationDocument::class, 'document_id'],
    'email candidate' => [EmailMessage::class, 'candidate', Candidate::class, 'candidate_id'],
    'email application' => [EmailMessage::class, 'application', Application::class, 'application_id'],
    'activity user' => [ActivityLog::class, 'user', User::class, 'user_id'],
    'activity application' => [ActivityLog::class, 'application', Application::class, 'application_id'],
    'activity candidate' => [ActivityLog::class, 'candidate', Candidate::class, 'candidate_id'],
]);

test('has-many relations prepare children with the expected parent foreign key', function (
    string $modelClass,
    string $relationName,
    string $childClass,
    string $foreignKey,
) {
    $parent = (new $modelClass)->forceFill(['id' => 42]);

    $child = $parent->{$relationName}()->make();

    expect($child)->toBeInstanceOf($childClass);
    expect($child->getAttribute($foreignKey))->toBe(42);
})->with([
    'company users' => [Company::class, 'users', User::class, 'company_id'],
    'company jobs' => [Company::class, 'jobs', Job::class, 'company_id'],
    'company candidates' => [Company::class, 'candidates', Candidate::class, 'company_id'],
    'company applications' => [Company::class, 'applications', Application::class, 'company_id'],
    'user reviewed applications' => [User::class, 'reviewedApplications', Application::class, 'reviewed_by'],
    'user activity logs' => [User::class, 'activityLogs', ActivityLog::class, 'user_id'],
    'job applications' => [Job::class, 'applications', Application::class, 'job_id'],
    'job screenings' => [Job::class, 'aiScreenings', AiScreening::class, 'job_id'],
    'candidate applications' => [Candidate::class, 'applications', Application::class, 'candidate_id'],
    'candidate emails' => [Candidate::class, 'emailMessages', EmailMessage::class, 'candidate_id'],
    'candidate activity logs' => [Candidate::class, 'activityLogs', ActivityLog::class, 'candidate_id'],
    'application documents' => [Application::class, 'documents', ApplicationDocument::class, 'application_id'],
    'application screenings' => [Application::class, 'aiScreenings', AiScreening::class, 'application_id'],
    'application emails' => [Application::class, 'emailMessages', EmailMessage::class, 'application_id'],
    'application activity logs' => [Application::class, 'activityLogs', ActivityLog::class, 'application_id'],
    'document screenings' => [ApplicationDocument::class, 'aiScreenings', AiScreening::class, 'document_id'],
]);

test('JSON fields round trip structured values', function (string $modelClass, string $attribute) {
    $model = new $modelClass;
    $value = ['skills' => ['PHP', 'Laravel'], 'metadata' => ['score' => 90]];

    $model->fill([$attribute => $value]);

    expect($model->getAttribute($attribute))->toBe($value);
    expect($model->getAttributes()[$attribute])->json()->toBe($value);
})->with([
    'required skills' => [Job::class, 'required_skills'],
    'optional skills' => [Job::class, 'nice_to_have'],
    'candidate source metadata' => [Candidate::class, 'source_meta'],
    'parsed AI response' => [AiScreening::class, 'parsed_response'],
    'candidate skills' => [AiScreening::class, 'candidate_skills'],
    'matched skills' => [AiScreening::class, 'matched_skills'],
    'missing skills' => [AiScreening::class, 'missing_skills'],
    'risk flags' => [AiScreening::class, 'risk_flags'],
    'activity metadata' => [ActivityLog::class, 'meta'],
    'setting value' => [Setting::class, 'value'],
]);

test('numeric attributes are exposed as integers', function (string $modelClass, string $attribute) {
    $model = new $modelClass;

    $model->fill([$attribute => '42']);

    expect($model->getAttribute($attribute))->toBe(42);
})->with([
    'minimum experience' => [Job::class, 'min_experience_years'],
    'latest match score' => [Application::class, 'latest_match_score'],
    'document size' => [ApplicationDocument::class, 'size_bytes'],
    'match score' => [AiScreening::class, 'match_score'],
    'candidate experience' => [AiScreening::class, 'experience_years'],
    'screening duration' => [AiScreening::class, 'duration_ms'],
]);

test('domain timestamps are exposed as date objects', function (string $modelClass, string $attribute) {
    $model = new $modelClass;

    $model->fill([$attribute => '2026-10-19 10:30:00']);

    expect($model->getAttribute($attribute))->toBeInstanceOf(CarbonInterface::class);
    expect($model->getAttribute($attribute)->toDateTimeString())->toBe('2026-10-19 10:30:00');
})->with([
    'latest AI run' => [Application::class, 'latest_ai_at'],
    'application submission' => [Application::class, 'submitted_at'],
    'application review' => [Application::class, 'reviewed_at'],
    'screening start' => [AiScreening::class, 'started_at'],
    'screening completion' => [AiScreening::class, 'completed_at'],
    'email receipt' => [EmailMessage::class, 'received_at'],
]);

test('optional cast attributes preserve null', function (string $modelClass, string $attribute) {
    $model = new $modelClass;

    $model->fill([$attribute => null]);

    expect($model->getAttribute($attribute))->toBeNull();
})->with([
    'optional skills' => [Job::class, 'nice_to_have'],
    'source metadata' => [Candidate::class, 'source_meta'],
    'latest score' => [Application::class, 'latest_match_score'],
    'latest AI timestamp' => [Application::class, 'latest_ai_at'],
    'review timestamp' => [Application::class, 'reviewed_at'],
    'reviewer' => [Application::class, 'reviewed_by'],
    'file size' => [ApplicationDocument::class, 'size_bytes'],
    'document' => [AiScreening::class, 'document_id'],
    'parsed response' => [AiScreening::class, 'parsed_response'],
    'candidate skills' => [AiScreening::class, 'candidate_skills'],
    'matched skills' => [AiScreening::class, 'matched_skills'],
    'missing skills' => [AiScreening::class, 'missing_skills'],
    'risk flags' => [AiScreening::class, 'risk_flags'],
    'screening start' => [AiScreening::class, 'started_at'],
    'screening completion' => [AiScreening::class, 'completed_at'],
    'activity metadata' => [ActivityLog::class, 'meta'],
    'setting value' => [Setting::class, 'value'],
]);

test('email attachment flags are exposed as booleans', function (int $value, bool $expected) {
    $email = new EmailMessage(['has_attachment' => $value]);

    expect($email->has_attachment)->toBe($expected);
})->with([
    'no attachment' => [0, false],
    'has attachment' => [1, true],
]);

test('activity logs maintain only a creation timestamp', function () {
    $this->travelTo(Carbon::parse('2026-10-19 10:30:00'));
    $log = ActivityLog::factory()->make();

    $log->updateTimestamps();

    expect($log->created_at->toDateTimeString())->toBe('2026-10-19 10:30:00');
    expect($log->getAttributes())->not->toHaveKey('updated_at');
});

test('user role and company cannot be changed through mass assignment', function () {
    $user = new User;
    $user->company_id = 1;
    $user->role = 'viewer';

    $user->fill(['company_id' => 2, 'role' => 'admin']);

    expect($user->company_id)->toBe(1);
    expect($user->role)->toBe('viewer');
});

test('factories can prepare models without SQL when parent identifiers are supplied', function (
    string $modelClass,
    array $attributes,
) {
    $model = $modelClass::factory()->make($attributes);

    expect($model)->toBeInstanceOf($modelClass);
    expect($model->exists)->toBeFalse();
})->with([
    'company' => [Company::class, []],
    'user' => [User::class, ['company_id' => 1]],
    'job' => [Job::class, ['company_id' => 1]],
    'candidate' => [Candidate::class, ['company_id' => 1]],
    'application' => [Application::class, ['company_id' => 1, 'candidate_id' => 1, 'job_id' => 1]],
    'document' => [ApplicationDocument::class, ['application_id' => 1]],
    'screening' => [AiScreening::class, ['application_id' => 1, 'job_id' => 1]],
    'email' => [EmailMessage::class, []],
    'activity log' => [ActivityLog::class, []],
    'setting' => [Setting::class, []],
]);

test('queue storage does not use the recruitment jobs table', function () {
    expect(config('queue.connections.database.table'))->toBe('queue_jobs');
    expect((new Job)->getTable())->toBe('jobs');
});

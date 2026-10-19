<?php

use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('job permissions require a company and an appropriate role', function (string $role, bool $canView, bool $canManage) {
    $user = User::factory()->make(['id' => 1, 'company_id' => 1, 'role' => $role]);
    $ownJob = Job::factory()->make(['id' => 1, 'company_id' => 1]);
    $otherJob = Job::factory()->make(['id' => 2, 'company_id' => 2]);
    $gate = Gate::forUser($user);

    expect($gate->allows('viewAny', Job::class))->toBe($canView);
    expect($gate->allows('view', $ownJob))->toBe($canView);
    expect($gate->allows('create', Job::class))->toBe($canManage);
    expect($gate->allows('update', $ownJob))->toBe($canManage);
    expect($gate->allows('view', $otherJob))->toBeFalse();
    expect($gate->allows('update', $otherJob))->toBeFalse();
    expect($gate->allows('delete', $otherJob))->toBeFalse();
})->with([
    'admin' => ['admin', true, true],
    'hr' => ['hr', true, true],
    'interviewer' => ['interviewer', true, false],
    'viewer' => ['viewer', true, false],
    'unknown role' => ['owner', false, false],
]);

test('a user without a company cannot access job management', function () {
    $user = User::factory()->make(['id' => 1, 'company_id' => null, 'role' => 'admin']);
    $job = Job::factory()->make(['id' => 1, 'company_id' => 1]);
    $gate = Gate::forUser($user);

    expect($gate->allows('viewAny', Job::class))->toBeFalse();
    expect($gate->allows('create', Job::class))->toBeFalse();
    expect($gate->allows('update', $job))->toBeFalse();
    expect($gate->allows('delete', $job))->toBeFalse();
});

<?php

use App\Models\Application;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('guests must log in to view the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirectToRoute('login');
});

test('users without a company are offered company setup', function () {
    $user = User::factory()->withoutCompany()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertSee('Create your company')->assertSee(route('companies.create'));
});

test('the dashboard shows only the authenticated users company', function () {
    $company = Company::factory()->create(['name' => 'My hiring company']);
    Company::factory()->create(['name' => 'Private other company']);
    $user = User::factory()->for($company)->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertSee('My hiring company')->assertDontSee('Private other company')
        ->assertDontSee('Create your company');
});

test('the dashboard escapes user and company names', function () {
    $company = Company::factory()->create(['name' => '<script>company()</script>']);
    $user = User::factory()->for($company)->create(['name' => '<script>user()</script>']);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertSee($company->name)->assertDontSee($company->name, false)
        ->assertSee($user->name)->assertDontSee($user->name, false);
});

test('the dashboard counts only job postings and applicants of the users company', function () {
    $user = User::factory()->create();
    $jobPosting = JobPosting::factory()->for($user->company)->create();
    JobPosting::factory()->for($user->company)->create();
    Application::factory()->count(3)->create(['company_id' => $user->company_id, 'job_id' => $jobPosting->id]);
    JobPosting::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertViewHas('jobPostingCount', 2)->assertViewHas('applicationCount', 3);
});

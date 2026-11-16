<?php

use App\Models\AiScreening;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

test('a screening summary is read from its markdown file', function () {
    Storage::fake('local');

    $screening = AiScreening::factory()->create();
    $screening->update(['summary_path' => $screening->summaryPathForStorage()]);
    Storage::disk('local')->put($screening->summary_path, "## Summary\n\nStrong match.");

    expect($screening->summary_path)->toBe("ai-screenings/{$screening->application_id}/{$screening->id}.md")
        ->and($screening->summaryMarkdown())->toBe("## Summary\n\nStrong match.");
});

test('a screening without a stored summary returns null', function (?string $summaryPath) {
    Storage::fake('local');

    $screening = AiScreening::factory()->create(['summary_path' => $summaryPath]);

    expect($screening->summaryMarkdown())->toBeNull();
})->with([
    'no path' => [null],
    'missing file' => ['ai-screenings/1/missing.md'],
]);

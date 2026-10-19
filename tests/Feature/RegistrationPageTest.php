<?php

use App\Filament\Auth\Register;
use App\Filament\Pages\CompanyInvitations;
use App\Models\CompanyInvitation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\PresenceVerifierInterface;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    DB::connection()->beforeExecuting(function (): never {
        throw new LogicException('UI tests must not execute SQL or migrations.');
    });

    // Exercise wizard validation without a database. Real uniqueness is covered separately.
    $presence = Mockery::mock(PresenceVerifierInterface::class);
    $presence->shouldReceive('getCount')->andReturn(0);
    app('validator')->setPresenceVerifier($presence);
});

test('registration starts with only email and displays the form before the introduction', function () {
    $this->get(route('filament.admin.auth.register'))
        ->assertOk()
        ->assertSeeInOrder(['id="auth-email"', 'aria-label="Tentang ruang kerja Roku"'], false)
        ->assertDontSee('id="auth-name"', false)
        ->assertDontSee('id="auth-password"', false)
        ->assertDontSee('id="auth-company_name"', false)
        ->assertSee('href="'.route('filament.admin.auth.login').'"', false);
});

test('sign in and the landing page link to registration', function () {
    $this->get(route('filament.admin.auth.login'))
        ->assertOk()
        ->assertSee('Masuk ke ruang Anda.')
        ->assertSee('href="'.route('filament.admin.auth.register').'"', false);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('filament.admin.auth.register').'"', false);
});

test('auth pages load the Filament theme required by their form controls', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('/css/filament/filament/app.css', false);
})->with(['filament.admin.auth.login', 'filament.admin.auth.register']);

test('auth pages offer an accessible light and dark mode toggle', function (string $route) {
    $response = $this->get(route($route));
    $response->assertOk();

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $buttons = $xpath->query('//button[@data-theme-toggle]');

    expect($buttons->length)->toBe(1);
    expect($buttons->item(0)->getAttribute('type'))->toBe('button');
    expect($buttons->item(0)->getAttribute('aria-label'))->toBe('Aktifkan dark mode');
    expect($buttons->item(0)->getAttribute('aria-pressed'))->toBe('false');
})->with(['filament.admin.auth.login', 'filament.admin.auth.register']);

test('public and auth pages load the shared theme controller once before styles', function (string $route) {
    $response = $this->get(route($route));
    $response->assertOk()
        ->assertSeeInOrder(['data-roku-theme-script', 'rel="stylesheet"'], false);

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    expect($xpath->query('//head/script[@data-roku-theme-script]')->length)->toBe(1);
})->with(['home', 'about', 'filament.admin.auth.login', 'filament.admin.auth.register']);

test('the local Filament dashboard loads the shared theme controller', function () {
    config(['app.env' => 'local']);
    $user = User::factory()->make(['id' => 1, 'company_id' => 1, 'role' => 'viewer']);

    $response = $this->actingAs($user)->get(route('filament.admin.pages.dashboard'));
    $response->assertOk()
        ->assertSeeInOrder(['data-roku-theme-script', 'rel="stylesheet"'], false);

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    expect($xpath->query('//head/script[@data-roku-theme-script]')->length)->toBe(1);
});

test('registration has no visible step indicators and only one text sign in link', function () {
    $response = $this->get(route('filament.admin.auth.register'));
    $response->assertOk()
        ->assertDontSee('Tahap pendaftaran')
        ->assertDontSee('Langkah 1 dari 3');

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $links = $xpath->query('//section[@id="auth-form"]//a[@href="'.route('filament.admin.auth.login').'"]');

    expect($links->length)->toBe(1);
    expect($links->item(0)->getAttribute('class'))->not->toContain('button-');
});

test('sign in offers account creation as a text link rather than a button', function () {
    $response = $this->get(route('filament.admin.auth.login'));
    $response->assertOk()->assertSee('Belum punya akun?');

    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $links = $xpath->query('//section[@id="auth-form"]//a[@href="'.route('filament.admin.auth.register').'"]');

    expect($links->length)->toBe(1);
    expect($links->item(0)->getAttribute('class'))->not->toContain('button-');
});

test('invalid email cannot advance to account details', function (mixed $email) {
    Livewire::test(Register::class)
        ->set('data.email', $email)
        ->call('nextStep')
        ->assertHasErrors('data.email')
        ->assertSet('step', 1);
})->with(['empty' => [''], 'malformed' => ['not-an-email'], 'array' => [['email@example.com']]]);

test('registration advances through all three stages and preserves inputs when going back', function () {
    Livewire::test(Register::class)
        ->set('data.email', ' PERSON@Example.com ')
        ->call('nextStep')
        ->assertSet('step', 2)
        ->assertSet('data.email', 'person@example.com')
        ->assertSee('Nama lengkap')
        ->set('data.name', 'Nadia')
        ->set('data.password', 'secure-password')
        ->call('nextStep')
        ->assertSet('step', 3)
        ->assertSee('Buat perusahaan')
        ->assertSee('Gabung perusahaan')
        ->set('data.company_mode', 'join')
        ->assertSee('Kode atau link undangan')
        ->assertDontSee('id="auth-company_name"', false)
        ->call('previousStep')
        ->assertSet('step', 2)
        ->assertSet('data.name', 'Nadia')
        ->assertSet('data.password', 'secure-password')
        ->call('previousStep')
        ->assertSet('step', 1)
        ->assertSet('data.email', 'person@example.com');
});

test('account details are required before advancing', function () {
    Livewire::test(Register::class)
        ->set('data.email', 'person@example.com')
        ->call('nextStep')
        ->set('data.password', 'short')
        ->call('nextStep')
        ->assertHasErrors(['data.name' => 'required', 'data.password'])
        ->assertSet('step', 2);
});

test('an email already in use cannot advance', function () {
    $presence = Mockery::mock(PresenceVerifierInterface::class);
    $presence->shouldReceive('getCount')->andReturn(1);
    app('validator')->setPresenceVerifier($presence);

    Livewire::test(Register::class)
        ->set('data.email', 'person@example.com')
        ->call('nextStep')
        ->assertHasErrors(['data.email' => 'unique'])
        ->assertSet('step', 1);
});

test('registration cannot submit before the last stage', function () {
    Livewire::test(Register::class)
        ->set('data.email', 'person@example.com')
        ->set('data.name', 'Nadia')
        ->set('data.password', 'secure-password')
        ->set('data.company_name', 'Nusantara')
        ->call('register')
        ->assertSet('step', 1)
        ->assertNoRedirect();
});

test('clients cannot forge the current registration stage', function () {
    expect(fn () => Livewire::test(Register::class)->set('step', 3))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('final submission revalidates earlier steps and returns to the invalid one', function () {
    Livewire::test(Register::class)
        ->set('data.email', 'person@example.com')
        ->call('nextStep')
        ->set('data.name', 'Nadia')
        ->set('data.password', 'secure-password')
        ->call('nextStep')
        ->set('data.email', 'invalid')
        ->set('data.company_name', 'Nusantara')
        ->call('register')
        ->assertHasErrors('data.email')
        ->assertSet('step', 1);
});

test('company selection is validated without attempting persistence', function (string $mode, string $field) {
    Livewire::test(Register::class)
        ->set('data.email', 'person@example.com')
        ->call('nextStep')
        ->set('data.name', 'Nadia')
        ->set('data.password', 'secure-password')
        ->call('nextStep')
        ->set('data.company_mode', $mode)
        ->call('register')
        ->assertHasErrors($field)
        ->assertSet('step', 3);
})->with([
    'missing company name' => ['create', 'data.company_name'],
    'missing invitation' => ['join', 'data.invitation'],
    'invalid choice' => ['other', 'data.company_mode'],
]);

test('invitation links preselect join without skipping email', function () {
    $token = str_repeat('a', 64);

    Livewire::withQueryParams(['invitation' => $token])->test(Register::class)
        ->assertSet('step', 1)
        ->assertSet('data.company_mode', 'join')
        ->assertSet('data.invitation', $token);
});

test('an invitation link pasted into the form is normalized to its token', function () {
    $token = str_repeat('a', 64);

    Livewire::test(Register::class)
        ->set('data.invitation', route('filament.admin.auth.register', ['invitation' => $token]))
        ->set('data.email', 'person@example.com')
        ->call('nextStep')
        ->assertSet('data.invitation', $token);
});

test('email validation attempts are rate limited', function () {
    $page = Livewire::test(Register::class);

    for ($attempt = 0; $attempt < 11; $attempt++) {
        $page->call('nextStep');
    }

    $page->assertHasErrors('attempt')->assertSet('step', 1);
});

test('an invitation only permits its recipient while unused and unexpired', function (array $overrides, string $email, bool $expected) {
    $this->travelTo(now()->startOfDay());
    $invitation = CompanyInvitation::factory()->make([
        'company_id' => 1,
        'email' => 'person@example.com',
        ...$overrides,
    ]);

    expect($invitation->canBeAcceptedBy($email))->toBe($expected);
    expect($invitation->toArray())->not->toHaveKey('token_hash');
})->with([
    'recipient' => [[], 'PERSON@example.com', true],
    'wrong email' => [[], 'other@example.com', false],
    'used' => [['accepted_at' => '2026-01-01'], 'person@example.com', false],
    'expired' => [['expires_at' => '2026-01-01'], 'person@example.com', false],
    'invalid role' => [['role' => 'admin'], 'person@example.com', false],
]);

test('only company admins can open invitation management', function (string $role, bool $allowed) {
    $user = User::factory()->make(['id' => 1, 'company_id' => 1, 'role' => $role]);
    $this->actingAs($user);

    expect(CompanyInvitations::canAccess())->toBe($allowed);
})->with([
    'admin' => ['admin', true],
    'hr' => ['hr', false],
    'interviewer' => ['interviewer', false],
    'viewer' => ['viewer', false],
]);

test('guests cannot create invitations', function () {
    Livewire::test(CompanyInvitations::class)->assertForbidden();
});

test('invitation management rejects roles outside the offered choices', function () {
    $user = User::factory()->make(['id' => 1, 'company_id' => 1, 'role' => 'admin']);

    Livewire::actingAs($user)->test(CompanyInvitations::class)
        ->assertSee('Email penerima')
        ->set('data.email', 'person@example.com')
        ->set('data.role', 'admin')
        ->call('createInvitation')
        ->assertHasErrors('data.role');
});

test('invitation submit button uses the spaced Filament form footer', function () {
    $user = User::factory()->make(['id' => 1, 'company_id' => 1, 'role' => 'admin']);

    $page = Livewire::actingAs($user)->test(CompanyInvitations::class);

    $document = new DOMDocument;
    $document->loadHTML($page->html(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $buttons = $xpath->query('//form[contains(@class, "fi-sc-form")]//*[contains(@class, "fi-sc-actions")]//button[@type="submit"]');

    expect($buttons->length)->toBe(1);
    expect(trim($buttons->item(0)->textContent))->toBe('Buat link undangan');
});

test('invitation results display the original code separately from its registration link', function () {
    $code = str_repeat('Ab12', 16);
    $url = route('filament.admin.auth.register', ['invitation' => $code]);

    $view = $this->blade('<x-company-invitation-result :code="$code" :url="$url" />', compact('code', 'url'));
    $view->assertSee('Kode undangan')
        ->assertSee('Link undangan')
        ->assertDontSee('Salin seluruh kode ini')
        ->assertDontSee('Huruf besar dan kecil harus tetap sama.');

    $document = new DOMDocument;
    $document->loadHTML((string) $view, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $codeInput = $xpath->query('//input[@id="invitation-code"]')->item(0);
    $urlInput = $xpath->query('//input[@id="invitation-url"]')->item(0);

    expect($codeInput->getAttribute('value'))->toBe($code);
    expect($codeInput->hasAttribute('readonly'))->toBeTrue();
    expect($urlInput->getAttribute('value'))->toBe($url);
    expect($urlInput->hasAttribute('readonly'))->toBeTrue();
});

test('both invitation fields have accessible copy buttons connected to their own value', function () {
    $code = str_repeat('Ab12', 16);
    $url = route('filament.admin.auth.register', ['invitation' => $code]);
    $view = $this->blade('<x-company-invitation-result :code="$code" :url="$url" />', compact('code', 'url'));

    $document = new DOMDocument;
    $document->loadHTML((string) $view, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $buttons = $xpath->query('//button');

    expect($buttons->length)->toBe(2);

    foreach (['invitation-code' => 'Salin kode undangan', 'invitation-url' => 'Salin link undangan'] as $id => $label) {
        $button = $xpath->query('//button[@aria-label="'.$label.'"]')->item(0);

        expect($button)->not->toBeNull();
        expect($button->getAttribute('type'))->toBe('button');
        expect($button->getAttribute('x-data'))->toBe('filamentCopyable');
        expect($button->getAttribute('x-on:click.prevent.stop'))->toContain("document.getElementById('{$id}').value");
        expect($xpath->query('.//*[local-name()="svg"]', $button)->length)->toBe(1);
    }
});

test('invitation creation rechecks admin access after the page was opened', function () {
    $user = User::factory()->make(['id' => 1, 'company_id' => 1, 'role' => 'admin']);
    $page = Livewire::actingAs($user)->test(CompanyInvitations::class);
    $user->role = 'viewer';

    $page->call('createInvitation')->assertForbidden();
});

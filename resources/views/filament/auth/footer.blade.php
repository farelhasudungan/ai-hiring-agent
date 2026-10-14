<footer class="hiring-auth-footer">
    <p>© {{ date('Y') }} Roku</p>
    <p>AI membantu. Manusia memutuskan.</p>
</footer>

<div class="hiring-auth-theme fi-theme-switcher" role="group" aria-label="Tema tampilan"
    x-data="{ theme: window.theme || 'system' }"
    x-on:theme-changed.window="theme = $event.detail">
    @foreach ([['light', 'Terang', 'heroicon-o-sun'], ['dark', 'Gelap', 'heroicon-o-moon'], ['system', 'Sistem', 'heroicon-o-computer-desktop']] as [$theme, $label, $icon])
        <button type="button" class="fi-theme-switcher-btn"
            aria-label="Tema {{ strtolower($label) }}" title="{{ $label }}"
            x-bind:aria-pressed="theme === '{{ $theme }}'"
            x-bind:class="{ 'fi-active': theme === '{{ $theme }}' }"
            x-on:click="$dispatch('theme-changed', '{{ $theme }}')">
            @svg($icon, 'fi-icon', ['aria-hidden' => 'true'])
        </button>
    @endforeach
</div>

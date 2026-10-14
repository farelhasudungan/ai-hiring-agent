<nav class="hiring-auth-nav" aria-label="Login navigation">
    <a href="{{ route('home') }}" class="hiring-auth-home">
        <span aria-hidden="true">&larr;</span> Back to home
    </a>
    <div class="fi-theme-switcher" role="group" aria-label="Color theme"
        x-data="{ theme: window.theme || 'system' }">
        @foreach (['light' => 'heroicon-o-sun', 'dark' => 'heroicon-o-moon', 'system' => 'heroicon-o-computer-desktop'] as $theme => $icon)
            <button type="button" class="fi-theme-switcher-btn"
                aria-label="{{ ucfirst($theme) }} theme"
                x-bind:aria-pressed="theme === '{{ $theme }}'"
                x-bind:class="{ 'fi-active': theme === '{{ $theme }}' }"
                x-on:click="theme = '{{ $theme }}'; $dispatch('theme-changed', theme)">
                @svg($icon, 'fi-icon')
            </button>
        @endforeach
    </div>
</nav>

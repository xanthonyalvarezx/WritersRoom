<nav class="rail" aria-label="Primary">
    <a class="brand" href="{{ route('landing') }}">
        <span class="brand-mark" aria-hidden="true">Wr</span>
        <span>
            <span class="brand-name">Writers Room</span>
            <span class="brand-kicker">Open shelf</span>
        </span>
    </a>

    <ul class="rail-nav">
        <li>
            <a href="{{ route('landing') }}" @if (request()->routeIs('landing')) aria-current="page" @endif>Home</a>
        </li>
        <li><a href="#">About</a></li>
        <li><a href="#">Contact</a></li>
        <li><select class="rail-select" name="genres" id="genres" aria-label="Genre">
                <option value="">Genre</option>
                <x-genre-options />
            </select></li>
    </ul>

    <div class="rail-account">
        <p class="rail-note">Post a piece and leave it open for anyone to read.</p>
        @auth
            <a class="btn btn-ghost" href="{{ route('dashboard') }}"
                @if (request()->routeIs('dashboard')) aria-current="page" @endif>Dashboard</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-ember" type="submit">Log out</button>
            </form>
        @else
            <a class="btn btn-ghost" href="{{ route('login') }}"
                @if (request()->routeIs('login')) aria-current="page" @endif>Login</a>
            <a class="btn btn-ember" href="{{ route('register') }}"
                @if (request()->routeIs('register')) aria-current="page" @endif>Register</a>
        @endauth
    </div>
</nav>

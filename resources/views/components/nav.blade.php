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
        <li><select name="genres" id="genres">
                <option value="all">All</option>
                <option value="fiction">Fiction</option>
                <option value="non-fiction">Non-Fiction</option>
                <option value="poetry">Poetry</option>
                <option value="short-stories">Short Stories</option>
                <option value="novels">Novels</option>
            </select></li>
    </ul>

    <div class="rail-account">
        <p class="rail-note">Post a piece and leave it open for anyone to read.</p>
        <a class="btn btn-ghost" href="#">Login</a>
        <a class="btn btn-ember" href="#">Register</a>
    </div>
</nav>
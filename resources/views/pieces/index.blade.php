<x-main-layout title="My Writings">
    <section class="creations">
        <div class="section-head">
            <div>
                <p class="eyebrow">Your desk</p>
                <h2>My Writings</h2>
            </div>
            <a class="btn btn-ink" href="{{ route('pieces.create') }}">Write another</a>
        </div>

        @if (session('status'))
            <p class="status-note">{{ session('status') }}</p>
        @endif

        @if ($pieces->isEmpty())
            <p class="lede">Nothing here yet. Save a draft or publish a piece and it will land here.</p>
        @else
            <div class="piece-grid">
                @foreach ($pieces as $piece)
                    <article class="piece">
                        @if ($piece->coverUrl())
                            <img class="piece-thumb" src="{{ $piece->coverUrl() }}" alt="">
                        @endif
                        <p class="genre">{{ $piece->statusLabel() }}</p>
                        <h3>
                            <a href="{{ $piece->deskUrl() }}">{{ $piece->title }}</a>
                        </h3>
                        <p class="excerpt">{{ $piece->excerpt() ?: 'No words yet.' }}</p>
                        <div class="piece-foot">
                            <span>{{ $piece->isDraft() ? 'Saved '.$piece->updated_at->format('M j, Y') : $piece->published_at->format('M j, Y') }}</span>
                            <a class="text-link" href="{{ $piece->deskUrl() }}">{{ $piece->deskAction() }}</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-main-layout>

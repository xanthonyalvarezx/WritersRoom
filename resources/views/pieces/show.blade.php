<x-main-layout :title="$piece->title">
    <article class="published">
        <p class="eyebrow">{{ $piece->genreLabel() }}</p>
        <h1>{{ $piece->title }}</h1>
        @if ($piece->coverUrl())
            <img class="piece-cover" src="{{ $piece->coverUrl() }}" alt="">
        @endif
        <p class="byline">{{ $piece->published_at->format('F j, Y') }}</p>
        <div class="piece-body">
            {!! \App\Support\PieceHtml::clean($piece->body) !!}
        </div>
        <a class="text-link" href="{{ route('pieces.index') }}">Back to My Writings</a>
    </article>
</x-main-layout>

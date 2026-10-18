<x-main-layout :title="$piece->title">
    <article class="published">
        <p class="eyebrow">Ready to submit</p>
        <h1>{{ $piece->title }}</h1>
        <p class="byline">{{ $piece->genreLabel() }}</p>
        <div class="piece-body">
            {!! \App\Support\PieceHtml::clean($piece->body) !!}
        </div>
        <div class="editor-actions">
            <form method="post" action="{{ route('pieces.continue', $piece) }}">
                @csrf
                <button class="btn btn-line" type="submit">Continue writing</button>
            </form>
            <form method="post" action="{{ route('pieces.submit', $piece) }}">
                @csrf
                <button class="btn btn-ink" type="submit">Submit</button>
            </form>
        </div>
        <a class="text-link" href="{{ route('pieces.index') }}">Back to My Writings</a>
    </article>
</x-main-layout>

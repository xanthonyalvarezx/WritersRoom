<x-main-layout title="Create">
    <section class="chooser">
        <p class="eyebrow">A new piece</p>
        <h1>What are you writing?</h1>
        <p class="lede">This decides whether the desk needs chapters and a plot.</p>

        <div class="chooser-grid">
            @foreach ($kinds as $key => $kind)
                <a class="chooser-card" href="{{ route('pieces.write', $key) }}">
                    <h2>{{ $kind['label'] }}</h2>
                    <p>{{ $kind['detail'] }}</p>
                </a>
            @endforeach
        </div>
    </section>
</x-main-layout>

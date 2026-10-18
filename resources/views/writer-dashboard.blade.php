<x-main-layout title="Dashboard">
    <section class="auth-panel">
        <div class="auth-card">
            <p class="eyebrow">Your desk</p>
            <h1>Writer Dashboard</h1>
            <p class="lede">Signed in as {{ auth()->user()->pen_name ?: auth()->user()->name }}.</p>
            <div class="desk-actions">
                <a href="{{ route('pieces.create') }}" class="btn btn-ink">Create Piece</a>
                <a href="{{ route('pieces.upload') }}" class="btn btn-line">Upload Piece</a>
                <a href="{{ route('pieces.index') }}" class="btn btn-line">My Pieces</a>
            </div>
        </div>
    </section>
</x-main-layout>

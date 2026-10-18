<x-main-layout title="Upload">
    <section class="chooser">
        <p class="eyebrow">From your computer</p>
        <h1>Upload a piece</h1>
        <p class="lede">Text, Markdown, HTML, Word (.doc and .docx), or OpenDocument (.odt). The writing is saved to your desk.</p>

        <form class="upload-form" method="post" action="{{ route('pieces.upload.store') }}" enctype="multipart/form-data">
            @csrf

            <label>
                <span class="field-label">Title</span>
                <input type="text" name="title" value="{{ old('title') }}">
            </label>
            @error('title')
                <p class="form-error">{{ $message }}</p>
            @enderror

            <label>
                <span class="field-label">Kind</span>
                <select name="kind" required>
                    @foreach ($kinds as $key => $kind)
                        <option value="{{ $key }}" @selected(old('kind', 'short-story') === $key)>{{ $kind['label'] }}</option>
                    @endforeach
                </select>
            </label>
            @error('kind')
                <p class="form-error">{{ $message }}</p>
            @enderror

            <label>
                <span class="field-label">Genre</span>
                <select name="genre" required>
                    <x-genre-options :selected="old('genre', 'fiction')" />
                </select>
            </label>
            @error('genre')
                <p class="form-error">{{ $message }}</p>
            @enderror

            <label>
                <span class="field-label">File</span>
                <input type="file" name="manuscript" accept=".txt,.md,.html,.htm,.doc,.docx,.docm,.odt,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text" required>
            </label>
            @error('manuscript')
                <p class="form-error">{{ $message }}</p>
            @enderror

            <fieldset class="upload-choice">
                <legend class="field-label">Is this finished?</legend>
                <label>
                    <input type="radio" name="finished" value="0" @checked((string) old('finished', '0') === '0')>
                    Still writing
                </label>
                <label>
                    <input type="radio" name="finished" value="1" @checked((string) old('finished', '0') === '1')>
                    Finished
                </label>
            </fieldset>
            @error('finished')
                <p class="form-error">{{ $message }}</p>
            @enderror

            <button class="btn btn-ink" type="submit">Upload</button>
        </form>
    </section>
</x-main-layout>

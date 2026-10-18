@props(['selected' => null])

@foreach (\App\Support\Genres::grouped() as $group => $options)
    <optgroup label="{{ $group }}">
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected(($selected ?? null) === $value)>{{ $label }}</option>
        @endforeach
    </optgroup>
@endforeach

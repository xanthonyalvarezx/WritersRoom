<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Support\Genres;
use App\Support\PieceHtml;
use App\Support\UploadedWriting;
use App\Support\WritingKinds;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PieceController extends Controller
{
    public function index(Request $request)
    {
        $pieces = $request->user()
            ->pieces()
            ->latest('updated_at')
            ->get();

        return view('pieces.index', [
            'pieces' => $pieces,
        ]);
    }

    public function create()
    {
        return view('pieces.choose', [
            'kinds' => WritingKinds::all(),
        ]);
    }

    public function uploadForm()
    {
        return view('pieces.upload', [
            'kinds' => WritingKinds::all(),
        ]);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'genre' => ['required', 'in:'.implode(',', Genres::values())],
            'kind' => ['required', 'in:'.implode(',', array_keys(WritingKinds::all()))],
            'manuscript' => ['required', 'file', 'max:8192', 'extensions:txt,text,md,html,htm,doc,docx,docm,odt'],
            'finished' => ['required', 'boolean'],
        ]);

        $file = $request->file('manuscript');
        $html = UploadedWriting::toHtml($file->getRealPath(), $file->getClientOriginalExtension());

        if (! $this->hasWords($html)) {
            return back()
                ->withInput()
                ->withErrors(['manuscript' => 'This file has no writing to save.']);
        }

        $finished = $request->boolean('finished');
        $story = WritingKinds::isStory($validated['kind']);
        $title = trim((string) ($validated['title'] ?? ''));

        if ($title === '') {
            $title = str(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->replace(['_', '-'], ' ')
                ->title()
                ->limit(255, '')
                ->toString();
        }

        $chapters = null;
        $body = $html;

        if ($story) {
            $chapters = $this->normalizeChapters([['title' => 'Chapter 1', 'body' => $html]]);
            $body = $this->manuscript($chapters, '', '');
        }

        $piece = $request->user()->pieces()->create([
            'kind' => $validated['kind'],
            'title' => $title !== '' ? $title : 'Untitled',
            'genre' => $validated['genre'],
            'body' => $body,
            'chapters' => $chapters,
            'finished' => $finished,
        ]);

        if ($finished) {
            return redirect()
                ->route('pieces.review', $piece)
                ->with('status', 'Saved. Review it, then submit when you are ready.');
        }

        return redirect()
            ->route('pieces.edit', $piece)
            ->with('status', 'Saved. You can continue this draft.');
    }

    public function review(Request $request, Piece $piece)
    {
        abort_unless($piece->user_id === $request->user()->id, 404);

        if (! $piece->isDraft()) {
            return redirect()->route('pieces.show', $piece);
        }

        if (! $piece->finished) {
            return redirect()->route('pieces.edit', $piece);
        }

        return view('pieces.review', [
            'piece' => $piece,
        ]);
    }

    public function submit(Request $request, Piece $piece)
    {
        abort_unless($piece->user_id === $request->user()->id, 404);
        abort_unless($piece->isReady(), 404);

        if (! $this->hasWords($piece->body)) {
            return back()->withErrors(['body' => 'Write something before you submit.']);
        }

        $piece->forceFill([
            'published_at' => $piece->published_at ?? now(),
            'finished' => true,
        ])->save();

        return redirect()
            ->route('pieces.index')
            ->with('status', 'Published to My Writings.');
    }

    public function continueWriting(Request $request, Piece $piece)
    {
        abort_unless($piece->user_id === $request->user()->id, 404);
        abort_unless($piece->isDraft(), 404);

        $piece->forceFill(['finished' => false])->save();

        return redirect()->route('pieces.edit', $piece);
    }

    public function write(string $kind)
    {
        abort_unless(WritingKinds::exists($kind), 404);

        return $this->editor(null, $kind);
    }

    public function edit(Request $request, Piece $piece)
    {
        abort_unless($piece->user_id === $request->user()->id, 404);

        return $this->editor($piece, $piece->kind);
    }

    public function store(Request $request)
    {
        $piece = $request->user()->pieces()->make();

        return $this->savePiece($request, $piece);
    }

    public function update(Request $request, Piece $piece)
    {
        abort_unless($piece->user_id === $request->user()->id, 404);

        return $this->savePiece($request, $piece);
    }

    public function show(Request $request, Piece $piece)
    {
        abort_unless($piece->user_id === $request->user()->id, 404);

        if ($piece->isDraft()) {
            return redirect()->route($piece->finished ? 'pieces.review' : 'pieces.edit', $piece);
        }

        return view('pieces.show', [
            'piece' => $piece,
        ]);
    }

    private function editor(?Piece $piece, ?string $kind)
    {
        $kind = old('kind', $kind);
        $story = WritingKinds::isStory($kind);

        return view('create', [
            'piece' => $piece,
            'kind' => $kind,
            'story' => $story,
            'chapters' => $story ? $this->chaptersFor($piece) : [],
            'desk' => $story ? $this->deskFor($piece) : ['characters' => [], 'notes' => [], 'scenes' => []],
            'prologue' => $story ? PieceHtml::clean((string) old('prologue', $piece?->prologue ?? '')) : '',
            'epilogue' => $story ? PieceHtml::clean((string) old('epilogue', $piece?->epilogue ?? '')) : '',
            'coverUrl' => $piece?->coverUrl(),
            'genreDefault' => $piece?->genre ?? WritingKinds::defaultGenre($kind),
        ]);
    }

    /**
     * @return list<array{title: string, body: string}>
     */
    private function chaptersFor(?Piece $piece): array
    {
        $old = request()->old('chapters');

        if (is_string($old)) {
            $old = json_decode($old, true);
        }

        if (is_array($old) && $old !== []) {
            return $this->normalizeChapters($old);
        }

        if (is_array($piece?->chapters) && $piece->chapters !== []) {
            return $this->normalizeChapters($piece->chapters);
        }

        return [['title' => 'Chapter 1', 'body' => '']];
    }

    /**
     * @param  array<int, mixed>  $chapters
     * @return list<array{title: string, body: string}>
     */
    private function normalizeChapters(array $chapters): array
    {
        $clean = [];

        foreach ($chapters as $chapter) {
            if (! is_array($chapter)) {
                continue;
            }

            $title = trim((string) ($chapter['title'] ?? ''));
            $clean[] = [
                'title' => $title !== '' ? $title : 'Untitled chapter',
                'body' => PieceHtml::clean((string) ($chapter['body'] ?? '')),
            ];
        }

        return $clean !== [] ? $clean : [['title' => 'Chapter 1', 'body' => '']];
    }

    /**
     * @return array{characters: list<array{name: string, body: string}>, notes: list<array{name: string, body: string}>, scenes: list<array{name: string, body: string}>}
     */
    private function deskFor(?Piece $piece): array
    {
        $old = request()->old('desk');

        if (is_string($old)) {
            $old = json_decode($old, true);
        }

        $source = is_array($old) ? $old : ($piece?->desk ?? []);

        return $this->normalizeDesk(is_array($source) ? $source : []);
    }

    /**
     * @param  array<string, mixed>  $desk
     * @return array{characters: list<array{name: string, body: string}>, notes: list<array{name: string, body: string}>, scenes: list<array{name: string, body: string}>}
     */
    private function normalizeDesk(array $desk): array
    {
        $clean = [
            'characters' => [],
            'notes' => [],
            'scenes' => [],
        ];

        foreach (array_keys($clean) as $key) {
            foreach ($desk[$key] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $name = trim(strip_tags((string) ($item['name'] ?? '')));
                $body = trim(strip_tags((string) ($item['body'] ?? '')));

                if ($name === '' && $body === '') {
                    continue;
                }

                $clean[$key][] = [
                    'name' => $name !== '' ? $name : 'Untitled',
                    'body' => $body,
                ];
            }
        }

        return $clean;
    }

    private function savePiece(Request $request, Piece $piece)
    {
        $publishing = $request->input('intent') === 'publish';
        $kind = $request->input('kind', $piece->kind);
        $story = WritingKinds::isStory(is_string($kind) ? $kind : null);
        $legacy = $piece->exists && $piece->kind === null && ! $request->filled('kind');

        foreach (['chapters', 'desk'] as $field) {
            if ($request->filled($field) && is_string($request->input($field))) {
                $decoded = json_decode($request->input($field), true);
                $request->merge([
                    $field => is_array($decoded) ? $decoded : [],
                ]);
            }
        }

        $validated = $request->validate([
            'title' => [$publishing ? 'required' : 'nullable', 'string', 'max:255'],
            'genre' => ['required', 'in:'.implode(',', Genres::values())],
            'kind' => [$legacy ? 'nullable' : 'required', 'in:'.implode(',', array_keys(WritingKinds::all()))],
            'body' => [$publishing && ! $story ? 'required' : 'nullable', 'string'],
            'plot' => ['nullable', 'string'],
            'chapters' => [$story ? 'required' : 'nullable', 'array'],
            'chapters.*.title' => ['nullable', 'string', 'max:255'],
            'chapters.*.body' => ['nullable', 'string'],
            'desk' => ['nullable', 'array'],
            'desk.characters' => ['nullable', 'array'],
            'desk.notes' => ['nullable', 'array'],
            'desk.scenes' => ['nullable', 'array'],
            'desk.characters.*.name' => ['nullable', 'string', 'max:255'],
            'desk.characters.*.body' => ['nullable', 'string'],
            'desk.notes.*.name' => ['nullable', 'string', 'max:255'],
            'desk.notes.*.body' => ['nullable', 'string'],
            'desk.scenes.*.name' => ['nullable', 'string', 'max:255'],
            'desk.scenes.*.body' => ['nullable', 'string'],
            'prologue' => ['nullable', 'string'],
            'epilogue' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);

        $chapters = $story ? $this->normalizeChapters($validated['chapters'] ?? []) : null;
        $desk = $story ? $this->normalizeDesk($validated['desk'] ?? []) : null;
        $prologue = $story ? $this->bookend($validated['prologue'] ?? '') : null;
        $epilogue = $story ? $this->bookend($validated['epilogue'] ?? '') : null;
        $body = $story
            ? $this->manuscript($chapters, $prologue ?? '', $epilogue ?? '')
            : PieceHtml::clean($validated['body'] ?? '');

        if ($publishing && ! $this->hasWords($body)) {
            return back()
                ->withInput()
                ->withErrors(['body' => 'Write something before you publish.']);
        }

        $piece->fill([
            'kind' => $legacy ? null : $validated['kind'],
            'title' => trim($validated['title'] ?? '') !== '' ? $validated['title'] : 'Untitled',
            'genre' => $validated['genre'],
            'body' => $body,
            'plot' => $story ? trim($validated['plot'] ?? '') : null,
            'chapters' => $chapters,
            'desk' => $story && ($desk['characters'] !== [] || $desk['notes'] !== [] || $desk['scenes'] !== []) ? $desk : null,
            'prologue' => $prologue,
            'epilogue' => $epilogue,
        ]);

        if ($story) {
            $this->storeCover($request, $piece);
        }

        if ($publishing) {
            $piece->published_at ??= now();
        }

        $piece->save();

        if ($publishing) {
            return redirect()
                ->route('pieces.index')
                ->with('status', 'Published to My Writings.');
        }

        return redirect()
            ->route('pieces.edit', $piece)
            ->with('status', 'Saved. You can leave and come back to this draft.');
    }

    private function bookend(string $html): ?string
    {
        $clean = PieceHtml::clean($html);

        return $this->hasWords($clean) ? $clean : null;
    }

    private function hasWords(string $html): bool
    {
        return trim(strip_tags(str_replace('&nbsp;', ' ', $html))) !== '';
    }

    private function storeCover(Request $request, Piece $piece): void
    {
        $replacing = $request->hasFile('cover');
        $removing = $request->boolean('remove_cover');

        if (! $replacing && ! $removing) {
            return;
        }

        if ($piece->cover_path) {
            File::delete(public_path($piece->cover_path));
            $piece->cover_path = null;
        }

        if (! $replacing) {
            return;
        }

        File::ensureDirectoryExists(public_path('covers'));
        $name = $request->file('cover')->hashName();
        $request->file('cover')->move(public_path('covers'), $name);
        $piece->cover_path = 'covers/'.$name;
    }

    /**
     * @param  list<array{title: string, body: string}>  $chapters
     */
    private function manuscript(array $chapters, string $prologue, string $epilogue): string
    {
        $parts = [];

        if ($this->hasWords($prologue)) {
            $parts[] = '<h2>Prologue</h2>'.$prologue;
        }

        foreach ($chapters as $chapter) {
            $heading = '<h2>'.e($chapter['title']).'</h2>';
            $parts[] = $heading.($chapter['body'] !== '' ? $chapter['body'] : '');
        }

        if ($this->hasWords($epilogue)) {
            $parts[] = '<h2>Epilogue</h2>'.$epilogue;
        }

        return implode('', $parts);
    }
}

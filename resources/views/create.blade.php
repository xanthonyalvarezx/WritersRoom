<x-main-layout :title="isset($piece) ? 'Continue' : 'Create'">
    <section class="editor">
        <div class="editor-head">
            <p class="eyebrow">{{ isset($piece) && $piece->isDraft() ? 'Draft' : 'At the desk' }}</p>
            <h1>{{ isset($piece) ? 'Continue writing' : 'Write a piece' }}</h1>
        </div>

        @if (session('status'))
            <p class="status-note">{{ session('status') }}</p>
        @endif

        <form class="editor-form {{ $story ? 'is-story' : '' }}" method="post" action="{{ isset($piece) ? route('pieces.update', $piece) : route('pieces.store') }}" enctype="multipart/form-data" data-editor-form>
            @csrf
            @isset($piece)
                @method('PATCH')
            @endisset
            <input type="hidden" name="kind" value="{{ $kind }}">

            <div class="editor-toolbar" role="toolbar" aria-label="Writing tools">
                <label class="tool-field">
                    <span class="visually-hidden">Font</span>
                    <select class="tool-select" data-font aria-label="Font">
                        <option value="Fraunces">Fraunces</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Palatino">Palatino</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Outfit">Outfit</option>
                        <option value="Arial">Arial</option>
                        <option value="Courier New">Courier New</option>
                    </select>
                </label>

                <label class="tool-field">
                    <span class="visually-hidden">Text size</span>
                    <select class="tool-select" data-size aria-label="Text size">
                        <option value="12pt">12</option>
                        <option value="14pt">14</option>
                        <option value="16pt" selected>16</option>
                        <option value="18pt">18</option>
                        <option value="20pt">20</option>
                        <option value="24pt">24</option>
                        <option value="28pt">28</option>
                        <option value="36pt">36</option>
                    </select>
                </label>

                <div class="tool-group">
                    <button class="tool-btn" type="button" data-command="bold" aria-label="Bold"><strong>B</strong></button>
                    <button class="tool-btn" type="button" data-command="italic" aria-label="Italic"><em>I</em></button>
                    <button class="tool-btn" type="button" data-command="underline" aria-label="Underline"><span class="tool-underline">U</span></button>
                    <button class="tool-btn" type="button" data-command="strikeThrough" aria-label="Strikethrough"><span class="tool-strike">S</span></button>
                </div>

                <div class="tool-group">
                    <label class="tool-color" title="Text color">
                        <span>A</span>
                        <input type="color" value="#1c1712" data-color="fore" aria-label="Text color">
                    </label>
                    <label class="tool-color" title="Highlight">
                        <span class="tool-highlight">A</span>
                        <input type="color" value="#f3dd8a" data-color="back" aria-label="Highlight color">
                    </label>
                </div>

                <div class="tool-group">
                    <button class="tool-btn" type="button" data-command="justifyLeft" aria-label="Align left">Left</button>
                    <button class="tool-btn" type="button" data-command="justifyCenter" aria-label="Align center">Center</button>
                    <button class="tool-btn" type="button" data-command="justifyRight" aria-label="Align right">Right</button>
                    <button class="tool-btn" type="button" data-command="justifyFull" aria-label="Justify">Justify</button>
                </div>

                <div class="tool-group">
                    <button class="tool-btn" type="button" data-command="insertUnorderedList" aria-label="Bulleted list">Bullets</button>
                    <button class="tool-btn" type="button" data-command="insertOrderedList" aria-label="Numbered list">Numbers</button>
                    <button class="tool-btn" type="button" data-command="removeFormat" aria-label="Clear formatting">Clear</button>
                </div>

                <div class="tool-group">
                    <button class="tool-btn" type="button" data-command="undo" aria-label="Undo">Undo</button>
                    <button class="tool-btn" type="button" data-command="redo" aria-label="Redo">Redo</button>
                </div>
            </div>

            <div class="editor-stage">
            <div class="editor-column">
            <div class="editor-meta">
                <label>
                    <span class="field-label">Title</span>
                    <input type="text" name="title" value="{{ old('title', isset($piece) ? $piece->title : '') }}">
                </label>
                @error('title')
                    <p class="form-error">{{ $message }}</p>
                @enderror

                <label>
                    <span class="field-label">Genre</span>
                    <select name="genre" required>
                        <x-genre-options :selected="old('genre', $genreDefault ?? 'fiction')" />
                    </select>
                </label>
                @error('genre')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div
                class="editor-sheet"
                data-editor
                contenteditable="true"
                role="textbox"
                aria-multiline="true"
                aria-label="Piece"
                spellcheck="true"
            >{!! \App\Support\PieceHtml::clean($story ? ($chapters[0]['body'] ?? '') : old('body', isset($piece) ? $piece->body : '')) !!}</div>
            @error('body')
                <p class="form-error">{{ $message }}</p>
            @enderror
            </div>

            @if ($story)
                <aside class="story-rail" data-story-rail>
                    <div class="cover-spot">
                        <p class="field-label">Cover</p>
                        <img class="cover-preview" data-cover-preview alt="" @if ($coverUrl) src="{{ $coverUrl }}" @else hidden @endif>
                        <label class="cover-file">
                            <span>Upload cover</span>
                            <input type="file" name="cover" accept="image/*" data-cover>
                        </label>
                        @if ($coverUrl)
                            <label class="cover-remove">
                                <input type="checkbox" name="remove_cover" value="1">
                                Remove cover
                            </label>
                        @endif
                        @error('cover')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <p class="field-label">Add to the novel</p>
                        <div class="desk-create">
                            <button class="btn btn-line" type="button" data-add-card="character">Character</button>
                            <button class="btn btn-line" type="button" data-add-card="note">Note</button>
                            <button class="btn btn-line" type="button" data-add-card="scene">Scene</button>
                        </div>
                        <div data-composer hidden>
                            <label>
                                <span class="field-label" data-composer-name-label>Name</span>
                                <input type="text" data-composer-name>
                            </label>
                            <label>
                                <span class="field-label" data-composer-body-label>Details</span>
                                <textarea class="plot-notes" data-composer-body rows="6"></textarea>
                            </label>
                            <p class="form-error" data-composer-error hidden>Add a name before you create it.</p>
                            <button class="btn btn-ink" type="button" data-create-card>Create</button>
                        </div>
                    </div>
                    <div class="chapter-list" data-desk-list></div>
                    <div class="manuscript-block">
                        <p class="field-label">Manuscript</p>
                        <button class="chapter-button" type="button" data-open-bookend="prologue">Prologue</button>
                        <label>
                            <span class="field-label">Chapter title</span>
                            <input type="text" data-chapter-title>
                        </label>
                        <p class="field-label">Chapters</p>
                        <div class="chapter-list" data-chapter-list></div>
                        <div class="chapter-actions">
                            <button class="btn btn-line" type="button" data-add-chapter>Add chapter</button>
                            <button class="btn btn-line" type="button" data-remove-chapter>Remove</button>
                        </div>
                        <label>
                            <span class="field-label">Characters</span>
                            <select data-card-select="character" aria-label="Characters"></select>
                        </label>
                        <label>
                            <span class="field-label">Scenes</span>
                            <select data-card-select="scene" aria-label="Scenes"></select>
                        </label>
                        <div data-desk-panel hidden>
                            <label>
                                <span class="field-label" data-desk-name-label>Name</span>
                                <input type="text" data-desk-name>
                            </label>
                            <label>
                                <span class="field-label" data-desk-body-label>Details</span>
                                <textarea class="plot-notes" data-desk-body rows="6"></textarea>
                            </label>
                            <button class="btn btn-line" type="button" data-remove-card>Remove</button>
                        </div>
                        <button class="chapter-button" type="button" data-open-bookend="epilogue">Epilogue</button>
                    </div>
                    <label>
                        <span class="field-label">Plot</span>
                        <textarea class="plot-notes" name="plot" rows="10">{{ old('plot', isset($piece) ? $piece->plot : '') }}</textarea>
                    </label>
                    <script type="application/json" data-chapters-json>@json($chapters)</script>
                    <script type="application/json" data-desk-json>@json($desk)</script>
                    <script type="application/json" data-bookends-json>@json(['prologue' => $prologue, 'epilogue' => $epilogue])</script>
                    <input type="hidden" name="chapters" data-chapters value="">
                    <input type="hidden" name="desk" data-desk value="">
                    <input type="hidden" name="prologue" data-prologue value="">
                    <input type="hidden" name="epilogue" data-epilogue value="">
                </aside>
            @endif
            </div>

            <textarea name="body" data-editor-body hidden></textarea>

            <div class="editor-actions">
                <button class="btn btn-line" type="submit" name="intent" value="save">Save</button>
                <button class="btn btn-ink" type="submit" name="intent" value="publish">Finished and publish</button>
            </div>
        </form>
    </section>

    @push('scripts')
        <script src="{{ asset('js/editor.js') }}"></script>
    @endpush
</x-main-layout>

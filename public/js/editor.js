const form = document.querySelector("[data-editor-form]");
const editor = document.querySelector("[data-editor]");
const body = document.querySelector("[data-editor-body]");

if (form && editor && body) {
    let savedRange = null;

    const saveSelection = () => {
        const selection = window.getSelection();

        if (!selection || selection.rangeCount === 0) {
            return;
        }

        if (editor.contains(selection.anchorNode)) {
            savedRange = selection.getRangeAt(0).cloneRange();
        }
    };

    const restoreSelection = () => {
        if (!savedRange) {
            editor.focus();
            return;
        }

        editor.focus();
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(savedRange);
    };

    editor.addEventListener("keyup", saveSelection);
    editor.addEventListener("mouseup", saveSelection);

    const focusEditor = () => {
        restoreSelection();
    };

    const run = (command, value = null) => {
        focusEditor();
        document.execCommand(command, false, value);
        saveSelection();
    };

    document.querySelectorAll("[data-command]").forEach((button) => {
        button.addEventListener("mousedown", (event) => {
            event.preventDefault();
        });

        button.addEventListener("click", () => {
            run(button.dataset.command);
        });
    });

    document.querySelector("[data-font]")?.addEventListener("mousedown", saveSelection);
    document.querySelector("[data-size]")?.addEventListener("mousedown", saveSelection);
    document.querySelectorAll("[data-color]").forEach((input) => {
        input.addEventListener("mousedown", saveSelection);
    });

    document.querySelector("[data-font]")?.addEventListener("change", (event) => {
        run("fontName", event.target.value);
    });

    document.querySelector("[data-size]")?.addEventListener("change", (event) => {
        focusEditor();
        document.execCommand("fontSize", false, "7");
        editor.querySelectorAll('font[size="7"]').forEach((node) => {
            node.removeAttribute("size");
            node.style.fontSize = event.target.value;
        });
        saveSelection();
    });

    document.querySelectorAll("[data-color]").forEach((input) => {
        input.addEventListener("input", () => {
            if (input.dataset.color === "back") {
                focusEditor();
                const highlighted = document.execCommand("hiliteColor", false, input.value);
                if (!highlighted) {
                    document.execCommand("backColor", false, input.value);
                }
                return;
            }

            run("foreColor", input.value);
        });
    });

    const normalize = (root) => {
        root.querySelectorAll("[align]").forEach((node) => {
            const align = node.getAttribute("align");

            if (align && !node.style.textAlign) {
                node.style.textAlign = align;
            }

            node.removeAttribute("align");
        });

        root.querySelectorAll("font").forEach((node) => {
            const span = document.createElement("span");
            const face = node.getAttribute("face");
            const color = node.getAttribute("color");

            if (face) {
                span.style.fontFamily = face;
            }

            if (color) {
                span.style.color = color;
            }

            if (node.style.fontSize) {
                span.style.fontSize = node.style.fontSize;
            }

            if (node.style.backgroundColor) {
                span.style.backgroundColor = node.style.backgroundColor;
            }

            span.append(...node.childNodes);
            node.replaceWith(span);
        });
    };

    const rail = document.querySelector("[data-story-rail]");
    let syncStory = null;

    if (rail) {
        const chapterList = rail.querySelector("[data-chapter-list]");
        const chapterTitle = rail.querySelector("[data-chapter-title]");
        const chaptersInput = rail.querySelector("[data-chapters]");
        let chapters = JSON.parse(rail.querySelector("[data-chapters-json]").textContent || "[]");
        let activeChapter = 0;
        const bookends = JSON.parse(rail.querySelector("[data-bookends-json]").textContent || "{}");
        const prologueInput = rail.querySelector("[data-prologue]");
        const epilogueInput = rail.querySelector("[data-epilogue]");
        let surface = "chapter";
        let prologue = typeof bookends.prologue === "string" ? bookends.prologue : "";
        let epilogue = typeof bookends.epilogue === "string" ? bookends.epilogue : "";

        if (!Array.isArray(chapters) || chapters.length === 0) {
            chapters = [{ title: "Chapter 1", body: "" }];
        }

        const renderChapters = () => {
            chapterList.replaceChildren();

            chapters.forEach((chapter, index) => {
                const button = document.createElement("button");
                button.type = "button";
                button.className = "chapter-button";
                button.textContent = chapter.title || `Chapter ${index + 1}`;

                if (surface === "chapter" && index === activeChapter) {
                    button.setAttribute("aria-current", "true");
                }

                button.addEventListener("click", () => selectChapter(index));
                chapterList.append(button);
            });

            chapterTitle.value = chapters[activeChapter].title || "";
            chapterTitle.disabled = surface !== "chapter";

            rail.querySelectorAll("[data-open-bookend]").forEach((button) => {
                if (button.getAttribute("data-open-bookend") === surface) {
                    button.setAttribute("aria-current", "true");
                } else {
                    button.removeAttribute("aria-current");
                }
            });
        };

        const rememberWriting = () => {
            normalize(editor);

            if (surface === "prologue") {
                prologue = editor.innerHTML;
                return;
            }

            if (surface === "epilogue") {
                epilogue = editor.innerHTML;
                return;
            }

            chapters[activeChapter].title = chapterTitle.value.trim() || `Chapter ${activeChapter + 1}`;
            chapters[activeChapter].body = editor.innerHTML;
        };

        const showChapter = (index) => {
            surface = "chapter";
            activeChapter = index;
            editor.innerHTML = chapters[index].body || "";
            renderChapters();
        };

        const selectChapter = (index) => {
            rememberWriting();
            showChapter(index);
        };

        const openBookend = (which) => {
            rememberWriting();
            surface = which;
            editor.innerHTML = (which === "prologue" ? prologue : epilogue) || "";
            renderChapters();
        };

        chapterTitle.addEventListener("input", () => {
            chapters[activeChapter].title = chapterTitle.value;
            const current = chapterList.children[activeChapter];

            if (current) {
                current.textContent = chapterTitle.value.trim() || `Chapter ${activeChapter + 1}`;
            }
        });

        rail.querySelector("[data-add-chapter]").addEventListener("click", () => {
            rememberWriting();
            chapters.push({ title: `Chapter ${chapters.length + 1}`, body: "" });
            showChapter(chapters.length - 1);
        });

        rail.querySelector("[data-remove-chapter]").addEventListener("click", () => {
            if (chapters.length === 1) {
                return;
            }

            rememberWriting();
            chapters.splice(activeChapter, 1);
            showChapter(Math.max(0, activeChapter - 1));
        });

        rail.querySelectorAll("[data-open-bookend]").forEach((button) => {
            button.addEventListener("click", () => openBookend(button.getAttribute("data-open-bookend")));
        });

        const deskKinds = {
            character: { key: "characters", label: "Character", name: "New character", bodyLabel: "Who they are" },
            note: { key: "notes", label: "Note", name: "New note", bodyLabel: "Note" },
            scene: { key: "scenes", label: "Scene", name: "New scene", bodyLabel: "What happens" },
        };
        const deskInput = rail.querySelector("[data-desk]");
        const deskList = rail.querySelector("[data-desk-list]");
        const deskPanel = rail.querySelector("[data-desk-panel]");
        const deskName = rail.querySelector("[data-desk-name]");
        const deskBody = rail.querySelector("[data-desk-body]");
        const deskNameLabel = rail.querySelector("[data-desk-name-label]");
        const deskBodyLabel = rail.querySelector("[data-desk-body-label]");
        const composer = rail.querySelector("[data-composer]");
        const composerName = rail.querySelector("[data-composer-name]");
        const composerBody = rail.querySelector("[data-composer-body]");
        const composerNameLabel = rail.querySelector("[data-composer-name-label]");
        const composerBodyLabel = rail.querySelector("[data-composer-body-label]");
        const composerError = rail.querySelector("[data-composer-error]");
        let desk = JSON.parse(rail.querySelector("[data-desk-json]").textContent || "{}");
        let activeCard = null;
        let drafting = null;

        if (!desk || typeof desk !== "object") {
            desk = {};
        }

        Object.values(deskKinds).forEach((kind) => {
            if (!Array.isArray(desk[kind.key])) {
                desk[kind.key] = [];
            }
        });

        const rememberCard = () => {
            if (!activeCard) {
                return;
            }

            const item = desk[deskKinds[activeCard.type].key][activeCard.index];

            if (!item) {
                return;
            }

            item.name = deskName.value;
            item.body = deskBody.value;
        };

        const fillCardSelect = (type) => {
            const select = rail.querySelector(`[data-card-select="${type}"]`);
            const kind = deskKinds[type];
            select.replaceChildren();

            const placeholder = document.createElement("option");
            placeholder.value = "";
            placeholder.textContent = desk[kind.key].length === 0 ? `No ${kind.key} yet` : `View ${kind.key}`;
            select.append(placeholder);

            desk[kind.key].forEach((item, index) => {
                const option = document.createElement("option");
                option.value = String(index);
                option.textContent = item.name.trim() || kind.name;
                select.append(option);
            });

            select.value = activeCard && activeCard.type === type ? String(activeCard.index) : "";
        };

        const renderDesk = () => {
            deskList.replaceChildren();

            desk.notes.forEach((item, index) => {
                const button = document.createElement("button");
                button.type = "button";
                button.className = "chapter-button";
                button.textContent = `Note · ${item.name.trim() || deskKinds.note.name}`;

                if (activeCard && activeCard.type === "note" && activeCard.index === index) {
                    button.setAttribute("aria-current", "true");
                }

                button.addEventListener("click", () => selectCard("note", index));
                deskList.append(button);
            });

            fillCardSelect("character");
            fillCardSelect("scene");

            if (!activeCard || !desk[deskKinds[activeCard.type].key][activeCard.index]) {
                deskPanel.hidden = true;
                activeCard = null;
                return;
            }

            const kind = deskKinds[activeCard.type];
            const item = desk[kind.key][activeCard.index];
            deskPanel.hidden = false;
            deskNameLabel.textContent = kind.label;
            deskBodyLabel.textContent = kind.bodyLabel;
            deskName.value = item.name;
            deskBody.value = item.body;
        };

        const closeComposer = () => {
            drafting = null;
            composer.hidden = true;
            composerName.value = "";
            composerBody.value = "";
            composerError.hidden = true;
        };

        const openComposer = (type) => {
            const kind = deskKinds[type];

            if (!kind) {
                return;
            }

            rememberCard();
            activeCard = null;
            drafting = type;
            composer.hidden = false;
            composerError.hidden = true;
            composerNameLabel.textContent = kind.label;
            composerBodyLabel.textContent = kind.bodyLabel;
            composerName.value = "";
            composerBody.value = "";
            renderDesk();
            composerName.focus();
        };

        const selectCard = (type, index) => {
            rememberCard();
            closeComposer();
            activeCard = { type, index };
            renderDesk();
        };

        deskName.addEventListener("input", () => {
            rememberCard();

            if (!activeCard) {
                return;
            }

            const kind = deskKinds[activeCard.type];
            const label = deskName.value.trim() || kind.name;

            if (activeCard.type === "note") {
                const current = deskList.children[activeCard.index];

                if (current) {
                    current.textContent = `${kind.label} · ${label}`;
                }

                return;
            }

            const select = rail.querySelector(`[data-card-select="${activeCard.type}"]`);
            const option = select.options[activeCard.index + 1];

            if (option) {
                option.textContent = label;
            }
        });

        rail.querySelectorAll("[data-card-select]").forEach((select) => {
            select.addEventListener("change", () => {
                const type = select.getAttribute("data-card-select");

                if (select.value === "") {
                    if (activeCard && activeCard.type === type) {
                        rememberCard();
                        activeCard = null;
                        renderDesk();
                    }

                    return;
                }

                selectCard(type, Number(select.value));
            });
        });

        deskBody.addEventListener("input", rememberCard);

        rail.querySelectorAll("[data-add-card]").forEach((button) => {
            button.addEventListener("click", () => openComposer(button.getAttribute("data-add-card")));
        });

        rail.querySelector("[data-create-card]").addEventListener("click", () => {
            const kind = drafting ? deskKinds[drafting] : null;
            const name = composerName.value.trim();

            if (!kind) {
                return;
            }

            if (name === "") {
                composerError.hidden = false;
                composerName.focus();
                return;
            }

            desk[kind.key].push({
                name,
                body: composerBody.value.trim(),
            });
            activeCard = { type: drafting, index: desk[kind.key].length - 1 };
            closeComposer();
            renderDesk();
        });

        rail.querySelector("[data-remove-card]").addEventListener("click", () => {
            if (!activeCard) {
                return;
            }

            desk[deskKinds[activeCard.type].key].splice(activeCard.index, 1);
            activeCard = null;
            renderDesk();
        });

        const coverInput = rail.querySelector("[data-cover]");
        const coverPreview = rail.querySelector("[data-cover-preview]");

        if (coverInput && coverPreview) {
            coverInput.addEventListener("change", () => {
                const file = coverInput.files && coverInput.files[0];

                if (!file) {
                    return;
                }

                coverPreview.src = URL.createObjectURL(file);
                coverPreview.hidden = false;
            });
        }

        syncStory = () => {
            rememberWriting();
            rememberCard();
            chaptersInput.value = JSON.stringify(chapters);
            deskInput.value = JSON.stringify(desk);
            prologueInput.value = prologue;
            epilogueInput.value = epilogue;
            body.value = [prologue, ...chapters.map((chapter) => chapter.body), epilogue].join("");
        };

        renderChapters();
        renderDesk();
    }

    form.addEventListener("submit", () => {
        normalize(editor);

        if (typeof syncStory === "function") {
            syncStory();
        } else {
            body.value = editor.innerHTML;
        }
    });
}

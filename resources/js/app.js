import $ from './jquery.js';

import tabler from '@tabler/core/dist/js/tabler.min.js';
import select2 from 'select2';
import * as FilePond from 'filepond';

// Tabler's bundle embeds Bootstrap's JS (Modal, Collapse, Dropdown, …) and wires
// up its `data-bs-*` API on import. Under a bundler the UMD wrapper takes its
// CommonJS branch, so nothing is published on `window` — republish it ourselves,
// both for the code below and for any inline script in a Blade view.
const bootstrap = tabler.bootstrap ?? tabler;
window.bootstrap = bootstrap;
window.tabler = tabler;

/* -------------------------------------------------------------------------
 * Select2 bootstrap
 *
 * select2 ships a UMD bundle. Under a bundler (Vite/Rollup/esbuild) the
 * CommonJS branch of that wrapper is taken, and it sets
 *
 *     module.exports = function (root, jQuery) { factory(jQuery); return jQuery; }
 *
 * i.e. the package's export is a *factory that still has to be invoked*. A bare
 * `import 'select2'` only evaluates the module body, never calls the factory,
 * so `$.fn.select2` is left undefined and every `$(el).select2(...)` call throws
 * "$(...).select2 is not a function". Calling the factory explicitly is what
 * actually installs the plugin onto jQuery.
 * ---------------------------------------------------------------------- */
select2(window, $);

const PT_BR = {
    errorLoading: () => 'Não foi possível carregar os resultados.',
    inputTooLong: (args) => {
        const over = args.input.length - args.maximum;
        return `Apague ${over} caractere${over !== 1 ? 's' : ''}.`;
    },
    inputTooShort: (args) => {
        const missing = args.minimum - args.input.length;
        return `Digite ${missing} caractere${missing !== 1 ? 's' : ''} ou mais.`;
    },
    loadingMore: () => 'Carregando mais resultados…',
    maximumSelected: (args) =>
        `Você só pode selecionar ${args.maximum} item${args.maximum !== 1 ? 's' : ''}.`,
    noResults: () => 'Nenhum resultado encontrado',
    searching: () => 'Buscando…',
    removeAllItems: () => 'Remover todos os itens',
    removeItem: () => 'Remover item',
    search: () => 'Buscar',
};

/**
 * Turn every `[data-searchable]` element into a Select2 control.
 *
 * Declarative on purpose: adding a searchable select to a Blade view is just
 * `<select data-searchable data-placeholder="…">`, with no matching JS to write.
 *
 * The attribute is deliberately NOT called `data-select2`: Select2 reads its own
 * instance back with `Utils.GetData(el, 'select2')`, which falls through to
 * jQuery's `.data('select2')` — and that resolves a `data-select2` attribute to
 * `""`. Being non-null, Select2 then calls `"".destroy()` and every
 * initialisation dies with "GetData(...).destroy is not a function".
 *
 * @param {ParentNode} root scope to scan (defaults to the whole document)
 */
function initSelect2(root = document) {
    root.querySelectorAll('[data-searchable]').forEach((el) => {
        const $el = $(el);

        // Idempotent: re-initialising would stack duplicate containers.
        // `select2-hidden-accessible` is the class Select2 puts on the original
        // <select>, so it is the reliable "already initialised" marker.
        if ($el.hasClass('select2-hidden-accessible')) {
            return;
        }

        const multiple = el.multiple;
        const placeholder =
            el.dataset.placeholder || (multiple ? 'Selecione uma ou mais opções' : 'Selecione');

        // Inside a Bootstrap modal the dropdown must be appended to the modal,
        // otherwise the modal's focus trap swallows clicks on the results.
        const modal = el.closest('.modal');

        $el.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder,
            language: PT_BR,
            closeOnSelect: !multiple,
            allowClear: !multiple && el.dataset.searchableClear !== 'false',
            selectionCssClass: 'select2--tabler',
            dropdownCssClass: 'select2--tabler',
            dropdownParent: modal ? $(modal) : undefined,
        });
    });
}

// Select2 does not focus its search box on open in every configuration; without
// this you have to click the field a second time before you can type.
$(document).on('select2:open', () => {
    document.querySelector('.select2-container--open .select2-search__field')?.focus();
});

/* -------------------------------------------------------------------------
 * Colour scheme (light / dark / follow the system)
 * ---------------------------------------------------------------------- */
const THEME_KEY = 'kzion-theme';

function resolveTheme(choice) {
    if (choice === 'light' || choice === 'dark') {
        return choice;
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyTheme(choice) {
    document.documentElement.dataset.bsTheme = resolveTheme(choice);
    document.documentElement.dataset.themeChoice = choice;

    document.querySelectorAll('[data-theme-value]').forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.themeValue === choice);
    });
}

function initTheme() {
    let stored = 'auto';
    try {
        stored = localStorage.getItem(THEME_KEY) || 'auto';
    } catch {
        // Private mode / blocked storage: fall back to following the system.
    }

    applyTheme(stored);

    document.querySelectorAll('[data-theme-value]').forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            const choice = btn.dataset.themeValue;
            try {
                localStorage.setItem(THEME_KEY, choice);
            } catch {
                // Not persisting is fine; the choice still applies to this page.
            }
            applyTheme(choice);
        });
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (document.documentElement.dataset.themeChoice === 'auto') {
            applyTheme('auto');
        }
    });
}

/* -------------------------------------------------------------------------
 * Photo lightbox
 *
 * Any `[data-lightbox]` link inside a `[data-lightbox-gallery]` container opens
 * in an overlay with keyboard navigation, instead of navigating away.
 * ---------------------------------------------------------------------- */
function initLightbox() {
    const galleries = document.querySelectorAll('[data-lightbox-gallery]');
    if (!galleries.length) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.className = 'lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Visualizador de fotos');
    overlay.innerHTML = `
        <button type="button" class="lightbox__btn lightbox__close" aria-label="Fechar">
            <i class="ti ti-x"></i>
        </button>
        <button type="button" class="lightbox__btn lightbox__prev" aria-label="Foto anterior">
            <i class="ti ti-chevron-left"></i>
        </button>
        <figure class="lightbox__figure">
            <img class="lightbox__img" alt="">
            <figcaption class="lightbox__caption"></figcaption>
        </figure>
        <button type="button" class="lightbox__btn lightbox__next" aria-label="Próxima foto">
            <i class="ti ti-chevron-right"></i>
        </button>
    `;
    document.body.appendChild(overlay);

    const img = overlay.querySelector('.lightbox__img');
    const caption = overlay.querySelector('.lightbox__caption');
    const prevBtn = overlay.querySelector('.lightbox__prev');
    const nextBtn = overlay.querySelector('.lightbox__next');

    let items = [];
    let index = 0;
    let lastFocused = null;

    function render() {
        const item = items[index];
        if (!item) {
            return;
        }
        img.src = item.dataset.lightbox;
        img.alt = item.dataset.lightboxCaption || '';
        caption.textContent = item.dataset.lightboxCaption || '';
        caption.hidden = !item.dataset.lightboxCaption;

        const many = items.length > 1;
        prevBtn.hidden = !many;
        nextBtn.hidden = !many;
    }

    function open(gallery, target) {
        items = Array.from(gallery.querySelectorAll('[data-lightbox]'));
        index = Math.max(0, items.indexOf(target));
        lastFocused = document.activeElement;
        render();
        overlay.classList.add('is-open');
        document.body.classList.add('lightbox-open');
        overlay.querySelector('.lightbox__close').focus();
    }

    function close() {
        overlay.classList.remove('is-open');
        document.body.classList.remove('lightbox-open');
        img.src = '';
        lastFocused?.focus();
    }

    function step(delta) {
        if (!items.length) {
            return;
        }
        index = (index + delta + items.length) % items.length;
        render();
    }

    galleries.forEach((gallery) => {
        gallery.addEventListener('click', (event) => {
            const target = event.target.closest('[data-lightbox]');
            if (!target || !gallery.contains(target)) {
                return;
            }
            event.preventDefault();
            open(gallery, target);
        });
    });

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay || event.target.closest('.lightbox__close')) {
            close();
        }
    });
    prevBtn.addEventListener('click', () => step(-1));
    nextBtn.addEventListener('click', () => step(1));

    document.addEventListener('keydown', (event) => {
        if (!overlay.classList.contains('is-open')) {
            return;
        }
        if (event.key === 'Escape') {
            close();
        } else if (event.key === 'ArrowLeft') {
            step(-1);
        } else if (event.key === 'ArrowRight') {
            step(1);
        }
    });
}

/* -------------------------------------------------------------------------
 * Destructive-action confirmation
 *
 * Replaces bare `onsubmit="return confirm(...)"` with a themed Bootstrap modal.
 * Markup: <form data-confirm="Excluir esta foto?" data-confirm-action="Excluir">
 * ---------------------------------------------------------------------- */
function initConfirm() {
    const forms = document.querySelectorAll('form[data-confirm]');
    if (!forms.length) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <div class="modal modal-blur fade" id="confirm-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center py-4">
                        <i class="ti ti-alert-triangle icon-lg text-danger mb-2"></i>
                        <h3 class="mb-1" data-confirm-title>Tem certeza?</h3>
                        <div class="text-secondary" data-confirm-text></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" data-confirm-ok>Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    const modalEl = wrapper.firstElementChild;
    document.body.appendChild(modalEl);

    const modal = new bootstrap.Modal(modalEl);
    const titleEl = modalEl.querySelector('[data-confirm-title]');
    const textEl = modalEl.querySelector('[data-confirm-text]');
    const okBtn = modalEl.querySelector('[data-confirm-ok]');
    let pending = null;

    okBtn.addEventListener('click', () => {
        const form = pending;
        pending = null;
        modal.hide();
        form?.submit();
    });

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (pending === form) {
                return;
            }
            event.preventDefault();
            pending = form;
            titleEl.textContent = form.dataset.confirm;
            textEl.textContent = form.dataset.confirmText || 'Esta ação não pode ser desfeita.';
            okBtn.textContent = form.dataset.confirmAction || 'Confirmar';
            modal.show();
        });
    });
}

/* -------------------------------------------------------------------------
 * Bulk upload
 *
 * Each dropped image is uploaded immediately (FilePond `server.process`), which
 * creates the Photo record without metadata; metadata is filled in later on
 * each photo's edit page.
 *
 * "Enviando 100%" only means the bytes left the browser — the item turns into
 * "Enviada" when the SERVER answers. When that answer is slow or gets lost
 * (proxy timeout, dropped connection) the item used to sit at 100% forever,
 * even though the photo was saved. So every file carries an `upload_token`
 * generated here, and when the answer doesn't come the browser asks the server
 * (`/photos/upload/status/{token}`) whether that photo exists. The item always
 * ends as "Enviada" or as an error — never in limbo.
 * ---------------------------------------------------------------------- */

/** UUID v4. `crypto.randomUUID` only exists on HTTPS/localhost. */
function uuidV4() {
    if (typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    const b = crypto.getRandomValues(new Uint8Array(16));
    b[6] = (b[6] & 0x0f) | 0x40;
    b[8] = (b[8] & 0x3f) | 0x80;
    const h = Array.from(b, (x) => x.toString(16).padStart(2, '0')).join('');

    return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20)}`;
}

/** Message the server put in a JSON error body, if any. */
function serverMessage(xhr) {
    try {
        const body = JSON.parse(xhr.responseText);
        const firstError = body.errors && Object.values(body.errors)[0];
        return (Array.isArray(firstError) ? firstError[0] : firstError) || body.message || null;
    } catch {
        return null;
    }
}

// How long to wait for the server after the last byte left before asking it
// directly, how often to ask, and when to give up on a request that never
// answers at all.
const UPLOAD_ANSWER_GRACE_MS = 15000;
const UPLOAD_STATUS_INTERVAL_MS = 5000;
const UPLOAD_GIVE_UP_MS = 3 * 60 * 1000;
// After an HTTP error the photo may still have been saved (e.g. a 504 from the
// proxy while PHP kept going): check a few times before calling it a failure.
const UPLOAD_CONFIRM_TRIES = 6;

function initBulkUpload() {
    const bulkInput = document.querySelector('#bulk-upload');
    if (!bulkInput) {
        return;
    }

    const token = document.querySelector('#bulk-form [name=_token]')?.value || '';
    const readExifSwitch = document.querySelector('#bulk-read-exif');
    const counter = document.querySelector('[data-upload-count]');
    const dupCounter = document.querySelector('[data-upload-duplicates]');
    let uploaded = 0;
    let duplicates = 0;

    function refreshCounter() {
        if (counter) {
            counter.textContent = String(uploaded);
            counter.closest('[data-upload-summary]')?.toggleAttribute('hidden', uploaded === 0);
        }
        if (dupCounter) {
            dupCounter.textContent = String(duplicates);
            dupCounter.closest('[data-upload-duplicates-summary]')?.toggleAttribute('hidden', duplicates === 0);
        }
    }

    /** The status line of the FilePond item showing `file`. */
    function statusElementFor(file) {
        const item = pond.getFiles().find((i) => i.file === file);
        return item ? document.querySelector(`#filepond--item-${item.id} .filepond--file-status-main`) : null;
    }

    const pond = FilePond.create(bulkInput, {
        name: 'filepond',
        allowMultiple: true,
        // UPLOAD_PARALLEL no .env (config/kzion.php), entregue pela view.
        maxParallelUploads: Number.parseInt(bulkInput.dataset.parallel, 10) || 3,
        credits: false,
        labelIdle:
            'Arraste e solte várias imagens ou <span class="filepond--label-action">procure no computador</span>',
        labelFileProcessing: 'Enviando',
        labelFileProcessingComplete: 'Enviada',
        labelFileProcessingAborted: 'Envio cancelado',
        // `error` below passes a specific message ("Já está no acervo", …).
        labelFileProcessingError: (err) => (err && typeof err.body === 'string' && err.body) || 'Erro no envio',
        labelTapToCancel: 'toque para cancelar',
        labelTapToRetry: 'toque para tentar de novo',
        labelTapToUndo: 'toque para desfazer',
        server: {
            process: (fieldName, file, metadata, load, error, progress, abort) => {
                const uploadToken = uuidV4();
                const fd = new FormData();
                fd.append('filepond', file, file.name);
                fd.append('upload_token', uploadToken);
                // Read at send time, so flipping the switch affects the files
                // that enter the queue from then on.
                fd.append('read_exif', readExifSwitch && !readExifSwitch.checked ? '0' : '1');

                const xhr = new XMLHttpRequest();
                let settled = false;
                let timer = null;
                let labelTimer = null;

                const settle = (fn, arg) => {
                    if (settled) {
                        return;
                    }
                    settled = true;
                    clearTimeout(timer);
                    clearInterval(labelTimer);
                    fn(arg);
                };

                const succeed = (id) => {
                    uploaded += 1;
                    refreshCounter();
                    settle(load, String(id).trim());
                };

                const fail = (message) => settle(error, message);

                /**
                 * Ask the server whether the photo with this token exists.
                 * Found → "Enviada". Not found → `onMissing()` once `tries` run out.
                 */
                const askServer = (tries, onMissing) => {
                    if (settled) {
                        return;
                    }
                    fetch(`/photos/upload/status/${uploadToken}`, {
                        headers: { Accept: 'text/plain' },
                        credentials: 'same-origin',
                    })
                        .then((r) => (r.ok ? r.text().then(succeed) : Promise.reject(r.status)))
                        .catch(() => {
                            if (settled) {
                                return;
                            }
                            if (tries > 1) {
                                timer = setTimeout(() => askServer(tries - 1, onMissing), UPLOAD_STATUS_INTERVAL_MS);
                            } else {
                                onMissing();
                            }
                        });
                };

                xhr.open('POST', '/photos/upload');
                xhr.setRequestHeader('X-CSRF-TOKEN', token);
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.onprogress = (e) => progress(e.lengthComputable, e.loaded, e.total);

                // Every byte has left the browser: from here on the server is
                // working, so say so instead of freezing on "Enviando 100%".
                // FilePond rewrites the label while it animates the progress
                // ring, hence the interval rather than a single write.
                xhr.upload.onload = () => {
                    labelTimer = setInterval(() => {
                        const el = statusElementFor(file);
                        if (el && !settled && el.textContent !== 'Processando…') {
                            el.textContent = 'Processando…';
                        }
                    }, 250);

                    // The answer is taking long: ask the server directly, and
                    // keep asking while the request is still open.
                    const tries = Math.ceil((UPLOAD_GIVE_UP_MS - UPLOAD_ANSWER_GRACE_MS) / UPLOAD_STATUS_INTERVAL_MS);
                    timer = setTimeout(
                        () =>
                            askServer(tries, () => {
                                xhr.abort();
                                fail('Não chegou ao servidor');
                            }),
                        UPLOAD_ANSWER_GRACE_MS
                    );
                };

                xhr.onload = () => {
                    if (settled) {
                        return;
                    }
                    if (xhr.status >= 200 && xhr.status < 300) {
                        succeed(xhr.responseText);
                        return;
                    }

                    clearTimeout(timer);

                    switch (xhr.status) {
                        case 409: // same image already in the archive
                            duplicates += 1;
                            refreshCounter();
                            fail('Já está no acervo');
                            return;
                        case 413:
                            fail('Arquivo grande demais para o servidor');
                            return;
                        case 419:
                            fail('Sessão expirada — recarregue a página');
                            return;
                        case 422:
                            fail(serverMessage(xhr) || 'Arquivo recusado');
                            return;
                        default:
                            // 5xx / proxy errors: the photo may have been saved anyway.
                            askServer(UPLOAD_CONFIRM_TRIES, () => fail('Erro no envio'));
                    }
                };

                // Connection dropped: same doubt, same check.
                xhr.onerror = () => {
                    clearTimeout(timer);
                    askServer(UPLOAD_CONFIRM_TRIES, () => fail('Erro no envio'));
                };

                xhr.send(fd);

                return {
                    abort: () => {
                        settled = true;
                        clearTimeout(timer);
                        clearInterval(labelTimer);
                        xhr.abort();
                        abort();
                    },
                };
            },
            revert: (id, load) => {
                fetch('/photos/upload/' + id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': token },
                })
                    .then(() => {
                        uploaded = Math.max(0, uploaded - 1);
                        refreshCounter();
                        load();
                    })
                    .catch(() => load());
            },
        },
    });

    return pond;
}

/* -------------------------------------------------------------------------
 * Search panel: keep the advanced filters open when any of them is in use.
 * ---------------------------------------------------------------------- */
function initFilters() {
    const panel = document.querySelector('#advanced-filters');
    if (panel && panel.dataset.expanded === '1') {
        bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).show();
    }
}

/* ---------------------------------------------------------------------- */

// The theme is also applied by an inline script in <head> to avoid a flash of
// the wrong palette; this call only wires up the toggle buttons.
initTheme();

document.addEventListener('DOMContentLoaded', () => {
    initSelect2();
    initLightbox();
    initConfirm();
    initFilters();
    initBulkUpload();
});

// Exposed so views with dynamically injected markup can re-scan for new selects.
window.kzion = { initSelect2 };

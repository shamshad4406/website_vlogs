import './bootstrap';

import Alpine from 'alpinejs';

/* -------------------------------------------------------------------------
 | Theme: light / dark / follow the system.
 |
 | Applied before Alpine starts so there is no flash of the wrong theme.
 * ---------------------------------------------------------------------- */
const THEME_KEY = 'vloghub:theme';

function storedTheme() {
    try {
        return localStorage.getItem(THEME_KEY);
    } catch {
        return null;
    }
}

function applyTheme(theme) {
    const dark = theme === 'dark'
        || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', dark);
}

window.setTheme = (theme) => {
    try {
        if (theme === 'system') {
            localStorage.removeItem(THEME_KEY);
        } else {
            localStorage.setItem(THEME_KEY, theme);
        }
    } catch {
        /* private mode: the choice just will not persist */
    }

    applyTheme(theme);
};

applyTheme(storedTheme() ?? document.documentElement.dataset.theme ?? 'system');

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (!storedTheme()) {
        applyTheme('system');
    }
});

/* -------------------------------------------------------------------------
 | Small fetch helper that always sends the CSRF token.
 * ---------------------------------------------------------------------- */
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function post(url, body = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
    }

    return response.json();
}

window.vlogPost = post;

/* -------------------------------------------------------------------------
 | Toasts
 * ---------------------------------------------------------------------- */
window.toast = (message, type = 'info') => {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
};

/* -------------------------------------------------------------------------
 | Alpine components
 * ---------------------------------------------------------------------- */

/** Like / save / follow toggles with optimistic UI and a server reconcile. */
Alpine.data('reaction', (config = {}) => ({
    active: config.active ?? false,
    count: config.count ?? null,
    busy: false,
    url: config.url,
    field: config.field ?? 'liked',
    requiresAuth: config.requiresAuth ?? false,
    loginUrl: config.loginUrl ?? '/login',

    async toggle() {
        if (this.requiresAuth) {
            window.location.href = this.loginUrl;
            return;
        }

        if (this.busy) return;

        const previous = { active: this.active, count: this.count };

        this.busy = true;
        this.active = !this.active;

        if (this.count !== null) {
            this.count += this.active ? 1 : -1;
        }

        try {
            const data = await post(this.url);
            this.active = data[this.field] ?? this.active;

            if (typeof data.count === 'number') {
                this.count = data.count;
            }
        } catch {
            this.active = previous.active;
            this.count = previous.count;
            window.toast('That did not save. Please try again.', 'error');
        } finally {
            this.busy = false;
        }
    },
}));

/** Header search with debounced suggestions. */
Alpine.data('searchBox', (config = {}) => ({
    query: config.query ?? '',
    open: false,
    loading: false,
    results: { vlogs: [], creators: [], tags: [] },
    timer: null,

    onInput() {
        clearTimeout(this.timer);

        if (this.query.trim().length < 2) {
            this.open = false;
            return;
        }

        this.timer = setTimeout(() => this.fetch(), 220);
    },

    async fetch() {
        this.loading = true;

        try {
            const response = await fetch(
                `${config.suggestUrl}?q=${encodeURIComponent(this.query)}`,
                { headers: { Accept: 'application/json' }, credentials: 'same-origin' },
            );

            this.results = await response.json();
            this.open = true;
        } catch {
            this.open = false;
        } finally {
            this.loading = false;
        }
    },

    get hasResults() {
        return this.results.vlogs.length || this.results.creators.length || this.results.tags.length;
    },
}));

/** Notification bell: unread count plus a dropdown loaded on demand. */
Alpine.data('notificationBell', (config = {}) => ({
    unread: config.unread ?? 0,
    items: [],
    open: false,
    loaded: false,

    async load() {
        this.open = !this.open;

        if (!this.open || this.loaded) return;

        try {
            const response = await fetch(config.recentUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            const data = await response.json();
            this.items = data.items ?? [];
            this.unread = data.unread ?? 0;
            this.loaded = true;
        } catch {
            this.items = [];
        }
    },

    async markAll() {
        try {
            await post(config.readAllUrl);
            this.items = this.items.map((item) => ({ ...item, is_read: true }));
            this.unread = 0;
        } catch {
            window.toast('Could not mark them as read.', 'error');
        }
    },
}));

/**
 * The watch page player.
 *
 * Plays HLS when the browser supports it natively (Safari, iOS) or through
 * hls.js when that is loaded; otherwise plays the progressive file. Sends a
 * heartbeat so watch time and the resume position are recorded, and restores
 * the viewer's position on load.
 */
Alpine.data('player', (config = {}) => ({
    src: config.src,
    isHls: config.isHls ?? false,
    resumeAt: config.resumeAt ?? 0,
    heartbeatUrl: config.heartbeatUrl,
    duration: config.duration ?? 0,
    accrued: 0,
    lastTick: null,
    timer: null,
    showResume: false,

    init() {
        const video = this.$refs.video;

        if (!video) return;

        this.attachSource(video);

        if (this.resumeAt > 10) {
            this.showResume = true;
        }

        video.addEventListener('play', () => this.startTicking());
        video.addEventListener('pause', () => this.stopTicking(video));
        video.addEventListener('ended', () => this.stopTicking(video));
        window.addEventListener('beforeunload', () => this.flush(video, true));

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) this.flush(video);
        });

        // Keyboard shortcuts, scoped to the player.
        this.$el.addEventListener('keydown', (event) => this.onKey(event, video));
    },

    async attachSource(video) {
        if (!this.src) return;

        const nativeHls = video.canPlayType('application/vnd.apple.mpegurl');

        // hls.js is only fetched when the stream is HLS and the browser
        // cannot play it natively, so most page loads never download it.
        if (this.isHls && !nativeHls) {
            try {
                const { default: Hls } = await import('hls.js');

                if (Hls.isSupported()) {
                    const hls = new Hls({ capLevelToPlayerSize: true });
                    hls.loadSource(this.src);
                    hls.attachMedia(video);
                    return;
                }
            } catch {
                /* fall through to the progressive source */
            }
        }

        video.src = this.src;
    },

    resume() {
        const video = this.$refs.video;

        if (video) {
            video.currentTime = this.resumeAt;
            video.play().catch(() => {});
        }

        this.showResume = false;
    },

    dismissResume() {
        this.showResume = false;
    },

    seekTo(seconds) {
        const video = this.$refs.video;

        if (!video) return;

        video.currentTime = seconds;
        video.play().catch(() => {});
    },

    setSpeed(rate) {
        const video = this.$refs.video;

        if (video) video.playbackRate = rate;
    },

    startTicking() {
        this.lastTick = Date.now();

        clearInterval(this.timer);
        this.timer = setInterval(() => {
            const now = Date.now();
            this.accrued += Math.round((now - this.lastTick) / 1000);
            this.lastTick = now;

            if (this.accrued >= 30) {
                this.flush(this.$refs.video);
            }
        }, 5000);
    },

    stopTicking(video) {
        clearInterval(this.timer);
        this.timer = null;

        if (this.lastTick) {
            this.accrued += Math.round((Date.now() - this.lastTick) / 1000);
            this.lastTick = null;
        }

        this.flush(video);
    },

    flush(video, unloading = false) {
        if (!this.heartbeatUrl || this.accrued < 3 || !video) return;

        const payload = {
            seconds: Math.min(600, this.accrued),
            position: Math.round(video.currentTime || 0),
        };

        this.accrued = 0;

        if (unloading && navigator.sendBeacon) {
            // Beacons cannot set headers, so include the token in the body.
            const blob = new Blob(
                [JSON.stringify({ ...payload, _token: csrfToken() })],
                { type: 'application/json' },
            );
            navigator.sendBeacon(this.heartbeatUrl, blob);
            return;
        }

        post(this.heartbeatUrl, payload).catch(() => {});
    },

    onKey(event, video) {
        const tag = event.target.tagName;

        if (tag === 'INPUT' || tag === 'TEXTAREA') return;

        const actions = {
            ' ': () => (video.paused ? video.play() : video.pause()),
            k: () => (video.paused ? video.play() : video.pause()),
            ArrowLeft: () => (video.currentTime -= 5),
            ArrowRight: () => (video.currentTime += 5),
            j: () => (video.currentTime -= 10),
            l: () => (video.currentTime += 10),
            m: () => (video.muted = !video.muted),
            f: () => (document.fullscreenElement
                ? document.exitFullscreen()
                : video.requestFullscreen?.()),
        };

        const action = actions[event.key];

        if (action) {
            event.preventDefault();
            action();
        }
    },
}));

/** Copy a link to the clipboard with a visible confirmation. */
Alpine.data('copyLink', (url) => ({
    copied: false,

    async copy() {
        try {
            await navigator.clipboard.writeText(url);
        } catch {
            const input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
        }

        this.copied = true;
        setTimeout(() => (this.copied = false), 2000);
    },
}));

/** Tag input used by the upload wizard. */
Alpine.data('tagInput', (initial = []) => ({
    tags: initial,
    draft: '',
    max: 12,

    add() {
        const value = this.draft.trim().replace(/,+$/, '');

        if (!value || this.tags.length >= this.max) {
            this.draft = '';
            return;
        }

        if (!this.tags.includes(value)) {
            this.tags.push(value);
        }

        this.draft = '';
    },

    remove(tag) {
        this.tags = this.tags.filter((t) => t !== tag);
    },

    onKey(event) {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            this.add();
        } else if (event.key === 'Backspace' && !this.draft) {
            this.tags.pop();
        }
    },
}));

/**
 * Upload wizard: three steps, a live file preview, client-side duration
 * detection (so duration is right even without FFmpeg on the server) and a
 * real progress bar via XHR.
 */
Alpine.data('uploadWizard', (config = {}) => ({
    step: 1,
    source: 'file',
    fileName: '',
    fileSize: 0,
    maxBytes: (config.maxSizeMb ?? 512) * 1024 * 1024,
    durationSeconds: null,
    thumbPreview: null,
    videoPreview: null,
    uploading: false,
    progress: 0,
    error: null,

    get fileSizeLabel() {
        if (!this.fileSize) return '';
        const mb = this.fileSize / (1024 * 1024);
        return mb >= 1 ? `${mb.toFixed(1)} MB` : `${(this.fileSize / 1024).toFixed(0)} KB`;
    },

    get durationLabel() {
        if (!this.durationSeconds) return '';
        const m = Math.floor(this.durationSeconds / 60);
        const s = this.durationSeconds % 60;
        return `${m}:${String(s).padStart(2, '0')}`;
    },

    pickVideo(event) {
        const file = event.target.files?.[0];
        this.error = null;

        if (!file) return;

        if (file.size > this.maxBytes) {
            this.error = `That file is ${(file.size / 1024 / 1024).toFixed(0)} MB. The limit is ${config.maxSizeMb} MB.`;
            event.target.value = '';
            return;
        }

        this.fileName = file.name;
        this.fileSize = file.size;
        this.videoPreview = URL.createObjectURL(file);

        // Read the real duration in the browser.
        const probe = document.createElement('video');
        probe.preload = 'metadata';
        probe.src = this.videoPreview;
        probe.onloadedmetadata = () => {
            if (Number.isFinite(probe.duration)) {
                this.durationSeconds = Math.round(probe.duration);
            }
        };
    },

    pickThumbnail(event) {
        const file = event.target.files?.[0];

        if (file) {
            this.thumbPreview = URL.createObjectURL(file);
        }
    },

    onDrop(event) {
        const file = event.dataTransfer?.files?.[0];

        if (!file || !file.type.startsWith('video/')) {
            this.error = 'Drop a video file.';
            return;
        }

        this.$refs.videoInput.files = event.dataTransfer.files;
        this.pickVideo({ target: this.$refs.videoInput });
    },

    next() {
        this.error = null;

        if (this.step === 1) {
            if (this.source === 'file' && !this.fileName) {
                this.error = 'Choose a video file first.';
                return;
            }

            if (this.source === 'url' && !this.$refs.videoUrl?.value) {
                this.error = 'Paste the video link first.';
                return;
            }
        }

        if (this.step === 2) {
            const title = this.$refs.title?.value?.trim() ?? '';
            const description = this.$refs.description?.value?.trim() ?? '';

            if (title.length < 5) {
                this.error = 'Give it a title of at least 5 characters.';
                return;
            }

            if (description.length < 20) {
                this.error = 'Write at least a couple of sentences of description.';
                return;
            }
        }

        this.step = Math.min(3, this.step + 1);
    },

    back() {
        this.error = null;
        this.step = Math.max(1, this.step - 1);
    },

    /** Submit through XHR so the creator sees genuine upload progress. */
    submit(action) {
        const form = this.$refs.form;

        if (!form) return;

        this.error = null;
        this.uploading = true;
        this.progress = 0;

        const data = new FormData(form);
        data.set('action', action);

        if (this.durationSeconds) {
            data.set('duration_seconds', this.durationSeconds);
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken());
        xhr.setRequestHeader('Accept', 'text/html');

        xhr.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                this.progress = Math.round((event.loaded / event.total) * 100);
            }
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 400) {
                window.location.href = xhr.responseURL || config.redirectUrl;
                return;
            }

            this.uploading = false;

            if (xhr.status === 422) {
                this.error = 'Some fields need attention. Submitting normally so the messages show.';
                form.querySelector('input[name="action"]').value = action;
                form.submit();
                return;
            }

            if (xhr.status === 413) {
                this.error = 'The server rejected the file as too large. Raise upload_max_filesize and post_max_size in php.ini.';
                return;
            }

            this.error = `Upload failed (${xhr.status}). Please try again.`;
        };

        xhr.onerror = () => {
            this.uploading = false;
            this.error = 'The connection dropped during upload. Please try again.';
        };

        xhr.send(data);
    },
}));

/** Chapter editor in the studio. */
Alpine.data('chapterEditor', (initial = []) => ({
    chapters: initial.length ? initial : [{ start_seconds: 0, title: '' }],

    add() {
        const last = this.chapters[this.chapters.length - 1];
        this.chapters.push({ start_seconds: (last?.start_seconds ?? 0) + 60, title: '' });
    },

    remove(index) {
        this.chapters.splice(index, 1);

        if (!this.chapters.length) {
            this.chapters.push({ start_seconds: 0, title: '' });
        }
    },
}));

/** Select-all / bulk action helper for the admin tables. */
Alpine.data('bulkSelect', () => ({
    selected: [],
    all: false,

    toggleAll(ids) {
        this.all = !this.all;
        this.selected = this.all ? [...ids] : [];
    },

    get count() {
        return this.selected.length;
    },
}));

window.Alpine = Alpine;
Alpine.start();

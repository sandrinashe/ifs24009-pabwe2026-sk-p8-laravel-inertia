import React, { Suspense, lazy } from 'react';

// Toast UI cukup besar, jadi dimuat hanya ketika halaman benar-benar membutuhkannya.
const MarkdownEditorImpl = lazy(() => import('./MarkdownEditor'));
const MarkdownViewerImpl = lazy(() => import('./MarkdownViewer'));

function Placeholder({ minHeight, label }) {
    return (
        <div
            role="status"
            aria-live="polite"
            className="rounded-xl bg-slate-100 animate-pulse flex items-center justify-center text-xs text-slate-600"
            style={{ minHeight }}
        >
            {label}
        </div>
    );
}

// Perkirakan tinggi hasil render Markdown agar halaman tidak "melompat" saat viewer selesai dimuat (CLS).
function estimateHeight(content = '') {
    const px = String(content)
        .split('\n')
        .reduce((total, line) => total + (line.trim() === '' ? 16 : Math.ceil(line.length / 70) * 28), 24);
    return `${Math.min(Math.max(px, 192), 1200)}px`;
}

export function MarkdownViewer(props) {
    return (
        <Suspense fallback={<Placeholder minHeight={estimateHeight(props.content)} label="Memuat konten..." />}>
            <MarkdownViewerImpl {...props} />
        </Suspense>
    );
}

export function MarkdownEditor(props) {
    return (
        <Suspense fallback={<Placeholder minHeight={props.height || '300px'} label="Memuat editor..." />}>
            <MarkdownEditorImpl {...props} />
        </Suspense>
    );
}

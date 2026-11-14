import React, { useEffect, useRef } from 'react';
import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';

export default function MarkdownEditor({
    value = '',
    onChange,
    height = '300px',
    placeholder = 'Tuliskan deskripsi / catatan dalam format Markdown...',
}) {
    const editorElRef = useRef(null);
    const editorInstanceRef = useRef(null);

    useEffect(() => {
        if (!editorElRef.current) return;

        editorInstanceRef.current = new Editor({
            el: editorElRef.current,
            height: height,
            initialEditType: 'markdown',
            previewStyle: 'tab',
            initialValue: value || '',
            placeholder: placeholder,
            usageStatistics: false,
            events: {
                change: () => {
                    if (editorInstanceRef.current && onChange) {
                        const md = editorInstanceRef.current.getMarkdown();
                        onChange(md);
                    }
                },
            },
        });

        // Rapikan atribut ARIA tidak valid bawaan Toast UI & beri nama pada textarea clipboard internalnya.
        editorElRef.current.querySelectorAll('[aria-role]').forEach((el) => el.removeAttribute('aria-role'));
        editorElRef.current.querySelectorAll('.tab-item').forEach((el) => el.removeAttribute('aria-selected'));
        editorElRef.current
            .querySelectorAll('textarea.toastui-editor-pseudo-clipboard')
            .forEach((el) => el.setAttribute('aria-label', 'Papan klip editor'));

        // Tombol "more" (overflow toolbar di layar sempit) dibuat dinamis oleh Toast UI dan tidak punya nama aksesibel.
        const root = editorElRef.current;
        const nameToolbarButtons = () => {
            root.querySelectorAll('button.more:not([aria-label])').forEach((el) =>
                el.setAttribute('aria-label', 'Alat editor lainnya'),
            );
        };
        nameToolbarButtons();
        const observer = new MutationObserver(nameToolbarButtons);
        observer.observe(root, { childList: true, subtree: true });

        return () => {
            observer.disconnect();
            if (editorInstanceRef.current) {
                editorInstanceRef.current.destroy();
                editorInstanceRef.current = null;
            }
        };
    }, []);

    useEffect(() => {
        if (editorInstanceRef.current) {
            const current = editorInstanceRef.current.getMarkdown();
            if (value !== undefined && value !== current) {
                editorInstanceRef.current.setMarkdown(value || '', false);
            }
        }
    }, [value]);

    return (
        <div className="toastui-editor-container border border-slate-300 rounded-xl overflow-hidden bg-white">
            <div ref={editorElRef} />
        </div>
    );
}


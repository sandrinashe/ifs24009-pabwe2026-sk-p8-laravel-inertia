import React, { useEffect, useLayoutEffect, useRef } from 'react';
import Viewer from '@toast-ui/editor/dist/toastui-editor-viewer';
import '@toast-ui/editor/dist/toastui-editor-viewer.css';

/**
 * TOAST UI Viewer (versi viewer-only yang lebih ringan dari Editor penuh).
 */
export default function MarkdownViewer({ content = '' }) {
    const viewerElRef = useRef(null);
    const viewerInstanceRef = useRef(null);

    // Blok kode yang bisa di-scroll harus dapat dijangkau keyboard (WCAG 2.1.1).
    const makeScrollableFocusable = () => {
        viewerElRef.current?.querySelectorAll('pre').forEach((el) => el.setAttribute('tabindex', '0'));
    };

    // useLayoutEffect: konten Markdown sudah ada sebelum paint pertama, sehingga tidak terjadi layout shift (CLS).
    useLayoutEffect(() => {
        if (!viewerElRef.current) return;

        viewerInstanceRef.current = new Viewer({
            el: viewerElRef.current,
            initialValue: content || '',
            usageStatistics: false,
        });
        makeScrollableFocusable();

        return () => {
            if (viewerInstanceRef.current) {
                viewerInstanceRef.current.destroy();
                viewerInstanceRef.current = null;
            }
        };
    }, []);

    const isFirstRender = useRef(true);
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        if (viewerInstanceRef.current) {
            viewerInstanceRef.current.setMarkdown(content || '');
            makeScrollableFocusable();
        }
    }, [content]);

    return (
        <div className="toastui-viewer-container">
            <div ref={viewerElRef} />
        </div>
    );
}

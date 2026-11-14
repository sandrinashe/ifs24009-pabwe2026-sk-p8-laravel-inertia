import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { MarkdownEditor } from './LazyMarkdown';
import { Spinner } from './LoadingIndicator';

/**
 * Modal Tambah / Ubah artikel: Full Screen, header & footer berwarna kontras,
 * tombol aksi (Batal & Simpan) rapat di sebelah kanan footer.
 */
export default function PostFormModal({ open, post = null, categories = [], onClose }) {
    const form = useForm({
        title: '',
        category: 'Umum',
        excerpt: '',
        content: '',
        is_published: false,
    });

    useEffect(() => {
        if (!open) return;
        form.clearErrors();
        form.setData({
            title: post?.title ?? '',
            category: post?.category ?? 'Umum',
            excerpt: post?.excerpt ?? '',
            content: post?.content ?? '',
            is_published: Boolean(post?.is_published),
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, post?.id]);

    if (!open) return null;

    const submit = (e) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => onClose() };

        if (post) {
            form.put(`/dashboard/posts/${post.id}`, options);
        } else {
            form.post('/dashboard/posts', options);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-white" role="dialog" aria-modal="true" aria-labelledby="post-modal-title">
            {/* Header (warna kontras) */}
            <div className="px-6 md:px-8 py-4 bg-indigo-700 text-white flex items-center justify-between shrink-0">
                <div className="flex items-center space-x-3">
                    <span className="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center font-black text-sm">✎</span>
                    <div>
                        <h2 id="post-modal-title" className="text-base font-bold leading-tight">{post ? 'Ubah Artikel' : 'Tulis Artikel Baru'}</h2>
                        <p className="text-xs text-indigo-100">Isi judul, kategori, ringkasan, dan konten Markdown artikel Anda</p>
                    </div>
                </div>
                <button type="button" onClick={onClose} className="text-indigo-100 hover:text-white p-1.5 rounded-xl hover:bg-indigo-800 transition" aria-label="Tutup">
                    ✕
                </button>
            </div>

            <form onSubmit={submit} className="flex-1 flex flex-col overflow-hidden">
                <div className="flex-1 p-6 md:p-8 space-y-5 overflow-y-auto w-full">
                    <div>
                        <label htmlFor="post-title" className="block text-xs font-bold uppercase tracking-wider text-slate-700">Judul Artikel *</label>
                        <input
                            id="post-title"
                            type="text"
                            value={form.data.title}
                            onChange={(e) => form.setData('title', e.target.value)}
                            required
                            className="mt-1 w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                            placeholder="Contoh: Belajar Laravel Inertia dari Nol"
                        />
                        {form.errors.title && <p className="mt-1 text-xs text-rose-600">{form.errors.title}</p>}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label htmlFor="post-category" className="block text-xs font-bold uppercase tracking-wider text-slate-700">Kategori *</label>
                            <select
                                id="post-category"
                                value={form.data.category}
                                onChange={(e) => form.setData('category', e.target.value)}
                                className="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                            >
                                {categories.map((c) => (
                                    <option key={c.value} value={c.value}>{c.value} — {c.label}</option>
                                ))}
                            </select>
                            {form.errors.category && <p className="mt-1 text-xs text-rose-600">{form.errors.category}</p>}
                        </div>

                        <div>
                            <label htmlFor="post-status" className="block text-xs font-bold uppercase tracking-wider text-slate-700">Status</label>
                            <select
                                id="post-status"
                                value={form.data.is_published ? '1' : '0'}
                                onChange={(e) => form.setData('is_published', e.target.value === '1')}
                                className="mt-1 w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="0">Draf (belum dipublikasikan)</option>
                                <option value="1">Dipublikasikan</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label htmlFor="post-excerpt" className="block text-xs font-bold uppercase tracking-wider text-slate-700">Ringkasan (1-2 kalimat)</label>
                        <textarea
                            id="post-excerpt"
                            rows={2}
                            value={form.data.excerpt}
                            onChange={(e) => form.setData('excerpt', e.target.value)}
                            className="mt-1 w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                            placeholder="Ringkasan pemikat pembaca..."
                        />
                        {form.errors.excerpt && <p className="mt-1 text-xs text-rose-600">{form.errors.excerpt}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Konten Artikel (TOAST UI Markdown Editor) *</label>
                        <MarkdownEditor
                            key={post ? `edit-${post.id}` : 'create-post'}
                            value={form.data.content}
                            onChange={(val) => form.setData('content', val)}
                            height="480px"
                            placeholder="Tulis artikel Anda dalam format Markdown..."
                        />
                        {form.errors.content && <p className="mt-1 text-xs text-rose-600">{form.errors.content}</p>}
                    </div>
                </div>

                {/* Footer (warna kontras) - tombol di kanan */}
                <div className="px-6 md:px-8 py-4 bg-slate-100 border-t border-slate-200 flex items-center justify-end space-x-3 shrink-0">
                    <button type="button" onClick={onClose} className="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-200/70 rounded-xl transition">
                        Batal
                    </button>
                    <button
                        id="post-submit"
                        type="submit"
                        disabled={form.processing}
                        className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs disabled:opacity-50 flex items-center space-x-2 transition"
                    >
                        {form.processing && <Spinner className="w-3.5 h-3.5 text-white" />}
                        <span>{form.processing ? 'Menyimpan...' : post ? 'Simpan Perubahan' : 'Simpan Artikel'}</span>
                    </button>
                </div>
            </form>
        </div>
    );
}

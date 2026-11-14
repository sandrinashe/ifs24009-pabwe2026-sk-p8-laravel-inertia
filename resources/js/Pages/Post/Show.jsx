import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import PostFormModal from '../../Components/PostFormModal';
import { MarkdownViewer } from '../../Components/LazyMarkdown';
import { Spinner } from '../../Components/LoadingIndicator';

export default function PostShow({ post, categories = [] }) {
    const [showEdit, setShowEdit] = useState(false);
    const [showDelete, setShowDelete] = useState(false);
    const [busy, setBusy] = useState('');

    const run = (key, fn) => {
        setBusy(key);
        fn(() => setBusy(''));
    };

    const togglePublish = () =>
        run('publish', (done) => router.patch(`/dashboard/posts/${post.id}/publish`, {}, { preserveScroll: true, onFinish: done }));

    const uploadCover = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        run('cover', (done) =>
            router.post(`/dashboard/posts/${post.id}/cover`, { coverPhoto: file }, { forceFormData: true, preserveScroll: true, onFinish: done })
        );
        e.target.value = '';
    };

    const deleteCover = () =>
        run('cover', (done) => router.delete(`/dashboard/posts/${post.id}/cover`, { preserveScroll: true, onFinish: done }));

    const destroy = () =>
        run('delete', (done) => router.delete(`/dashboard/posts/${post.id}`, { onFinish: done }));

    return (
        <AppLayout>
            <Head title={post.title} />

            <div className="max-w-4xl mx-auto space-y-6">
                <Link href="/dashboard/posts" className="text-xs font-semibold text-indigo-600 hover:text-indigo-800">&larr; Kembali ke Artikel Saya</Link>

                <div className="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                    {post.cover_image_path && (
                        <img src={`/storage/${post.cover_image_path}`} alt={post.title} className="w-full max-h-96 object-cover" />
                    )}

                    <div className="p-6 md:p-8 space-y-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200/50">{post.category}</span>
                            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${post.is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                                {post.is_published ? 'Published' : 'Draf'}
                            </span>
                            <span className="text-[11px] text-slate-600">Dibuat {post.created_at} · Diperbarui {post.updated_at}</span>
                            {post.published_at && <span className="text-[11px] text-slate-600">· Terbit {post.published_at}</span>}
                        </div>

                        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">{post.title}</h1>
                        {post.excerpt && <p className="text-slate-600 italic">{post.excerpt}</p>}

                        {/* Aksi */}
                        <div className="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100">
                            <button onClick={() => setShowEdit(true)} className="px-3.5 py-2 text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition">
                                Ubah Edit
                            </button>
                            <button
                                onClick={togglePublish}
                                disabled={busy === 'publish'}
                                className="inline-flex items-center space-x-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl disabled:opacity-60 transition"
                            >
                                {busy === 'publish' && <Spinner className="w-3 h-3 text-slate-600" />}
                                <span>{post.is_published ? 'Jadikan Draf' : 'Publikasikan'}</span>
                            </button>
                            {post.is_published && (
                                <Link href={`/articles/${post.slug}`} className="px-3.5 py-2 text-xs font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl transition">
                                    Lihat di Halaman Publik
                                </Link>
                            )}
                            <label className="px-3.5 py-2 text-xs font-semibold bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-xl cursor-pointer transition inline-flex items-center space-x-1.5">
                                {busy === 'cover' && <Spinner className="w-3 h-3 text-purple-600" />}
                                <span>{post.cover_image_path ? 'Ganti Cover' : 'Unggah Cover'}</span>
                                <input type="file" accept="image/*" className="hidden" onChange={uploadCover} />
                            </label>
                            {post.cover_image_path && (
                                <button onClick={deleteCover} disabled={busy === 'cover'} className="px-3.5 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-600 border border-slate-300 rounded-xl disabled:opacity-60 transition">
                                    Hapus Cover
                                </button>
                            )}
                            <button onClick={() => setShowDelete(true)} className="ml-auto px-3.5 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-xl transition">
                                Hapus Artikel
                            </button>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6 md:p-8">
                    <MarkdownViewer content={post.content} />
                </div>
            </div>

            <PostFormModal open={showEdit} post={post} categories={categories} onClose={() => setShowEdit(false)} />

            {showDelete && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
                    <div className="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
                        <div className="px-6 py-4 bg-rose-600 text-white font-bold">Hapus Artikel</div>
                        <div className="p-6 text-sm text-slate-700">Anda yakin ingin menghapus artikel <strong>“{post.title}”</strong>?</div>
                        <div className="px-6 py-4 bg-rose-50 border-t border-rose-100 flex justify-end space-x-3">
                            <button onClick={() => setShowDelete(false)} className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-white rounded-xl transition">Batal</button>
                            <button onClick={destroy} disabled={busy === 'delete'} className="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl disabled:opacity-50 flex items-center space-x-2 transition">
                                {busy === 'delete' && <Spinner className="w-3.5 h-3.5 text-white" />}
                                <span>Ya, Hapus</span>
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}

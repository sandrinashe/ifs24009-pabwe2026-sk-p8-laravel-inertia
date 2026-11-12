import React, { useState, useEffect } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import PostFormModal from '../../Components/PostFormModal';
import { Spinner } from '../../Components/LoadingIndicator';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function StatCard({ label, value, unit, tone, icon }) {
    const tones = {
        indigo: ['bg-indigo-50 text-indigo-600 border-indigo-100', 'text-slate-900', 'text-slate-500'],
        emerald: ['bg-emerald-50 text-emerald-600 border-emerald-100', 'text-emerald-600', 'text-emerald-600'],
        amber: ['bg-amber-50 text-amber-600 border-amber-100', 'text-amber-600', 'text-amber-600'],
    }[tone];

    return (
        <div className="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs flex items-center space-x-3.5">
            <div className={`w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border ${tones[0]}`}>
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d={icon} />
                </svg>
            </div>
            <div>
                <span className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">{label}</span>
                <div className="flex items-baseline space-x-1.5 mt-0.5">
                    <span className={`text-2xl font-black ${tones[1]}`}>{value}</span>
                    <span className={`text-xs font-medium ${tones[2]}`}>{unit}</span>
                </div>
            </div>
        </div>
    );
}

export default function PostIndex({ posts, filters = {}, categories = [], stats = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [category, setCategory] = useState(filters.category || '');
    const [activeMenuId, setActiveMenuId] = useState(null);

    const [showForm, setShowForm] = useState(false);
    const [editingPost, setEditingPost] = useState(null);

    const [deletingPost, setDeletingPost] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState(null);

    const [showImport, setShowImport] = useState(false);
    const importForm = useForm({ importFile: null });

    // AI Generator
    const [showAi, setShowAi] = useState(false);
    const [aiPrompt, setAiPrompt] = useState('');
    const [aiCount, setAiCount] = useState(1);
    const [aiLoading, setAiLoading] = useState(false);
    const [aiItems, setAiItems] = useState([]);
    const [aiSelected, setAiSelected] = useState([]);
    const [aiError, setAiError] = useState('');
    const [aiSaving, setAiSaving] = useState(false);

    useEffect(() => {
        const close = (e) => {
            if (!e.target.closest('.post-menu-container')) setActiveMenuId(null);
        };
        document.addEventListener('click', close);
        return () => document.removeEventListener('click', close);
    }, []);

    const applyFilters = (next) => {
        router.get('/dashboard/posts', { search, status, category, ...next }, { preserveState: true, replace: true });
    };

    const openCreate = () => {
        setEditingPost(null);
        setShowForm(true);
    };

    const openEdit = (post) => {
        setEditingPost(post);
        setShowForm(true);
    };

    const togglePublish = (post) => {
        setTogglingId(post.id);
        router.patch(`/dashboard/posts/${post.id}/publish`, {}, {
            preserveScroll: true,
            onFinish: () => setTogglingId(null),
        });
    };

    const executeDelete = () => {
        if (!deletingPost) return;
        setIsDeleting(true);
        router.delete(`/dashboard/posts/${deletingPost.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeletingPost(null),
            onFinish: () => setIsDeleting(false),
        });
    };

    const submitImport = (e) => {
        e.preventDefault();
        importForm.post('/dashboard/posts/import', {
            forceFormData: true,
            onSuccess: () => {
                setShowImport(false);
                importForm.reset();
            },
        });
    };

    // --- AI Generator ---
    const countNumber = Number(aiCount);
    const countInvalid = !Number.isInteger(countNumber) || countNumber < 1 || countNumber > 5;

    const generateAi = async () => {
        if (aiPrompt.trim().length < 3 || countInvalid) return;
        setAiLoading(true);
        setAiError('');
        setAiItems([]);

        try {
            const res = await fetch('/dashboard/posts/ai-generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ prompt: aiPrompt, count: countNumber }),
            });
            const data = await res.json();

            if (!res.ok || data.success === false) {
                const validation = data.errors ? Object.values(data.errors).flat().join('. ') : '';
                setAiError(validation || data.message || 'Gagal menghasilkan artikel dengan AI.');
            } else {
                setAiItems(data.posts || []);
                setAiSelected((data.posts || []).map((_, i) => i));
            }
        } catch (err) {
            setAiError(err.message || 'Terjadi kesalahan jaringan atau server tidak merespon.');
        } finally {
            setAiLoading(false);
        }
    };

    const toggleAiSelect = (i) => {
        setAiSelected((prev) => (prev.includes(i) ? prev.filter((x) => x !== i) : [...prev, i]));
    };

    const saveAi = () => {
        const selected = aiSelected.map((i) => aiItems[i]);
        if (selected.length === 0) return;

        setAiSaving(true);
        router.post('/dashboard/posts/ai-save', { posts: selected }, {
            onSuccess: () => {
                setShowAi(false);
                setAiItems([]);
                setAiSelected([]);
                setAiPrompt('');
            },
            onFinish: () => setAiSaving(false),
        });
    };

    return (
        <AppLayout>
            <Head title="Artikel Saya" />

            <div className="w-full space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Artikel Saya</h1>
                    <p className="text-sm text-slate-500 mt-1">Tulis, kelola, dan publikasikan artikel Anda. Ekspor/impor via Excel atau rancang instan dengan AI.</p>
                </div>

                {/* Toolbar */}
                <div className="flex flex-wrap items-center gap-2.5">
                    <button
                        id="btn-create-post"
                        onClick={openCreate}
                        className="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs transition"
                    >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tulis Artikel</span>
                    </button>

                    <button
                        onClick={() => setShowAi(true)}
                        className="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-semibold rounded-xl transition"
                    >
                        <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>AI Generator</span>
                    </button>

                    <a
                        href="/dashboard/posts/export"
                        className="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-xl transition"
                    >
                        <svg className="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export Excel</span>
                    </a>

                    <button
                        onClick={() => setShowImport(true)}
                        className="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 text-xs font-semibold rounded-xl transition"
                    >
                        <svg className="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span>Import Excel</span>
                    </button>
                </div>

                {/* Ringkasan Statistik */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <StatCard label="Total Artikel" value={stats.total ?? 0} unit="tulisan" tone="indigo" icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    <StatCard label="Total Selesai" value={stats.published ?? 0} unit="dipublikasikan" tone="emerald" icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    <StatCard label="Belum Selesai" value={stats.draft ?? 0} unit="draf" tone="amber" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </div>

                {/* Filter */}
                <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs grid grid-cols-1 md:grid-cols-[1fr_12rem_12rem] gap-3">
                    <input
                        type="text"
                        placeholder="Cari judul atau konten..."
                        value={search}
                        onChange={(e) => {
                            setSearch(e.target.value);
                            applyFilters({ search: e.target.value });
                        }}
                        className="w-full px-4 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                    />
                    <select
                        value={category}
                        onChange={(e) => {
                            setCategory(e.target.value);
                            applyFilters({ category: e.target.value });
                        }}
                        className="py-2 px-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Semua Kategori</option>
                        {categories.map((c) => (
                            <option key={c.value} value={c.value}>{c.value}</option>
                        ))}
                    </select>
                    <select
                        value={status}
                        onChange={(e) => {
                            setStatus(e.target.value);
                            applyFilters({ status: e.target.value });
                        }}
                        className="py-2 px-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Semua Status</option>
                        <option value="published">Dipublikasikan</option>
                        <option value="draft">Draf</option>
                    </select>
                </div>

                {/* List View (bukan card) */}
                {posts?.data?.length === 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200/80 p-12 text-center">
                        <h3 className="text-base font-bold text-slate-800">Belum Ada Artikel</h3>
                        <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Mulai menulis artikel pertama Anda, impor dari Excel, atau gunakan AI Generator.</p>
                        <button onClick={openCreate} className="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl transition">
                            + Tulis Artikel
                        </button>
                    </div>
                ) : (
                    <div className="bg-white rounded-2xl border border-slate-200/90 shadow-xs divide-y divide-slate-100">
                        {posts.data.map((post) => (
                            <div key={post.id} className="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50/80 transition-colors">
                                <div className="min-w-0 flex-1 flex flex-wrap items-center gap-2">
                                    <Link href={`/dashboard/posts/${post.id}`} className="font-semibold text-sm text-slate-900 hover:text-indigo-600 transition truncate max-w-md">
                                        {post.title}
                                    </Link>
                                    <span className="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200/50">
                                        {post.category}
                                    </span>
                                    <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${post.is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                                        {post.is_published ? 'Published' : 'Draf'}
                                    </span>
                                    {post.cover_image_path && (
                                        <span className="text-[10px] font-semibold px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded">📷 Cover</span>
                                    )}
                                    <span className="text-[11px] text-slate-400">
                                        {post.created_at ? new Date(post.created_at).toLocaleDateString('id-ID') : ''}
                                    </span>
                                </div>

                                <div className="flex items-center gap-2 shrink-0">
                                    {/* Tombol cepat toggle status */}
                                    <button
                                        type="button"
                                        onClick={() => togglePublish(post)}
                                        disabled={togglingId === post.id}
                                        className={`inline-flex items-center space-x-1.5 px-3 py-1.5 text-[11px] font-semibold rounded-lg border transition disabled:opacity-60 ${
                                            post.is_published
                                                ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                                                : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                                        }`}
                                    >
                                        {togglingId === post.id && <Spinner className="w-3 h-3 text-current" />}
                                        <span>{post.is_published ? 'Jadikan Draf' : 'Publikasikan'}</span>
                                    </button>

                                    {/* Kebab menu */}
                                    <div className="relative post-menu-container">
                                        <button
                                            type="button"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                setActiveMenuId(activeMenuId === post.id ? null : post.id);
                                            }}
                                            className="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                            title="Menu Tindakan"
                                            aria-label="Menu Tindakan"
                                        >
                                            <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="12" cy="5" r="1.8" />
                                                <circle cx="12" cy="12" r="1.8" />
                                                <circle cx="12" cy="19" r="1.8" />
                                            </svg>
                                        </button>

                                        {activeMenuId === post.id && (
                                            <div className="absolute right-0 mt-1 w-36 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-30">
                                                <Link
                                                    href={`/dashboard/posts/${post.id}`}
                                                    className="w-full px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50/70 flex items-center space-x-2 transition"
                                                >
                                                    <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span>Detail</span>
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setActiveMenuId(null);
                                                        openEdit(post);
                                                    }}
                                                    className="w-full px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50/70 flex items-center space-x-2 transition text-left"
                                                >
                                                    <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    <span>Ubah Edit</span>
                                                </button>
                                                <div className="border-t border-slate-100 my-1"></div>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setActiveMenuId(null);
                                                        setDeletingPost(post);
                                                    }}
                                                    className="w-full px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 flex items-center space-x-2 transition text-left"
                                                >
                                                    <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {posts?.links?.length > 3 && (
                    <div className="flex items-center justify-center space-x-1 pt-4">
                        {posts.links.map((link, idx) => (
                            <Link
                                key={idx}
                                href={link.url || '#'}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={`px-3 py-1.5 text-xs font-semibold rounded-lg border transition ${
                                    link.active
                                        ? 'bg-indigo-600 text-white border-indigo-600'
                                        : link.url
                                        ? 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'
                                        : 'bg-slate-50 text-slate-300 border-slate-200 cursor-not-allowed'
                                }`}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Modal Tambah / Ubah (Full Screen) */}
            <PostFormModal open={showForm} post={editingPost} categories={categories} onClose={() => setShowForm(false)} />

            {/* Modal Hapus */}
            {deletingPost && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
                    <div className="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
                        <div className="px-6 py-4 bg-rose-600 text-white font-bold">Hapus Artikel</div>
                        <div className="p-6 text-sm text-slate-700">
                            Anda yakin ingin menghapus artikel <strong>“{deletingPost.title}”</strong>? Tindakan ini tidak dapat dibatalkan.
                        </div>
                        <div className="px-6 py-4 bg-rose-50 border-t border-rose-100 flex justify-end space-x-3">
                            <button onClick={() => setDeletingPost(null)} className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-white rounded-xl transition">Batal</button>
                            <button
                                onClick={executeDelete}
                                disabled={isDeleting}
                                className="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl disabled:opacity-50 flex items-center space-x-2 transition"
                            >
                                {isDeleting && <Spinner className="w-3.5 h-3.5 text-white" />}
                                <span>{isDeleting ? 'Menghapus...' : 'Ya, Hapus'}</span>
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal Import */}
            {showImport && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
                    <form onSubmit={submitImport} className="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
                        <div className="px-6 py-4 bg-emerald-700 text-white font-bold">Import Artikel dari Excel</div>
                        <div className="p-6 space-y-3">
                            <p className="text-xs text-slate-600">
                                Gunakan format hasil Export Excel (kolom: No, Judul, Kategori, Ringkasan, Konten, Status). Hasil impor disimpan sebagai draf kecuali kolom Status berisi “Published”.
                            </p>
                            <input
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                onChange={(e) => importForm.setData('importFile', e.target.files[0])}
                                className="block w-full text-xs text-slate-600 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-semibold"
                            />
                            {importForm.errors.importFile && <p className="text-xs text-rose-600">{importForm.errors.importFile}</p>}
                        </div>
                        <div className="px-6 py-4 bg-emerald-50 border-t border-emerald-100 flex justify-end space-x-3">
                            <button type="button" onClick={() => setShowImport(false)} className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-white rounded-xl transition">Batal</button>
                            <button
                                type="submit"
                                disabled={importForm.processing || !importForm.data.importFile}
                                className="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl disabled:opacity-50 flex items-center space-x-2 transition"
                            >
                                {importForm.processing && <Spinner className="w-3.5 h-3.5 text-white" />}
                                <span>{importForm.processing ? 'Mengimpor...' : 'Import'}</span>
                            </button>
                        </div>
                    </form>
                </div>
            )}

            {/* Modal AI Generator */}
            {showAi && (
                <div className="fixed inset-0 z-50 overflow-y-auto flex items-start justify-center p-4 bg-slate-900/50">
                    <div className="bg-white rounded-2xl max-w-2xl w-full my-8 shadow-2xl overflow-hidden">
                        <div className="px-6 py-4 bg-purple-700 text-white flex items-center justify-between">
                            <h3 className="font-bold">AI Generator Artikel</h3>
                            <button onClick={() => setShowAi(false)} className="text-purple-200 hover:text-white p-1" aria-label="Tutup">✕</button>
                        </div>

                        <div className="p-6 space-y-4">
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">Topik / Instruksi *</label>
                                <textarea
                                    rows={3}
                                    value={aiPrompt}
                                    onChange={(e) => setAiPrompt(e.target.value)}
                                    className="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500"
                                    placeholder="Contoh: Tips belajar pemrograman web untuk pemula"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-700">Jumlah Artikel (1-5)</label>
                                <input
                                    id="ai-count"
                                    type="number"
                                    min={1}
                                    max={5}
                                    value={aiCount}
                                    onChange={(e) => setAiCount(e.target.value)}
                                    className="mt-1 w-32 px-3 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500"
                                />
                                {countInvalid && <p className="mt-1 text-xs text-rose-600">Jumlah artikel harus berupa angka bulat antara 1 dan 5.</p>}
                            </div>

                            {aiError && <div className="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs">{aiError}</div>}

                            <button
                                type="button"
                                onClick={generateAi}
                                disabled={aiLoading || aiPrompt.trim().length < 3 || countInvalid}
                                className="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-xl disabled:opacity-50 flex items-center space-x-2 transition"
                            >
                                {aiLoading && <Spinner className="w-3.5 h-3.5 text-white" />}
                                <span>{aiLoading ? 'Menghasilkan...' : 'Generate Artikel'}</span>
                            </button>

                            {aiItems.length > 0 && (
                                <div className="space-y-3 pt-2 border-t border-slate-100">
                                    <p className="text-xs font-bold text-slate-600 uppercase tracking-wider">Pilih draf yang akan disimpan</p>
                                    {aiItems.map((item, i) => (
                                        <label key={i} className="flex items-start space-x-3 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={aiSelected.includes(i)}
                                                onChange={() => toggleAiSelect(i)}
                                                className="mt-1 w-4 h-4 text-purple-600 rounded"
                                            />
                                            <div className="min-w-0">
                                                <p className="text-sm font-semibold text-slate-900">{item.title}</p>
                                                <span className="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700">{item.category}</span>
                                                {item.excerpt && <p className="text-xs text-slate-500 mt-1">{item.excerpt}</p>}
                                            </div>
                                        </label>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="px-6 py-4 bg-purple-50 border-t border-purple-100 flex justify-end space-x-3">
                            <button onClick={() => setShowAi(false)} className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-white rounded-xl transition">Tutup</button>
                            <button
                                onClick={saveAi}
                                disabled={aiSaving || aiSelected.length === 0}
                                className="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl disabled:opacity-50 flex items-center space-x-2 transition"
                            >
                                {aiSaving && <Spinner className="w-3.5 h-3.5 text-white" />}
                                <span>{aiSaving ? 'Menyimpan...' : `Simpan ${aiSelected.length} Draf`}</span>
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}

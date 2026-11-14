import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function PostIndex({ posts, filters = {}, categories = [] }) {
    const { auth } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const category = filters.category || '';

    const go = (next) => {
        router.get('/articles', { search, category, ...next }, { preserveState: true, replace: true });
    };

    return (
        <PublicLayout>
            <Head title="Beranda" />

            {/* Hero Banner */}
            <section className="bg-gradient-to-br from-indigo-700 via-indigo-600 to-purple-700 text-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
                    <span className="inline-block text-xs font-bold uppercase tracking-widest bg-white/15 px-3 py-1 rounded-full">Platform Publikasi Artikel</span>
                    <h1 className="mt-4 text-4xl md:text-6xl font-black tracking-tight max-w-3xl">Baca, tulis, dan bagikan ide Anda ke dunia.</h1>
                    <p className="mt-5 text-lg text-indigo-100 max-w-2xl">
                        BlogVerse Pro mengumpulkan tulisan dari berbagai penulis — teknologi, pendidikan, gaya hidup, bisnis, dan opini. Jelajahi tanpa perlu login.
                    </p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        {auth?.user ? (
                            <Link href={auth.user.role === 'admin' ? '/admin/users' : '/dashboard/posts'} className="px-6 py-3 bg-white text-indigo-700 font-bold rounded-xl shadow hover:bg-indigo-50 transition">
                                Buka Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="px-6 py-3 bg-white text-indigo-700 font-bold rounded-xl shadow hover:bg-indigo-50 transition">
                                    Halaman Login (Masuk)
                                </Link>
                                <Link href="/register" className="px-6 py-3 bg-indigo-900/40 border border-white/30 text-white font-bold rounded-xl hover:bg-indigo-900/60 transition">
                                    Halaman Register (Daftar Penulis)
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </section>

            {/* Pencarian & Filter */}
            <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10">
                <div className="flex flex-col md:flex-row gap-3 md:items-center">
                    <input
                        id="public-search"
                        type="text"
                        placeholder="Cari artikel berdasarkan judul atau konten..."
                        value={search}
                        onChange={(e) => {
                            setSearch(e.target.value);
                            go({ search: e.target.value });
                        }}
                        className="flex-1 px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500"
                    />
                </div>
                <div className="flex flex-wrap gap-2 mt-4">
                    <button
                        onClick={() => go({ category: '' })}
                        className={`px-3.5 py-1.5 rounded-full text-xs font-semibold transition ${!category ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}`}
                    >
                        Semua
                    </button>
                    {categories.map((c) => (
                        <button
                            key={c.value}
                            onClick={() => go({ category: c.value })}
                            className={`px-3.5 py-1.5 rounded-full text-xs font-semibold transition ${category === c.value ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}`}
                        >
                            {c.value}
                        </button>
                    ))}
                </div>
            </section>

            {/* Daftar Artikel */}
            <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
                {posts.data.length === 0 ? (
                    <div className="text-center py-20 text-slate-600">
                        <p className="text-lg font-bold text-slate-700">Belum ada artikel yang cocok.</p>
                        <p className="text-sm mt-1">Coba kata kunci atau kategori lain.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        {posts.data.map((post) => (
                            <Link
                                key={post.id}
                                href={`/articles/${post.slug}`}
                                className="group bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition flex flex-col"
                            >
                                {post.cover_image_path ? (
                                    <img src={`/storage/${post.cover_image_path}`} alt={post.title} className="h-44 w-full object-cover" />
                                ) : (
                                    <div className="h-44 w-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center text-4xl font-black text-indigo-600">
                                        {post.title.charAt(0).toUpperCase()}
                                    </div>
                                )}
                                <div className="p-5 flex-1 flex flex-col">
                                    <span className="self-start text-[10px] font-bold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700">{post.category}</span>
                                    <h2 className="mt-2 text-lg font-bold text-slate-900 group-hover:text-indigo-600 transition line-clamp-2">{post.title}</h2>
                                    {post.excerpt && <p className="mt-2 text-sm text-slate-600 line-clamp-3">{post.excerpt}</p>}
                                    <div className="mt-auto pt-4 text-xs text-slate-600">
                                        {post.author} · {post.published_at}
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}

                {posts.links?.length > 3 && (
                    <div className="flex items-center justify-center flex-wrap gap-1 pt-10">
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
                                        : 'bg-slate-50 text-slate-500 border-slate-200 cursor-not-allowed'
                                }`}
                            />
                        ))}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}

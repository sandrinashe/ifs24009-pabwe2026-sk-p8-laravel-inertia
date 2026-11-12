import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import MarkdownViewer from '../../Components/MarkdownViewer';

export default function PostShow({ post }) {
    const { auth } = usePage().props;

    return (
        <PublicLayout>
            <Head title={post.title} />

            <article className="w-full">
                {post.cover_image_path && (
                    <div className="w-full max-h-[28rem] overflow-hidden bg-slate-100">
                        <img src={`/storage/${post.cover_image_path}`} alt={post.title} className="w-full h-full max-h-[28rem] object-cover" />
                    </div>
                )}

                {/* Full-width reading layout */}
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                    <Link href="/articles" className="text-xs font-semibold text-indigo-600 hover:text-indigo-800">&larr; Semua Artikel</Link>

                    <span className="block mt-5 w-fit text-[11px] font-bold px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700">{post.category}</span>
                    <h1 className="mt-3 text-4xl md:text-5xl font-black tracking-tight text-slate-900">{post.title}</h1>
                    {post.excerpt && <p className="mt-4 text-xl text-slate-500">{post.excerpt}</p>}
                    <p className="mt-5 text-sm text-slate-400">Oleh <span className="font-semibold text-slate-600">{post.author}</span> · {post.published_at}</p>

                    <div className="mt-8 pt-8 border-t border-slate-200">
                        <MarkdownViewer content={post.content} />
                    </div>

                    {/* Kartu ajakan komunitas */}
                    {!auth?.user && (
                        <div className="mt-14 rounded-3xl bg-gradient-to-br from-indigo-600 to-purple-700 text-white p-8 md:p-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                            <div>
                                <h2 className="text-2xl font-extrabold">Suka dengan tulisan ini?</h2>
                                <p className="mt-1 text-indigo-100">Bergabunglah dengan komunitas BlogVerse Pro dan mulai menulis artikel Anda sendiri.</p>
                            </div>
                            <div className="flex flex-wrap gap-3 shrink-0">
                                <Link href="/login" className="px-5 py-2.5 bg-white text-indigo-700 font-bold rounded-xl hover:bg-indigo-50 transition">Masuk</Link>
                                <Link href="/register" className="px-5 py-2.5 bg-indigo-900/40 border border-white/30 font-bold rounded-xl hover:bg-indigo-900/60 transition">Daftar</Link>
                            </div>
                        </div>
                    )}
                </div>
            </article>
        </PublicLayout>
    );
}

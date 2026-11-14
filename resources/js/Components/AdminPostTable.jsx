import React from 'react';
import { Link } from '@inertiajs/react';

/**
 * Tabel artikel read-only untuk panel admin (dipakai halaman semua artikel & artikel per-user).
 */
export default function AdminPostTable({ posts, showAuthor = true }) {
    return (
        <>
            <div className="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div className="overflow-x-auto" tabIndex={0} role="region" aria-label="Tabel artikel">
                    <table className="w-full text-left text-xs">
                        <thead className="bg-slate-50 border-b border-slate-100 text-slate-600 uppercase tracking-wider font-semibold">
                            <tr>
                                <th className="px-6 py-3.5">Judul</th>
                                {showAuthor && <th className="px-6 py-3.5">Penulis</th>}
                                <th className="px-6 py-3.5">Kategori</th>
                                <th className="px-6 py-3.5">Status</th>
                                <th className="px-6 py-3.5">Dibuat</th>
                                <th className="px-6 py-3.5 text-right">Baca</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {posts.data.length === 0 && (
                                <tr>
                                    <td colSpan={showAuthor ? 6 : 5} className="px-6 py-10 text-center text-slate-600">Tidak ada artikel.</td>
                                </tr>
                            )}
                            {posts.data.map((post) => (
                                <tr key={post.id} className="hover:bg-slate-50/60 transition">
                                    <td className="px-6 py-4 font-semibold text-slate-900 max-w-xs truncate">{post.title}</td>
                                    {showAuthor && <td className="px-6 py-4 text-slate-600">{post.user?.name ?? '-'}</td>}
                                    <td className="px-6 py-4">
                                        <span className="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700">{post.category}</span>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${post.is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}`}>
                                            {post.is_published ? 'Published' : 'Draf'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-slate-600">{post.created_at ? new Date(post.created_at).toLocaleDateString('id-ID') : '-'}</td>
                                    <td className="px-6 py-4 text-right">
                                        {post.is_published ? (
                                            <Link href={`/articles/${post.slug}`} className="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold rounded-lg transition">
                                                Buka
                                            </Link>
                                        ) : (
                                            <span className="text-slate-500">—</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {posts.links?.length > 3 && (
                <div className="flex items-center justify-center space-x-1 pt-4">
                    {posts.links.map((link, idx) => (
                        <Link
                            key={idx}
                            href={link.url || '#'}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`px-3 py-1.5 text-xs font-semibold rounded-lg border transition ${
                                link.active
                                    ? 'bg-purple-600 text-white border-purple-600'
                                    : link.url
                                    ? 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'
                                    : 'bg-slate-50 text-slate-500 border-slate-200 cursor-not-allowed'
                            }`}
                        />
                    ))}
                </div>
            )}
        </>
    );
}

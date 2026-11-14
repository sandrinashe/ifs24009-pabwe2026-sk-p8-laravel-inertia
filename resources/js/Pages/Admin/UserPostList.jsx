import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminPostTable from '../../Components/AdminPostTable';

export default function UserPostList({ targetUser, posts, filters = {}, categories = [] }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [category, setCategory] = useState(filters.category || '');

    const apply = (next) => {
        router.get(`/admin/users/${targetUser.id}/posts`, { search, status, category, ...next }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout>
            <Head title={`Artikel ${targetUser.name} - Admin`} />

            <div className="space-y-6">
                <Link href="/admin/users" className="text-xs font-semibold text-purple-600 hover:text-purple-800">&larr; Kembali ke Manajemen Pengguna</Link>

                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Artikel {targetUser.name}</h1>
                        <p className="text-sm text-slate-600 mt-0.5">@{targetUser.username} · {targetUser.email} · mode pantau (read-only)</p>
                    </div>
                    <a
                        href={`/admin/users/${targetUser.id}/posts/export`}
                        className="inline-flex items-center justify-center px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-xl transition"
                    >
                        Export Excel User Ini
                    </a>
                </div>

                <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs grid grid-cols-1 md:grid-cols-[1fr_12rem_12rem] gap-3">
                    <input
                        type="text"
                        aria-label="Cari artikel berdasarkan judul atau konten"
                        placeholder="Cari judul atau konten..."
                        value={search}
                        onChange={(e) => {
                            setSearch(e.target.value);
                            apply({ search: e.target.value });
                        }}
                        className="w-full px-4 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500"
                    />
                    <select
                        aria-label="Filter kategori artikel"
                        value={category}
                        onChange={(e) => {
                            setCategory(e.target.value);
                            apply({ category: e.target.value });
                        }}
                        className="py-2 px-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500"
                    >
                        <option value="">Semua Kategori</option>
                        {categories.map((c) => (
                            <option key={c.value} value={c.value}>{c.value}</option>
                        ))}
                    </select>
                    <select
                        aria-label="Filter status artikel"
                        value={status}
                        onChange={(e) => {
                            setStatus(e.target.value);
                            apply({ status: e.target.value });
                        }}
                        className="py-2 px-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-purple-500"
                    >
                        <option value="">Semua Status</option>
                        <option value="published">Dipublikasikan</option>
                        <option value="draft">Draf</option>
                    </select>
                </div>

                <AdminPostTable posts={posts} showAuthor={false} />
            </div>
        </AppLayout>
    );
}

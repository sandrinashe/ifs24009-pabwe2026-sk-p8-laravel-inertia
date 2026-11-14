import React from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import LoadingIndicator from '../Components/LoadingIndicator';

/**
 * Layout untuk halaman publik: tanpa sidebar, Sticky Top Navbar, full-width reading layout, footer editorial.
 */
export default function PublicLayout({ children }) {
    const { auth, flash } = usePage().props;
    const user = auth?.user;

    return (
        <div className="min-h-screen bg-white flex flex-col">
            <LoadingIndicator />

            {/* Sticky Top Navbar */}
            <header className="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-slate-200">
                <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <Link href="/" className="flex items-center space-x-2.5">
                        <span className="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white font-black flex items-center justify-center shadow-sm">B</span>
                        <span className="font-extrabold text-lg tracking-tight text-slate-900">BlogVerse <span className="text-indigo-600">Pro</span></span>
                    </Link>

                    <nav aria-label="Navigasi utama" className="flex items-center gap-2">
                        <Link href="/articles" className="hidden sm:inline-flex px-3 py-2 text-sm font-semibold text-slate-600 hover:text-indigo-600 transition">
                            Artikel
                        </Link>

                        {user ? (
                            <>
                                <Link
                                    href={user.role === 'admin' ? '/admin/users' : '/dashboard/posts'}
                                    className="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-sm font-semibold transition"
                                >
                                    <span className="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">
                                        {user.name?.charAt(0).toUpperCase()}
                                    </span>
                                    <span>{user.role === 'admin' ? 'Panel Admin' : 'Dashboard Penulis'}</span>
                                </Link>
                                <button
                                    type="button"
                                    onClick={() => router.post('/logout')}
                                    className="px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 rounded-xl transition"
                                >
                                    Keluar
                                </button>
                            </>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="inline-flex items-center space-x-1.5 px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 rounded-xl transition"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Masuk</span>
                                </Link>
                                <Link
                                    href="/register"
                                    className="inline-flex items-center space-x-1.5 px-4 py-2 text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-xs transition"
                                >
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    <span>Daftar</span>
                                </Link>
                            </>
                        )}
                    </nav>
                </div>
            </header>

            {flash?.success && (
                <div className="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div className="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">{flash.success}</div>
                </div>
            )}

            <main className="flex-1 w-full">{children}</main>

            {/* Footer Editorial */}
            <footer className="bg-slate-900 text-slate-300 mt-16">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <p className="text-white font-extrabold text-lg">BlogVerse Pro</p>
                        <p className="text-sm text-slate-400 mt-1 max-w-sm">Platform publikasi artikel multi-tier: baca, tulis, dan kelola tulisan dengan mudah.</p>
                    </div>
                    <nav aria-label="Navigasi footer" className="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-semibold">
                        <Link href="/" className="hover:text-white transition">Beranda Publik</Link>
                        <Link href="/login" className="hover:text-white transition">Halaman Login</Link>
                        <Link href="/register" className="hover:text-white transition">Halaman Register</Link>
                    </nav>
                </div>
                <div className="border-t border-slate-800 py-4 text-center text-xs text-slate-400">
                    &copy; {new Date().getFullYear()} BlogVerse Pro. Dibangun dengan Laravel 13, Inertia.js &amp; React.
                </div>
            </footer>
        </div>
    );
}

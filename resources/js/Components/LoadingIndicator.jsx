import React, { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';

export function Spinner({ className = 'w-4 h-4 text-white' }) {
    return (
        <svg className={`animate-spin shrink-0 ${className}`} fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    );
}

/**
 * Top progress bar + floating "Memproses tindakan..." pill shown during any Inertia visit/request.
 */
export default function LoadingIndicator() {
    const [isProcessing, setIsProcessing] = useState(false);

    useEffect(() => {
        const removeStart = router.on('start', () => setIsProcessing(true));
        const removeFinish = router.on('finish', () => setIsProcessing(false));
        return () => {
            removeStart();
            removeFinish();
        };
    }, []);

    if (!isProcessing) return null;

    return (
        <>
            <div className="fixed top-0 left-0 right-0 z-[60] h-1 bg-gradient-to-r from-indigo-600 via-blue-600 to-purple-600">
                <div className="h-full bg-white/50 w-full animate-pulse"></div>
            </div>
            <div className="fixed bottom-5 right-5 z-[60] bg-slate-900/95 text-white px-4 py-2.5 rounded-2xl shadow-xl flex items-center space-x-2.5 text-xs font-semibold backdrop-blur-sm border border-slate-700/60">
                <Spinner className="w-4 h-4 text-indigo-400" />
                <span>Memproses tindakan...</span>
            </div>
        </>
    );
}

import { createContext, useCallback, useContext, useState, type ReactNode } from 'react';

type ToastKind = 'success' | 'error' | 'warning' | 'info';
type Toast = { id: number; kind: ToastKind; message: string };

type ToastContextValue = {
    success: (message: string) => void;
    error: (message: string) => void;
    warning: (message: string) => void;
    info: (message: string) => void;
};

const ToastContext = createContext<ToastContextValue | null>(null);

const styles: Record<ToastKind, string> = {
    success: 'border-blue-600',
    error: 'border-orange-600',
    warning: 'border-orange-400',
    info: 'border-slate-400',
};

export function ToastProvider({ children }: { children: ReactNode }) {
    const [toasts, setToasts] = useState<Toast[]>([]);

    const push = useCallback((kind: ToastKind, message: string) => {
        const id = Date.now() + Math.random();
        setToasts((t) => [...t, { id, kind, message }]);
        setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), 4500);
    }, []);

    return (
        <ToastContext.Provider
            value={{
                success: (m) => push('success', m),
                error: (m) => push('error', m),
                warning: (m) => push('warning', m),
                info: (m) => push('info', m),
            }}
        >
            {children}
            <div className="fixed bottom-4 right-4 z-[200] flex flex-col gap-2">
                {toasts.map((t) => (
                    <div
                        key={t.id}
                        className={`w-80 border-l-4 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm ${styles[t.kind]}`}
                        role="status"
                    >
                        {t.message}
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
}

export function useToast() {
    const ctx = useContext(ToastContext);
    if (!ctx) throw new Error('useToast() must be used within <ToastProvider>.');
    return ctx;
}

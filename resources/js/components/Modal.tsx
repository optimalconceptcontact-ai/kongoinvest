import { RiCloseLine } from '@remixicon/react';
import { type ReactNode, useEffect, useRef } from 'react';

type ModalProps = {
    open: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    children: ReactNode;
    maxWidth?: 'sm' | 'md' | 'lg';
};

const widths = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg' };

export function Modal({ open, onClose, title, description, children, maxWidth = 'sm' }: ModalProps) {
    const panelRef = useRef<HTMLDivElement>(null);

    // Escape handling + basic focus trapping (spec §82 accessibility).
    useEffect(() => {
        if (!open) return;

        function onKeyDown(e: KeyboardEvent) {
            if (e.key === 'Escape') onClose();
        }

        document.addEventListener('keydown', onKeyDown);
        panelRef.current?.querySelector<HTMLElement>('input, button')?.focus();

        return () => document.removeEventListener('keydown', onKeyDown);
    }, [open, onClose]);

    if (!open) return null;

    return (
        <div
            className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-title"
            onMouseDown={(e) => {
                if (e.target === e.currentTarget) onClose();
            }}
        >
            <div
                ref={panelRef}
                className={`w-full ${widths[maxWidth]} border border-slate-900 bg-white`}
            >
                <div className="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <h2 id="modal-title" className="text-item-title-lg">
                            {title}
                        </h2>
                        {description && (
                            <p className="mt-1 text-body">{description}</p>
                        )}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Fermer"
                        className="text-slate-500 hover:text-slate-900"
                    >
                        <RiCloseLine size={20} />
                    </button>
                </div>
                <div className="px-6 py-6">{children}</div>
            </div>
        </div>
    );
}

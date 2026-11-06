import { RiCloseLine } from '@remixicon/react';
import { AnimatePresence, motion } from 'framer-motion';
import { useEffect, useId, useRef } from 'react';
import type { ReactNode } from 'react';
import { fadeIn, scaleIn } from '@/lib/animations';

type ModalProps = {
    open: boolean;
    onClose: () => void;
    title: string;
    description?: string;
    children: ReactNode;
    maxWidth?: 'sm' | 'md' | 'lg';
};

const widths = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg' };

const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'textarea:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

export function Modal({
    open,
    onClose,
    title,
    description,
    children,
    maxWidth = 'sm',
}: ModalProps) {
    const panelRef = useRef<HTMLDivElement>(null);
    const previousFocusRef = useRef<HTMLElement | null>(null);
    const onCloseRef = useRef(onClose);
    const titleId = useId();
    const descriptionId = useId();

    onCloseRef.current = onClose;

    useEffect(() => {
        if (!open) {
            return;
        }

        previousFocusRef.current = document.activeElement as HTMLElement | null;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        function getFocusable(): HTMLElement[] {
            return Array.from(
                panelRef.current?.querySelectorAll<HTMLElement>(
                    focusableSelector,
                ) ?? [],
            ).filter(
                (element) =>
                    element.offsetParent !== null ||
                    element === document.activeElement,
            );
        }

        function focusFirst() {
            const items = getFocusable();
            (items[0] ?? panelRef.current)?.focus();
        }

        function onKeyDown(e: KeyboardEvent) {
            if (e.key === 'Escape') {
                e.preventDefault();
                onCloseRef.current();
                return;
            }

            if (e.key !== 'Tab') {
                return;
            }

            const items = getFocusable();

            if (items.length === 0) {
                e.preventDefault();
                panelRef.current?.focus();
                return;
            }

            const first = items[0];
            const last = items[items.length - 1];
            const active = document.activeElement;
            const outsidePanel = !panelRef.current?.contains(active);

            if (e.shiftKey && (active === first || outsidePanel)) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && (active === last || outsidePanel)) {
                e.preventDefault();
                first.focus();
            }
        }

        document.addEventListener('keydown', onKeyDown);
        const focusTimer = window.setTimeout(focusFirst, 0);

        return () => {
            window.clearTimeout(focusTimer);
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = previousOverflow;
            previousFocusRef.current?.focus();
        };
    }, [open]);

    return (
        <AnimatePresence>
            {open && (
                <motion.div
                    variants={fadeIn}
                    initial="initial"
                    animate="animate"
                    exit="exit"
                    className="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4 backdrop-blur-[2px]"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby={titleId}
                    aria-describedby={description ? descriptionId : undefined}
                    onMouseDown={(e) => {
                        if (e.target === e.currentTarget) {
                            onCloseRef.current();
                        }
                    }}
                >
                    <motion.div
                        ref={panelRef}
                        variants={scaleIn}
                        tabIndex={-1}
                        className={`max-h-[calc(100vh-2rem)] w-full ${widths[maxWidth]} overflow-y-auto border border-rule-strong bg-paper focus:outline-none`}
                    >
                        <div className="flex items-start justify-between gap-4 border-b border-rule px-5 py-4">
                            <div>
                                <h2 id={titleId} className="text-item-title-lg">
                                    {title}
                                </h2>
                                {description && (
                                    <p
                                        id={descriptionId}
                                        className="mt-1 text-body"
                                    >
                                        {description}
                                    </p>
                                )}
                            </div>
                            <button
                                type="button"
                                onClick={onClose}
                                aria-label="Fermer"
                                className="-mt-2 -mr-2 flex h-11 w-11 flex-shrink-0 cursor-pointer items-center justify-center text-ink-muted hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action"
                            >
                                <RiCloseLine size={20} />
                            </button>
                        </div>
                        <div className="px-6 py-6">{children}</div>
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}

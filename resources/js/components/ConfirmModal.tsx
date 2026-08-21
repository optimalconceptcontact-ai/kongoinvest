import { Modal } from '@/components/Modal';
import { Button } from '@/components/Ui/Button';

/**
 * Spec §101's global modal system — this is what every scattered
 * `confirm('...')` browser dialog in earlier phases (Escrow release,
 * KYC admin actions, project archive) should be routed through instead.
 * A native `confirm()` can't be styled, can't show async state, and
 * doesn't match this app's design system at all — this does.
 */
export function ConfirmModal({
    open,
    onClose,
    onConfirm,
    title,
    description,
    confirmLabel = 'Confirmer',
    danger = false,
    processing = false,
}: {
    open: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    description: string;
    confirmLabel?: string;
    danger?: boolean;
    processing?: boolean;
}) {
    return (
        <Modal open={open} onClose={onClose} title={title} description={description} maxWidth="sm">
            <div className="flex justify-end gap-2">
                <Button type="button" variant="outline" onClick={onClose} className="text-slate-700">
                    Annuler
                </Button>
                <Button type="button" variant={danger ? 'destructive' : 'primary'} onClick={onConfirm} disabled={processing}>
                    {processing ? '...' : confirmLabel}
                </Button>
            </div>
        </Modal>
    );
}

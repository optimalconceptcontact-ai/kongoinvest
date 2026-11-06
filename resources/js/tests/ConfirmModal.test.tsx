import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { ConfirmModal } from '@/components/ConfirmModal';

describe('ConfirmModal', () => {
    it('calls onConfirm when the confirm button is clicked', () => {
        const onConfirm = vi.fn();
        render(
            <ConfirmModal
                open
                onClose={() => {}}
                onConfirm={onConfirm}
                title="Libérer les fonds"
                description="Cette action est irréversible."
            />,
        );

        fireEvent.click(screen.getByText('Confirmer'));

        expect(onConfirm).toHaveBeenCalledTimes(1);
    });

    it('calls onClose, not onConfirm, when Annuler is clicked', () => {
        const onConfirm = vi.fn();
        const onClose = vi.fn();
        render(
            <ConfirmModal
                open
                onClose={onClose}
                onConfirm={onConfirm}
                title="Titre"
                description="Description"
            />,
        );

        fireEvent.click(screen.getByText('Annuler'));

        expect(onClose).toHaveBeenCalledTimes(1);
        expect(onConfirm).not.toHaveBeenCalled();
    });

    it('disables the confirm button while processing, preventing a double-submit', () => {
        const onConfirm = vi.fn();
        render(
            <ConfirmModal
                open
                onClose={() => {}}
                onConfirm={onConfirm}
                title="Titre"
                description="Description"
                processing
            />,
        );

        const button = screen.getByRole('button', { name: '...' });
        expect(button).toBeDisabled();
    });

    it('uses the custom confirm label when provided', () => {
        render(
            <ConfirmModal
                open
                onClose={() => {}}
                onConfirm={() => {}}
                title="Titre"
                description="Description"
                confirmLabel="Confirmer la libération"
            />,
        );

        expect(screen.getByText('Confirmer la libération')).toBeInTheDocument();
    });
});

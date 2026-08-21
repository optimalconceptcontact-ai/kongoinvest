import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { Modal } from '@/components/Modal';

describe('Modal', () => {
    it('renders title and description when open', () => {
        render(
            <Modal open onClose={() => {}} title="Confirmer" description="Voulez-vous continuer ?">
                <p>Contenu</p>
            </Modal>,
        );

        expect(screen.getByText('Confirmer')).toBeInTheDocument();
        expect(screen.getByText('Voulez-vous continuer ?')).toBeInTheDocument();
        expect(screen.getByText('Contenu')).toBeInTheDocument();
    });

    it('renders nothing when closed', () => {
        render(
            <Modal open={false} onClose={() => {}} title="Confirmer">
                <p>Contenu</p>
            </Modal>,
        );

        expect(screen.queryByText('Confirmer')).not.toBeInTheDocument();
    });

    it('calls onClose when Escape is pressed (spec §82 accessibility)', () => {
        const onClose = vi.fn();
        render(
            <Modal open onClose={onClose} title="Confirmer">
                <p>Contenu</p>
            </Modal>,
        );

        fireEvent.keyDown(document, { key: 'Escape' });

        expect(onClose).toHaveBeenCalledTimes(1);
    });

    it('calls onClose when clicking the backdrop', () => {
        const onClose = vi.fn();
        const { container } = render(
            <Modal open onClose={onClose} title="Confirmer">
                <p>Contenu</p>
            </Modal>,
        );

        // The backdrop is the outer dialog container itself (mousedown on
        // it, not on the panel inside, triggers close).
        const backdrop = container.querySelector('[role="dialog"]')!;
        fireEvent.mouseDown(backdrop);

        expect(onClose).toHaveBeenCalledTimes(1);
    });
});

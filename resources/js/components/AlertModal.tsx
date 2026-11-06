import { Modal } from '@/components/Modal';
import { Button } from '@/components/ui/button';

export function AlertModal({
    open,
    onClose,
    title,
    description,
}: {
    open: boolean;
    onClose: () => void;
    title: string;
    description: string;
}) {
    return (
        <Modal
            open={open}
            onClose={onClose}
            title={title}
            description={description}
            maxWidth="sm"
        >
            <div className="flex justify-end">
                <Button type="button" onClick={onClose}>
                    Compris
                </Button>
            </div>
        </Modal>
    );
}

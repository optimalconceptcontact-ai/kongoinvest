import type { ReactNode } from 'react';
import { AuthModalProvider } from '@/components/Auth/AuthModalProvider';
import { FlashToastBridge } from '@/components/Toast/FlashToastBridge';
import { ToastProvider } from '@/components/Toast/ToastProvider';

export default function RootLayout({ children }: { children: ReactNode }) {
    return (
        <ToastProvider>
            <AuthModalProvider>
                <FlashToastBridge />
                {children}
            </AuthModalProvider>
        </ToastProvider>
    );
}

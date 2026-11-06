import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { PhoneInput, splitPhone } from '@/components/ui/PhoneInput';
import { UrlInput, splitUrl } from '@/components/ui/UrlInput';

describe('splitPhone', () => {
    it('keeps the stored dial code and national part', () => {
        const { dial, national } = splitPhone('+242 055 123 456');
        expect(dial).toBe('+242');
        expect(national).toBe('055 123 456');
    });

    it('falls back to the DRC dial code for values without a prefix', () => {
        const { dial, national } = splitPhone('0812 345 678');
        expect(dial).toBe('+243');
        expect(national).toBe('0812 345 678');
    });

    it('surfaces unknown dial codes as-is', () => {
        const { dial, national } = splitPhone('+99555 123');
        expect(dial).toBe('+995');
        expect(national).toBe('55 123');
    });
});

describe('PhoneInput', () => {
    it('edits the national part while keeping the dial code', () => {
        let latest = '';
        render(
            <PhoneInput
                value="+243812 345"
                onChange={(v) => {
                    latest = v;
                }}
            />,
        );
        const input = screen.getByPlaceholderText('812 345 678');
        fireEvent.change(input, { target: { value: '812 346' } });
        expect(latest).toBe('+243812 346');
    });

    it('switches dial code when the country select changes', () => {
        let latest = '';
        render(
            <PhoneInput
                value="+243812 345"
                onChange={(v) => {
                    latest = v;
                }}
            />,
        );
        fireEvent.change(screen.getByLabelText('Indicatif du pays'), {
            target: { value: '+32' },
        });
        expect(latest).toBe('+32812 345');
    });

    it('keeps unknown dial codes editable', () => {
        let latest = '';
        render(
            <PhoneInput
                value="+99555 123"
                onChange={(v) => {
                    latest = v;
                }}
            />,
        );
        fireEvent.change(screen.getByPlaceholderText('812 345 678'), {
            target: { value: '55 456' },
        });
        expect(latest).toBe('+99555 456');
    });
});

describe('splitUrl', () => {
    it('defaults to https for values without a scheme', () => {
        const { scheme, path } = splitUrl('exemple.com');
        expect(scheme).toBe('https://');
        expect(path).toBe('exemple.com');
    });

    it('keeps an http scheme when one was stored', () => {
        const { scheme, path } = splitUrl('http://exemple.com');
        expect(scheme).toBe('http://');
        expect(path).toBe('exemple.com');
    });
});

describe('UrlInput', () => {
    it('recomposes the scheme with the typed path', () => {
        let latest = '';
        render(
            <UrlInput
                data-testid="url-input"
                value="https://exemple.com"
                onChange={(v) => {
                    latest = v;
                }}
            />,
        );
        fireEvent.change(screen.getByTestId('url-input'), {
            target: { value: 'exemple.com/ong' },
        });
        expect(latest).toBe('https://exemple.com/ong');
    });

    it('accepts a URL typed with an explicit scheme once', () => {
        let latest = '';
        render(
            <UrlInput
                data-testid="url-input"
                value="https://exemple.com"
                onChange={(v) => {
                    latest = v;
                }}
            />,
        );
        fireEvent.change(screen.getByTestId('url-input'), {
            target: { value: 'https://linkedin.com/in/x' },
        });
        expect(latest).toBe('https://linkedin.com/in/x');
    });

    it('clears fully instead of storing a bare scheme', () => {
        let latest = 'https://exemple.com';
        render(
            <UrlInput
                data-testid="url-input"
                value="https://exemple.com"
                onChange={(v) => {
                    latest = v;
                }}
            />,
        );
        fireEvent.change(screen.getByTestId('url-input'), {
            target: { value: '' },
        });
        expect(latest).toBe('');
    });
});

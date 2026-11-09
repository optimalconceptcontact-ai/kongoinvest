import {
    RiBold,
    RiDoubleQuotesL,
    RiFormatClear,
    RiH2,
    RiH3,
    RiItalic,
    RiLink,
    RiListOrdered,
    RiListUnordered,
    RiTableLine,
    RiUnderline,
} from '@remixicon/react';
import { useEffect, useRef, type ReactNode } from 'react';
import { useField } from '@/components/ui/Field';
import { cn } from '@/lib/utils';

type RichTextEditorProps = {
    value: string;
    onChange: (html: string) => void;
    error?: boolean;
    placeholder?: string;
    className?: string;
};

/**
 * Dependency-free rich text editor (contentEditable + execCommand).
 * The editing surface uses the same `.article-body` typography as the
 * public article, so what the admin writes is what readers will see.
 */
export function RichTextEditor({
    value,
    onChange,
    error,
    placeholder = 'Rédigez le contenu de l’article…',
    className,
}: RichTextEditorProps) {
    const ref = useRef<HTMLDivElement>(null);
    const field = useField();
    const showError = error || field?.hasError;

    // Render the initial HTML once, and re-render only when the value is
    // changed from the outside (e.g. switching to another article).
    useEffect(() => {
        if (ref.current && ref.current.innerHTML !== value) {
            ref.current.innerHTML = value ?? '';
        }
    }, [value]);

    function sync() {
        if (ref.current) {
            onChange(ref.current.innerHTML);
        }
    }

    function exec(command: string, argument?: string) {
        ref.current?.focus();
        document.execCommand(command, false, argument);
        sync();
    }

    function addLink() {
        const url = window.prompt('Adresse du lien (https://…)');

        if (url) {
            exec('createLink', url);
        }
    }

    /**
     * Toggle the current block into/out of a quote. formatBlock does not
     * toggle by itself, so we detect whether the caret is already inside
     * a <blockquote> first.
     */
    function toggleQuote() {
        let node: Node | null = window.getSelection()?.anchorNode ?? null;
        let inQuote = false;

        while (node && node !== ref.current) {
            if (
                node.nodeType === Node.ELEMENT_NODE &&
                (node as HTMLElement).tagName === 'BLOCKQUOTE'
            ) {
                inQuote = true;
                break;
            }

            node = node.parentNode;
        }

        exec('formatBlock', inQuote ? '<p>' : '<blockquote>');
    }

    /**
     * Insert a table with the asked dimensions. The first row is treated
     * as the header row; a trailing paragraph keeps the caret usable
     * after the table.
     */
    function insertTable() {
        const answer = window.prompt(
            'Dimensions du tableau (lignes x colonnes)',
            '3x3',
        );

        if (!answer) {
            return;
        }

        const [rawRows, rawColumns] = answer
            .split(/[x×*]/i)
            .map((part) => Number.parseInt(part.trim(), 10));

        const rows = Math.min(Math.max(rawRows || 3, 1), 20);
        const columns = Math.min(Math.max(rawColumns || 3, 1), 20);

        const header = Array.from(
            { length: columns },
            (_, index) => `<th>En-tête ${index + 1}</th>`,
        ).join('');

        const body = Array.from(
            { length: rows - 1 },
            () => `<tr>${'<td><br></td>'.repeat(columns)}</tr>`,
        ).join('');

        exec(
            'insertHTML',
            `<table><thead><tr>${header}</tr></thead><tbody>${body}</tbody></table><p><br></p>`,
        );
    }

    return (
        <div className={cn('w-full', className)}>
            <div
                role="toolbar"
                aria-label="Mise en forme de l’article"
                aria-controls={field?.controlId}
                className="flex flex-wrap items-center gap-1 border border-rule-strong bg-surface-2 p-1"
            >
                <ToolbarButton label="Gras" onClick={() => exec('bold')}>
                    <RiBold size={16} />
                </ToolbarButton>
                <ToolbarButton label="Italique" onClick={() => exec('italic')}>
                    <RiItalic size={16} />
                </ToolbarButton>
                <ToolbarButton
                    label="Souligné"
                    onClick={() => exec('underline')}
                >
                    <RiUnderline size={16} />
                </ToolbarButton>
                <ToolbarButton
                    label="Titre de section"
                    onClick={() => exec('formatBlock', '<h2>')}
                >
                    <RiH2 size={16} />
                </ToolbarButton>
                <ToolbarButton
                    label="Sous-titre"
                    onClick={() => exec('formatBlock', '<h3>')}
                >
                    <RiH3 size={16} />
                </ToolbarButton>
                <ToolbarButton
                    label="Liste à puces"
                    onClick={() => exec('insertUnorderedList')}
                >
                    <RiListUnordered size={16} />
                </ToolbarButton>
                <ToolbarButton
                    label="Liste numérotée"
                    onClick={() => exec('insertOrderedList')}
                >
                    <RiListOrdered size={16} />
                </ToolbarButton>
                <ToolbarButton label="Citation" onClick={toggleQuote}>
                    <RiDoubleQuotesL size={16} />
                </ToolbarButton>
                <ToolbarButton label="Insérer un lien" onClick={addLink}>
                    <RiLink size={16} />
                </ToolbarButton>
                <ToolbarButton label="Insérer un tableau" onClick={insertTable}>
                    <RiTableLine size={16} />
                </ToolbarButton>
                <ToolbarButton
                    label="Effacer la mise en forme"
                    onClick={() => {
                        exec('removeFormat');
                        exec('formatBlock', '<p>');
                    }}
                >
                    <RiFormatClear size={16} />
                </ToolbarButton>
            </div>

            <div
                ref={ref}
                id={field?.controlId}
                role="textbox"
                aria-multiline="true"
                aria-label="Contenu de l’article"
                aria-describedby={showError ? field?.errorId : undefined}
                aria-invalid={showError || undefined}
                contentEditable
                suppressContentEditableWarning
                data-placeholder={placeholder}
                onInput={sync}
                onBlur={sync}
                className={cn(
                    'article-body article-editor min-h-64 w-full border border-t-0 bg-paper px-4 py-3 focus-visible:border-action focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-action',
                    showError ? 'border-danger' : 'border-rule-strong',
                )}
            />
        </div>
    );
}

function ToolbarButton({
    label,
    onClick,
    children,
}: {
    label: string;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            title={label}
            aria-label={label}
            onMouseDown={(e) => e.preventDefault()}
            onClick={onClick}
            className="flex h-9 w-9 items-center justify-center border border-transparent text-ink-muted transition-colors hover:border-rule-strong hover:bg-paper hover:text-ink"
        >
            {children}
        </button>
    );
}

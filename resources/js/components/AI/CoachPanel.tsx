import { router } from '@inertiajs/react';
import { RiSparklingLine } from '@remixicon/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/Textarea';
import type { AiConversationData } from './InvestorAdvisorPanel';

export function CoachPanel({
    projectId,
    conversation,
}: {
    projectId: number;
    conversation: AiConversationData | null;
}) {
    const [question, setQuestion] = useState('');
    const [sending, setSending] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();

        if (!question.trim()) {
            return;
        }

        setSending(true);
        router.post(
            `/entrepreneur/projects/${projectId}/ai-coach`,
            { question },
            {
                preserveScroll: true,
                onFinish: () => setSending(false),
                onSuccess: () => setQuestion(''),
            },
        );
    }

    return (
        <div className="border border-rule bg-surface-2 p-5">
            <div className="mb-2 flex items-center gap-1.5 text-lg font-bold text-ink">
                <RiSparklingLine size={16} className="text-ink" /> Coach IA
            </div>
            <p className="mb-4 text-caption">
                Demandez comment améliorer votre projet — l'IA identifie les
                faiblesses réelles et suggère des priorités concrètes, jamais de
                fausses métriques (spec §53).
            </p>

            {conversation && conversation.messages.length > 0 && (
                <div className="mb-4 flex max-h-72 flex-col gap-3 overflow-y-auto border-t border-rule pt-4">
                    {conversation.messages.map((m) => (
                        <div
                            key={m.id}
                            className={`text-lg ${m.role === 'user' ? 'font-medium text-ink' : 'border-l-2 border-action pl-3 text-ink'}`}
                        >
                            {m.content}
                        </div>
                    ))}
                </div>
            )}

            <form onSubmit={submit} className="flex flex-col gap-2">
                <Textarea
                    value={question}
                    onChange={(e) => setQuestion(e.target.value)}
                    placeholder="Comment faire passer mon projet de 72 à 85 ?"
                    rows={2}
                />
                <Button
                    type="submit"
                    variant="cta"
                    size="sm"
                    disabled={sending || !question.trim()}
                    className="self-start px-4 py-2"
                >
                    {sending ? 'Envoi...' : 'Demander conseil'}
                </Button>
            </form>
        </div>
    );
}

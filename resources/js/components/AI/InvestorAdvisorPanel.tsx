import { router } from '@inertiajs/react';
import { RiQuestionAnswerLine, RiSparkling2Line } from '@remixicon/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export type AiMessageData = {
    id: number;
    role: string;
    content: string;
    createdAt: string;
};
export type AiConversationData = { id: number; messages: AiMessageData[] };

const presetQuestions = [
    'Quels sont les risques principaux de ce projet ?',
    'Que dois-je vérifier avant d\u2019investir ?',
    'Le besoin de financement semble-t-il cohérent ?',
    'Quels signaux sont positifs ?',
    'Quels sont les red flags potentiels ?',
];

export function InvestorAdvisorPanel({
    projectId,
    conversation,
}: {
    projectId: number;
    conversation: AiConversationData | null;
}) {
    const [question, setQuestion] = useState('');
    const [sending, setSending] = useState(false);

    function ask(q: string) {
        if (!q.trim()) {
            return;
        }

        setSending(true);
        router.post(
            `/investor/projects/${projectId}/ai-advisor`,
            { question: q },
            {
                preserveScroll: true,
                onFinish: () => setSending(false),
                onSuccess: () => setQuestion(''),
            },
        );
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        ask(question);
    }

    return (
        <div className="mt-4 border border-rule-strong p-4">
            <div className="mb-3 flex items-center gap-1.5 text-section-label">
                <RiQuestionAnswerLine size={14} className="text-ink" /> Coach IA
                investisseur
            </div>

            {conversation && conversation.messages.length > 0 && (
                <div className="mb-3 flex max-h-64 flex-col gap-2 overflow-y-auto">
                    {conversation.messages.map((m) => (
                        <div
                            key={m.id}
                            className={`text-lg ${m.role === 'user' ? 'text-ink-muted' : 'border-l-2 border-action pl-2 text-ink'}`}
                        >
                            {m.content}
                        </div>
                    ))}
                </div>
            )}

            {(!conversation || conversation.messages.length === 0) && (
                <div className="mb-3 flex flex-col gap-1.5">
                    {presetQuestions.map((q) => (
                        <Button
                            key={q}
                            variant="text"
                            size="inline"
                            onClick={() => ask(q)}
                            disabled={sending}
                            className="text-left"
                        >
                            {q}
                        </Button>
                    ))}
                </div>
            )}

            <form onSubmit={submit} className="flex gap-1.5">
                <Input
                    icon={<RiSparkling2Line size={18} />}
                    value={question}
                    onChange={(e) => setQuestion(e.target.value)}
                    placeholder="Poser une question..."
                    size="sm"
                    className="flex-1"
                />
                <Button
                    type="submit"
                    size="sm"
                    disabled={sending || !question.trim()}
                >
                    →
                </Button>
            </form>

            <p className="mt-2 text-lg leading-relaxed text-ink-muted">
                Analyse générée par IA, à titre informatif — ne remplace pas
                votre propre analyse.
            </p>
        </div>
    );
}

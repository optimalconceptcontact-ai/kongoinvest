import { router } from '@inertiajs/react';
import { RiQuestionAnswerLine } from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { Button } from '@/components/Ui/Button';
import { Input } from '@/components/Ui/Input';

export type AiMessageData = { id: number; role: string; content: string; createdAt: string };
export type AiConversationData = { id: number; messages: AiMessageData[] };

const presetQuestions = [
    'Quels sont les risques principaux de ce projet ?',
    'Que dois-je vérifier avant d\u2019investir ?',
    'Le besoin de financement semble-t-il cohérent ?',
    'Quels signaux sont positifs ?',
    'Quels sont les red flags potentiels ?',
];

export function InvestorAdvisorPanel({ projectId, conversation }: { projectId: number; conversation: AiConversationData | null }) {
    const [question, setQuestion] = useState('');
    const [sending, setSending] = useState(false);

    function ask(q: string) {
        if (!q.trim()) return;
        setSending(true);
        router.post(`/investor/projects/${projectId}/ai-advisor`, { question: q }, {
            preserveScroll: true,
            onFinish: () => setSending(false),
            onSuccess: () => setQuestion(''),
        });
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        ask(question);
    }

    return (
        <div className="mt-4 border border-slate-200 p-4">
            <div className="mb-3 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-slate-900">
                <RiQuestionAnswerLine size={14} className="text-orange-500" /> Conseiller IA
            </div>

            {conversation && conversation.messages.length > 0 && (
                <div className="mb-3 flex max-h-64 flex-col gap-2 overflow-y-auto">
                    {conversation.messages.map((m) => (
                        <div key={m.id} className={`text-xs ${m.role === 'user' ? 'text-slate-500' : 'border-l-2 border-blue-500 pl-2 text-slate-700'}`}>
                            {m.content}
                        </div>
                    ))}
                </div>
            )}

            {(!conversation || conversation.messages.length === 0) && (
                <div className="mb-3 flex flex-col gap-1.5">
                    {presetQuestions.map((q) => (
                        <Button key={q} variant="text" size="inline" onClick={() => ask(q)} disabled={sending} className="text-left">
                            {q}
                        </Button>
                    ))}
                </div>
            )}

            <form onSubmit={submit} className="flex gap-1.5">
                <Input
                    value={question}
                    onChange={(e) => setQuestion(e.target.value)}
                    placeholder="Poser une question..."
                    size="sm"
                    className="flex-1"
                />
                <Button type="submit" size="sm" disabled={sending || !question.trim()}>
                    →
                </Button>
            </form>

            <p className="mt-2 text-[10px] leading-relaxed text-slate-400">
                Analyse générée par IA, à titre informatif — ne remplace pas votre propre analyse.
            </p>
        </div>
    );
}

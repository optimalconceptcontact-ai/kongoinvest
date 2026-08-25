import { router } from '@inertiajs/react';
import { RiSparklingLine } from '@remixicon/react';
import { FormEvent, useState } from 'react';
import type { AiConversationData } from './InvestorAdvisorPanel';
import { Button } from '@/components/Ui/Button';
import { Textarea } from '@/components/Ui/Textarea';

export function CoachPanel({ projectId, conversation }: { projectId: number; conversation: AiConversationData | null }) {
    const [question, setQuestion] = useState('');
    const [sending, setSending] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!question.trim()) return;
        setSending(true);
        router.post(`/entrepreneur/projects/${projectId}/ai-coach`, { question }, {
            preserveScroll: true,
            onFinish: () => setSending(false),
            onSuccess: () => setQuestion(''),
        });
    }

    return (
        <div className="border border-slate-200 bg-slate-50 p-5">
            <div className="mb-2 flex items-center gap-1.5 text-sm font-bold text-slate-900">
                <RiSparklingLine size={16} className="text-orange-500" /> Coach IA
            </div>
            <p className="mb-4 text-caption">
                Demandez comment améliorer votre projet — l'IA identifie les faiblesses réelles et suggère des
                priorités concrètes, jamais de fausses métriques (spec §53).
            </p>

            {conversation && conversation.messages.length > 0 && (
                <div className="mb-4 flex max-h-72 flex-col gap-3 overflow-y-auto border-t border-slate-200 pt-4">
                    {conversation.messages.map((m) => (
                        <div key={m.id} className={`text-sm ${m.role === 'user' ? 'font-medium text-slate-700' : 'border-l-2 border-orange-400 pl-3 text-slate-700'}`}>
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
                <Button type="submit" variant="cta" size="sm" disabled={sending || !question.trim()} className="self-start px-4 py-2">
                    {sending ? 'Envoi...' : 'Demander conseil'}
                </Button>
            </form>
        </div>
    );
}

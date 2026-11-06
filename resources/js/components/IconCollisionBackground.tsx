import { useEffect, useMemo, useRef } from 'react';
import {
    forceCollide,
    forceManyBody,
    forceSimulation,
    forceX,
    forceY,
    type ForceX,
    type ForceY,
    type SimulationNodeDatum,
} from 'd3-force';
import {
    RiBriefcaseLine,
    RiContractLine,
    RiFileTextLine,
    RiHandCoinLine,
    RiPieChartLine,
    RiRocketLine,
    RiShakeHandsLine,
    RiShieldCheckLine,
    RiSparklingLine,
    RiWalletLine,
    type RemixiconComponentType,
} from '@remixicon/react';

const ICONS: RemixiconComponentType[] = [
    RiBriefcaseLine,
    RiPieChartLine,
    RiHandCoinLine,
    RiShakeHandsLine,
    RiShieldCheckLine,
    RiContractLine,
    RiWalletLine,
    RiRocketLine,
    RiSparklingLine,
    RiFileTextLine,
];

const COLORS = [
    '#e5484d',
    '#f5a524',
    '#2eb67d',
    '#2f81f7',
    '#8e4ec6',
    '#e93d82',
    '#0e9fa8',
    '#d9822b',
];

type IconNode = SimulationNodeDatum & {
    r: number;
};

type IconCollisionBackgroundProps = {
    count?: number;
    padding?: number;
    push?: number;
    opacity?: number;
    className?: string;
};

/**
 * Arrière-plan d'icônes Remix qui se repoussent (d3-force) et fuient le
 * pointeur. Remplit tout son parent (`absolute inset-0`) et suit ses
 * dimensions via ResizeObserver — pensé pour une section entière.
 * À placer dans un parent en `position: relative`, contenu par-dessus.
 */
export default function IconCollisionBackground({
    count = 30,
    padding = 3,
    push = 10,
    opacity = 0.6,
    className = '',
}: IconCollisionBackgroundProps) {
    const wrapRef = useRef<HTMLDivElement | null>(null);

    const items = useMemo(
        () =>
            Array.from({ length: count }, (_, i) => ({
                Icon: ICONS[i % ICONS.length],
                color: COLORS[i % COLORS.length],
                r: 16 + Math.random() * 22,
            })),
        [count],
    );

    useEffect(() => {
        const wrap = wrapRef.current;
        if (!wrap) {
            return;
        }
        const els = Array.from(wrap.children) as HTMLDivElement[];
        let { width: W, height: H } = wrap.getBoundingClientRect();
        if (W === 0 || H === 0) {
            return;
        }

        const nodes: IconNode[] = items.map((item) => ({
            r: item.r,
            x: W / 2 + (Math.random() - 0.5) * 60,
            y: H / 2 + (Math.random() - 0.5) * 60,
        }));

        const fx = forceX<IconNode>(W / 2).strength(0.04);
        const fy = forceY<IconNode>(H / 2).strength(0.04);
        const sim = forceSimulation<IconNode>(nodes)
            .force('x', fx)
            .force('y', fy)
            .force('charge', forceManyBody().strength(2))
            .force(
                'collide',
                forceCollide<IconNode>((d) => d.r + padding)
                    .strength(0.9)
                    .iterations(3),
            )
            .on('tick', () => {
                nodes.forEach((d, i) => {
                    const x = d.x ?? 0;
                    const y = d.y ?? 0;
                    const cx = Math.max(d.r, Math.min(W - d.r, x));
                    const cy = Math.max(d.r, Math.min(H - d.r, y));
                    els[i]?.setAttribute(
                        'style',
                        `transform: translate(${cx - d.r}px,${cy - d.r}px); color: ${items[i].color}; width: ${d.r * 2}px; height: ${d.r * 2}px;`,
                    );
                });
            });

        // Le pointeur est écouté sur la fenêtre : le fond reste réactif même
        // si du contenu est posé par-dessus (le fond a pointer-events: none).
        const onMove = (e: PointerEvent) => {
            const b = wrap.getBoundingClientRect();
            const px = e.clientX - b.left;
            const py = e.clientY - b.top;
            if (px < 0 || py < 0 || px > b.width || py > b.height) {
                return;
            }
            let hit = false;
            for (const d of nodes) {
                const dx = (d.x ?? 0) - px;
                const dy = (d.y ?? 0) - py;
                const dist = Math.hypot(dx, dy) || 1;
                if (dist < d.r + 4) {
                    d.vx = (d.vx ?? 0) + (dx / dist) * push;
                    d.vy = (d.vy ?? 0) + (dy / dist) * push;
                    hit = true;
                }
            }
            if (hit) {
                sim.alpha(Math.max(sim.alpha(), 0.4)).restart();
            }
        };
        window.addEventListener('pointermove', onMove);

        const ro = new ResizeObserver(() => {
            ({ width: W, height: H } = wrap.getBoundingClientRect());
            (fx as ForceX<IconNode>).x(W / 2);
            (fy as ForceY<IconNode>).y(H / 2);
            sim.alpha(0.5).restart();
        });
        ro.observe(wrap);

        return () => {
            window.removeEventListener('pointermove', onMove);
            ro.disconnect();
            sim.stop();
        };
    }, [items, padding, push]);

    return (
        <div
            ref={wrapRef}
            aria-hidden="true"
            className={`pointer-events-none absolute inset-0 overflow-hidden ${className}`}
            style={{ opacity }}
        >
            {items.map((item, i) => (
                <div
                    // eslint-disable-next-line react/no-array-index-key
                    key={i}
                    className="absolute top-0 left-0 flex items-center justify-center"
                    style={{
                        width: item.r * 2,
                        height: item.r * 2,
                        color: item.color,
                    }}
                >
                    <item.Icon size={Math.round(item.r * 1.2)} />
                </div>
            ))}
        </div>
    );
}

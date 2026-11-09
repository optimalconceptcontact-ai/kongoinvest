import {
    Area,
    AreaChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { formatNumber } from './utils';
import type { ChartDatum, StatisticsChart } from './types';

function colorValue(color: string | undefined): string {
    return `var(--${color ?? 'chart-1'})`;
}

export function ChartCard({ chart }: { chart: StatisticsChart }) {
    const hasData = chart.data.length > 0;

    return (
        <article className="border border-rule-strong bg-paper p-4 print:break-inside-avoid">
            <h3 className="text-item-title">{chart.title}</h3>
            <p className="mt-0.5 text-caption">{chart.description}</p>

            {!hasData ? (
                <div className="mt-4 flex h-56 items-center justify-center border border-dashed border-rule text-caption">
                    Aucune donnée sur la période sélectionnée.
                </div>
            ) : chart.type === 'pie' ? (
                <div className="mt-2 h-64">
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={chart.data}
                                dataKey="value"
                                nameKey="name"
                                cx="50%"
                                cy="50%"
                                innerRadius={50}
                                outerRadius={90}
                                paddingAngle={1}
                            >
                                {chart.data.map((entry: ChartDatum) => (
                                    <Cell
                                        key={String(entry.name)}
                                        fill={colorValue(entry.color)}
                                    />
                                ))}
                            </Pie>
                            <Tooltip
                                formatter={(value) =>
                                    typeof value === 'number'
                                        ? formatNumber(value)
                                        : String(value)
                                }
                                contentStyle={{
                                    border: '1px solid var(--rule-strong)',
                                    background: 'var(--paper)',
                                    color: 'var(--ink)',
                                    borderRadius: 0,
                                }}
                            />
                            <Legend
                                formatter={(value) => (
                                    <span className="text-caption">{value}</span>
                                )}
                            />
                        </PieChart>
                    </ResponsiveContainer>
                </div>
            ) : chart.type === 'area' ? (
                <div className="mt-2 h-64">
                    <ResponsiveContainer width="100%" height="100%">
                        <AreaChart data={chart.data}>
                            <CartesianGrid
                                stroke="var(--rule)"
                                vertical={false}
                            />
                            <XAxis
                                dataKey="label"
                                tick={{ fontSize: 12 }}
                                stroke="var(--rule-strong)"
                            />
                            <YAxis
                                allowDecimals={false}
                                tick={{ fontSize: 12 }}
                                stroke="var(--rule-strong)"
                                width={40}
                            />
                            <Tooltip
                                formatter={(value) =>
                                    typeof value === 'number'
                                        ? formatNumber(value)
                                        : String(value)
                                }
                                contentStyle={{
                                    border: '1px solid var(--rule-strong)',
                                    background: 'var(--paper)',
                                    color: 'var(--ink)',
                                    borderRadius: 0,
                                }}
                            />
                            {chart.series.map((series) => (
                                <Area
                                    key={series.key}
                                    type="monotone"
                                    dataKey={series.key}
                                    name={series.label}
                                    stroke={colorValue(series.color)}
                                    fill={colorValue(series.color)}
                                    fillOpacity={0.16}
                                    strokeWidth={2}
                                />
                            ))}
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            ) : (
                <div className="mt-2 h-64">
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={chart.data}>
                            <CartesianGrid
                                stroke="var(--rule)"
                                vertical={false}
                            />
                            <XAxis
                                dataKey="label"
                                tick={{ fontSize: 12 }}
                                stroke="var(--rule-strong)"
                            />
                            <YAxis
                                allowDecimals={false}
                                tick={{ fontSize: 12 }}
                                stroke="var(--rule-strong)"
                                width={40}
                            />
                            <Tooltip
                                formatter={(value) =>
                                    typeof value === 'number'
                                        ? formatNumber(value)
                                        : String(value)
                                }
                                contentStyle={{
                                    border: '1px solid var(--rule-strong)',
                                    background: 'var(--paper)',
                                    color: 'var(--ink)',
                                    borderRadius: 0,
                                }}
                            />
                            <Legend
                                formatter={(value) => (
                                    <span className="text-caption">{value}</span>
                                )}
                            />
                            {chart.series.map((series) => (
                                <Line
                                    key={series.key}
                                    type="monotone"
                                    dataKey={series.key}
                                    name={series.label}
                                    stroke={colorValue(series.color)}
                                    strokeWidth={2}
                                    dot={{ r: 2 }}
                                    activeDot={{ r: 4 }}
                                />
                            ))}
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            )}
        </article>
    );
}

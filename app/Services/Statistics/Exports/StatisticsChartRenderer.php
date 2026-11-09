<?php

namespace App\Services\Statistics\Exports;

/**
 * Renders a statistics chart as inline SVG for the PDF report.
 *
 * Recharts only runs in the browser, so the PDF cannot reuse the React
 * chart components. This renderer draws the same series from the same
 * report payload, keeping PDF and screen figures aligned.
 */
final class StatisticsChartRenderer
{
    /** @var array<string, string> */
    private const COLORS = [
        'chart-1' => '#1d4ed8',
        'chart-2' => '#f2b705',
        'chart-3' => '#0f766e',
        'chart-4' => '#7c3aed',
        'chart-5' => '#4a5568',
    ];

    /** @param array<string, mixed> $chart */
    public function render(array $chart): string
    {
        return match ($chart['type'] ?? 'line') {
            'pie' => $this->renderPie($chart),
            default => $this->renderLine($chart),
        };
    }

    /** @param array<string, mixed> $chart */
    private function renderLine(array $chart): string
    {
        $data = array_values($chart['data'] ?? []);
        $series = array_values($chart['series'] ?? []);

        if ($data === [] || $series === []) {
            return $this->emptyState();
        }

        $width = 660;
        $height = 260;
        $padding = ['top' => 30, 'right' => 16, 'bottom' => 34, 'left' => 44];
        $plotWidth = $width - $padding['left'] - $padding['right'];
        $plotHeight = $height - $padding['top'] - $padding['bottom'];
        $count = count($data);
        $step = $count > 1 ? $plotWidth / ($count - 1) : 0;

        $max = 0.0;

        foreach ($series as $definition) {
            foreach ($data as $row) {
                $max = max($max, (float) ($row[$definition['key']] ?? 0));
            }
        }

        $max = $this->niceMax($max);

        $svg = $this->openSvg($width, $height);

        // Horizontal grid + Y labels.
        for ($index = 0; $index <= 4; $index++) {
            $y = $padding['top'] + ($plotHeight / 4) * $index;
            $value = $max - ($max / 4) * $index;

            $svg .= sprintf(
                '<line x1="%1$d" y1="%2$.2f" x2="%3$d" y2="%2$.2f" stroke="#e2e8f0" stroke-width="1" />',
                $padding['left'],
                $y,
                $width - $padding['right'],
            );
            $svg .= sprintf(
                '<text x="%1$d" y="%2$.2f" text-anchor="end" font-family="DejaVu Sans, sans-serif" font-size="10" fill="#4a5568">%3$s</text>',
                $padding['left'] - 8,
                $y + 3,
                $this->formatNumber($value),
            );
        }

        // X labels, thinned out so they never overlap.
        $labelEvery = max(1, (int) ceil($count / 8));
        $last = $count - 1;

        foreach ($data as $index => $row) {
            if ($index % $labelEvery !== 0 && $index !== $last) {
                continue;
            }

            $x = $padding['left'] + $step * $index;

            $svg .= sprintf(
                '<text x="%1$.2f" y="%2$d" text-anchor="middle" font-family="DejaVu Sans, sans-serif" font-size="10" fill="#4a5568">%3$s</text>',
                $x,
                $height - 12,
                $this->escape((string) ($row['label'] ?? '')),
            );
        }

        foreach ($series as $definition) {
            $color = self::COLORS[$definition['color'] ?? 'chart-1'] ?? self::COLORS['chart-1'];
            $points = [];

            foreach ($data as $index => $row) {
                $value = (float) ($row[$definition['key']] ?? 0);
                $points[] = [
                    'x' => $padding['left'] + $step * $index,
                    'y' => $padding['top'] + $plotHeight - ($value / $max) * $plotHeight,
                ];
            }

            $polyline = implode(' ', array_map(
                fn (array $point) => sprintf('%.2f,%.2f', $point['x'], $point['y']),
                $points,
            ));

            $svg .= sprintf(
                '<polyline points="%1$s" fill="none" stroke="%2$s" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />',
                $polyline,
                $color,
            );

            foreach ($points as $point) {
                $svg .= sprintf(
                    '<circle cx="%.2f" cy="%.2f" r="2.5" fill="%s" />',
                    $point['x'],
                    $point['y'],
                    $color,
                );
            }
        }

        $svg .= $this->legend($series);

        return $svg.'</svg>';
    }

    /** @param array<string, mixed> $chart */
    private function renderPie(array $chart): string
    {
        $items = array_values(array_filter(
            $chart['data'] ?? [],
            fn (array $item) => (float) ($item['value'] ?? 0) > 0,
        ));
        $total = array_sum(array_map(fn (array $item) => (float) $item['value'], $items));

        if ($items === [] || $total <= 0) {
            return $this->emptyState();
        }

        $width = 660;
        $height = 240;
        $centerX = 120.0;
        $centerY = 112.0;
        $radius = 88.0;
        $angle = -90.0;

        $svg = $this->openSvg($width, $height);

        foreach ($items as $item) {
            $value = (float) $item['value'];
            $sweep = ($value / $total) * 360;
            $start = $this->point($centerX, $centerY, $radius, $angle);
            $end = $this->point($centerX, $centerY, $radius, $angle + $sweep);
            $largeArc = $sweep > 180 ? 1 : 0;
            $color = self::COLORS[$item['color'] ?? 'chart-1'] ?? self::COLORS['chart-1'];

            $svg .= sprintf(
                '<path d="M %.2f %.2f L %.2f %.2f A %.2f %.2f 0 %d 1 %.2f %.2f Z" fill="%s" stroke="#ffffff" stroke-width="1" />',
                $centerX,
                $centerY,
                $start['x'],
                $start['y'],
                $radius,
                $radius,
                $largeArc,
                $end['x'],
                $end['y'],
                $color,
            );

            $angle += $sweep;
        }

        $legendY = 36.0;

        foreach ($items as $item) {
            $color = self::COLORS[$item['color'] ?? 'chart-1'] ?? self::COLORS['chart-1'];
            $value = (float) $item['value'];
            $percentage = ($value / $total) * 100;

            $svg .= sprintf(
                '<rect x="250" y="%.2f" width="12" height="12" fill="%s" />',
                $legendY - 10,
                $color,
            );
            $svg .= sprintf(
                '<text x="270" y="%.2f" font-family="DejaVu Sans, sans-serif" font-size="12" fill="#0e1726">%s — %s (%s%%)</text>',
                $legendY,
                $this->escape((string) ($item['name'] ?? '')),
                $this->formatNumber($value),
                number_format($percentage, 1, ',', ' '),
            );

            $legendY += 26;
        }

        return $svg.'</svg>';
    }

    private function openSvg(int $width, int $height): string
    {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">',
            $width,
            $height,
            $width,
            $height,
        );
    }

    private function emptyState(): string
    {
        return '<p style="color:#4a5568;font-size:12px;">Aucune donnée sur la période sélectionnée.</p>';
    }

    /** @param array<int, array<string, mixed>> $series */
    private function legend(array $series): string
    {
        $svg = '';
        $x = 48.0;

        foreach ($series as $definition) {
            $color = self::COLORS[$definition['color'] ?? 'chart-1'] ?? self::COLORS['chart-1'];

            $svg .= sprintf('<rect x="%.2f" y="8" width="10" height="10" fill="%s" />', $x, $color);
            $svg .= sprintf(
                '<text x="%.2f" y="17" font-family="DejaVu Sans, sans-serif" font-size="11" fill="#4a5568">%s</text>',
                $x + 16,
                $this->escape((string) ($definition['label'] ?? $definition['key'] ?? '')),
            );

            $x += 26 + (mb_strlen((string) ($definition['label'] ?? $definition['key'] ?? '')) * 7);
        }

        return $svg;
    }

    /** @return array{x: float, y: float} */
    private function point(float $centerX, float $centerY, float $radius, float $angle): array
    {
        $radians = deg2rad($angle);

        return [
            'x' => $centerX + $radius * cos($radians),
            'y' => $centerY + $radius * sin($radians),
        ];
    }

    private function niceMax(float $max): float
    {
        if ($max <= 0) {
            return 1.0;
        }

        $magnitude = 10 ** floor(log10($max));
        $normalized = $max / $magnitude;
        $nice = match (true) {
            $normalized <= 1 => 1,
            $normalized <= 2 => 2,
            $normalized <= 5 => 5,
            default => 10,
        };

        return max(1.0, $nice * $magnitude);
    }

    private function formatNumber(float $value): string
    {
        return number_format($value, $value === floor($value) ? 0 : 1, ',', ' ');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

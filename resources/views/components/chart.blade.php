@props([
    'labels' => [],
    'series' => [],      // [['name' => 'Views', 'data' => [...], 'color' => '#4338CA']]
    'type' => 'line',    // line | bar
    'height' => 220,
    'format' => 'number',
])

@php
    // Drawn as inline SVG: no charting library, no extra request, and it
    // renders correctly in both themes because it uses currentColor for
    // the axes and explicit token colours for the data.
    $series = collect($series)->filter(fn ($s) => ! empty($s['data']))->values();
    $labels = array_values($labels);
    $count = max(1, count($labels));

    $max = (int) max(1, $series->flatMap(fn ($s) => $s['data'])->max() ?? 1);
    // Round the axis up to something readable.
    $step = max(1, (int) ceil($max / 4));
    $magnitude = 10 ** max(0, strlen((string) $step) - 1);
    $step = (int) (ceil($step / $magnitude) * $magnitude);
    $axisMax = max(1, $step * 4);

    $w = 1000;
    $h = 320;
    $padL = 46;
    $padR = 12;
    $padT = 14;
    $padB = 34;

    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;

    $x = fn ($i) => $padL + ($count > 1 ? $i * ($plotW / ($count - 1)) : $plotW / 2);
    $xBand = fn ($i) => $padL + $i * ($plotW / $count);
    $y = fn ($v) => $padT + $plotH - ($v / $axisMax) * $plotH;

    $fmt = function ($v) use ($format) {
        if ($format === 'duration') {
            return $v >= 60 ? round($v / 60, 1).'h' : $v.'m';
        }

        if ($v >= 1000000) return round($v / 1000000, 1).'M';
        if ($v >= 1000) return round($v / 1000, 1).'k';

        return (string) $v;
    };

    // Label every nth point so the axis never overlaps.
    $labelEvery = (int) max(1, ceil($count / 8));
    $chartId = 'chart-'.Str::random(6);
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($series->isNotEmpty() && $series->flatMap(fn ($s) => $s['data'])->sum() > 0)
        <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1">
            @foreach ($series as $s)
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-600 dark:text-ink-300">
                    <span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $s['color'] ?? '#4338CA' }}"></span>
                    {{ $s['name'] }}
                    <span class="tabular-nums text-ink-400">({{ $fmt(array_sum($s['data'])) }})</span>
                </span>
            @endforeach
        </div>

        <svg viewBox="0 0 {{ $w }} {{ $h }}" style="height: {{ $height }}px" class="w-full overflow-visible"
             role="img" aria-label="{{ $series->pluck('name')->implode(', ') }} over time">
            <defs>
                @foreach ($series as $i => $s)
                    <linearGradient id="{{ $chartId }}-g{{ $i }}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="{{ $s['color'] ?? '#4338CA' }}" stop-opacity="0.26" />
                        <stop offset="100%" stop-color="{{ $s['color'] ?? '#4338CA' }}" stop-opacity="0" />
                    </linearGradient>
                @endforeach
            </defs>

            {{-- Horizontal grid and value axis --}}
            @for ($g = 0; $g <= 4; $g++)
                @php $gv = $axisMax / 4 * $g; @endphp
                <line x1="{{ $padL }}" y1="{{ $y($gv) }}" x2="{{ $w - $padR }}" y2="{{ $y($gv) }}"
                      stroke="currentColor" stroke-opacity="0.12" stroke-width="1" />
                <text x="{{ $padL - 10 }}" y="{{ $y($gv) + 4 }}" text-anchor="end"
                      font-size="13" fill="currentColor" fill-opacity="0.5">{{ $fmt((int) $gv) }}</text>
            @endfor

            @if ($type === 'bar')
                @php
                    $groupW = $plotW / $count;
                    $barW = min(26, ($groupW * 0.62) / max(1, $series->count()));
                @endphp

                @foreach ($series as $si => $s)
                    @foreach ($s['data'] as $i => $v)
                        @php
                            $bx = $xBand($i) + ($groupW - $barW * $series->count()) / 2 + $si * $barW;
                            $bh = max(0, $plotH - ($y($v) - $padT));
                        @endphp
                        <rect x="{{ round($bx, 2) }}" y="{{ round($y($v), 2) }}"
                              width="{{ round($barW - 2, 2) }}" height="{{ round($bh, 2) }}"
                              rx="2" fill="{{ $s['color'] ?? '#4338CA' }}" fill-opacity="0.9">
                            <title>{{ $labels[$i] ?? '' }}: {{ $fmt($v) }} {{ $s['name'] }}</title>
                        </rect>
                    @endforeach
                @endforeach
            @else
                @foreach ($series as $si => $s)
                    @php
                        $points = collect($s['data'])
                            ->map(fn ($v, $i) => round($x($i), 2).','.round($y($v), 2))
                            ->implode(' ');

                        $area = $points.' '.round($x($count - 1), 2).','.round($y(0), 2)
                            .' '.round($x(0), 2).','.round($y(0), 2);
                    @endphp

                    <polygon points="{{ $area }}" fill="url(#{{ $chartId }}-g{{ $si }})" />
                    <polyline points="{{ $points }}" fill="none"
                              stroke="{{ $s['color'] ?? '#4338CA' }}" stroke-width="2.5"
                              stroke-linejoin="round" stroke-linecap="round" />

                    @foreach ($s['data'] as $i => $v)
                        <circle cx="{{ round($x($i), 2) }}" cy="{{ round($y($v), 2) }}" r="7"
                                fill="transparent">
                            <title>{{ $labels[$i] ?? '' }}: {{ $fmt($v) }} {{ $s['name'] }}</title>
                        </circle>
                        @if ($count <= 32)
                            <circle cx="{{ round($x($i), 2) }}" cy="{{ round($y($v), 2) }}" r="2.5"
                                    fill="{{ $s['color'] ?? '#4338CA' }}" />
                        @endif
                    @endforeach
                @endforeach
            @endif

            {{-- Date axis --}}
            @foreach ($labels as $i => $label)
                @if ($i % $labelEvery === 0 || $i === $count - 1)
                    <text x="{{ round($type === 'bar' ? $xBand($i) + ($plotW / $count) / 2 : $x($i), 2) }}"
                          y="{{ $h - 10 }}" text-anchor="middle"
                          font-size="13" fill="currentColor" fill-opacity="0.5">{{ $label }}</text>
                @endif
            @endforeach
        </svg>
    @else
        <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-ink-200 px-4 text-center dark:border-white/10"
             style="height: {{ $height }}px">
            <x-icon name="chart" class="mb-2 h-6 w-6 text-ink-300" />
            <p class="text-sm text-ink-500 dark:text-ink-400">No data for this period yet.</p>
        </div>
    @endif
</div>

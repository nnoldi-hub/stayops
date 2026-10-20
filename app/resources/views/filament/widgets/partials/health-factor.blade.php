<div class="text-xs text-gray-500">{{ $factor['label'] }}</div>
<div class="mt-1 flex items-baseline gap-2">
    <span class="font-display text-xl font-extrabold tabular-nums text-gray-950">{{ $factor['value'] }}</span>
    @if ($factor['points'] > 0)
        <span class="text-xs font-semibold text-danger-600">-{{ $factor['points'] }} pct</span>
    @else
        <span class="text-xs font-semibold text-success-600">ok</span>
    @endif
</div>

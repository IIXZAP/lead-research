@props(['issues' => []])

@php
    $severityRank = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3, 'opportunity' => 4];

    $severityStyle = [
        'critical'    => 'bg-red-100 text-red-800',
        'high'        => 'bg-red-50 text-red-700',
        'medium'      => 'bg-amber-50 text-amber-700',
        'low'         => 'bg-slate-100 text-slate-600',
        'opportunity' => 'bg-emerald-50 text-emerald-700',
    ];

    $severityLabel = [
        'critical'    => 'วิกฤต',
        'high'        => 'ร้ายแรง',
        'medium'      => 'ปานกลาง',
        'low'         => 'เล็กน้อย',
        'opportunity' => 'โอกาส',
    ];

    // เผื่อกรณี issue แต่ละตัวยังเป็น JSON string ซ้อนอยู่ (double-encoded)
    // ถอดออกไปเรื่อยๆ จนกว่าจะได้ array จริง
    $normalizeIssue = function ($issue) {
        $depth = 0;
        while (is_string($issue) && $depth < 3) {
            $decoded = json_decode($issue, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                break;
            }
            $issue = $decoded;
            $depth++;
        }

        return is_array($issue)
            ? $issue
            : ['severity' => 'low', 'message_th' => (string) $issue];
    };

    $sorted = collect($issues)
        ->map($normalizeIssue)
        ->sortBy(fn ($issue) => $severityRank[$issue['severity'] ?? 'low'] ?? 99)
        ->values();
@endphp

@if ($sorted->isEmpty())
    <p class="text-sm text-slate-400">ยังไม่พบปัญหาที่ชัดเจนจากการตรวจสอบเบื้องต้น</p>
@else
    <ul class="space-y-2">
        @foreach ($sorted as $issue)
            <li class="flex items-start gap-3 px-3 py-2.5 rounded-lg ring-1 ring-slate-100">
                <span class="shrink-0 text-xs font-semibold px-2 py-1 rounded-full {{ $severityStyle[$issue['severity']] ?? 'bg-slate-100 text-slate-600' }}">
                    {{ $severityLabel[$issue['severity']] ?? ($issue['severity'] ?? '-') }}
                </span>
                <p class="text-sm text-slate-800">{{ $issue['message_th'] ?? '-' }}</p>
            </li>
        @endforeach
    </ul>
@endif
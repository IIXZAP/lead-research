@props(['status'])

@php
    $map = [
        'draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-600'],
        'queued' => ['label' => 'Queued', 'class' => 'bg-indigo-50 text-indigo-600'],
        'processing' => ['label' => 'Processing', 'class' => 'bg-indigo-50 text-indigo-600'],
        'partially_completed' => ['label' => 'Partially Completed', 'class' => 'bg-amber-50 text-amber-600'],
        'completed' => ['label' => 'Completed', 'class' => 'bg-emerald-50 text-emerald-600'],
        'failed' => ['label' => 'Failed', 'class' => 'bg-rose-50 text-rose-600'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-slate-100 text-slate-500'],
        // lead statuses
        'new' => ['label' => 'New', 'class' => 'bg-indigo-50 text-indigo-600'],
        'qualified' => ['label' => 'Qualified', 'class' => 'bg-emerald-50 text-emerald-600'],
        'contacted' => ['label' => 'Contacted', 'class' => 'bg-blue-50 text-blue-600'],
        'interested' => ['label' => 'Interested', 'class' => 'bg-emerald-50 text-emerald-700'],
        'not_interested' => ['label' => 'Not Interested', 'class' => 'bg-slate-100 text-slate-500'],
        'invalid' => ['label' => 'Invalid', 'class' => 'bg-rose-50 text-rose-600'],
        'duplicate' => ['label' => 'Duplicate', 'class' => 'bg-amber-50 text-amber-600'],
        'converted' => ['label' => 'Converted', 'class' => 'bg-emerald-100 text-emerald-700'],
    ];
    $meta = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'bg-slate-100 text-slate-500'];
@endphp

<span class="inline-flex items-center text-xs font-medium px-2.5 py-1 rounded-full shrink-0 {{ $meta['class'] }}">
    {{ $meta['label'] }}
</span>

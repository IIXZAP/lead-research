<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Lead\StoreLeadNoteRequest;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;

class LeadNoteController extends Controller
{
    public function __construct(private readonly LeadService $leads)
    {
    }

    public function store(StoreLeadNoteRequest $request, Lead $lead): RedirectResponse
    {
        $this->leads->addNote($lead, $request->user(), $request->validated('note'));

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'บันทึกโน้ตแล้ว');
    }
}

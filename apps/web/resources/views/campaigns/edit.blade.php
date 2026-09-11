@extends('layouts.app')

@php($activePage = 'campaigns')
@php($pageTitle = 'แก้ไข Campaign')
@php($isRunning = in_array($campaign->status->value, ['queued', 'processing']))

@section('title', 'แก้ไข Campaign')

@section('content')

<div class="max-w-4xl w-full mx-auto">

    <div class="mb-5">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-800">แก้ไข Campaign — {{ $campaign->name }}</h1>
        <p class="text-sm text-slate-500 mt-0.5">แก้ไขพื้นที่ ประเภทธุรกิจ และเงื่อนไขคุณภาพ Lead ของแคมเปญนี้</p>
    </div>

    @if ($isRunning)
        <div class="mb-5 flex items-start gap-2.5 rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-100 text-sm px-4 py-3">
            <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0"></i>
            <span>Campaign นี้กำลังทำงานอยู่ (สถานะ {{ $campaign->status->value }}) การแก้ไขบางส่วนอาจมีผลกับรอบค้นหาถัดไปเท่านั้น ไม่ใช่รอบที่กำลังรัน</span>
        </div>
    @endif

    {{-- Step indicator --}}
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4 mb-5 overflow-x-auto">
        <div id="step-indicator" class="flex items-center gap-2 min-w-max"></div>
    </div>

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-2.5 rounded-lg bg-rose-50 text-rose-700 text-sm px-4 py-3 ring-1 ring-rose-100">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
            <div>
                <p class="font-medium mb-1">กรุณาตรวจสอบข้อมูลอีกครั้ง</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form id="campaign-form" method="POST" action="{{ route('campaigns.update', $campaign) }}" novalidate
          class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-5 sm:p-7">
        @csrf
        @method('PUT')

        {{-- ================= STEP 1: ข้อมูล Campaign ================= --}}
        <section data-step="1" class="wizard-step space-y-4">
            <h2 class="text-lg font-semibold text-slate-800 mb-1">ข้อมูล Campaign</h2>
            <p class="text-sm text-slate-500 mb-4">ตั้งชื่อ Campaign เพื่อใช้แยกแยะและค้นหาภายหลัง</p>

            <div>
                <label for="field-name" class="block text-sm font-medium text-slate-700 mb-1.5">ชื่อ Campaign <span class="text-rose-500">*</span></label>
                <input id="field-name" name="name" type="text" maxlength="150" value="{{ old('name', $campaign->name) }}"
                       class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none @error('name') border-rose-400 @enderror" />
                <div class="flex items-center justify-between mt-1">
                    <p class="field-error text-xs text-rose-600" data-error-for="name">@error('name'){{ $message }}@enderror</p>
                    <p id="name-char-count" class="text-xs text-slate-400 shrink-0">0/150</p>
                </div>
            </div>
        </section>

        {{-- ================= STEP 2: พื้นที่เป้าหมาย ================= --}}
        <section data-step="2" class="wizard-step space-y-5 hidden">
            <h2 class="text-lg font-semibold text-slate-800 mb-1">พื้นที่เป้าหมาย</h2>
            <p class="text-sm text-slate-500 mb-4">กำหนดจังหวัด อำเภอ และรัศมีพื้นที่ที่ต้องการค้นหา</p>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="field-province" class="block text-sm font-medium text-slate-700 mb-1.5">จังหวัด <span class="text-rose-500">*</span></label>
                    <input id="field-province" name="province" type="text" value="{{ old('province', $campaign->province) }}"
                           class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none @error('province') border-rose-400 @enderror" />
                    <p class="field-error text-xs text-rose-600 mt-1" data-error-for="province">@error('province'){{ $message }}@enderror</p>
                </div>
                <div>
                    <label for="field-district" class="block text-sm font-medium text-slate-700 mb-1.5">อำเภอ/เขต</label>
                    <input id="field-district" name="district" type="text" value="{{ old('district', $campaign->district) }}"
                           class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none" />
                </div>
            </div>

            <div>
                <label for="field-location-text" class="block text-sm font-medium text-slate-700 mb-1.5">รายละเอียดพื้นที่เพิ่มเติม</label>
                <input id="field-location-text" name="location_text" type="text" value="{{ old('location_text', $campaign->location_text) }}"
                       class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none" />
            </div>

            <div>
                <label for="field-radius" class="block text-sm font-medium text-slate-700 mb-1.5">รัศมีพื้นที่ค้นหา (กิโลเมตร) <span class="text-rose-500">*</span></label>
                <div class="flex items-center gap-3">
                    <input id="field-radius-range" type="range" min="1" max="200" value="{{ old('radius_km', $campaign->radius_km ?? 25) }}" class="flex-1 accent-indigo-600" />
                    <input id="field-radius" name="radius_km" type="number" min="1" max="200" value="{{ old('radius_km', $campaign->radius_km ?? 25) }}"
                           class="form-field w-20 text-sm rounded-lg border border-slate-200 px-2 py-2 text-center focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none" />
                </div>
                <div id="radius-quick-select" class="flex flex-wrap gap-1.5 mt-2"></div>
                <p id="radius-summary" class="text-xs text-slate-500 mt-2 bg-slate-50 rounded-lg px-3 py-2"></p>
                <p class="field-error text-xs text-rose-600 mt-1" data-error-for="radius_km">@error('radius_km'){{ $message }}@enderror</p>
            </div>
        </section>

        {{-- ================= STEP 3: ข้อมูลธุรกิจเป้าหมาย ================= --}}
        <section data-step="3" class="wizard-step space-y-5 hidden">
            <h2 class="text-lg font-semibold text-slate-800 mb-1">ข้อมูลธุรกิจเป้าหมาย</h2>
            <p class="text-sm text-slate-500 mb-4">กำหนดประเภทธุรกิจและเงื่อนไขคุณภาพ Lead ที่ต้องการ</p>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="field-business-keyword" class="block text-sm font-medium text-slate-700 mb-1.5">คำค้นหาธุรกิจ <span class="text-rose-500">*</span></label>
                    <input id="field-business-keyword" name="business_keyword" type="text" value="{{ old('business_keyword', $campaign->business_keyword) }}"
                           class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none @error('business_keyword') border-rose-400 @enderror" />
                    <p class="field-error text-xs text-rose-600 mt-1" data-error-for="business_keyword">@error('business_keyword'){{ $message }}@enderror</p>
                </div>
                <div>
                    <label for="field-business-category" class="block text-sm font-medium text-slate-700 mb-1.5">หมวดหมู่ธุรกิจ</label>
                    <input id="field-business-category" name="business_category" type="text" value="{{ old('business_category', $campaign->business_category) }}"
                           class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none" />
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">การตั้งค่าการค้นหา</h3>

                <div class="mb-4">
                    <label for="field-max-leads" class="block text-sm font-medium text-slate-700 mb-1.5">จำนวน Lead สูงสุด</label>
                    <div class="flex items-center gap-3">
                        <input id="field-max-leads" name="maximum_leads" type="number" min="1" max="1000" value="{{ old('maximum_leads', $campaign->maximum_leads) }}"
                               class="form-field w-28 text-sm rounded-lg border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none" />
                        <div id="max-leads-quick-select" class="flex flex-wrap gap-1.5"></div>
                    </div>
                    <p class="field-error text-xs text-rose-600 mt-1" data-error-for="maximum_leads">@error('maximum_leads'){{ $message }}@enderror</p>
                </div>

                <div class="grid sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="field-min-rating" class="block text-sm font-medium text-slate-700 mb-1.5">คะแนนรีวิวขั้นต่ำ</label>
                        <input id="field-min-rating" name="minimum_rating" type="number" step="0.1" min="0" max="5" value="{{ old('minimum_rating', $campaign->minimum_rating) }}"
                               class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none" />
                    </div>
                    <div>
                        <label for="field-min-reviews" class="block text-sm font-medium text-slate-700 mb-1.5">จำนวนรีวิวขั้นต่ำ</label>
                        <input id="field-min-reviews" name="minimum_review_count" type="number" min="0" value="{{ old('minimum_review_count', $campaign->minimum_review_count) }}"
                               class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none" />
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">ข้อมูลที่ต้องการ</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 text-sm text-slate-600 border border-slate-200 rounded-lg px-3 py-2 cursor-pointer hover:bg-slate-50">
                            <input type="checkbox" name="include_businesses_with_website" value="1"
                                   {{ old('include_businesses_with_website', $campaign->include_businesses_with_website) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            มีเว็บไซต์
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 border border-slate-200 rounded-lg px-3 py-2 cursor-pointer hover:bg-slate-50">
                            <input type="checkbox" name="include_businesses_without_website" value="1"
                                   {{ old('include_businesses_without_website', $campaign->include_businesses_without_website) ? 'checked' : '' }}
                                   class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            ไม่มีเว็บไซต์
                        </label>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="field-language" class="block text-sm font-medium text-slate-700 mb-1.5">ภาษาที่ใช้ค้นหา</label>
                        <select id="field-language" name="search_language"
                                class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none">
                            <option value="th" {{ old('search_language', $campaign->search_language) === 'th' ? 'selected' : '' }}>ไทย</option>
                            <option value="en" {{ old('search_language', $campaign->search_language) === 'en' ? 'selected' : '' }}>English</option>
                        </select>
                    </div>
                    <div>
                        <label for="field-country" class="block text-sm font-medium text-slate-700 mb-1.5">ประเทศ</label>
                        <input id="field-country" name="country" type="text" value="{{ old('country', $campaign->country) }}" maxlength="2"
                               class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none uppercase" />
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= STEP 4: ตรวจสอบและบันทึก ================= --}}
        <section data-step="4" class="wizard-step space-y-4 hidden">
            <h2 class="text-lg font-semibold text-slate-800 mb-1">ตรวจสอบและบันทึกการแก้ไข</h2>
            <p class="text-sm text-slate-500 mb-4">ตรวจสอบข้อมูลทั้งหมดก่อนบันทึก</p>
            <div id="review-summary" class="space-y-3"></div>
        </section>

        {{-- Wizard navigation --}}
        <div class="flex items-center justify-between mt-7 pt-5 border-t border-slate-100">
            <div>
                <button type="button" id="btn-back" class="hidden inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg px-4 py-2.5">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> ย้อนกลับ
                </button>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm font-medium text-slate-500 hover:bg-slate-50 rounded-lg px-4 py-2.5">ยกเลิก</a>
                <button type="button" id="btn-next" class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-4 py-2.5">
                    ถัดไป <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
                <button type="submit" id="btn-create" class="hidden inline-flex items-center gap-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-4 py-2.5">
                    <i data-lucide="check" class="w-4 h-4"></i> บันทึกการแก้ไข
                </button>
            </div>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const WIZARD_STEPS = [
        { step: 1, label: 'ข้อมูล Campaign' },
        { step: 2, label: 'พื้นที่เป้าหมาย' },
        { step: 3, label: 'ข้อมูลธุรกิจ' },
        { step: 4, label: 'ตรวจสอบและบันทึก' },
    ];

    // ถ้ามาจากหน้าที่ redirect กลับพร้อม validation error ให้เปิด step ที่มี error ก่อน
    const stepFieldMap = { 1: ['name'], 2: ['province', 'radius_km'], 3: ['business_keyword', 'maximum_leads'] };
    const errorFields = Array.from(document.querySelectorAll('[data-error-for]'))
        .filter((el) => el.textContent.trim() !== '')
        .map((el) => el.dataset.errorFor);
    let startStep = 1;
    Object.entries(stepFieldMap).forEach(([step, fields]) => {
        if (fields.some((f) => errorFields.includes(f))) startStep = Math.max(startStep, parseInt(step, 10));
    });

    let currentStep = startStep;

    function renderStepIndicator() {
        const el = document.getElementById('step-indicator');
        el.innerHTML = WIZARD_STEPS.map((s, i) => `
            <div class="flex items-center gap-2" data-step-indicator="${s.step}">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0
                    ${currentStep === s.step ? 'bg-indigo-600 text-white' : currentStep > s.step ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400'}">
                    ${currentStep > s.step ? '<i data-lucide="check" class="w-4 h-4"></i>' : s.step}
                </span>
                <span class="text-xs sm:text-sm whitespace-nowrap ${currentStep === s.step ? 'text-slate-800 font-medium' : 'text-slate-400'}">${s.label}</span>
                ${i < WIZARD_STEPS.length - 1 ? '<span class="w-6 sm:w-10 h-px bg-slate-200 mx-1"></span>' : ''}
            </div>
        `).join('');
        if (window.lucide) lucide.createIcons();
    }

    function validateStep(step) {
        let valid = true;
        const requiredByStep = {
            1: [['field-name', 'name']],
            2: [['field-province', 'province'], ['field-radius', 'radius_km']],
            3: [['field-business-keyword', 'business_keyword']],
            4: [],
        };
        (requiredByStep[step] || []).forEach(([inputId, errorKey]) => {
            const input = document.getElementById(inputId);
            const errorEl = document.querySelector(`[data-error-for="${errorKey}"]`);
            if (!input.value.trim()) {
                valid = false;
                input.classList.add('border-rose-400');
                if (errorEl) errorEl.textContent = 'กรุณากรอกข้อมูลนี้';
            } else {
                input.classList.remove('border-rose-400');
                if (errorEl) errorEl.textContent = '';
            }
        });
        return valid;
    }

    function goToStep(step, { skipValidation = false } = {}) {
        if (!skipValidation && step > currentStep && !validateStep(currentStep)) return;
        currentStep = step;
        document.querySelectorAll('.wizard-step').forEach((el) => {
            el.classList.toggle('hidden', parseInt(el.dataset.step, 10) !== step);
        });
        renderStepIndicator();
        document.getElementById('btn-back').classList.toggle('hidden', step === 1);
        document.getElementById('btn-next').classList.toggle('hidden', step === WIZARD_STEPS.length);
        document.getElementById('btn-create').classList.toggle('hidden', step !== WIZARD_STEPS.length);
        if (step === WIZARD_STEPS.length) renderReviewSummary();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function renderReviewSummary() {
        const val = (id) => document.getElementById(id)?.value || '-';
        const rows = [
            ['ชื่อ Campaign', val('field-name')],
            ['จังหวัด / อำเภอ', `${val('field-province')} ${val('field-district') === '-' ? '' : val('field-district')}`],
            ['รัศมีค้นหา', `${val('field-radius')} กม.`],
            ['คำค้นหาธุรกิจ', val('field-business-keyword')],
            ['หมวดหมู่ธุรกิจ', val('field-business-category')],
            ['จำนวน Lead สูงสุด', val('field-max-leads')],
            ['คะแนนขั้นต่ำ', val('field-min-rating')],
        ];
        document.getElementById('review-summary').innerHTML = rows.map(([label, value]) => `
            <div class="flex items-center justify-between text-sm py-2 border-b border-slate-50 last:border-0">
                <span class="text-slate-500">${label}</span>
                <span class="text-slate-800 font-medium">${value}</span>
            </div>
        `).join('');
    }

    // Radius quick-select buttons
    const radiusQuick = document.getElementById('radius-quick-select');
    [5, 10, 25, 50, 100, 200].forEach((km) => {
        radiusQuick.insertAdjacentHTML('beforeend', `<button type="button" class="radius-quick-btn text-xs font-medium px-2.5 py-1 rounded-full border border-slate-200 text-slate-600 hover:border-indigo-300 hover:bg-indigo-50" data-value="${km}">${km} กม.</button>`);
    });
    radiusQuick.addEventListener('click', (e) => {
        const btn = e.target.closest('.radius-quick-btn');
        if (!btn) return;
        document.getElementById('field-radius').value = btn.dataset.value;
        document.getElementById('field-radius-range').value = btn.dataset.value;
        updateRadiusSummary();
    });

    const radiusRange = document.getElementById('field-radius-range');
    const radiusInput = document.getElementById('field-radius');
    radiusRange.addEventListener('input', () => { radiusInput.value = radiusRange.value; updateRadiusSummary(); });
    radiusInput.addEventListener('input', () => { radiusRange.value = radiusInput.value || 1; updateRadiusSummary(); });
    function updateRadiusSummary() {
        document.getElementById('radius-summary').textContent =
            `ระบบจะค้นหาธุรกิจในรัศมี ${radiusInput.value || 0} กิโลเมตรจากศูนย์กลางพื้นที่ที่เลือก`;
    }
    updateRadiusSummary();

    // Max leads quick-select buttons
    const maxLeadsQuick = document.getElementById('max-leads-quick-select');
    [25, 50, 100, 250, 500].forEach((n) => {
        maxLeadsQuick.insertAdjacentHTML('beforeend', `<button type="button" class="max-leads-quick-btn text-xs font-medium px-2.5 py-1 rounded-full border border-slate-200 text-slate-600 hover:border-indigo-300 hover:bg-indigo-50" data-value="${n}">${n}</button>`);
    });
    maxLeadsQuick.addEventListener('click', (e) => {
        const btn = e.target.closest('.max-leads-quick-btn');
        if (!btn) return;
        document.getElementById('field-max-leads').value = btn.dataset.value;
    });

    // Name char counter
    const nameInput = document.getElementById('field-name');
    const nameCharCount = document.getElementById('name-char-count');
    function updateNameCharCount() { nameCharCount.textContent = `${nameInput.value.length}/150`; }
    nameInput.addEventListener('input', updateNameCharCount);
    updateNameCharCount();

    // Navigation buttons
    document.getElementById('btn-next').addEventListener('click', () => goToStep(currentStep + 1));
    document.getElementById('btn-back').addEventListener('click', () => goToStep(currentStep - 1, { skipValidation: true }));

    // Step indicator click — ย้อนกลับได้ทันที ไปข้างหน้าต้อง validate
    document.getElementById('step-indicator').addEventListener('click', (e) => {
        const target = e.target.closest('[data-step-indicator]');
        if (!target) return;
        const step = parseInt(target.dataset.stepIndicator, 10);
        if (step <= currentStep) goToStep(step, { skipValidation: true });
    });

    // Submit ต้อง validate ทุก step ก่อนจริง ๆ (กันกรณี user แก้ URL หรือ JS error)
    document.getElementById('campaign-form').addEventListener('submit', (e) => {
        for (let s = 1; s <= 3; s++) {
            if (!validateStep(s)) { e.preventDefault(); goToStep(s, { skipValidation: true }); return; }
        }
    });

    goToStep(startStep, { skipValidation: true });
});
</script>
@endpush

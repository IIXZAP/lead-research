@extends('layouts.app')

@php($activePage = 'campaigns')
@php($pageTitle = 'สร้าง Campaign')

@section('title', 'สร้าง Campaign')

@section('content')

    <div class="max-w-4xl w-full mx-auto">

        <div class="mb-5">
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">สร้าง Campaign สำหรับค้นหา Lead</h1>
            <p class="text-sm text-slate-500 mt-0.5">กำหนดพื้นที่ ประเภทธุรกิจ และคุณสมบัติของบริษัทที่ต้องการค้นหา</p>
        </div>

        {{-- Step indicator --}}
        <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4 mb-5 overflow-x-auto">
            <div id="step-indicator" class="flex items-center gap-2 min-w-max"></div>
        </div>

        @if ($errors->any())
            <div
                class="mb-5 flex items-start gap-2.5 rounded-lg bg-rose-50 text-rose-700 text-sm px-4 py-3 ring-1 ring-rose-100">
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

        <form id="campaign-form" method="POST" action="{{ route('campaigns.store') }}" novalidate
            class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-5 sm:p-7">
            @csrf

            {{-- ================= STEP 1: ข้อมูล Campaign ================= --}}
            <section data-step="1" class="wizard-step space-y-4">
                <h2 class="text-lg font-semibold text-slate-800 mb-1">ข้อมูล Campaign</h2>
                <p class="text-sm text-slate-500 mb-4">ตั้งชื่อ Campaign เพื่อใช้แยกแยะและค้นหาภายหลัง</p>

                <div>
                    <label for="field-name" class="block text-sm font-medium text-slate-700 mb-1.5">ชื่อ Campaign <span
                            class="text-rose-500">*</span></label>
                    <input id="field-name" name="name" type="text" maxlength="150" value="{{ old('name') }}"
                        placeholder="ค้นหาบริษัทผลิตอาหารในกรุงเทพฯ"
                        class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none focus:outline-none @error('name') border-rose-400 @enderror" />
                    <div class="flex items-center justify-between mt-1">
                        <p class="field-error text-xs text-rose-600" data-error-for="name">
                            @error('name')
                                {{ $message }}
                            @enderror
                        </p>
                        <p id="name-char-count" class="text-xs text-slate-400 shrink-0">0/150</p>
                    </div>
                </div>
            </section>

            {{-- ================= STEP 2: พื้นที่เป้าหมาย ================= --}}
            <section data-step="2" class="wizard-step space-y-5 hidden">
                <h2 class="text-lg font-semibold text-slate-800 mb-1">พื้นที่เป้าหมาย</h2>
                <p class="text-sm text-slate-500 mb-4">กำหนดประเทศ จังหวัด และรัศมีพื้นที่ที่ต้องการค้นหา</p>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="field-country" class="block text-sm font-medium text-slate-700 mb-1.5">ประเทศ</label>
                        <select id="field-country" disabled
                            class="form-field w-full text-sm rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-500">
                            <option value="TH" selected>🇹🇭 ประเทศไทย</option>
                        </select>

                    </div>
                    {{-- <div>
                        <label for="field-province" class="block text-sm font-medium text-slate-700 mb-1.5">จังหวัด <span
                                class="text-rose-500">*</span></label>
                        <input id="field-province" name="province" type="text" value="{{ old('province') }}"
                            placeholder="เช่น กรุงเทพมหานคร"
                            class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none @error('province') border-rose-400 @enderror" />
                        <p class="field-error text-xs text-rose-600 mt-1" data-error-for="province">
                            @error('province')
                                {{ $message }}
                            @enderror
                        </p>
                    </div> --}}

                    <div>
                        <label for="field-province-search" class="block text-sm font-medium text-slate-700 mb-1.5">
                            จังหวัด <span class="text-rose-500">*</span>
                        </label>

                        <div class="relative">
                            <input id="field-province-search" type="text" autocomplete="off"
                                placeholder="ค้นหาจังหวัด..." value="{{ old('province') }}"
                                class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none @error('province') border-rose-400 @enderror" />

                            <div id="province-dropdown"
                                class="hidden absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white rounded-lg shadow-lg ring-1 ring-slate-200 py-1">
                            </div>
                        </div>

                        {{-- ค่าจริงที่จะถูก submit ไป backend --}}
                        <input type="hidden" name="province" id="field-province" value="{{ old('province') }}" />

                        @error('province')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @else
                            <p class="field-error hidden text-xs text-rose-600 mt-1" data-error-for="province"></p>
                        @enderror
                    </div>

                </div>

                {{-- <div>
                    <label for="field-location-text"
                        class="block text-sm font-medium text-slate-700 mb-1.5">รายละเอียดพื้นที่เพิ่มเติม</label>
                    <input id="field-location-text" name="location_text" type="text" value="{{ old('location_text') }}"
                        placeholder="เช่น ย่านสีลม, ใกล้ BTS อโศก"
                        class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none" />
                </div> --}}

                <div>
                    <label for="field-radius" class="block text-sm font-medium text-slate-700 mb-1.5">รัศมีพื้นที่ค้นหา
                        (กิโลเมตร) <span class="text-rose-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <input id="field-radius-range" type="range" min="1" max="200"
                            value="{{ old('radius_km', 25) }}" class="flex-1 accent-indigo-600" />
                        <input id="field-radius" name="radius_km" type="number" min="1" max="200"
                            value="{{ old('radius_km', 25) }}"
                            class="form-field w-20 text-sm rounded-lg border border-slate-200 px-2 py-2 text-center focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none" />
                    </div>
                    <div id="radius-quick-select" class="flex flex-wrap gap-1.5 mt-2"></div>
                    <p id="radius-summary" class="text-xs text-slate-500 mt-2 bg-slate-50 rounded-lg px-3 py-2"></p>
                    <p class="field-error text-xs text-rose-600 mt-1" data-error-for="radius_km">
                        @error('radius_km')
                            {{ $message }}
                        @enderror
                    </p>
                </div>
            </section>

            {{-- ================= STEP 3: ข้อมูลธุรกิจเป้าหมาย ================= --}}
            <section data-step="3" class="wizard-step space-y-5 hidden">
                <h2 class="text-lg font-semibold text-slate-800 mb-1">ข้อมูลธุรกิจเป้าหมาย</h2>
                <p class="text-sm text-slate-500 mb-4">กำหนดประเภทธุรกิจและเงื่อนไขคุณภาพ Lead ที่ต้องการ</p>

                <div class="grid sm:grid-cols-2 gap-4">
                    {{-- <div>
                        <label for="field-business-keyword"
                            class="block text-sm font-medium text-slate-700 mb-1.5">Keyword ธุรกิจ <span
                                class="text-rose-500">*</span></label>
                        <input id="field-business-keyword" name="business_keyword" type="text"
                            value="{{ old('business_keyword') }}" placeholder="เช่น ร้านอาหาร, โรงงานผลิตอาหาร"
                            class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none @error('business_keyword') border-rose-400 @enderror" />
                        <p class="field-error text-xs text-rose-600 mt-1" data-error-for="business_keyword">
                            @error('business_keyword')
                                {{ $message }}
                            @enderror
                        </p>
                    </div> --}}
                    <div>
                        <label for="field-business-category"
                            class="block text-sm font-medium text-slate-700 mb-1.5">ประเภทธุรกิจ <span
                                class="text-rose-500">*</span></label>
                        <select id="field-business-category" name="business_category"
                            class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none @error('business_category') border-rose-400 @enderror">
                            <option value="" disabled {{ old('business_category') ? '' : 'selected' }}>
                                เลือกประเภทธุรกิจ</option>
                            @foreach (['โรงงานผลิตอาหาร', 'ร้านอาหาร', 'โรงแรมและที่พัก', 'อสังหาริมทรัพย์', 'ก่อสร้าง', 'โลจิสติกส์และขนส่ง', 'คลังสินค้า', 'ค้าปลีก', 'ค้าส่ง', 'โรงงานอุตสาหกรรม', 'เครื่องสำอาง', 'การแพทย์และคลินิก', 'การศึกษา', 'เทคโนโลยี', 'บริการด้านการตลาด', 'บริการด้านบัญชี', 'นำเข้าและส่งออก', 'ธุรกิจ E-commerce', 'อื่น ๆ'] as $type)
                                <option value="{{ $type }}"
                                    {{ old('business_category') === $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                        <p class="field-error text-xs text-rose-600 mt-1" data-error-for="business_category">
                            @error('business_category')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>
                </div>
                <div>
                    <label for="field-description"
                        class="block text-sm font-medium text-slate-700 mb-1.5">อธิบายธุรกิจเป้าหมาย <span
                            class="text-rose-500">*</span></label>
                    <textarea id="field-description" rows="4" maxlength="1000"
                        placeholder="ตัวอย่าง: ต้องการค้นหาบริษัทที่เป็นโรงงานผลิตอาหารแปรรูป มีเว็บไซต์บริษัท และอาจมีความต้องการปรับปรุงเว็บไซต์ ระบบขาย หรือระบบบริหารจัดการภายในองค์กร"
                        class="form-field w-full text-sm rounded-lg border border-slate-200 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none resize-none"></textarea>
                    <div class="flex justify-between mt-1">
                        <p class="field-error hidden text-xs text-rose-600" data-error-for="description"></p>
                        <p class="text-xs text-slate-400"><span id="description-char-count">0</span>/1000 (ขั้นต่ำ 20
                            ตัวอักษร)</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-700 mb-3">การตั้งค่าการค้นหา</h3>

                    <div class="mb-4">
                        <label for="field-max-leads" class="block text-sm font-medium text-slate-700 mb-1.5">จำนวน Lead
                            สูงสุด</label>
                        <div class="flex items-center gap-3">
                            <input id="field-max-leads" name="maximum_leads" type="number" value="20"
                                class="form-field w-28 text-sm rounded-lg border border-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none" />
                            {{-- <div id="max-leads-quick-select" class="flex flex-wrap gap-1.5"></div> --}}
                        </div>
                        <p class="field-error text-xs text-rose-600 mt-1" data-error-for="maximum_leads">
                            @error('maximum_leads')
                                {{ $message }}
                            @enderror
                        </p>
                    </div>



                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-2">ข้อมูลที่ต้องการ</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <label
                                class="flex items-center gap-2 text-sm text-slate-600 border border-slate-200 rounded-lg px-3 py-2 cursor-pointer hover:bg-slate-50">
                                <input type="checkbox" name="include_businesses_with_website" value="1"
                                    {{ old('include_businesses_with_website', true) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                มีเว็บไซต์
                            </label>
                            <label
                                class="flex items-center gap-2 text-sm text-slate-600 border border-slate-200 rounded-lg px-3 py-2 cursor-pointer hover:bg-slate-50">
                                <input type="checkbox" name="include_businesses_without_website" value="1"
                                    {{ old('include_businesses_without_website', true) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                ไม่มีเว็บไซต์
                            </label>
                        </div>
                    </div>


                </div>
            </section>

            {{-- ================= STEP 4: ตรวจสอบและสร้าง ================= --}}
            <section data-step="4" class="wizard-step space-y-4 hidden">
                <h2 class="text-lg font-semibold text-slate-800 mb-1">ตรวจสอบและสร้าง Campaign</h2>
                <p class="text-sm text-slate-500 mb-4">ตรวจสอบข้อมูลทั้งหมดก่อนเริ่มค้นหา Lead</p>
                <div id="review-summary" class="space-y-3"></div>

                <label
                    class="flex items-center gap-2.5 text-sm text-slate-700 bg-indigo-50/60 rounded-lg px-4 py-3 cursor-pointer select-none mt-4">
                    <input type="checkbox" id="field-start-immediately" name="start_immediately" value="1"
                        class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked />
                    เริ่มค้นหา Lead ทันทีหลังสร้าง Campaign (ถ้าไม่เลือก จะบันทึกเป็น Draft)
                </label>
            </section>

            {{-- Wizard navigation --}}
            <div class="flex items-center justify-between mt-7 pt-5 border-t border-slate-100">
                <div>
                    <button type="button" id="btn-back"
                        class="hidden inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 rounded-lg px-4 py-2.5">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> ย้อนกลับ
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('campaigns.index') }}"
                        class="text-sm font-medium text-slate-500 hover:bg-slate-50 rounded-lg px-4 py-2.5">ยกเลิก</a>
                    <button type="button" id="btn-next"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-4 py-2.5">
                        ถัดไป <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                    <button type="submit" id="btn-create"
                        class="hidden inline-flex items-center gap-1.5 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-4 py-2.5">
                        <i data-lucide="check" class="w-4 h-4"></i> สร้าง Campaign
                    </button>
                </div>
            </div>
        </form>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const PROVINCES_TH = @json(config('provinces.thailand'));

            const provinceSearch = document.getElementById('field-province-search');
            const provinceHidden = document.getElementById('field-province');
            const provinceDropdown = document.getElementById('province-dropdown');

            function renderProvinceOptions(term) {
                const t = (term || '').trim();
                const options = PROVINCES_TH.filter((p) => !t || p.includes(t));

                if (!options.length) {
                    provinceDropdown.innerHTML =
                        `<div class="px-3 py-2 text-sm text-slate-400">ไม่พบจังหวัดที่ค้นหา</div>`;
                    provinceDropdown.classList.remove('hidden');
                    return;
                }

                provinceDropdown.innerHTML = options.map((p) => `
            <button type="button"
                    class="province-option w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-indigo-50 hover:text-indigo-700"
                    data-province="${p}">
                ${p}
            </button>
        `).join('');

                provinceDropdown.classList.remove('hidden');

                provinceDropdown.querySelectorAll('.province-option').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        provinceSearch.value = btn.dataset.province;
                        provinceHidden.value = btn.dataset.province;
                        provinceDropdown.classList.add('hidden');
                        const errEl = document.querySelector('[data-error-for="province"]');
                        if (errEl) errEl.textContent = '';
                        provinceSearch.classList.remove('border-rose-400');
                    });
                });
            }

            if (provinceSearch && provinceHidden && provinceDropdown) {
                provinceSearch.addEventListener('input', () => {
                    renderProvinceOptions(provinceSearch.value);
                    provinceHidden.value = ''; // ต้องเลือกจาก dropdown จริงเท่านั้นถึงจะมีค่า
                });

                provinceSearch.addEventListener('focus', () => renderProvinceOptions(provinceSearch.value));

                document.addEventListener('click', (e) => {
                    if (!e.target.closest('#field-province-search') && !e.target.closest(
                            '#province-dropdown')) {
                        provinceDropdown.classList.add('hidden');
                    }
                });
            }
            const WIZARD_STEPS = [{
                    step: 1,
                    label: 'ข้อมูล Campaign'
                },
                {
                    step: 2,
                    label: 'พื้นที่เป้าหมาย'
                },
                {
                    step: 3,
                    label: 'ข้อมูลธุรกิจ'
                },
                {
                    step: 4,
                    label: 'ตรวจสอบและสร้าง'
                },
            ];

            // ถ้ามาจากหน้าที่ redirect กลับพร้อม validation error ให้เปิด step ที่มี error ก่อน
            const stepFieldMap = {
                1: ['name'],
                2: ['province', 'radius_km'],
                3: ['description', 'maximum_leads']
            };
            const errorFields = Array.from(document.querySelectorAll('[data-error-for]'))
                .filter((el) => el.textContent.trim() !== '')
                .map((el) => el.dataset.errorFor);
            let startStep = 1;
            Object.entries(stepFieldMap).forEach(([step, fields]) => {
                if (fields.some((f) => errorFields.includes(f))) startStep = Math.max(startStep, parseInt(
                    step, 10));
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
                    1: [
                        ['field-name', 'name']
                    ],
                    2: [
                        ['field-province', 'province'],
                        ['field-radius', 'radius_km']
                    ],
                    3: [
                        ['field-description', 'description']
                    ], // แก้จาก field-business-keyword
                    4: [],
                };
                (requiredByStep[step] || []).forEach(([inputId, errorKey]) => {
                    const input = document.getElementById(inputId);
                    if (!input) return; // ← เพิ่มบรรทัดนี้กันพังถ้า element หาย
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

            function goToStep(step, {
                skipValidation = false
            } = {}) {
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
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            function renderReviewSummary() {
                const val = (id) => document.getElementById(id)?.value || '-';
                const checked = (id) => document.getElementById(id)?.checked;
                const rows = [
                    ['ชื่อ Campaign', val('field-name')],
                    ['จังหวัด', val('field-province')],
                    ['รัศมีค้นหา', `${val('field-radius')} กม.`],
                    ['ประเภทธุรกิจ', val('field-business-category')],
                    ['อธิบายธุรกิจเป้าหมาย', val('field-description')],
                    ['จำนวน Lead สูงสุด', val('field-max-leads')],
                    ['เริ่มค้นหาทันที', checked('field-start-immediately') ? 'ใช่' : 'ไม่ (บันทึกเป็น Draft)'],
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
                radiusQuick.insertAdjacentHTML('beforeend',
                    `<button type="button" class="radius-quick-btn text-xs font-medium px-2.5 py-1 rounded-full border border-slate-200 text-slate-600 hover:border-indigo-300 hover:bg-indigo-50" data-value="${km}">${km} กม.</button>`
                );
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
            radiusRange.addEventListener('input', () => {
                radiusInput.value = radiusRange.value;
                updateRadiusSummary();
            });
            radiusInput.addEventListener('input', () => {
                radiusRange.value = radiusInput.value || 1;
                updateRadiusSummary();
            });

            function updateRadiusSummary() {
                document.getElementById('radius-summary').textContent =
                    `ระบบจะค้นหาธุรกิจในรัศมี ${radiusInput.value || 0} กิโลเมตรจากศูนย์กลางพื้นที่ที่เลือก`;
            }
            updateRadiusSummary();

            // Max leads quick-select buttons
            // Max leads quick-select buttons
            const maxLeadsQuick = document.getElementById('max-leads-quick-select');
            if (maxLeadsQuick) {
                [25, 50, 100, 250, 500].forEach((n) => {
                    maxLeadsQuick.insertAdjacentHTML('beforeend',
                        `<button type="button" class="max-leads-quick-btn text-xs font-medium px-2.5 py-1 rounded-full border border-slate-200 text-slate-600 hover:border-indigo-300 hover:bg-indigo-50" data-value="${n}">${n}</button>`
                    );
                });
                maxLeadsQuick.addEventListener('click', (e) => {
                    const btn = e.target.closest('.max-leads-quick-btn');
                    if (!btn) return;
                    document.getElementById('field-max-leads').value = btn.dataset.value;
                });
            }

            // Name char counter
            const nameInput = document.getElementById('field-name');
            const nameCharCount = document.getElementById('name-char-count');

            function updateNameCharCount() {
                nameCharCount.textContent = `${nameInput.value.length}/150`;
            }
            nameInput.addEventListener('input', updateNameCharCount);
            updateNameCharCount();

            // Navigation buttons
            document.getElementById('btn-next').addEventListener('click', () => goToStep(currentStep + 1));
            document.getElementById('btn-back').addEventListener('click', () => goToStep(currentStep - 1, {
                skipValidation: true
            }));

            // Step indicator click — ย้อนกลับได้ทันที ไปข้างหน้าต้อง validate
            document.getElementById('step-indicator').addEventListener('click', (e) => {
                const target = e.target.closest('[data-step-indicator]');
                if (!target) return;
                const step = parseInt(target.dataset.stepIndicator, 10);
                if (step <= currentStep) goToStep(step, {
                    skipValidation: true
                });
            });

            // Submit ต้อง validate ทุก step ก่อนจริง ๆ (กันกรณี user แก้ URL หรือ JS error)
            document.getElementById('campaign-form').addEventListener('submit', (e) => {
                for (let s = 1; s <= 3; s++) {
                    if (!validateStep(s)) {
                        e.preventDefault();
                        goToStep(s, {
                            skipValidation: true
                        });
                        return;
                    }
                }
            });

            goToStep(startStep, {
                skipValidation: true
            });

            // Description char counter
            const descInput = document.getElementById('field-description');
            const descCharCount = document.getElementById('description-char-count');
            if (descInput && descCharCount) {
                const updateDescCharCount = () => {
                    descCharCount.textContent = descInput.value.length;
                };
                descInput.addEventListener('input', updateDescCharCount);
                updateDescCharCount();
            }
        });
    </script>
@endpush

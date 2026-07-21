/**
 * campaign-form.js
 * ควบคุม Wizard สร้าง/แก้ไข Campaign (4 Steps): Campaign Information,
 * Target Location, Business Target, Review & Create
 */

const WIZARD_STEPS = [
  { step: 1, label: "ข้อมูล Campaign", icon: "file-text" },
  { step: 2, label: "พื้นที่เป้าหมาย", icon: "map-pin" },
  { step: 3, label: "ข้อมูลธุรกิจ", icon: "building-2" },
  { step: 4, label: "ตรวจสอบและสร้าง", icon: "check-square" },
];

const REQUIRED_FIELD_LABELS = {
  website: "ต้องมี Website", phone: "ต้องมีเบอร์โทร", email: "ต้องมี Email", address: "ต้องมีที่อยู่",
  registration_number: "ต้องมีเลขทะเบียนนิติบุคคล", directors: "ต้องมีข้อมูลกรรมการ", registered_capital: "ต้องมีข้อมูลทุนจดทะเบียน",
};

const formState = {
  title: "",
  target: { country: "TH", province: "", search_radius_km: 25 },
  business_target: { registered_business: "", business_type: "", business_keywords: [], description: "" },
  maximum_leads: 50,
  required_fields: { website: true, phone: true, email: false, address: true, registration_number: true, directors: false, registered_capital: false },
  duplicate_handling: "skip",
};

const wizardState = { currentStep: 1, isEditMode: false, editingId: null, isDirty: false, submitting: false };

document.addEventListener("DOMContentLoaded", () => {
  const user = checkAuthentication();
  if (!user) return;

  renderSidebar("campaigns");
  renderTopbar("campaign-create");

  if (!hasPermission("campaign.create")) {
    document.getElementById("campaign-form").outerHTML = permissionDeniedState("คุณไม่มีสิทธิ์สร้าง Campaign — เฉพาะ Admin และ Manager เท่านั้น");
    document.getElementById("step-indicator").parentElement.classList.add("hidden");
    if (window.lucide) lucide.createIcons();
    return;
  }

  const editId = new URLSearchParams(location.search).get("edit");
  if (editId) initEditMode(editId);

  populateStaticOptions();
  renderStepIndicator();
  goToStep(1, { skipValidation: true });
  bindWizardEvents();
  trackDirtyState();
});

function initEditMode(campaignId) {
  const campaigns = loadCampaignsFromStorage();
  const campaign = campaigns.find((c) => c.id === campaignId);
  if (!campaign) { showToast("ไม่พบ Campaign ที่ต้องการแก้ไข", "error"); return; }
  if (!canAccessCampaign(campaign, getCurrentUser()) || !(campaign.status === "draft" || campaign.status === "queued")) {
    showToast("Campaign นี้ไม่สามารถแก้ไขได้ในสถานะปัจจุบัน", "error");
    setTimeout(() => window.location.href = "campaigns.html", 1200);
    return;
  }
  wizardState.isEditMode = true;
  wizardState.editingId = campaignId;
  document.getElementById("wizard-page-title").textContent = `แก้ไข Campaign — ${campaign.title}`;

  formState.title = campaign.title;
  formState.target = { ...campaign.target };
  formState.business_target = { ...campaign.business_target, business_keywords: [...campaign.business_target.business_keywords] };
  formState.maximum_leads = campaign.maximum_leads;
  formState.required_fields = { ...campaign.required_fields };
  formState.duplicate_handling = campaign.duplicate_handling;
}

// -----------------------------------------------------------------
// Populate static select/datalist options
// -----------------------------------------------------------------
function populateStaticOptions() {
  const businessTypeSel = document.getElementById("field-business-type");
  BUSINESS_TYPES.forEach((t) => businessTypeSel.insertAdjacentHTML("beforeend", `<option value="${t}">${t}</option>`));

  const registeredBusinessList = document.getElementById("registered-business-options");
  REGISTERED_BUSINESS_TYPES.forEach((t) => registeredBusinessList.insertAdjacentHTML("beforeend", `<option value="${t}"></option>`));

  const dupSel = document.getElementById("field-duplicate-handling");
  DUPLICATE_HANDLING_OPTIONS.forEach((o) => dupSel.insertAdjacentHTML("beforeend", `<option value="${o.value}">${o.label}</option>`));

  const radiusQuick = document.getElementById("radius-quick-select");
  [5, 10, 25, 50, 100, 200].forEach((km) => radiusQuick.insertAdjacentHTML("beforeend", `<button type="button" class="radius-quick-btn text-xs font-medium px-2.5 py-1 rounded-full border border-slate-200 text-slate-600 hover:border-indigo-300 hover:bg-indigo-50" data-value="${km}">${km} กม.</button>`));

  const maxLeadsQuick = document.getElementById("max-leads-quick-select");
  [25, 50, 100, 250, 500].forEach((n) => maxLeadsQuick.insertAdjacentHTML("beforeend", `<button type="button" class="max-leads-quick-btn text-xs font-medium px-2.5 py-1 rounded-full border border-slate-200 text-slate-600 hover:border-indigo-300 hover:bg-indigo-50" data-value="${n}">${n}</button>`));

  const reqFieldsContainer = document.getElementById("required-fields-checkboxes");
  Object.entries(REQUIRED_FIELD_LABELS).forEach(([key, label]) => {
    reqFieldsContainer.insertAdjacentHTML("beforeend", `
      <label class="flex items-center gap-2 text-sm text-slate-600 border border-slate-200 rounded-lg px-3 py-2 cursor-pointer hover:bg-slate-50">
        <input type="checkbox" data-required-field="${key}" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
        ${label}
      </label>
    `);
  });

  // เติมค่าจาก formState (สำคัญตอน Edit Mode)
  document.getElementById("field-title").value = formState.title;
  document.getElementById("field-province-search").value = formState.target.province;
  document.getElementById("field-province").value = formState.target.province;
  document.getElementById("field-radius").value = formState.target.search_radius_km;
  document.getElementById("field-radius-range").value = formState.target.search_radius_km;
  document.getElementById("field-registered-business").value = formState.business_target.registered_business;
  businessTypeSel.value = formState.business_target.business_type;
  document.getElementById("field-description").value = formState.business_target.description;
  document.getElementById("field-max-leads").value = formState.maximum_leads;
  dupSel.value = formState.duplicate_handling;
  Object.keys(REQUIRED_FIELD_LABELS).forEach((key) => {
    const cb = reqFieldsContainer.querySelector(`[data-required-field="${key}"]`);
    if (cb) cb.checked = !!formState.required_fields[key];
  });

  updateTitleCharCount();
  updateDescriptionCharCount();
  updateRadiusSummary();
  renderKeywordChips();
  highlightQuickSelectButtons();
}

// -----------------------------------------------------------------
// Step indicator + navigation
// -----------------------------------------------------------------
function renderStepIndicator() {
  const el = document.getElementById("step-indicator");
  el.innerHTML = WIZARD_STEPS.map((s, i) => `
    <div class="step-item flex items-center gap-2 ${wizardState.currentStep === s.step ? "is-active" : wizardState.currentStep > s.step ? "is-done" : ""}" data-step-indicator="${s.step}">
      <span class="step-circle w-8 h-8 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xs font-semibold shrink-0">
        ${wizardState.currentStep > s.step ? '<i data-lucide="check" class="w-4 h-4"></i>' : s.step}
      </span>
      <span class="step-label text-xs sm:text-sm text-slate-400 whitespace-nowrap">${s.label}</span>
      ${i < WIZARD_STEPS.length - 1 ? '<span class="w-6 sm:w-10 h-px bg-slate-200 mx-1"></span>' : ""}
    </div>
  `).join("");
  if (window.lucide) lucide.createIcons();
}

function goToStep(step, options = {}) {
  if (!options.skipValidation && step > wizardState.currentStep) {
    if (!validateStep(wizardState.currentStep)) return;
  }
  saveCurrentStepIntoState();

  wizardState.currentStep = step;
  document.querySelectorAll(".wizard-step").forEach((el) => el.classList.toggle("hidden", parseInt(el.dataset.step, 10) !== step));
  renderStepIndicator();

  document.getElementById("btn-back").classList.toggle("hidden", step === 1);
  document.getElementById("btn-next").classList.toggle("hidden", step === WIZARD_STEPS.length);
  document.getElementById("btn-create").classList.toggle("hidden", step !== WIZARD_STEPS.length);

  if (step === WIZARD_STEPS.length) renderReviewSummary();
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function bindWizardEvents() {
  document.getElementById("btn-next").addEventListener("click", () => goToStep(wizardState.currentStep + 1));
  document.getElementById("btn-back").addEventListener("click", () => goToStep(wizardState.currentStep - 1, { skipValidation: true }));
  document.getElementById("btn-save-draft").addEventListener("click", saveCampaignDraft);
  document.getElementById("btn-create").addEventListener("click", createCampaign);

  // Step indicator ให้กดย้อนกลับไป step ก่อนหน้าได้โดยตรง (แต่ไปข้างหน้าต้อง validate)
  document.getElementById("step-indicator").addEventListener("click", (e) => {
    const target = e.target.closest("[data-step-indicator]");
    if (!target) return;
    const step = parseInt(target.dataset.stepIndicator, 10);
    if (step <= wizardState.currentStep) goToStep(step, { skipValidation: true });
  });

  // Step 1
  document.getElementById("field-title").addEventListener("input", () => { updateTitleCharCount(); clearStepFieldError("title"); });

  // Step 2 — Province searchable dropdown
  const provinceSearch = document.getElementById("field-province-search");
  const provinceDropdown = document.getElementById("province-dropdown");
  provinceSearch.addEventListener("input", () => { renderProvinceOptions(provinceSearch.value); clearStepFieldError("province"); });
  provinceSearch.addEventListener("focus", () => renderProvinceOptions(provinceSearch.value));
  document.addEventListener("click", (e) => { if (!e.target.closest("#field-province-search") && !e.target.closest("#province-dropdown")) provinceDropdown.classList.add("hidden"); });

  // Step 2 — Radius
  const radiusRange = document.getElementById("field-radius-range");
  const radiusNumber = document.getElementById("field-radius");
  radiusRange.addEventListener("input", () => { radiusNumber.value = radiusRange.value; updateRadiusSummary(); highlightQuickSelectButtons(); clearStepFieldError("search_radius_km"); });
  radiusNumber.addEventListener("input", () => { radiusRange.value = radiusNumber.value || 1; updateRadiusSummary(); highlightQuickSelectButtons(); clearStepFieldError("search_radius_km"); });
  document.getElementById("radius-quick-select").addEventListener("click", (e) => {
    const btn = e.target.closest(".radius-quick-btn");
    if (!btn) return;
    radiusNumber.value = btn.dataset.value;
    radiusRange.value = btn.dataset.value;
    updateRadiusSummary(); highlightQuickSelectButtons(); clearStepFieldError("search_radius_km");
  });

  // Step 3 — Business type & description
  document.getElementById("field-business-type").addEventListener("change", () => clearStepFieldError("business_type"));
  document.getElementById("field-description").addEventListener("input", (e) => {
    updateDescriptionCharCount();
    clearStepFieldError("description");
    e.target.style.height = "auto"; e.target.style.height = e.target.scrollHeight + "px"; // Auto resize
  });

  // Step 3 — Keyword tags input
  document.getElementById("field-keyword-input").addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    e.preventDefault();
    addKeyword(e.target.value.trim());
    e.target.value = "";
  });

  // Step 3 — Max leads quick select
  document.getElementById("max-leads-quick-select").addEventListener("click", (e) => {
    const btn = e.target.closest(".max-leads-quick-btn");
    if (!btn) return;
    document.getElementById("field-max-leads").value = btn.dataset.value;
    highlightQuickSelectButtons();
    clearStepFieldError("maximum_leads");
  });
  document.getElementById("field-max-leads").addEventListener("input", () => { highlightQuickSelectButtons(); clearStepFieldError("maximum_leads"); });

  // Unsaved changes warning
  window.addEventListener("beforeunload", (e) => {
    if (wizardState.isDirty && !wizardState.submitting) { e.preventDefault(); e.returnValue = ""; }
  });
}

function trackDirtyState() {
  document.getElementById("campaign-form").addEventListener("input", () => { wizardState.isDirty = true; });
}

// -----------------------------------------------------------------
// Char counters / summaries / quick-select highlighting
// -----------------------------------------------------------------
function updateTitleCharCount() {
  document.getElementById("title-char-count").textContent = document.getElementById("field-title").value.length;
}

function updateDescriptionCharCount() {
  document.getElementById("description-char-count").textContent = document.getElementById("field-description").value.length;
}

function updateRadiusSummary() {
  const val = document.getElementById("field-radius").value || 25;
  document.getElementById("radius-summary").textContent = `ระบบจะค้นหาบริษัทภายในรัศมีประมาณ ${val} กิโลเมตรจากพื้นที่เป้าหมาย`;
}

function highlightQuickSelectButtons() {
  const radiusVal = String(document.getElementById("field-radius").value);
  document.querySelectorAll(".radius-quick-btn").forEach((btn) => setQuickBtnActive(btn, btn.dataset.value === radiusVal));

  const maxLeadsVal = String(document.getElementById("field-max-leads").value);
  document.querySelectorAll(".max-leads-quick-btn").forEach((btn) => setQuickBtnActive(btn, btn.dataset.value === maxLeadsVal));
}

function setQuickBtnActive(btn, active) {
  btn.classList.toggle("bg-indigo-600", active);
  btn.classList.toggle("text-white", active);
  btn.classList.toggle("border-indigo-600", active);
  btn.classList.toggle("text-slate-600", !active);
  btn.classList.toggle("border-slate-200", !active);
}

// -----------------------------------------------------------------
// Province searchable dropdown
// -----------------------------------------------------------------
function renderProvinceOptions(term) {
  const dropdown = document.getElementById("province-dropdown");
  const t = (term || "").trim();
  const options = ["ทุกจังหวัด", ...PROVINCES_TH].filter((p) => !t || p.includes(t));

  if (!options.length) {
    dropdown.innerHTML = `<div class="px-3 py-2 text-sm text-slate-400">ไม่พบจังหวัดที่ค้นหา</div>`;
    dropdown.classList.remove("hidden");
    return;
  }

  dropdown.innerHTML = options.map((p) => `
    <button type="button" class="province-option w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-indigo-50 hover:text-indigo-700" data-province="${escapeHTML(p)}">${escapeHTML(p)}</button>
  `).join("");
  dropdown.classList.remove("hidden");

  dropdown.querySelectorAll(".province-option").forEach((btn) => btn.addEventListener("click", () => {
    document.getElementById("field-province-search").value = btn.dataset.province;
    document.getElementById("field-province").value = btn.dataset.province;
    dropdown.classList.add("hidden");
    clearStepFieldError("province");
  }));
}

// -----------------------------------------------------------------
// Keyword tags input (Step 3)
// -----------------------------------------------------------------
function addKeyword(value) {
  if (!value) return;
  if (formState.business_target.business_keywords.length >= 10) { showToast("เพิ่ม Keyword ได้สูงสุด 10 รายการ", "warning"); return; }
  if (formState.business_target.business_keywords.includes(value)) { showToast("Keyword นี้ถูกเพิ่มไปแล้ว", "warning"); return; }
  formState.business_target.business_keywords.push(value);
  renderKeywordChips();
  clearStepFieldError("business_keywords");
}

function removeKeyword(value) {
  formState.business_target.business_keywords = formState.business_target.business_keywords.filter((k) => k !== value);
  renderKeywordChips();
}

function renderKeywordChips() {
  const container = document.getElementById("keyword-chips");
  container.innerHTML = formState.business_target.business_keywords.map((k) => `
    <span class="inline-flex items-center gap-1 text-xs font-medium bg-indigo-50 text-indigo-700 rounded-full pl-2.5 pr-1.5 py-1">
      ${escapeHTML(k)}
      <button type="button" class="keyword-remove-btn hover:bg-indigo-100 rounded-full p-0.5" data-keyword="${escapeHTML(k)}" aria-label="ลบ Keyword ${escapeHTML(k)}">
        <i data-lucide="x" class="w-3 h-3"></i>
      </button>
    </span>
  `).join("");
  container.querySelectorAll(".keyword-remove-btn").forEach((btn) => btn.addEventListener("click", () => removeKeyword(btn.dataset.keyword)));
  if (window.lucide) lucide.createIcons();
}

// -----------------------------------------------------------------
// Field error helpers (real-time clear + validation display)
// -----------------------------------------------------------------
const STEP_FIELD_INPUT_MAP = {
  title: "field-title",
  province: "field-province-search",
  search_radius_km: "field-radius",
  business_type: "field-business-type",
  description: "field-description",
  maximum_leads: "field-max-leads",
  business_keywords: "field-keyword-input",
};

const STEP_FIELD_KEYS = {
  1: ["title"],
  2: ["province", "search_radius_km"],
  3: ["business_type", "description", "maximum_leads", "business_keywords"],
};

function showStepFieldError(key, message) {
  const errEl = document.querySelector(`[data-error-for="${key}"]`);
  if (errEl) { errEl.textContent = message; errEl.classList.remove("hidden"); }
  const input = document.getElementById(STEP_FIELD_INPUT_MAP[key]);
  if (input) input.classList.add("border-rose-400", "ring-rose-100");
}

function clearStepFieldError(key) {
  const errEl = document.querySelector(`[data-error-for="${key}"]`);
  if (errEl) { errEl.textContent = ""; errEl.classList.add("hidden"); }
  const input = document.getElementById(STEP_FIELD_INPUT_MAP[key]);
  if (input) input.classList.remove("border-rose-400", "ring-rose-100");
}

function clearStepErrors(step) {
  (STEP_FIELD_KEYS[step] || []).forEach((key) => clearStepFieldError(key));
}

// -----------------------------------------------------------------
// Sync DOM -> formState (called before leaving a step / before submit)
// -----------------------------------------------------------------
function saveCurrentStepIntoState() {
  formState.title = document.getElementById("field-title").value.trim();
  formState.target.province = document.getElementById("field-province").value;
  formState.target.search_radius_km = parseInt(document.getElementById("field-radius").value, 10) || 25;
  formState.business_target.registered_business = document.getElementById("field-registered-business").value.trim();
  formState.business_target.business_type = document.getElementById("field-business-type").value;
  formState.business_target.description = document.getElementById("field-description").value.trim();
  formState.maximum_leads = parseInt(document.getElementById("field-max-leads").value, 10) || 50;
  formState.duplicate_handling = document.getElementById("field-duplicate-handling").value;
  Object.keys(REQUIRED_FIELD_LABELS).forEach((key) => {
    const cb = document.querySelector(`[data-required-field="${key}"]`);
    formState.required_fields[key] = !!(cb && cb.checked);
  });
  // business_target.business_keywords ถูกดูแลแบบ live โดย addKeyword() / removeKeyword() อยู่แล้ว
}

// -----------------------------------------------------------------
// Validation ต่อ Step — คืนค่า true/false และ focus ไปยัง field แรกที่ error
// -----------------------------------------------------------------
function validateStep(step) {
  clearStepErrors(step);
  let valid = true;
  let firstInvalidEl = null;
  const setInvalid = (key, message, el) => {
    showStepFieldError(key, message);
    valid = false;
    if (!firstInvalidEl) firstInvalidEl = el;
  };

  if (step === 1) {
    const title = document.getElementById("field-title").value.trim();
    if (!title) setInvalid("title", "กรุณากรอกชื่อ Campaign", document.getElementById("field-title"));
    else if (title.length < 3 || title.length > 150) setInvalid("title", "ชื่อ Campaign ต้องมีความยาว 3-150 ตัวอักษร", document.getElementById("field-title"));
  }

  if (step === 2) {
    const province = document.getElementById("field-province").value;
    if (!province) setInvalid("province", "กรุณาเลือกจังหวัด", document.getElementById("field-province-search"));

    const radius = parseInt(document.getElementById("field-radius").value, 10);
    if (!radius || radius < 1 || radius > 200) setInvalid("search_radius_km", "รัศมีค้นหาต้องอยู่ระหว่าง 1-200 กิโลเมตร", document.getElementById("field-radius"));
  }

  if (step === 3) {
    const businessType = document.getElementById("field-business-type").value;
    if (!businessType) setInvalid("business_type", "กรุณาเลือกประเภทธุรกิจ", document.getElementById("field-business-type"));

    const desc = document.getElementById("field-description").value.trim();
    if (desc.length < 20 || desc.length > 1000) setInvalid("description", "คำอธิบายต้องมีความยาวระหว่าง 20-1,000 ตัวอักษร", document.getElementById("field-description"));

    const maxLeads = parseInt(document.getElementById("field-max-leads").value, 10);
    if (!maxLeads || maxLeads < 1 || maxLeads > 1000) setInvalid("maximum_leads", "จำนวน Lead สูงสุดต้องอยู่ระหว่าง 1-1,000", document.getElementById("field-max-leads"));

    if (formState.business_target.business_keywords.length > 10) setInvalid("business_keywords", "เพิ่ม Keyword ได้สูงสุด 10 รายการ", document.getElementById("field-keyword-input"));
  }

  if (!valid && firstInvalidEl) {
    firstInvalidEl.focus();
    showToast("กรุณาตรวจสอบข้อมูลให้ครบถ้วนและถูกต้อง", "error");
  }
  return valid;
}

// -----------------------------------------------------------------
// Step 4: Review summary
// -----------------------------------------------------------------
function renderReviewSummary() {
  saveCurrentStepIntoState();
  const requiredLabels = Object.entries(REQUIRED_FIELD_LABELS)
    .filter(([key]) => formState.required_fields[key])
    .map(([, label]) => label);
  const dupLabel = DUPLICATE_HANDLING_OPTIONS.find((o) => o.value === formState.duplicate_handling)?.label || formState.duplicate_handling;

  const rows = [
    ["ชื่อ Campaign", formState.title],
    ["ประเทศ", "ประเทศไทย"],
    ["จังหวัด", formState.target.province],
    ["รัศมีค้นหา", `${formState.target.search_radius_km} กิโลเมตร`],
    ["รูปแบบนิติบุคคล", formState.business_target.registered_business || "ไม่จำกัดรูปแบบนิติบุคคล"],
    ["ประเภทธุรกิจ", formState.business_target.business_type],
    ["จำนวน Lead สูงสุด", formatNumber(formState.maximum_leads)],
    ["การจัดการข้อมูลซ้ำ", dupLabel],
  ];

  document.getElementById("review-summary").innerHTML = `
    <div class="rounded-xl bg-slate-50 p-4 space-y-3">
      ${rows.map(([label, value]) => `
        <div class="flex items-start justify-between gap-4 text-sm">
          <span class="text-slate-500 shrink-0">${escapeHTML(label)}</span>
          <span class="text-slate-800 font-medium text-right">${escapeHTML(String(value || "-"))}</span>
        </div>
      `).join("")}
      <div class="pt-3 border-t border-slate-200">
        <p class="text-sm text-slate-500 mb-1.5">Keywords</p>
        <div class="flex flex-wrap gap-1.5">
          ${formState.business_target.business_keywords.length
            ? formState.business_target.business_keywords.map((k) => `<span class="text-xs font-medium bg-white ring-1 ring-slate-200 rounded-full px-2.5 py-1">${escapeHTML(k)}</span>`).join("")
            : '<span class="text-sm text-slate-400">ไม่ระบุ</span>'}
        </div>
      </div>
      <div class="pt-3 border-t border-slate-200">
        <p class="text-sm text-slate-500 mb-1">คำอธิบายธุรกิจเป้าหมาย</p>
        <p class="text-sm text-slate-700 whitespace-pre-line">${escapeHTML(formState.business_target.description)}</p>
      </div>
      <div class="pt-3 border-t border-slate-200">
        <p class="text-sm text-slate-500 mb-1.5">ข้อมูลที่จำเป็น</p>
        <div class="flex flex-wrap gap-1.5">
          ${requiredLabels.length
            ? requiredLabels.map((l) => `<span class="text-xs font-medium bg-emerald-50 text-emerald-700 rounded-full px-2.5 py-1">${l}</span>`).join("")
            : '<span class="text-sm text-slate-400">ไม่ระบุ</span>'}
        </div>
      </div>
    </div>
  `;
}

function reviewCampaign() { renderReviewSummary(); }

// -----------------------------------------------------------------
// Build payload (shape ready for POST /api/v1/campaigns — ดู README)
// -----------------------------------------------------------------
function buildCampaignPayload() {
  saveCurrentStepIntoState();
  return JSON.parse(JSON.stringify(formState));
}

/** Mock Service Function — พร้อมเปลี่ยนเป็น POST /api/v1/campaigns ภายหลัง */
function mockCreateCampaignApiCall(payload) {
  return new Promise((resolve) => setTimeout(() => resolve({ ok: true }), 600));
}

function setCreateButtonLoading(loading) {
  document.getElementById("btn-create").disabled = loading;
  document.getElementById("btn-save-draft").disabled = loading;
  document.getElementById("btn-create-text").textContent = loading ? "กำลังสร้าง..." : "สร้างและเริ่มค้นหา";
  document.getElementById("btn-create-spinner").classList.toggle("hidden", !loading);
}

// -----------------------------------------------------------------
// Submit: Create / Save Draft / Update (Edit Mode)
// -----------------------------------------------------------------
async function createCampaign() {
  saveCurrentStepIntoState();
  const results = [1, 2, 3].map((s) => ({ step: s, valid: validateStep(s) }));
  const firstInvalid = results.find((r) => !r.valid);
  if (firstInvalid) {
    goToStep(firstInvalid.step, { skipValidation: true });
    showToast("กรุณาตรวจสอบข้อมูลให้ครบถ้วนก่อนสร้าง Campaign", "error");
    return;
  }

  showConfirmModal({
    title: "ยืนยันการสร้าง Campaign?",
    message: "ระบบจะเริ่มค้นหาและประมวลผล Lead ตามเงื่อนไขที่กำหนด",
    confirmText: "ยืนยันและเริ่มค้นหา",
    onConfirm: () => submitCampaign("queued"),
  });
}

function saveCampaignDraft() {
  saveCurrentStepIntoState();
  if (!validateStep(1)) { goToStep(1, { skipValidation: true }); return; }
  submitCampaign("draft");
}

async function submitCampaign(status) {
  if (wizardState.submitting) return;
  wizardState.submitting = true;
  setCreateButtonLoading(true);

  const payload = buildCampaignPayload();
  await mockCreateCampaignApiCall(payload); // Future API: POST /api/v1/campaigns (ดู README)

  const campaigns = loadCampaignsFromStorage();
  const user = getCurrentUser();
  const nowStr = "2026-07-17 12:00:00";

  if (wizardState.isEditMode) {
    const idx = campaigns.findIndex((c) => c.id === wizardState.editingId);
    if (idx > -1) {
      campaigns[idx] = {
        ...campaigns[idx],
        title: payload.title,
        target: payload.target,
        business_target: payload.business_target,
        maximum_leads: payload.maximum_leads,
        required_fields: payload.required_fields,
        duplicate_handling: payload.duplicate_handling,
        updated_at: nowStr,
      };
      campaigns[idx].activity_logs.push({ icon: "pencil", text: "แก้ไขข้อมูล Campaign", user: user.name, at: nowStr, source: "manual", status: "completed" });
      saveCampaignsToStorage(campaigns);
      wizardState.isDirty = false;
      wizardState.submitting = false;
      showToast("บันทึกการแก้ไข Campaign เรียบร้อยแล้ว", "success");
      setTimeout(() => { window.location.href = `campaign-detail.html?id=${wizardState.editingId}`; }, 500);
      return;
    }
  }

  const newCampaign = {
    id: generateSequentialId("CMP", campaigns),
    title: payload.title,
    target: payload.target,
    business_target: payload.business_target,
    maximum_leads: payload.maximum_leads,
    leads_found: 0,
    required_fields: payload.required_fields,
    duplicate_handling: payload.duplicate_handling,
    status,
    progress: 0,
    assigned_user_ids: user.account_type === "sale" ? [user.id] : [],
    created_by: { id: user.id, name: user.name, account_type: user.account_type },
    created_at: nowStr,
    updated_at: nowStr,
    pipeline: buildCampaignPipeline(status, 0, Math.random),
    activity_logs: [{ icon: "plus-circle", text: "สร้าง Campaign", user: user.name, at: nowStr, source: "system", status: "completed" }],
  };

  campaigns.unshift(newCampaign);
  saveCampaignsToStorage(campaigns);

  wizardState.isDirty = false;
  wizardState.submitting = false;
  showToast(status === "draft" ? "บันทึก Campaign เป็น Draft เรียบร้อยแล้ว" : "สร้าง Campaign และเริ่มค้นหาเรียบร้อยแล้ว", "success");
  setTimeout(() => { window.location.href = `campaign-detail.html?id=${newCampaign.id}`; }, 500);
}

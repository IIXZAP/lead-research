/**
 * business-info.js
 * ควบคุมหน้า Business Information: Header Card + Actions (โทร/Email/Website/
 * เพิ่มเป็น Lead/มอบหมาย Sale/แก้ไข), Tab Overview, DBD Information,
 * Contact Information, Website Analysis, Lead Analysis, Activity Timeline
 */

const bizState = {
  currentUser: null,
  business: null,
  activeTab: "overview",
};

document.addEventListener("DOMContentLoaded", () => {
  const user = checkAuthentication();
  if (!user) return;
  bizState.currentUser = user;

  renderSidebar("leads");
  renderTopbar("business-info");

  const businessId = new URLSearchParams(location.search).get("id");

  fetchBusinessInformation(businessId).then(({ business, denied }) => {
    document.getElementById("detail-loading").classList.add("hidden");

    if (denied || !business) {
      document.getElementById("detail-denied").classList.remove("hidden");
      document.getElementById("detail-denied").innerHTML = permissionDeniedState(
        !business ? "ไม่พบข้อมูลบริษัทที่ต้องการ" : "คุณไม่มีสิทธิ์เข้าถึงข้อมูลบริษัทนี้"
      );
      if (window.lucide) lucide.createIcons();
      return;
    }

    bizState.business = business;
    document.getElementById("detail-content").classList.remove("hidden");

    renderHeader();
    bindTabEvents();
    switchTab("overview");
    if (window.lucide) lucide.createIcons();
  });
});

/** Mock Service Function — พร้อมเปลี่ยนเป็น GET /api/v1/businesses/{businessId} ภายหลัง */
async function fetchBusinessInformation(businessId) {
  return new Promise((resolve) => {
    setTimeout(() => {
      const businesses = loadBusinessesFromStorage();
      const business = businesses.find((b) => b.id === businessId);
      if (!business) { resolve({ business: null, denied: false }); return; }
      if (!hasPermission("business.view")) { resolve({ business: null, denied: true }); return; }
      // sale เห็นได้เฉพาะบริษัทที่ตนเองรับผิดชอบ
      if (bizState.currentUser.account_type === "sale" && business.assigned_user_id !== bizState.currentUser.id) {
        resolve({ business: null, denied: true });
        return;
      }
      resolve({ business, denied: false });
    }, 500);
  });
}

// -----------------------------------------------------------------
// Header + Actions
// -----------------------------------------------------------------
function renderHeader() {
  const b = bizState.business;
  document.getElementById("detail-avatar").innerHTML = initialsAvatar(b.company_name_th, "w-16 h-16 text-lg rounded-2xl");
  document.getElementById("detail-name-th").textContent = b.company_name_th;
  document.getElementById("detail-name-en").textContent = b.company_name_en || "";
  document.getElementById("detail-reg-number").textContent = `เลขทะเบียนนิติบุคคล: ${b.registration_number}`;
  document.getElementById("detail-legal-status-badge").innerHTML = `
    <span class="text-xs font-medium px-2 py-0.5 rounded-full ${b.legal_status === "ยังดำเนินกิจการอยู่" ? "bg-emerald-50 text-emerald-700" : "bg-rose-50 text-rose-700"}">${b.legal_status}</span>
  `;
  document.getElementById("detail-business-type").textContent = b.business_type;
  document.getElementById("detail-province").textContent = b.province;
  document.getElementById("detail-updated").textContent = formatThaiDate(b.legal_entity.updated_at);
  document.getElementById("detail-lead-status").innerHTML = leadStatusBadge(b.lead_status);

  document.getElementById("detail-source-badges").innerHTML = b.source.map((s) => dataSourceBadge(s)).join("");

  renderHeaderActions();
}

function renderHeaderActions() {
  const b = bizState.business;
  const btn = (label, icon, id, extraClass) => `
    <button id="${id}" class="inline-flex items-center gap-1.5 text-sm font-medium rounded-lg px-3.5 py-2 ${extraClass}">
      <i data-lucide="${icon}" class="w-4 h-4"></i> <span class="hidden sm:inline">${label}</span>
    </button>`;
  const linkBtn = (label, icon, href, extraClass, extraAttrs = "") => `
    <a href="${href}" ${extraAttrs} class="inline-flex items-center gap-1.5 text-sm font-medium rounded-lg px-3.5 py-2 ${extraClass}">
      <i data-lucide="${icon}" class="w-4 h-4"></i> <span class="hidden sm:inline">${label}</span>
    </a>`;

  document.getElementById("detail-actions").innerHTML = `
    ${b.website ? linkBtn("เปิดเว็บไซต์", "external-link", b.website, "text-slate-600 border border-slate-200 hover:bg-slate-50", 'target="_blank" rel="noopener noreferrer"') : ""}
    ${b.phone ? linkBtn("โทร", "phone", `tel:${b.phone}`, "text-white bg-indigo-600 hover:bg-indigo-700") : ""}
    ${b.email ? linkBtn("ส่ง Email", "mail", `mailto:${b.email}`, "text-slate-600 border border-slate-200 hover:bg-slate-50") : ""}
    ${hasPermission("lead.add_to_lead") ? btn("เพิ่มเป็น Lead", "user-plus", "act-add-lead", "text-emerald-700 bg-emerald-50 hover:bg-emerald-100") : ""}
    ${hasPermission("lead.assign") ? btn("มอบหมายให้ Sale", "user-check", "act-assign", "text-slate-600 border border-slate-200 hover:bg-slate-50") : ""}
    ${hasPermission("business.view") ? btn("แก้ไขข้อมูล", "pencil", "act-edit", "text-slate-600 border border-slate-200 hover:bg-slate-50") : ""}
  `;
  if (window.lucide) lucide.createIcons();

  const addLeadBtn = document.getElementById("act-add-lead");
  if (addLeadBtn) addLeadBtn.addEventListener("click", () => {
    showToast(`บันทึก "${b.company_name_th}" เป็น Lead ในระบบเรียบร้อยแล้ว`, "success");
  });

  const assignBtn = document.getElementById("act-assign");
  if (assignBtn) assignBtn.addEventListener("click", openAssignModal);

  const editBtn = document.getElementById("act-edit");
  if (editBtn) editBtn.addEventListener("click", openEditModal);
}

// -----------------------------------------------------------------
// Quick modal helper (Assign / Edit) — โครงเดียวกับ showConfirmModal ใน toast.js
// -----------------------------------------------------------------
function openQuickModal(title, bodyHtml, onConfirm) {
  let root = document.getElementById("biz-quick-modal");
  if (!root) {
    root = document.createElement("div");
    root.id = "biz-quick-modal";
    root.className = "hidden fixed inset-0 z-[70] flex items-center justify-center p-4";
    root.innerHTML = `
      <div class="modal-backdrop absolute inset-0 bg-slate-900/50 opacity-0"></div>
      <div class="modal-panel relative bg-white w-full max-w-md rounded-2xl shadow-xl p-6 opacity-0 scale-95"></div>
    `;
    document.body.appendChild(root);
  }
  const panel = root.querySelector(".modal-panel");
  panel.innerHTML = `
    <button id="biz-quick-close" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600" aria-label="ปิด"><i data-lucide="x" class="w-4 h-4"></i></button>
    <h3 class="font-semibold text-slate-800 mb-4">${escapeHTML(title)}</h3>
    <div class="mb-5">${bodyHtml}</div>
    <div class="flex justify-end gap-2">
      <button id="biz-quick-cancel" class="text-sm font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg px-4 py-2">ยกเลิก</button>
      <button id="biz-quick-ok" class="text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-4 py-2">บันทึก</button>
    </div>
  `;

  root.classList.remove("hidden");
  const backdrop = root.querySelector(".modal-backdrop");
  requestAnimationFrame(() => { backdrop.classList.remove("opacity-0"); panel.classList.remove("opacity-0", "scale-95"); });
  if (window.lucide) lucide.createIcons();
  document.body.style.overflow = "hidden";

  const close = () => {
    backdrop.classList.add("opacity-0"); panel.classList.add("opacity-0", "scale-95");
    setTimeout(() => { root.classList.add("hidden"); document.body.style.overflow = ""; }, 200);
  };
  document.getElementById("biz-quick-close").onclick = close;
  document.getElementById("biz-quick-cancel").onclick = close;
  backdrop.onclick = close;
  document.getElementById("biz-quick-ok").onclick = () => {
    const result = onConfirm();
    if (result !== false) close();
  };
}

function persistBusiness(business) {
  const all = loadBusinessesFromStorage();
  const idx = all.findIndex((b) => b.id === business.id);
  if (idx > -1) all[idx] = business;
  saveBusinessesToStorage(all);
}

function openAssignModal() {
  const b = bizState.business;
  const salesUsers = MOCK_USERS.filter((u) => u.account_type === "sale");
  openQuickModal("มอบหมายให้ Sale", `
    <label class="block text-sm font-medium text-slate-700 mb-1.5">มอบหมาย "${escapeHTML(b.company_name_th)}" ให้</label>
    <select id="quick-assign-select" class="filter-input w-full !bg-white">
      ${salesUsers.map((u) => `<option value="${u.id}" ${u.id === b.assigned_user_id ? "selected" : ""}>${u.name}</option>`).join("")}
    </select>
  `, () => {
    const newUserId = parseInt(document.getElementById("quick-assign-select").value, 10);
    const newUser = salesUsers.find((u) => u.id === newUserId);
    b.assigned_user_id = newUser.id;
    b.assigned_user_name = newUser.name;
    b.activity.push({ icon: "user-check", text: `มอบหมายให้ ${newUser.name}`, user: bizState.currentUser.name, at: "2026-07-17 12:00:00", source: "manual", status: "completed" });
    persistBusiness(b);
    showToast("มอบหมายผู้รับผิดชอบเรียบร้อยแล้ว", "success");
    if (bizState.activeTab === "activity") renderActivityTab();
  });
}

function openEditModal() {
  const b = bizState.business;
  openQuickModal("แก้ไขข้อมูลบริษัท", `
    <div class="space-y-3">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">เบอร์โทร</label>
        <input id="quick-edit-phone" type="text" value="${escapeHTML(b.phone || "")}" class="filter-input w-full !bg-white" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
        <input id="quick-edit-email" type="text" value="${escapeHTML(b.email || "")}" class="filter-input w-full !bg-white" />
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">สถานะ Lead</label>
        <select id="quick-edit-status" class="filter-input w-full !bg-white">
          ${LEAD_STATUSES.map((s) => `<option value="${s}" ${s === b.lead_status ? "selected" : ""}>${LEAD_STATUS_META[s].label}</option>`).join("")}
        </select>
      </div>
    </div>
  `, () => {
    const phone = document.getElementById("quick-edit-phone").value.trim();
    const email = document.getElementById("quick-edit-email").value.trim();
    if (email && !validateEmail(email)) { showToast("รูปแบบ Email ไม่ถูกต้อง", "error"); return false; }
    if (phone && !validatePhone(phone)) { showToast("รูปแบบเบอร์โทรไม่ถูกต้อง", "error"); return false; }

    b.phone = phone || null;
    b.email = email || null;
    b.lead_status = document.getElementById("quick-edit-status").value;
    b.contact.phone = b.phone;
    b.contact.email = b.email;
    b.activity.push({ icon: "pencil", text: "แก้ไขข้อมูลติดต่อของบริษัท", user: bizState.currentUser.name, at: "2026-07-17 12:00:00", source: "manual", status: "completed" });
    persistBusiness(b);
    renderHeader();
    showToast("บันทึกการแก้ไขเรียบร้อยแล้ว", "success");
    if (bizState.activeTab === "contact") renderContactTab();
    if (bizState.activeTab === "activity") renderActivityTab();
  });
}

// -----------------------------------------------------------------
// Tabs
// -----------------------------------------------------------------
function bindTabEvents() {
  document.querySelectorAll(".detail-tab").forEach((btn) => btn.addEventListener("click", () => switchTab(btn.dataset.tab)));
}

function switchTab(tab) {
  bizState.activeTab = tab;
  document.querySelectorAll(".detail-tab").forEach((btn) => {
    const active = btn.dataset.tab === tab;
    btn.classList.toggle("border-indigo-600", active);
    btn.classList.toggle("text-indigo-600", active);
    btn.classList.toggle("border-transparent", !active);
    btn.classList.toggle("text-slate-500", !active);
  });
  document.querySelectorAll(".tab-panel").forEach((panel) => panel.classList.toggle("hidden", panel.dataset.tabPanel !== tab));

  if (tab === "overview") renderOverviewTab();
  if (tab === "dbd") renderDbdTab();
  if (tab === "contact") renderContactTab();
  if (tab === "website") renderWebsiteTab();
  if (tab === "lead") renderLeadAnalysisTab();
  if (tab === "activity") renderActivityTab();
}

function infoRow(label, value) {
  return `
    <div class="flex items-start justify-between gap-4 text-sm py-2 border-b border-slate-50 last:border-0">
      <span class="text-slate-500 shrink-0">${escapeHTML(label)}</span>
      <span class="text-slate-800 font-medium text-right break-words">${value === null || value === undefined || value === "" ? "-" : value}</span>
    </div>
  `;
}

// -----------------------------------------------------------------
// Tab: Overview
// -----------------------------------------------------------------
function renderOverviewTab() {
  const b = bizState.business;
  const problemCount = (b.website_problems || []).length;
  const panel = document.querySelector('[data-tab-panel="overview"]');

  panel.innerHTML = `
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-slate-50 rounded-xl p-4">
        <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3"><i data-lucide="gauge" class="w-5 h-5"></i></div>
        <p class="text-2xl font-bold text-slate-800">${b.lead_analysis.score}</p>
        <p class="text-xs text-slate-500 mt-1">Lead Score (${b.lead_analysis.quality})</p>
      </div>
      <div class="bg-slate-50 rounded-xl p-4">
        <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3"><i data-lucide="database" class="w-5 h-5"></i></div>
        <p class="text-2xl font-bold text-slate-800">${b.data_completeness}%</p>
        <p class="text-xs text-slate-500 mt-1">ความสมบูรณ์ของข้อมูล</p>
      </div>
      <div class="bg-slate-50 rounded-xl p-4">
        <div class="w-9 h-9 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center mb-3"><i data-lucide="globe" class="w-5 h-5"></i></div>
        <p class="text-2xl font-bold text-slate-800">${b.website_info ? (b.website_info.status === "Online" ? "Online" : "Offline") : "ไม่พบ"}</p>
        <p class="text-xs text-slate-500 mt-1">สถานะเว็บไซต์</p>
      </div>
      <div class="bg-slate-50 rounded-xl p-4">
        <div class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center mb-3"><i data-lucide="alert-triangle" class="w-5 h-5"></i></div>
        <p class="text-2xl font-bold text-slate-800">${problemCount}</p>
        <p class="text-xs text-slate-500 mt-1">ปัญหาที่ตรวจพบ</p>
      </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
      <div class="rounded-xl bg-slate-50 p-4">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">ข้อมูลนิติบุคคลโดยย่อ</h4>
        ${infoRow("ประเภทนิติบุคคล", escapeHTML(b.legal_entity.entity_type))}
        ${infoRow("วันที่จดทะเบียน", formatThaiDate(b.legal_entity.registration_date))}
        ${infoRow("ทุนจดทะเบียน", formatCurrency(b.capital.registered_capital))}
        ${infoRow("กรรมการ", `${b.directors.length} คน`)}
      </div>
      <div class="rounded-xl bg-slate-50 p-4">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">โอกาสทางการขาย (AI วิเคราะห์)</h4>
        ${infoRow("Opportunity", escapeHTML(b.lead_analysis.opportunity))}
        ${infoRow("บริการที่แนะนำ", b.lead_analysis.recommended_services.map((s) => escapeHTML(s)).join(", "))}
        ${infoRow("ระดับความมั่นใจ", `${Math.round(b.lead_analysis.confidence_score * 100)}%`)}
      </div>
    </div>
  `;
  if (window.lucide) lucide.createIcons();
}

// -----------------------------------------------------------------
// Tab: DBD Information
// -----------------------------------------------------------------
function renderDbdTab() {
  const b = bizState.business;
  const addr = b.address;
  const fullAddress = [
    addr.house_no && `เลขที่ ${addr.house_no}`, addr.building, addr.floor && `ชั้น ${addr.floor}`, addr.moo && `หมู่ ${addr.moo}`,
    addr.soi, addr.road, `แขวง/ตำบล${addr.sub_district}`, `เขต/อำเภอ${addr.district}`, `จังหวัด${addr.province}`, addr.postal_code,
  ].filter(Boolean).join(" ");

  const panel = document.querySelector('[data-tab-panel="dbd"]');
  panel.innerHTML = `
    <div class="grid lg:grid-cols-2 gap-5">
      <div class="rounded-xl bg-slate-50 p-4">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">1. ข้อมูลนิติบุคคล</h4>
        ${infoRow("เลขทะเบียนนิติบุคคล", escapeHTML(b.legal_entity.registration_number))}
        ${infoRow("ชื่อนิติบุคคล (ไทย)", escapeHTML(b.legal_entity.company_name_th))}
        ${infoRow("ชื่อนิติบุคคล (อังกฤษ)", escapeHTML(b.legal_entity.company_name_en))}
        ${infoRow("ประเภทนิติบุคคล", escapeHTML(b.legal_entity.entity_type))}
        ${infoRow("สถานะนิติบุคคล", escapeHTML(b.legal_entity.status))}
        ${infoRow("วันที่จดทะเบียน", formatThaiDate(b.legal_entity.registration_date))}
        ${infoRow("วันที่เลิกกิจการ", b.legal_entity.dissolution_date ? formatThaiDate(b.legal_entity.dissolution_date) : "-")}
        ${infoRow("จังหวัดที่จดทะเบียน", escapeHTML(b.legal_entity.province))}
        ${infoRow("รหัสวัตถุประสงค์", escapeHTML(b.legal_entity.business_objective_code))}
        ${infoRow("รายละเอียดวัตถุประสงค์", escapeHTML(b.legal_entity.business_objective))}
        ${infoRow("อัปเดตล่าสุด", formatThaiDate(b.legal_entity.updated_at, true))}
      </div>

      <div class="space-y-5">
        <div class="rounded-xl bg-slate-50 p-4">
          <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">2. ทุนจดทะเบียน</h4>
          ${infoRow("ทุนจดทะเบียน", formatCurrency(b.capital.registered_capital))}
          ${infoRow("ทุนชำระแล้ว", formatCurrency(b.capital.paid_up_capital))}
          ${infoRow("สกุลเงิน", b.capital.currency)}
          ${infoRow("อัปเดตล่าสุด", formatThaiDate(b.capital.updated_at))}
        </div>

        <div class="rounded-xl bg-slate-50 p-4">
          <div class="flex items-center justify-between mb-2">
            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400">3. ที่ตั้งสำนักงานใหญ่</h4>
          </div>
          <p class="text-sm text-slate-700 leading-relaxed">${escapeHTML(fullAddress)}</p>
          <div class="flex gap-2 mt-3">
            <button id="btn-copy-address" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 border border-slate-200 hover:bg-white rounded-lg px-3 py-1.5">
              <i data-lucide="copy" class="w-3.5 h-3.5"></i> คัดลอกที่อยู่
            </button>
            <a href="https://www.google.com/maps/search/${encodeURIComponent(fullAddress)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 border border-indigo-100 hover:bg-indigo-50 rounded-lg px-3 py-1.5">
              <i data-lucide="map" class="w-3.5 h-3.5"></i> เปิด Google Maps
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-xl bg-slate-50 p-4">
      <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">4. กรรมการบริษัท</h4>
      ${b.directors.length ? `
        <div class="grid sm:grid-cols-2 gap-3">
          ${b.directors.map((d) => `
            <div class="bg-white rounded-lg p-3 ring-1 ring-slate-100">
              <p class="text-sm font-medium text-slate-800">${escapeHTML(d.name)}</p>
              <p class="text-xs text-slate-500 mt-0.5">${escapeHTML(d.position)}${d.signing_authority ? " · มีอำนาจลงนาม" : ""}</p>
              ${d.start_date ? `<p class="text-xs text-slate-400 mt-0.5">เริ่มดำรงตำแหน่ง ${formatThaiDate(d.start_date)}</p>` : ""}
            </div>
          `).join("")}
        </div>
      ` : `<p class="text-sm text-slate-400">ไม่พบข้อมูลกรรมการจากแหล่งข้อมูลปัจจุบัน</p>`}
    </div>

    <div class="rounded-xl bg-slate-50 p-4">
      <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">5. วัตถุประสงค์และประเภทธุรกิจ</h4>
      ${infoRow("ประเภทธุรกิจหลัก", escapeHTML(b.business_type))}
      ${infoRow("วัตถุประสงค์ตอนจดทะเบียน", escapeHTML(b.legal_entity.business_objective))}
      <div class="pt-2">
        <p class="text-sm text-slate-500 mb-1.5">AI Business Summary <span class="text-[10px] font-semibold uppercase tracking-wide bg-violet-50 text-violet-600 rounded-full px-2 py-0.5 ml-1">ข้อมูลวิเคราะห์ ไม่ใช่ข้อมูลทางการจาก DBD</span></p>
        <p class="text-sm text-slate-700 leading-relaxed">บริษัทดำเนินธุรกิจเกี่ยวกับ${escapeHTML(b.business_type)} โดยมีกลุ่มลูกค้าหลักในพื้นที่${escapeHTML(b.province)}และบริเวณใกล้เคียง จากข้อมูลที่ตรวจพบ ระบบประเมินว่าธุรกิจนี้${b.website ? "มีช่องทางออนไลน์อยู่แล้วแต่อาจต้องปรับปรุงเพิ่มเติม" : "ยังไม่มีช่องทางออนไลน์ที่ชัดเจน"}</p>
      </div>
    </div>
  `;
  if (window.lucide) lucide.createIcons();

  document.getElementById("btn-copy-address").addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(fullAddress);
      showToast("คัดลอกที่อยู่เรียบร้อยแล้ว", "success");
    } catch (e) {
      showToast("ไม่สามารถคัดลอกที่อยู่ได้ กรุณาคัดลอกด้วยตนเอง", "error");
    }
  });
}

// -----------------------------------------------------------------
// Tab: Contact Information
// -----------------------------------------------------------------
function renderContactTab() {
  const b = bizState.business;
  const c = b.contact;
  const panel = document.querySelector('[data-tab-panel="contact"]');
  panel.innerHTML = `
    <div class="flex items-center justify-between mb-1">
      <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400">6. ข้อมูลการติดต่อ</h4>
      ${verificationBadge(c.verification_status)}
    </div>
    <div class="rounded-xl bg-slate-50 p-4">
      ${infoRow("Website", c.website ? `<a href="${c.website}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-700">${escapeHTML(shortDomain(c.website))}</a>` : "-")}
      ${infoRow("Phone", c.phone ? `<a href="tel:${c.phone}" class="text-indigo-600 hover:text-indigo-700">${escapeHTML(c.phone)}</a>` : "-")}
      ${infoRow("Mobile", c.mobile ? `<a href="tel:${c.mobile}" class="text-indigo-600 hover:text-indigo-700">${escapeHTML(c.mobile)}</a>` : "-")}
      ${infoRow("Email", c.email ? `<a href="mailto:${c.email}" class="text-indigo-600 hover:text-indigo-700">${escapeHTML(c.email)}</a>` : "-")}
      ${infoRow("LINE ID", escapeHTML(c.line_id))}
      ${infoRow("Facebook", c.facebook ? `<a href="https://${c.facebook}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-700">${escapeHTML(c.facebook)}</a>` : "-")}
      ${infoRow("Social Media", escapeHTML(c.social_media))}
      ${infoRow("Contact Person", escapeHTML(c.contact_person))}
    </div>
  `;
  if (window.lucide) lucide.createIcons();
}

// -----------------------------------------------------------------
// Tab: Website Analysis
// -----------------------------------------------------------------
function renderWebsiteTab() {
  const b = bizState.business;
  const panel = document.querySelector('[data-tab-panel="website"]');

  if (!b.website_info) {
    panel.innerHTML = genericEmptyState("ไม่พบเว็บไซต์ของบริษัทนี้", "ระบบยังไม่พบข้อมูลเว็บไซต์ที่สามารถตรวจสอบได้");
    if (window.lucide) lucide.createIcons();
    return;
  }

  const w = b.website_info;
  panel.innerHTML = `
    <div class="rounded-xl bg-slate-50 p-4">
      <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">7. ข้อมูลเว็บไซต์</h4>
      ${infoRow("Website URL", `<a href="${w.url}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-700">${escapeHTML(shortDomain(w.url))}</a>`)}
      ${infoRow("Website Status", `<span class="${w.status === "Online" ? "text-emerald-600" : "text-rose-600"}">${w.status}</span>`)}
      ${infoRow("HTTP Status", w.http_status)}
      ${infoRow("SSL", w.ssl ? '<span class="text-emerald-600">มี SSL</span>' : '<span class="text-rose-600">ไม่มี SSL</span>')}
      ${infoRow("Mobile Friendly", w.mobile_friendly ? '<span class="text-emerald-600">รองรับ</span>' : '<span class="text-rose-600">ไม่รองรับ</span>')}
      ${infoRow("Page Speed Score", `${w.page_speed}/100`)}
      ${infoRow("Last Checked", formatThaiDate(w.last_checked, true))}
      ${infoRow("CMS / Technology", escapeHTML(w.technology))}
      ${infoRow("Facebook Pixel", w.facebook_pixel ? "ตรวจพบ" : "ไม่พบ")}
      ${infoRow("Google Analytics", w.google_analytics ? "ตรวจพบ" : "ไม่พบ")}
      ${infoRow("Contact Form", w.contact_form ? "มี" : "ไม่มี")}
      ${infoRow("SEO Title", escapeHTML(w.seo_title))}
      ${infoRow("Meta Description", escapeHTML(w.meta_description) || "ไม่พบ Meta Description")}
    </div>

    <div class="rounded-xl bg-slate-50 p-4">
      <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">8. Website Problems</h4>
      ${b.website_problems.length ? `
        <div class="space-y-2">
          ${b.website_problems.map((p) => `
            <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2.5 ring-1 ring-slate-100">
              <span class="text-sm text-slate-700">${escapeHTML(p.text)}</span>
              ${severityBadge(p.severity)}
            </div>
          `).join("")}
        </div>
      ` : `<p class="text-sm text-slate-400">ไม่พบปัญหาที่มีนัยสำคัญจากการตรวจสอบ</p>`}
    </div>
  `;
  if (window.lucide) lucide.createIcons();
}

// -----------------------------------------------------------------
// Tab: Lead Analysis
// -----------------------------------------------------------------
function renderLeadAnalysisTab() {
  const b = bizState.business;
  const la = b.lead_analysis;
  const panel = document.querySelector('[data-tab-panel="lead"]');

  panel.innerHTML = `
    <div class="rounded-lg bg-violet-50 text-violet-700 text-xs font-medium px-3 py-2 flex items-center gap-2">
      <i data-lucide="sparkles" class="w-4 h-4 shrink-0"></i>
      ข้อมูลในหน้านี้มาจากการวิเคราะห์ด้วย AI เพื่อประกอบการตัดสินใจเท่านั้น ไม่ใช่ข้อมูลทางการจาก DBD
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-slate-50 rounded-xl p-4">
        <p class="text-2xl font-bold text-slate-800">${la.score}</p>
        <p class="text-xs text-slate-500 mt-1">Lead Score</p>
      </div>
      <div class="bg-slate-50 rounded-xl p-4">
        <p class="text-2xl font-bold text-slate-800">${la.quality}</p>
        <p class="text-xs text-slate-500 mt-1">Lead Quality</p>
      </div>
      <div class="bg-slate-50 rounded-xl p-4">
        <p class="text-2xl font-bold text-slate-800">${Math.round(la.confidence_score * 100)}%</p>
        <p class="text-xs text-slate-500 mt-1">ระดับความมั่นใจ</p>
      </div>
      <div class="bg-slate-50 rounded-xl p-4">
        <p class="text-sm font-bold text-slate-800 truncate" title="${escapeHTML(la.opportunity)}">${escapeHTML(la.opportunity)}</p>
        <p class="text-xs text-slate-500 mt-1">Opportunity</p>
      </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
      <div class="rounded-xl bg-slate-50 p-4">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Pain Points</h4>
        <ul class="space-y-1.5">
          ${la.pain_points.map((p) => `<li class="flex items-start gap-2 text-sm text-slate-700"><i data-lucide="minus" class="w-4 h-4 text-rose-400 mt-0.5 shrink-0"></i>${escapeHTML(p)}</li>`).join("")}
        </ul>
      </div>
      <div class="rounded-xl bg-slate-50 p-4">
        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">บริการที่แนะนำ</h4>
        <div class="flex flex-wrap gap-1.5">
          ${la.recommended_services.map((s) => `<span class="text-xs font-medium bg-white ring-1 ring-slate-200 rounded-full px-2.5 py-1">${escapeHTML(s)}</span>`).join("")}
        </div>
      </div>
    </div>

    <div class="rounded-xl bg-slate-50 p-4">
      <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Suggested Sales Message</h4>
      <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">${escapeHTML(la.suggested_sales_message)}</p>
    </div>
  `;
  if (window.lucide) lucide.createIcons();
}

// -----------------------------------------------------------------
// Tab: Activity Timeline
// -----------------------------------------------------------------
function renderActivityTab() {
  const b = bizState.business;
  const sorted = [...b.activity].sort((a, b2) => new Date(b2.at) - new Date(a.at));
  const panel = document.querySelector('[data-tab-panel="activity"]');
  panel.innerHTML = `<div class="max-w-2xl">${activityTimelineHtml(sorted)}</div>`;
  if (window.lucide) lucide.createIcons();
}

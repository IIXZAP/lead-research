/**
 * campaign-detail.js
 * ควบคุมหน้า Campaign Detail: Header + Actions (Pause/Resume/Retry/Edit/Delete),
 * Tab Overview (Summary + Pipeline Timeline), Tab Leads (ตาราง Business ภายใน
 * Campaign นี้), Tab Search Criteria (อ่านอย่างเดียว), Tab Activity Logs
 */

const detailState = {
  currentUser: null,
  campaign: null,
  businesses: [],   // เฉพาะของ Campaign นี้ (หลัง scope filter)
  activeTab: "overview",
  leadsSearch: "",
};

document.addEventListener("DOMContentLoaded", () => {
  const user = checkAuthentication();
  if (!user) return;
  detailState.currentUser = user;

  renderSidebar("campaigns");
  renderTopbar("campaign-detail");

  const params = new URLSearchParams(location.search);
  const campaignId = params.get("id");
  const initialTab = params.get("tab") || "overview";

  fetchCampaignDetail(campaignId).then(({ campaign, businesses, denied }) => {
    document.getElementById("detail-loading").classList.add("hidden");

    if (denied || !campaign) {
      document.getElementById("detail-denied").classList.remove("hidden");
      document.getElementById("detail-denied").innerHTML = permissionDeniedState(
        !campaign ? "ไม่พบ Campaign ที่ต้องการ" : "คุณไม่มีสิทธิ์เข้าถึง Campaign นี้"
      );
      if (window.lucide) lucide.createIcons();
      return;
    }

    detailState.campaign = campaign;
    detailState.businesses = businesses;
    document.getElementById("detail-content").classList.remove("hidden");

    renderHeader();
    bindTabEvents();
    switchTab(["overview", "leads", "criteria", "activity"].includes(initialTab) ? initialTab : "overview");
    if (window.lucide) lucide.createIcons();
  });
});

/**
 * Mock Service Function — พร้อมเปลี่ยนเป็น GET /api/v1/campaigns/{campaignId}
 * และ GET /api/v1/campaigns/{campaignId}/leads ภายหลัง
 */
async function fetchCampaignDetail(campaignId) {
  return new Promise((resolve) => {
    setTimeout(() => {
      const campaigns = loadCampaignsFromStorage();
      const campaign = campaigns.find((c) => c.id === campaignId);
      if (!campaign) { resolve({ campaign: null, businesses: [], denied: false }); return; }

      if (!canAccessCampaign(campaign, detailState.currentUser)) {
        resolve({ campaign: null, businesses: [], denied: true });
        return;
      }

      const allBusinesses = loadBusinessesFromStorage().filter((b) => b.campaign_id === campaignId);
      const businesses = filterBusinessesByScope(allBusinesses, detailState.currentUser);
      resolve({ campaign, businesses, denied: false });
    }, 500);
  });
}

// -----------------------------------------------------------------
// Header + Actions
// -----------------------------------------------------------------
function renderHeader() {
  const c = detailState.campaign;
  document.getElementById("detail-title").textContent = c.title;
  document.getElementById("detail-status-badge").innerHTML = campaignStatusBadge(c.status);
  document.getElementById("detail-meta").textContent = `${c.id} · สร้างโดย ${c.created_by.name} · ${formatThaiDate(c.created_at, true)}`;
  document.getElementById("detail-leads-found").textContent = formatNumber(c.leads_found);
  document.getElementById("detail-max-leads").textContent = formatNumber(c.maximum_leads);
  document.getElementById("detail-province").textContent = c.target.province;
  document.getElementById("detail-business-type").textContent = c.business_target.business_type;
  document.getElementById("detail-progress-wrap").innerHTML = progressBarHtml(c.progress, "ความคืบหน้าโดยรวม");

  renderHeaderActions();
}

function renderHeaderActions() {
  const c = detailState.campaign;
  const canEdit = hasPermission("campaign.edit") && (c.status === "draft" || c.status === "queued");
  const canStart = hasPermission("campaign.process") && (c.status === "draft" || c.status === "queued");
  const canPause = hasPermission("campaign.pause") && c.status === "processing";
  const canRetry = hasPermission("campaign.retry") && (c.status === "failed" || c.status === "cancelled");
  const canDelete = hasPermission("campaign.delete");

  const btn = (label, icon, id, extraClass) => `
    <button id="${id}" class="inline-flex items-center gap-1.5 text-sm font-medium rounded-lg px-3.5 py-2 ${extraClass}">
      <i data-lucide="${icon}" class="w-4 h-4"></i> <span class="hidden sm:inline">${label}</span>
    </button>`;

  document.getElementById("detail-actions").innerHTML = `
    ${canStart ? btn("เริ่มประมวลผล", "play", "act-start", "text-white bg-emerald-600 hover:bg-emerald-700") : ""}
    ${canPause ? btn("หยุด (Pause)", "pause", "act-pause", "text-white bg-amber-500 hover:bg-amber-600") : ""}
    ${canRetry ? btn("ลองประมวลผลใหม่", "rotate-ccw", "act-retry", "text-white bg-indigo-600 hover:bg-indigo-700") : ""}
    ${canEdit ? `<a href="campaign-create.html?edit=${c.id}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg px-3.5 py-2"><i data-lucide="pencil" class="w-4 h-4"></i> <span class="hidden sm:inline">Edit</span></a>` : ""}
    ${canDelete ? btn("ลบ", "trash-2", "act-delete", "text-rose-600 border border-rose-200 hover:bg-rose-50") : ""}
  `;
  if (window.lucide) lucide.createIcons();

  const startBtn = document.getElementById("act-start");
  if (startBtn) startBtn.addEventListener("click", () => showConfirmModal({
    title: "ยืนยันการเริ่มประมวลผล Campaign?",
    message: "ระบบจะเริ่มค้นหาและประมวลผล Lead ตามเงื่อนไขที่กำหนด",
    confirmText: "ยืนยันและเริ่มค้นหา",
    onConfirm: () => { updateCampaignField({ status: "queued", progress: 0 }, "เริ่มประมวลผล Campaign แล้ว"); },
  }));

  const pauseBtn = document.getElementById("act-pause");
  if (pauseBtn) pauseBtn.addEventListener("click", () => updateCampaignField({ status: "partially_completed" }, "หยุด Campaign ชั่วคราวแล้ว"));

  const retryBtn = document.getElementById("act-retry");
  if (retryBtn) retryBtn.addEventListener("click", () => updateCampaignField({ status: "queued", progress: 0 }, "นำ Campaign เข้าคิวประมวลผลใหม่แล้ว"));

  const deleteBtn = document.getElementById("act-delete");
  if (deleteBtn) deleteBtn.addEventListener("click", () => showConfirmModal({
    title: `ลบ Campaign "${c.title}"?`,
    message: "การลบไม่สามารถย้อนกลับได้ กรุณายืนยันอีกครั้ง",
    danger: true, confirmText: "ยืนยันลบ",
    onConfirm: () => {
      const campaigns = loadCampaignsFromStorage().filter((camp) => camp.id !== c.id);
      saveCampaignsToStorage(campaigns);
      showToast("ลบ Campaign เรียบร้อยแล้ว", "success");
      setTimeout(() => { window.location.href = "campaigns.html"; }, 600);
    },
  }));
}

function updateCampaignField(patch, successMessage) {
  const permOk = {
    processing: hasPermission("campaign.pause"),
  };
  const campaigns = loadCampaignsFromStorage();
  const idx = campaigns.findIndex((c) => c.id === detailState.campaign.id);
  if (idx === -1) return;
  Object.assign(campaigns[idx], patch, { updated_at: "2026-07-17 12:00:00" });
  campaigns[idx].pipeline = buildCampaignPipeline(campaigns[idx].status, campaigns[idx].progress, Math.random);
  campaigns[idx].activity_logs.push({
    icon: patch.status === "queued" ? "play" : patch.status === "partially_completed" ? "pause" : "refresh-cw",
    text: successMessage, user: detailState.currentUser.name, at: "2026-07-17 12:00:00", source: "manual", status: "completed",
  });
  saveCampaignsToStorage(campaigns);
  detailState.campaign = campaigns[idx];
  renderHeader();
  if (detailState.activeTab === "overview") renderOverviewTab();
  if (detailState.activeTab === "activity") renderActivityTab();
  showToast(successMessage, "success");
}

// -----------------------------------------------------------------
// Tabs
// -----------------------------------------------------------------
function bindTabEvents() {
  document.querySelectorAll(".detail-tab").forEach((btn) => btn.addEventListener("click", () => switchTab(btn.dataset.tab)));
}

function switchTab(tab) {
  detailState.activeTab = tab;
  document.querySelectorAll(".detail-tab").forEach((btn) => {
    const active = btn.dataset.tab === tab;
    btn.classList.toggle("border-indigo-600", active);
    btn.classList.toggle("text-indigo-600", active);
    btn.classList.toggle("border-transparent", !active);
    btn.classList.toggle("text-slate-500", !active);
  });
  document.querySelectorAll(".tab-panel").forEach((panel) => panel.classList.toggle("hidden", panel.dataset.tabPanel !== tab));

  if (tab === "overview") renderOverviewTab();
  if (tab === "leads") renderLeadsTab();
  if (tab === "criteria") renderCriteriaTab();
  if (tab === "activity") renderActivityTab();
}

// -----------------------------------------------------------------
// Tab: Overview
// -----------------------------------------------------------------
function renderOverviewTab() {
  const c = detailState.campaign;
  const businesses = detailState.businesses;
  const withWebsite = businesses.filter((b) => b.website).length;
  const withPhone = businesses.filter((b) => b.phone).length;
  const withEmail = businesses.filter((b) => b.email).length;
  const incomplete = businesses.filter((b) => b.data_completeness < 60).length;
  // Duplicate heuristic (mock): บริษัทที่เลขทะเบียนซ้ำกันภายใน Campaign เดียวกัน
  const regCounts = {};
  businesses.forEach((b) => { regCounts[b.registration_number] = (regCounts[b.registration_number] || 0) + 1; });
  const duplicates = businesses.filter((b) => regCounts[b.registration_number] > 1).length;

  const cards = [
    { label: "Lead ที่พบทั้งหมด", value: businesses.length, icon: "users", color: "indigo" },
    { label: "มี Website", value: withWebsite, icon: "globe", color: "sky" },
    { label: "มีเบอร์โทร", value: withPhone, icon: "phone", color: "emerald" },
    { label: "มี Email", value: withEmail, icon: "mail", color: "violet" },
    { label: "ข้อมูลซ้ำ", value: duplicates, icon: "copy", color: "amber" },
    { label: "ข้อมูลไม่สมบูรณ์", value: incomplete, icon: "alert-circle", color: "rose" },
  ];
  const colorMap = { indigo: "bg-indigo-50 text-indigo-600", sky: "bg-sky-50 text-sky-600", emerald: "bg-emerald-50 text-emerald-600", violet: "bg-violet-50 text-violet-600", amber: "bg-amber-50 text-amber-600", rose: "bg-rose-50 text-rose-600" };

  const panel = document.querySelector('[data-tab-panel="overview"]');
  panel.innerHTML = `
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
      ${cards.map((cd) => `
        <div class="bg-slate-50 rounded-xl p-4">
          <div class="w-9 h-9 rounded-lg ${colorMap[cd.color]} flex items-center justify-center mb-3">
            <i data-lucide="${cd.icon}" class="w-5 h-5"></i>
          </div>
          <p class="text-2xl font-bold text-slate-800">${formatNumber(cd.value)}</p>
          <p class="text-xs text-slate-500 mt-1">${cd.label}</p>
        </div>
      `).join("")}
    </div>

    <div>
      <h3 class="text-sm font-semibold text-slate-700 mb-4">Progress Timeline</h3>
      <div>${pipelineTimelineHtml(c.pipeline)}</div>
    </div>
  `;
  if (window.lucide) lucide.createIcons();
}

// -----------------------------------------------------------------
// Tab: Leads (ภายใน Campaign นี้เท่านั้น)
// -----------------------------------------------------------------
function renderLeadsTab() {
  const panel = document.querySelector('[data-tab-panel="leads"]');
  panel.innerHTML = `
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="relative flex-1 max-w-sm">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input id="campaign-leads-search" type="text" placeholder="ค้นหาชื่อบริษัท..." value="${escapeHTML(detailState.leadsSearch)}"
               class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400 outline-none transition" />
      </div>
      <button id="campaign-leads-export" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg px-3 py-2 shrink-0">
        <i data-lucide="download" class="w-4 h-4"></i> Export CSV
      </button>
    </div>

    <div class="bg-white rounded-xl ring-1 ring-slate-100 overflow-hidden hidden md:block">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50">
            <tr class="text-left text-slate-500 text-xs uppercase tracking-wide">
              <th class="px-4 py-3">Company</th>
              <th class="px-4 py-3">Website</th>
              <th class="px-4 py-3">Phone</th>
              <th class="px-4 py-3">จังหวัด</th>
              <th class="px-4 py-3">ประเภทธุรกิจ</th>
              <th class="px-4 py-3">เลขทะเบียนนิติบุคคล</th>
              <th class="px-4 py-3">สถานะ Lead</th>
              <th class="px-4 py-3">ความสมบูรณ์</th>
              <th class="px-4 py-3">วันที่ค้นพบ</th>
              <th class="px-4 py-3 w-16"></th>
            </tr>
          </thead>
          <tbody id="campaign-leads-tbody" class="divide-y divide-slate-50"></tbody>
        </table>
      </div>
    </div>
    <div id="campaign-leads-cardlist" class="md:hidden space-y-3"></div>
  `;
  if (window.lucide) lucide.createIcons();

  document.getElementById("campaign-leads-search").addEventListener("input", debounce((e) => {
    detailState.leadsSearch = e.target.value;
    renderCampaignLeadsTable();
  }, 250));
  document.getElementById("campaign-leads-export").addEventListener("click", () => {
    exportCampaignLeadsToCsv(filteredCampaignLeads());
  });

  renderCampaignLeadsTable();
}

function filteredCampaignLeads() {
  const term = detailState.leadsSearch.trim().toLowerCase();
  if (!term) return detailState.businesses;
  return detailState.businesses.filter((b) => b.company_name_th.toLowerCase().includes(term) || b.company_name_en.toLowerCase().includes(term));
}

function renderCampaignLeadsTable() {
  const items = filteredCampaignLeads();
  const tbody = document.getElementById("campaign-leads-tbody");
  const cardList = document.getElementById("campaign-leads-cardlist");
  if (!tbody) return;

  if (!items.length) {
    tbody.innerHTML = `<tr><td colspan="10">${genericEmptyState("ยังไม่พบ Lead ใน Campaign นี้", "Campaign อาจยังไม่เริ่มประมวลผล หรือยังไม่พบบริษัทที่ตรงเงื่อนไข")}</td></tr>`;
    cardList.innerHTML = genericEmptyState("ยังไม่พบ Lead ใน Campaign นี้");
    if (window.lucide) lucide.createIcons();
    return;
  }

  tbody.innerHTML = items.map((b) => businessRowHtml(b)).join("");
  cardList.innerHTML = items.map((b) => businessCardHtml(b)).join("");
  if (window.lucide) lucide.createIcons();
}

function exportCampaignLeadsToCsv(businesses) {
  if (!businesses.length) { showToast("ไม่มีข้อมูลสำหรับ Export", "warning"); return; }
  const headers = ["Company (TH)", "Company (EN)", "Website", "Phone", "จังหวัด", "ประเภทธุรกิจ", "เลขทะเบียนนิติบุคคล", "สถานะ Lead", "ความสมบูรณ์ของข้อมูล (%)", "วันที่ค้นพบ"];
  const rows = businesses.map((b) => [b.company_name_th, b.company_name_en, b.website || "-", b.phone || "-", b.province, b.business_type, b.registration_number, LEAD_STATUS_META[b.lead_status].label, b.data_completeness, b.created_at]);
  const csv = [headers, ...rows].map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\r\n");
  const blob = new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url; a.download = `campaign-${detailState.campaign.id}-leads-${Date.now()}.csv`; a.click();
  URL.revokeObjectURL(url);
  showToast(`Export ${businesses.length} รายการเรียบร้อยแล้ว`, "success");
}

// -----------------------------------------------------------------
// Tab: Search Criteria (Read-only)
// -----------------------------------------------------------------
function renderCriteriaTab() {
  const c = detailState.campaign;
  const requiredLabels = Object.entries(c.required_fields).filter(([, v]) => v).map(([k]) => REQUIRED_FIELD_LABELS_FALLBACK[k] || k);
  const dupLabel = DUPLICATE_HANDLING_OPTIONS.find((o) => o.value === c.duplicate_handling)?.label || c.duplicate_handling;

  const rows = [
    ["ชื่อ Campaign", c.title],
    ["ประเทศ", "ประเทศไทย"],
    ["จังหวัด", c.target.province],
    ["รัศมีค้นหา", `${c.target.search_radius_km} กิโลเมตร`],
    ["รูปแบบนิติบุคคล", c.business_target.registered_business || "ไม่จำกัดรูปแบบนิติบุคคล"],
    ["ประเภทธุรกิจ", c.business_target.business_type],
    ["จำนวน Lead สูงสุด", formatNumber(c.maximum_leads)],
    ["การจัดการข้อมูลซ้ำ", dupLabel],
  ];

  const panel = document.querySelector('[data-tab-panel="criteria"]');
  panel.innerHTML = `
    <div class="max-w-2xl space-y-3">
      ${rows.map(([label, value]) => `
        <div class="flex items-start justify-between gap-4 text-sm py-2 border-b border-slate-50">
          <span class="text-slate-500 shrink-0">${escapeHTML(label)}</span>
          <span class="text-slate-800 font-medium text-right">${escapeHTML(String(value || "-"))}</span>
        </div>
      `).join("")}
      <div class="pt-2">
        <p class="text-sm text-slate-500 mb-1.5">Keywords</p>
        <div class="flex flex-wrap gap-1.5">
          ${c.business_target.business_keywords.length
            ? c.business_target.business_keywords.map((k) => `<span class="text-xs font-medium bg-indigo-50 text-indigo-700 rounded-full px-2.5 py-1">${escapeHTML(k)}</span>`).join("")
            : '<span class="text-sm text-slate-400">ไม่ระบุ</span>'}
        </div>
      </div>
      <div class="pt-2">
        <p class="text-sm text-slate-500 mb-1">คำอธิบายธุรกิจเป้าหมาย</p>
        <p class="text-sm text-slate-700 whitespace-pre-line">${escapeHTML(c.business_target.description)}</p>
      </div>
      <div class="pt-2">
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

const REQUIRED_FIELD_LABELS_FALLBACK = {
  website: "ต้องมี Website", phone: "ต้องมีเบอร์โทร", email: "ต้องมี Email", address: "ต้องมีที่อยู่",
  registration_number: "ต้องมีเลขทะเบียนนิติบุคคล", directors: "ต้องมีข้อมูลกรรมการ", registered_capital: "ต้องมีข้อมูลทุนจดทะเบียน",
};

// -----------------------------------------------------------------
// Tab: Activity Logs
// -----------------------------------------------------------------
function renderActivityTab() {
  const panel = document.querySelector('[data-tab-panel="activity"]');
  const sorted = [...detailState.campaign.activity_logs].sort((a, b) => new Date(b.at) - new Date(a.at));
  panel.innerHTML = `<div class="max-w-2xl">${activityTimelineHtml(sorted)}</div>`;
  if (window.lucide) lucide.createIcons();
}

/**
 * campaigns.js
 * ควบคุมหน้า Campaign Management: Summary Cards, Search, Filter, Sort,
 * Pagination, Bulk Selection, Action Menu (Start/Pause/Retry/Duplicate/Delete)
 */

const campaignsState = {
  currentUser: null,
  allCampaigns: [],   // หลัง scope filter ตาม account_type
  filtered: [],
  search: "",
  filters: { status: "", province: "", businessType: "", creator: "", dateFrom: "" },
  sort: { column: "created_at", dir: "desc" },
  page: 1,
  pageSize: 10,
  selectedIds: new Set(),
  loading: true,
  error: false,
};

const CAMPAIGN_TABLE_COLUMNS = [
  { key: "checkbox", label: "" },
  { key: "id", label: "Campaign ID", sortable: true },
  { key: "title", label: "ชื่อ Campaign", sortable: true },
  { key: "province", label: "จังหวัด", sortable: false },
  { key: "search_radius_km", label: "รัศมี", sortable: false },
  { key: "business_type", label: "ประเภทธุรกิจ", sortable: false },
  { key: "maximum_leads", label: "Lead เป้าหมาย", sortable: true },
  { key: "leads_found", label: "Lead ที่พบ", sortable: true },
  { key: "status", label: "สถานะ", sortable: true },
  { key: "progress", label: "Progress", sortable: true },
  { key: "created_by", label: "ผู้สร้าง", sortable: false },
  { key: "created_at", label: "วันที่สร้าง", sortable: true },
  { key: "updated_at", label: "อัปเดตล่าสุด", sortable: true },
  { key: "action", label: "" },
];

document.addEventListener("DOMContentLoaded", () => {
  const user = checkAuthentication();
  if (!user) return;
  campaignsState.currentUser = user;

  renderSidebar("campaigns");
  renderTopbar("campaigns", { onSearch: (val) => { campaignsState.search = val; campaignsState.page = 1; applyFiltersAndRender(); } });

  if (hasPermission("campaign.create")) document.getElementById("btn-create-campaign").classList.remove("hidden");

  bindStaticEvents();
  renderSummaryCardsSkeleton();
  campaignsState.loading = true;
  renderTable();

  fetchCampaigns().then(({ campaigns }) => {
    campaignsState.allCampaigns = campaigns;
    campaignsState.scopeIds = new Set(campaigns.map((c) => c.id)); // จำ id ชุดที่ผู้ใช้นี้มีสิทธิ์เห็น ณ ตอนโหลด
    campaignsState.loading = false;
    populateFilterOptions(campaigns);
    renderSummaryCards(campaigns);
    applyFiltersAndRender();
  }).catch(() => {
    campaignsState.loading = false;
    campaignsState.error = true;
    renderTable();
  });
});

/**
 * Mock Service Function — พร้อมเปลี่ยนเป็น GET /api/v1/campaigns ภายหลัง
 * @param {object} params query params เช่น status, province ฯลฯ (ยังไม่ได้ใช้ฝั่ง mock)
 */
async function fetchCampaigns(params = {}) {
  return new Promise((resolve) => {
    setTimeout(() => {
      const stored = loadCampaignsFromStorage();
      const scoped = filterCampaignsByScope(stored, campaignsState.currentUser);
      resolve({ campaigns: scoped, total: scoped.length });
    }, 500); // จำลอง network latency
  });
}

function populateFilterOptions(campaigns) {
  const statusSel = document.getElementById("filter-status");
  CAMPAIGN_STATUSES.forEach((s) => statusSel.insertAdjacentHTML("beforeend", `<option value="${s}">${CAMPAIGN_STATUS_META[s].label}</option>`));

  const provinceSel = document.getElementById("filter-province");
  [...new Set(campaigns.map((c) => c.target.province))].sort().forEach((p) => provinceSel.insertAdjacentHTML("beforeend", `<option value="${p}">${p}</option>`));

  const typeSel = document.getElementById("filter-business-type");
  [...new Set(campaigns.map((c) => c.business_target.business_type))].sort().forEach((t) => typeSel.insertAdjacentHTML("beforeend", `<option value="${t}">${t}</option>`));

  const creatorSel = document.getElementById("filter-creator");
  const creators = [...new Map(campaigns.map((c) => [c.created_by.id, c.created_by])).values()];
  creators.forEach((u) => creatorSel.insertAdjacentHTML("beforeend", `<option value="${u.id}">${u.name}</option>`));
}

function bindStaticEvents() {
  document.getElementById("btn-toggle-filters").addEventListener("click", () => {
    document.getElementById("filters-panel").classList.toggle("hidden");
    document.getElementById("filters-reset-row").classList.toggle("hidden");
  });

  const filterIdMap = { "filter-status": "status", "filter-province": "province", "filter-business-type": "businessType", "filter-creator": "creator", "filter-date-from": "dateFrom" };
  Object.keys(filterIdMap).forEach((id) => {
    document.getElementById(id).addEventListener("change", (e) => {
      campaignsState.filters[filterIdMap[id]] = e.target.value;
      campaignsState.page = 1;
      applyFiltersAndRender();
    });
  });

  document.getElementById("btn-reset-filters").addEventListener("click", () => {
    campaignsState.filters = { status: "", province: "", businessType: "", creator: "", dateFrom: "" };
    Object.keys(filterIdMap).forEach((id) => document.getElementById(id).value = "");
    campaignsState.page = 1;
    applyFiltersAndRender();
  });

  document.getElementById("page-size-select").addEventListener("change", (e) => {
    campaignsState.pageSize = parseInt(e.target.value, 10);
    campaignsState.page = 1;
    renderTable();
  });

  document.getElementById("btn-refresh").addEventListener("click", () => {
    campaignsState.loading = true;
    campaignsState.selectedIds.clear();
    renderTable();
    fetchCampaigns().then(({ campaigns }) => {
      campaignsState.allCampaigns = campaigns;
      campaignsState.loading = false;
      renderSummaryCards(campaigns);
      applyFiltersAndRender();
      showToast("รีเฟรชข้อมูล Campaign เรียบร้อยแล้ว", "success");
    });
  });

  document.getElementById("btn-export").addEventListener("click", () => exportCampaignsToCsv(campaignsState.filtered));
  document.getElementById("bulk-export").addEventListener("click", () => {
    const selected = campaignsState.filtered.filter((c) => campaignsState.selectedIds.has(c.id));
    exportCampaignsToCsv(selected);
  });
  document.getElementById("bulk-delete").addEventListener("click", () => {
    if (!hasPermission("campaign.delete")) { showToast("คุณไม่มีสิทธิ์ลบ Campaign", "error"); return; }
    showConfirmModal({
      title: `ลบ Campaign ที่เลือกไว้ ${campaignsState.selectedIds.size} รายการ?`,
      message: "การลบไม่สามารถย้อนกลับได้ กรุณายืนยันอีกครั้ง",
      danger: true, confirmText: "ยืนยันลบ",
      onConfirm: () => {
        campaignsState.allCampaigns = campaignsState.allCampaigns.filter((c) => !campaignsState.selectedIds.has(c.id));
        persistCampaigns();
        campaignsState.selectedIds.clear();
        applyFiltersAndRender();
        showToast("ลบ Campaign ที่เลือกเรียบร้อยแล้ว", "success");
      },
    });
  });
}

// -----------------------------------------------------------------
// Filtering + Searching + Sorting
// -----------------------------------------------------------------
function applyFiltersAndRender() {
  const { search, filters, sort } = campaignsState;
  const term = search.trim().toLowerCase();

  let result = campaignsState.allCampaigns.filter((c) => {
    if (term && !c.title.toLowerCase().includes(term) && !c.id.toLowerCase().includes(term)) return false;
    if (filters.status && c.status !== filters.status) return false;
    if (filters.province && c.target.province !== filters.province) return false;
    if (filters.businessType && c.business_target.business_type !== filters.businessType) return false;
    if (filters.creator && String(c.created_by.id) !== filters.creator) return false;
    if (filters.dateFrom && c.created_at.slice(0, 10) < filters.dateFrom) return false;
    return true;
  });

  result.sort((a, b) => {
    const getVal = (c) => {
      if (sort.column === "province") return c.target.province;
      return c[sort.column];
    };
    let va = getVal(a), vb = getVal(b);
    if (va === undefined) va = "";
    if (vb === undefined) vb = "";
    if (typeof va === "string") { va = va.toLowerCase(); vb = String(vb).toLowerCase(); }
    if (va < vb) return sort.dir === "asc" ? -1 : 1;
    if (va > vb) return sort.dir === "asc" ? 1 : -1;
    return 0;
  });

  campaignsState.filtered = result;
  updateActiveFilterBadge();
  renderTable();
}

function updateActiveFilterBadge() {
  const count = Object.values(campaignsState.filters).filter(Boolean).length;
  const badge = document.getElementById("active-filter-badge");
  if (count > 0) { badge.textContent = count; badge.classList.remove("hidden"); } else { badge.classList.add("hidden"); }
}

function sortCampaignsByColumn(column) {
  if (campaignsState.sort.column === column) campaignsState.sort.dir = campaignsState.sort.dir === "asc" ? "desc" : "asc";
  else { campaignsState.sort.column = column; campaignsState.sort.dir = "asc"; }
  applyFiltersAndRender();
}

// -----------------------------------------------------------------
// Persistence (mock) — TODO: แทนที่ด้วย PUT/DELETE /api/v1/campaigns/{id}
// -----------------------------------------------------------------
function persistCampaigns() {
  // Merge กลับกับข้อมูลทั้งหมด โดยใช้ scopeIds ที่จำไว้ตอนโหลด (คงที่ ไม่ถูกกระทบจากการลบ/แก้ไขระหว่าง session)
  const everyone = loadCampaignsFromStorage();
  const scopeIds = campaignsState.scopeIds || new Set(campaignsState.allCampaigns.map((c) => c.id));
  const currentById = new Map(campaignsState.allCampaigns.map((c) => [c.id, c]));

  const merged = everyone
    .filter((c) => !scopeIds.has(c.id) || currentById.has(c.id)) // ตัดออกถ้าอยู่ใน scope เดิมแต่ถูกลบไปแล้ว
    .map((c) => currentById.get(c.id) || c);

  // เพิ่ม Campaign ใหม่ที่ยังไม่เคยอยู่ใน storage (เช่นเพิ่งสร้าง/duplicate)
  campaignsState.allCampaigns.forEach((c) => { if (!merged.some((m) => m.id === c.id)) merged.push(c); });
  saveCampaignsToStorage(merged);
}

// -----------------------------------------------------------------
// Summary Cards
// -----------------------------------------------------------------
function renderSummaryCardsSkeleton() {
  document.getElementById("campaign-summary-cards").innerHTML = Array.from({ length: 4 }).map(() => `
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
      <div class="skeleton w-9 h-9 rounded-lg mb-3"></div>
      <div class="skeleton h-6 w-16 rounded mb-2"></div>
      <div class="skeleton h-3 w-24 rounded"></div>
    </div>
  `).join("");
}

function renderSummaryCards(campaigns) {
  const total = campaigns.length;
  const processing = campaigns.filter((c) => c.status === "processing" || c.status === "queued").length;
  const completed = campaigns.filter((c) => c.status === "completed" || c.status === "partially_completed").length;
  const totalLeads = campaigns.reduce((sum, c) => sum + c.leads_found, 0);

  const cards = [
    { label: "Campaign ทั้งหมด", value: total, icon: "target", color: "indigo", trend: "+12.5%", up: true },
    { label: "กำลังประมวลผล", value: processing, icon: "loader-circle", color: "amber", trend: "+2", up: true },
    { label: "เสร็จแล้ว", value: completed, icon: "check-circle-2", color: "emerald", trend: "+8.1%", up: true },
    { label: "Lead ที่ค้นพบทั้งหมด", value: totalLeads, icon: "users", color: "sky", trend: "+18.4%", up: true },
  ];
  const colorMap = { indigo: "bg-indigo-50 text-indigo-600", amber: "bg-amber-50 text-amber-600", emerald: "bg-emerald-50 text-emerald-600", sky: "bg-sky-50 text-sky-600" };

  document.getElementById("campaign-summary-cards").innerHTML = cards.map((c) => `
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4 hover:shadow-md transition-shadow">
      <div class="w-9 h-9 rounded-lg ${colorMap[c.color]} flex items-center justify-center mb-3">
        <i data-lucide="${c.icon}" class="w-5 h-5"></i>
      </div>
      <p class="text-2xl font-bold text-slate-800">${formatNumber(c.value)}</p>
      <div class="flex items-center justify-between mt-1">
        <p class="text-xs text-slate-500">${c.label}</p>
        <span class="text-xs font-medium ${c.up ? "text-emerald-600" : "text-rose-500"} flex items-center gap-0.5 shrink-0">
          <i data-lucide="${c.up ? "arrow-up-right" : "arrow-down-right"}" class="w-3 h-3"></i>${c.trend}
        </span>
      </div>
    </div>
  `).join("");
  if (window.lucide) lucide.createIcons();
}

// -----------------------------------------------------------------
// Table rendering
// -----------------------------------------------------------------
function renderTableHead() {
  const row = document.getElementById("table-head-row");
  row.innerHTML = CAMPAIGN_TABLE_COLUMNS.map((col) => {
    if (col.key === "checkbox") return `<th class="px-4 py-3 w-10"><input type="checkbox" id="select-all-checkbox" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" /></th>`;
    if (col.key === "action") return `<th class="px-4 py-3 w-10"></th>`;
    const isSorted = campaignsState.sort.column === col.key;
    const icon = isSorted ? (campaignsState.sort.dir === "asc" ? "arrow-up" : "arrow-down") : "arrow-up-down";
    return `
      <th class="px-4 py-3 whitespace-nowrap ${col.sortable ? "cursor-pointer select-none hover:text-slate-700" : ""}" ${col.sortable ? `data-sort-col="${col.key}"` : ""}>
        <span class="inline-flex items-center gap-1">${col.label}${col.sortable ? `<i data-lucide="${icon}" class="w-3 h-3 ${isSorted ? "text-indigo-600" : "text-slate-300"}"></i>` : ""}</span>
      </th>`;
  }).join("");
  row.querySelectorAll("[data-sort-col]").forEach((th) => th.addEventListener("click", () => sortCampaignsByColumn(th.dataset.sortCol)));
  const selectAll = document.getElementById("select-all-checkbox");
  if (selectAll) selectAll.addEventListener("change", (e) => toggleSelectAllOnPage(e.target.checked));
  if (window.lucide) lucide.createIcons();
}

function currentPageItems() {
  const start = (campaignsState.page - 1) * campaignsState.pageSize;
  return campaignsState.filtered.slice(start, start + campaignsState.pageSize);
}

function renderTable() {
  renderTableHead();
  const tbody = document.getElementById("campaigns-table-body");
  const cardList = document.getElementById("campaigns-card-list");
  document.getElementById("campaigns-total-count").textContent = formatNumber(campaignsState.allCampaigns.length);

  if (campaignsState.loading) {
    tbody.innerHTML = skeletonRows(CAMPAIGN_TABLE_COLUMNS.length, 6);
    cardList.innerHTML = Array.from({ length: 3 }).map(() => `<div class="bg-white rounded-xl ring-1 ring-slate-100 p-4"><div class="skeleton h-4 w-2/3 rounded mb-2"></div><div class="skeleton h-3 w-1/2 rounded"></div></div>`).join("");
    renderPagination();
    return;
  }
  if (campaignsState.error) {
    tbody.innerHTML = `<tr><td colspan="${CAMPAIGN_TABLE_COLUMNS.length}">${genericErrorState("เกิดข้อผิดพลาดในการโหลดข้อมูล Campaign")}</td></tr>`;
    cardList.innerHTML = genericErrorState("เกิดข้อผิดพลาดในการโหลดข้อมูล Campaign");
    if (window.lucide) lucide.createIcons();
    renderPagination();
    return;
  }

  const items = currentPageItems();
  if (!items.length) {
    tbody.innerHTML = `<tr><td colspan="${CAMPAIGN_TABLE_COLUMNS.length}">${genericEmptyState("ไม่พบ Campaign ที่ตรงกับเงื่อนไข", "ลองปรับคำค้นหาหรือรีเซ็ตตัวกรอง")}</td></tr>`;
    cardList.innerHTML = genericEmptyState("ไม่พบ Campaign ที่ตรงกับเงื่อนไข", "ลองปรับคำค้นหาหรือรีเซ็ตตัวกรอง");
    if (window.lucide) lucide.createIcons();
    renderPagination();
    updateBulkBar();
    return;
  }

  tbody.innerHTML = items.map(campaignRowHtml).join("");
  cardList.innerHTML = items.map(campaignCardHtml).join("");
  if (window.lucide) lucide.createIcons();
  bindRowEvents();
  renderPagination();
  updateBulkBar();
}

function campaignRowHtml(c) {
  const checked = campaignsState.selectedIds.has(c.id);
  return `
    <tr class="campaign-row transition-colors" data-campaign-id="${c.id}">
      <td class="px-4 py-3"><input type="checkbox" class="row-checkbox w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" data-id="${c.id}" ${checked ? "checked" : ""} /></td>
      <td class="px-4 py-3 font-medium text-slate-700 whitespace-nowrap">${c.id}</td>
      <td class="px-4 py-3 max-w-[220px]">
        <a href="campaign-detail.html?id=${c.id}" class="font-medium text-slate-800 hover:text-indigo-600 truncate block" title="${escapeHTML(c.title)}">${escapeHTML(c.title)}</a>
      </td>
      <td class="px-4 py-3 whitespace-nowrap">${escapeHTML(c.target.province)}</td>
      <td class="px-4 py-3 whitespace-nowrap text-slate-500">${c.target.search_radius_km} กม.</td>
      <td class="px-4 py-3 whitespace-nowrap">${escapeHTML(c.business_target.business_type)}</td>
      <td class="px-4 py-3 whitespace-nowrap text-slate-500">${formatNumber(c.maximum_leads)}</td>
      <td class="px-4 py-3 whitespace-nowrap font-medium text-slate-700">${formatNumber(c.leads_found)}</td>
      <td class="px-4 py-3 whitespace-nowrap">${campaignStatusBadge(c.status)}</td>
      <td class="px-4 py-3 whitespace-nowrap w-40">${progressBarHtml(c.progress)}</td>
      <td class="px-4 py-3 whitespace-nowrap text-slate-500">${escapeHTML(c.created_by.name)}</td>
      <td class="px-4 py-3 whitespace-nowrap text-slate-500">${formatThaiDate(c.created_at)}</td>
      <td class="px-4 py-3 whitespace-nowrap text-slate-500">${formatThaiDate(c.updated_at)}</td>
      <td class="px-4 py-3 text-right relative">${campaignActionMenuHtml(c)}</td>
    </tr>
  `;
}

function campaignCardHtml(c) {
  const checked = campaignsState.selectedIds.has(c.id);
  return `
    <div class="bg-white rounded-xl ring-1 ring-slate-100 shadow-sm p-4" data-campaign-id="${c.id}">
      <div class="flex items-start gap-3">
        <input type="checkbox" class="row-checkbox mt-1 w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" data-id="${c.id}" ${checked ? "checked" : ""} />
        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-2">
            <a href="campaign-detail.html?id=${c.id}" class="font-medium text-slate-800 truncate">${escapeHTML(c.title)}</a>
            <div class="relative shrink-0">${campaignActionMenuHtml(c)}</div>
          </div>
          <p class="text-xs text-slate-400 mt-0.5">${c.id} · ${escapeHTML(c.target.province)}</p>
          <div class="mt-2">${campaignStatusBadge(c.status)}</div>
          <div class="mt-3">${progressBarHtml(c.progress, "Progress")}</div>
          <div class="grid grid-cols-2 gap-y-1 mt-3 text-xs text-slate-500">
            <span><i data-lucide="users" class="w-3 h-3 inline mr-1"></i>${c.leads_found}/${c.maximum_leads} Lead</span>
            <span><i data-lucide="user" class="w-3 h-3 inline mr-1"></i>${escapeHTML(c.created_by.name)}</span>
            <span><i data-lucide="calendar" class="w-3 h-3 inline mr-1"></i>${formatThaiDate(c.created_at)}</span>
            <span><i data-lucide="building-2" class="w-3 h-3 inline mr-1"></i>${escapeHTML(c.business_target.business_type)}</span>
          </div>
        </div>
      </div>
    </div>
  `;
}

function campaignActionMenuHtml(c) {
  const canEdit = hasPermission("campaign.edit") && (c.status === "draft" || c.status === "queued");
  const canStart = hasPermission("campaign.process") && (c.status === "draft" || c.status === "queued");
  const canPause = hasPermission("campaign.pause") && c.status === "processing";
  const canRetry = hasPermission("campaign.retry") && (c.status === "failed" || c.status === "cancelled");
  const canDuplicate = hasPermission("campaign.duplicate");
  const canDelete = hasPermission("campaign.delete");

  return `
    <button class="action-menu-btn text-slate-400 hover:text-slate-600 p-1 rounded hover:bg-slate-100" data-menu-toggle="${c.id}" aria-label="เมนู Action">
      <i data-lucide="more-vertical" class="w-4 h-4"></i>
    </button>
    <div class="action-menu hidden absolute right-0 mt-1 w-52 bg-white rounded-lg shadow-lg ring-1 ring-slate-200 py-1 z-30 text-left" data-menu="${c.id}">
      <a href="campaign-detail.html?id=${c.id}" class="menu-item w-full flex items-center gap-2 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50"><i data-lucide="eye" class="w-4 h-4"></i>ดูรายละเอียด Campaign</a>
      ${c.leads_found > 0 ? `<a href="campaign-detail.html?id=${c.id}&tab=leads" class="menu-item w-full flex items-center gap-2 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50"><i data-lucide="list-checks" class="w-4 h-4"></i>ดูผลลัพธ์</a>` : ""}
      ${canEdit ? `<a href="campaign-create.html?edit=${c.id}" class="menu-item w-full flex items-center gap-2 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50"><i data-lucide="pencil" class="w-4 h-4"></i>แก้ไข</a>` : ""}
      ${canDuplicate ? `<button class="menu-item w-full flex items-center gap-2 px-3.5 py-2 text-sm text-slate-600 hover:bg-slate-50" data-action="duplicate" data-id="${c.id}"><i data-lucide="copy" class="w-4 h-4"></i>Duplicate Campaign</button>` : ""}
      ${canStart ? `<button class="menu-item w-full flex items-center gap-2 px-3.5 py-2 text-sm text-emerald-600 hover:bg-emerald-50" data-action="start" data-id="${c.id}"><i data-lucide="play" class="w-4 h-4"></i>เริ่มประมวลผล</button>` : ""}
      ${canPause ? `<button class="menu-item w-full flex items-center gap-2 px-3.5 py-2 text-sm text-amber-600 hover:bg-amber-50" data-action="pause" data-id="${c.id}"><i data-lucide="pause" class="w-4 h-4"></i>หยุด Campaign</button>` : ""}
      ${canRetry ? `<button class="menu-item w-full flex items-center gap-2 px-3.5 py-2 text-sm text-indigo-600 hover:bg-indigo-50" data-action="retry" data-id="${c.id}"><i data-lucide="rotate-ccw" class="w-4 h-4"></i>ลองประมวลผลใหม่</button>` : ""}
      ${canDelete ? `<button class="menu-item w-full flex items-center gap-2 px-3.5 py-2 text-sm text-rose-600 hover:bg-rose-50" data-action="delete" data-id="${c.id}"><i data-lucide="trash-2" class="w-4 h-4"></i>ลบ</button>` : ""}
    </div>
  `;
}

function bindRowEvents() {
  document.querySelectorAll(".row-checkbox").forEach((cb) => cb.addEventListener("change", (e) => {
    const id = e.target.dataset.id;
    if (e.target.checked) campaignsState.selectedIds.add(id); else campaignsState.selectedIds.delete(id);
    document.querySelectorAll(`.row-checkbox[data-id="${id}"]`).forEach((el) => el.checked = e.target.checked);
    updateBulkBar();
  }));

  document.querySelectorAll("[data-menu-toggle]").forEach((btn) => btn.addEventListener("click", (e) => {
    e.stopPropagation();
    const id = btn.dataset.menuToggle;
    document.querySelectorAll(".action-menu").forEach((m) => { if (m.dataset.menu !== id) m.classList.add("hidden"); });
    document.querySelector(`.action-menu[data-menu="${id}"]`).classList.toggle("hidden");
  }));

  document.querySelectorAll(".menu-item[data-action]").forEach((btn) => btn.addEventListener("click", (e) => {
    e.stopPropagation();
    handleCampaignAction(btn.dataset.action, btn.dataset.id);
    document.querySelectorAll(".action-menu").forEach((m) => m.classList.add("hidden"));
  }));
}

document.addEventListener("click", () => document.querySelectorAll(".action-menu").forEach((m) => m.classList.add("hidden")));

function toggleSelectAllOnPage(checked) {
  currentPageItems().forEach((c) => { if (checked) campaignsState.selectedIds.add(c.id); else campaignsState.selectedIds.delete(c.id); });
  renderTable();
}

function updateBulkBar() {
  const bar = document.getElementById("bulk-bar");
  const count = campaignsState.selectedIds.size;
  document.getElementById("bulk-count").textContent = count;
  bar.classList.toggle("hidden", count === 0);
  bar.classList.toggle("flex", count > 0);
  document.getElementById("bulk-delete").classList.toggle("hidden", !hasPermission("campaign.delete"));
}

// -----------------------------------------------------------------
// Pagination
// -----------------------------------------------------------------
function renderPagination() {
  const total = campaignsState.filtered.length;
  const totalPages = Math.max(1, Math.ceil(total / campaignsState.pageSize));
  if (campaignsState.page > totalPages) campaignsState.page = totalPages;

  const start = total === 0 ? 0 : (campaignsState.page - 1) * campaignsState.pageSize + 1;
  const end = Math.min(total, campaignsState.page * campaignsState.pageSize);
  document.getElementById("pagination-info").textContent = `${start}-${end} จาก ${formatNumber(total)} รายการ`;

  const controls = document.getElementById("pagination-controls");
  const pages = [];
  const maxButtons = 5;
  let from = Math.max(1, campaignsState.page - Math.floor(maxButtons / 2));
  let to = Math.min(totalPages, from + maxButtons - 1);
  from = Math.max(1, to - maxButtons + 1);
  for (let p = from; p <= to; p++) pages.push(p);

  const btnClass = (active) => `w-8 h-8 rounded-lg text-sm font-medium ${active ? "bg-indigo-600 text-white" : "text-slate-600 hover:bg-slate-100"}`;
  controls.innerHTML = `
    <button id="page-prev" class="${btnClass(false)}" ${campaignsState.page === 1 ? "disabled" : ""} aria-label="ก่อนหน้า"><i data-lucide="chevron-left" class="w-4 h-4 mx-auto"></i></button>
    ${pages.map((p) => `<button class="page-num-btn ${btnClass(p === campaignsState.page)}" data-page="${p}">${p}</button>`).join("")}
    <button id="page-next" class="${btnClass(false)}" ${campaignsState.page === totalPages ? "disabled" : ""} aria-label="ถัดไป"><i data-lucide="chevron-right" class="w-4 h-4 mx-auto"></i></button>
  `;
  if (window.lucide) lucide.createIcons();
  document.getElementById("page-prev").addEventListener("click", () => { if (campaignsState.page > 1) { campaignsState.page--; renderTable(); } });
  document.getElementById("page-next").addEventListener("click", () => { if (campaignsState.page < totalPages) { campaignsState.page++; renderTable(); } });
  controls.querySelectorAll(".page-num-btn").forEach((btn) => btn.addEventListener("click", () => { campaignsState.page = parseInt(btn.dataset.page, 10); renderTable(); }));
}

// -----------------------------------------------------------------
// Action handlers
// -----------------------------------------------------------------
function handleCampaignAction(action, id) {
  const campaign = campaignsState.allCampaigns.find((c) => c.id === id);
  if (!campaign) return;

  switch (action) {
    case "duplicate":
      if (!hasPermission("campaign.duplicate")) { showToast("คุณไม่มีสิทธิ์ Duplicate Campaign", "error"); return; }
      duplicateCampaign(campaign);
      break;
    case "start":
      if (!hasPermission("campaign.process")) { showToast("คุณไม่มีสิทธิ์เริ่มประมวลผล Campaign", "error"); return; }
      showConfirmModal({
        title: "ยืนยันการเริ่มประมวลผล Campaign?",
        message: "ระบบจะเริ่มค้นหาและประมวลผล Lead ตามเงื่อนไขที่กำหนด",
        confirmText: "ยืนยันและเริ่มค้นหา",
        onConfirm: () => {
          campaign.status = "queued";
          campaign.progress = 0;
          campaign.updated_at = "2026-07-17 12:00:00";
          persistCampaigns();
          applyFiltersAndRender();
          showToast(`เริ่มประมวลผล Campaign "${campaign.title}" แล้ว`, "success");
        },
      });
      break;
    case "pause":
      if (!hasPermission("campaign.pause")) { showToast("คุณไม่มีสิทธิ์หยุด Campaign", "error"); return; }
      campaign.status = "partially_completed";
      campaign.updated_at = "2026-07-17 12:00:00";
      persistCampaigns();
      applyFiltersAndRender();
      showToast(`หยุด Campaign "${campaign.title}" ชั่วคราวแล้ว`, "success");
      break;
    case "retry":
      if (!hasPermission("campaign.retry")) { showToast("คุณไม่มีสิทธิ์ลองประมวลผลใหม่", "error"); return; }
      campaign.status = "queued";
      campaign.progress = 0;
      campaign.updated_at = "2026-07-17 12:00:00";
      persistCampaigns();
      applyFiltersAndRender();
      showToast(`นำ Campaign "${campaign.title}" เข้าคิวประมวลผลใหม่แล้ว`, "success");
      break;
    case "delete":
      if (!hasPermission("campaign.delete")) { showToast("คุณไม่มีสิทธิ์ลบ Campaign", "error"); return; }
      showConfirmModal({
        title: `ลบ Campaign "${campaign.title}"?`,
        message: "การลบไม่สามารถย้อนกลับได้ กรุณายืนยันอีกครั้ง",
        danger: true, confirmText: "ยืนยันลบ",
        onConfirm: () => {
          campaignsState.allCampaigns = campaignsState.allCampaigns.filter((c) => c.id !== id);
          campaignsState.selectedIds.delete(id);
          persistCampaigns();
          applyFiltersAndRender();
          showToast("ลบ Campaign เรียบร้อยแล้ว", "success");
        },
      });
      break;
  }
}

function duplicateCampaign(campaign) {
  const copy = JSON.parse(JSON.stringify(campaign));
  copy.id = generateSequentialId("CMP", campaignsState.allCampaigns);
  copy.title = `${campaign.title} (สำเนา)`;
  copy.status = "draft";
  copy.progress = 0;
  copy.leads_found = 0;
  copy.created_at = "2026-07-17 12:00:00";
  copy.updated_at = "2026-07-17 12:00:00";
  copy.created_by = { id: campaignsState.currentUser.id, name: campaignsState.currentUser.name, account_type: campaignsState.currentUser.account_type };
  copy.pipeline = buildCampaignPipeline("draft", 0, Math.random);
  copy.activity_logs = [{ icon: "copy", text: `Duplicate จาก ${campaign.id}`, user: campaignsState.currentUser.name, at: copy.created_at, source: "manual", status: "completed" }];

  campaignsState.allCampaigns.unshift(copy);
  persistCampaigns();
  applyFiltersAndRender();
  showToast(`Duplicate Campaign สำเร็จ — สร้าง "${copy.id}" เป็น Draft แล้ว`, "success");
}

// -----------------------------------------------------------------
// CSV Export
// -----------------------------------------------------------------
function exportCampaignsToCsv(campaigns) {
  if (!campaigns.length) { showToast("ไม่มีข้อมูลสำหรับ Export", "warning"); return; }
  const headers = ["Campaign ID", "ชื่อ Campaign", "จังหวัด", "ประเภทธุรกิจ", "Lead เป้าหมาย", "Lead ที่พบ", "สถานะ", "Progress", "ผู้สร้าง", "วันที่สร้าง"];
  const rows = campaigns.map((c) => [c.id, c.title, c.target.province, c.business_target.business_type, c.maximum_leads, c.leads_found, CAMPAIGN_STATUS_META[c.status].label, `${c.progress}%`, c.created_by.name, c.created_at]);
  const csv = [headers, ...rows].map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\r\n");
  const blob = new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url; a.download = `campaigns-export-${Date.now()}.csv`; a.click();
  URL.revokeObjectURL(url);
  showToast(`Export ${campaigns.length} รายการเรียบร้อยแล้ว`, "success");
}

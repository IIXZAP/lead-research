/**
 * leads.js
 * ควบคุมหน้า Leads (Directory รวมบริษัท/Lead จากทุก Campaign): Search, Filter,
 * Sort, Pagination, Export CSV
 *
 * หมายเหตุ: businessRowHtml() / businessCardHtml() ถูกออกแบบให้ใช้ซ้ำได้จาก
 * campaign-detail.js (Tab "Leads" ภายใน Campaign เดียว) เพื่อไม่ต้องเขียนโค้ด
 * render ตารางซ้ำสองที่
 */

const leadsState = {
  currentUser: null,
  allBusinesses: [],   // หลัง scope filter ตาม account_type
  filtered: [],
  search: "",
  filters: { province: "", businessType: "", leadStatus: "", hasWebsite: "", hasPhone: "", hasEmail: "" },
  sort: { column: "created_at", dir: "desc" },
  page: 1,
  pageSize: 20,
  loading: true,
  error: false,
};

const LEADS_TABLE_COLUMNS = [
  { key: "company_name_th", label: "Company", sortable: true },
  { key: "website", label: "Website", sortable: false },
  { key: "phone", label: "Phone", sortable: false },
  { key: "province", label: "จังหวัด", sortable: true },
  { key: "business_type", label: "ประเภทธุรกิจ", sortable: false },
  { key: "registration_number", label: "เลขทะเบียนนิติบุคคล", sortable: false },
  { key: "lead_status", label: "สถานะ Lead", sortable: true },
  { key: "data_completeness", label: "ความสมบูรณ์", sortable: true },
  { key: "created_at", label: "วันที่ค้นพบ", sortable: true },
  { key: "view", label: "" },
];

document.addEventListener("DOMContentLoaded", () => {
  const user = checkAuthentication();
  if (!user) return;
  leadsState.currentUser = user;

  // [Laravel] Sidebar/Topbar ถูก render ฝั่ง Server แล้ว (partials/sidebar.blade.php, partials/topbar.blade.php)
  // จึงไม่ต้องเรียก renderSidebar()/renderTopbar() — รับค่าค้นหาจาก Topbar ผ่าน hook กลางแทน options.onSearch เดิม
  window.onTopbarSearch = (val) => { leadsState.search = val; leadsState.page = 1; applyFiltersAndRender(); };

  bindStaticEvents();
  renderSummaryCardsSkeleton();
  leadsState.loading = true;
  renderTable();

  fetchLeadsList().then(({ businesses }) => {
    leadsState.allBusinesses = businesses;
    leadsState.loading = false;
    populateFilterOptions(businesses);
    renderSummaryCards(businesses);
    applyFiltersAndRender();
  }).catch(() => {
    leadsState.loading = false;
    leadsState.error = true;
    renderTable();
  });
});

/**
 * Mock Service Function — พร้อมเปลี่ยนเป็น GET /api/v1/leads ภายหลัง
 * (รวมข้อมูลบริษัท/Lead จากทุก Campaign ที่ผู้ใช้ปัจจุบันมีสิทธิ์เห็น)
 */
async function fetchLeadsList(params = {}) {
  return new Promise((resolve) => {
    setTimeout(() => {
      const businesses = loadBusinessesFromStorage();
      const scoped = filterBusinessesByScope(businesses, leadsState.currentUser);
      resolve({ businesses: scoped, total: scoped.length });
    }, 500);
  });
}

function populateFilterOptions(businesses) {
  const provinceSel = document.getElementById("filter-province");
  [...new Set(businesses.map((b) => b.province))].sort().forEach((p) => provinceSel.insertAdjacentHTML("beforeend", `<option value="${p}">${p}</option>`));

  const typeSel = document.getElementById("filter-business-type");
  [...new Set(businesses.map((b) => b.business_type))].sort().forEach((t) => typeSel.insertAdjacentHTML("beforeend", `<option value="${t}">${t}</option>`));

  const statusSel = document.getElementById("filter-lead-status");
  LEAD_STATUSES.forEach((s) => statusSel.insertAdjacentHTML("beforeend", `<option value="${s}">${LEAD_STATUS_META[s].label}</option>`));
}

function bindStaticEvents() {
  document.getElementById("btn-toggle-filters").addEventListener("click", () => {
    document.getElementById("filters-panel").classList.toggle("hidden");
    document.getElementById("filters-reset-row").classList.toggle("hidden");
  });

  const filterIdMap = {
    "filter-province": "province", "filter-business-type": "businessType", "filter-lead-status": "leadStatus",
    "filter-has-website": "hasWebsite", "filter-has-phone": "hasPhone", "filter-has-email": "hasEmail",
  };
  Object.keys(filterIdMap).forEach((id) => {
    document.getElementById(id).addEventListener("change", (e) => {
      leadsState.filters[filterIdMap[id]] = e.target.value;
      leadsState.page = 1;
      applyFiltersAndRender();
    });
  });

  document.getElementById("btn-reset-filters").addEventListener("click", () => {
    leadsState.filters = { province: "", businessType: "", leadStatus: "", hasWebsite: "", hasPhone: "", hasEmail: "" };
    Object.keys(filterIdMap).forEach((id) => document.getElementById(id).value = "");
    leadsState.page = 1;
    applyFiltersAndRender();
  });

  document.getElementById("page-size-select").addEventListener("change", (e) => {
    leadsState.pageSize = parseInt(e.target.value, 10);
    leadsState.page = 1;
    renderTable();
  });

  document.getElementById("btn-export").addEventListener("click", () => exportLeadsToCsv(leadsState.filtered));
}

// -----------------------------------------------------------------
// Summary Cards
// -----------------------------------------------------------------
function renderSummaryCardsSkeleton() {
  document.getElementById("lead-summary-cards").innerHTML = Array.from({ length: 4 }).map(() => `
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
      <div class="skeleton w-9 h-9 rounded-lg mb-3"></div>
      <div class="skeleton h-6 w-16 rounded mb-2"></div>
      <div class="skeleton h-3 w-24 rounded"></div>
    </div>
  `).join("");
}

function renderSummaryCards(businesses) {
  const total = businesses.length;
  const withWebsite = businesses.filter((b) => b.website).length;
  const withPhone = businesses.filter((b) => b.phone).length;
  const avgCompleteness = total ? Math.round(businesses.reduce((s, b) => s + b.data_completeness, 0) / total) : 0;

  const cards = [
    { label: "Lead ทั้งหมด", value: total, icon: "users", color: "indigo" },
    { label: "มี Website", value: withWebsite, icon: "globe", color: "sky" },
    { label: "มีเบอร์โทร", value: withPhone, icon: "phone", color: "emerald" },
    { label: "ความสมบูรณ์เฉลี่ย", value: `${avgCompleteness}%`, icon: "gauge", color: "amber" },
  ];
  const colorMap = { indigo: "bg-indigo-50 text-indigo-600", sky: "bg-sky-50 text-sky-600", emerald: "bg-emerald-50 text-emerald-600", amber: "bg-amber-50 text-amber-600" };

  document.getElementById("lead-summary-cards").innerHTML = cards.map((c) => `
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
      <div class="w-9 h-9 rounded-lg ${colorMap[c.color]} flex items-center justify-center mb-3">
        <i data-lucide="${c.icon}" class="w-5 h-5"></i>
      </div>
      <p class="text-2xl font-bold text-slate-800">${typeof c.value === "number" ? formatNumber(c.value) : c.value}</p>
      <p class="text-xs text-slate-500 mt-1">${c.label}</p>
    </div>
  `).join("");
  if (window.lucide) lucide.createIcons();
}

// -----------------------------------------------------------------
// Filtering + Searching + Sorting
// -----------------------------------------------------------------
function applyFiltersAndRender() {
  const { search, filters, sort } = leadsState;
  const term = search.trim().toLowerCase();

  let result = leadsState.allBusinesses.filter((b) => {
    if (term && !b.company_name_th.toLowerCase().includes(term) && !b.company_name_en.toLowerCase().includes(term)) return false;
    if (filters.province && b.province !== filters.province) return false;
    if (filters.businessType && b.business_type !== filters.businessType) return false;
    if (filters.leadStatus && b.lead_status !== filters.leadStatus) return false;
    if (filters.hasWebsite === "yes" && !b.website) return false;
    if (filters.hasWebsite === "no" && b.website) return false;
    if (filters.hasPhone === "yes" && !b.phone) return false;
    if (filters.hasPhone === "no" && b.phone) return false;
    if (filters.hasEmail === "yes" && !b.email) return false;
    if (filters.hasEmail === "no" && b.email) return false;
    return true;
  });

  result.sort((a, b) => {
    let va = a[sort.column], vb = b[sort.column];
    if (va === undefined) va = "";
    if (vb === undefined) vb = "";
    if (typeof va === "string") { va = va.toLowerCase(); vb = String(vb).toLowerCase(); }
    if (va < vb) return sort.dir === "asc" ? -1 : 1;
    if (va > vb) return sort.dir === "asc" ? 1 : -1;
    return 0;
  });

  leadsState.filtered = result;
  updateActiveFilterBadge();
  renderTable();
}

function updateActiveFilterBadge() {
  const count = Object.values(leadsState.filters).filter(Boolean).length;
  const badge = document.getElementById("active-filter-badge");
  if (count > 0) { badge.textContent = count; badge.classList.remove("hidden"); } else { badge.classList.add("hidden"); }
}

function sortLeadsByColumn(column) {
  if (leadsState.sort.column === column) leadsState.sort.dir = leadsState.sort.dir === "asc" ? "desc" : "asc";
  else { leadsState.sort.column = column; leadsState.sort.dir = "asc"; }
  applyFiltersAndRender();
}

// -----------------------------------------------------------------
// Table rendering
// -----------------------------------------------------------------
function renderTableHead() {
  const row = document.getElementById("table-head-row");
  if (!row) return;
  row.innerHTML = LEADS_TABLE_COLUMNS.map((col) => {
    if (col.key === "view") return `<th class="px-4 py-3 w-16"></th>`;
    const isSorted = leadsState.sort.column === col.key;
    const icon = isSorted ? (leadsState.sort.dir === "asc" ? "arrow-up" : "arrow-down") : "arrow-up-down";
    return `
      <th class="px-4 py-3 whitespace-nowrap ${col.sortable ? "cursor-pointer select-none hover:text-slate-700" : ""}" ${col.sortable ? `data-sort-col="${col.key}"` : ""}>
        <span class="inline-flex items-center gap-1">${col.label}${col.sortable ? `<i data-lucide="${icon}" class="w-3 h-3 ${isSorted ? "text-indigo-600" : "text-slate-300"}"></i>` : ""}</span>
      </th>`;
  }).join("");
  row.querySelectorAll("[data-sort-col]").forEach((th) => th.addEventListener("click", () => sortLeadsByColumn(th.dataset.sortCol)));
  if (window.lucide) lucide.createIcons();
}

function currentPageItems() {
  const start = (leadsState.page - 1) * leadsState.pageSize;
  return leadsState.filtered.slice(start, start + leadsState.pageSize);
}

function renderTable() {
  renderTableHead();
  const tbody = document.getElementById("leads-table-body");
  const cardList = document.getElementById("leads-card-list");
  if (!tbody || !cardList) return;
  document.getElementById("leads-total-count").textContent = formatNumber(leadsState.allBusinesses.length);

  if (leadsState.loading) {
    tbody.innerHTML = skeletonRows(LEADS_TABLE_COLUMNS.length, 6);
    cardList.innerHTML = Array.from({ length: 3 }).map(() => `<div class="bg-white rounded-xl ring-1 ring-slate-100 p-4"><div class="skeleton h-4 w-2/3 rounded mb-2"></div><div class="skeleton h-3 w-1/2 rounded"></div></div>`).join("");
    renderPagination();
    return;
  }
  if (leadsState.error) {
    tbody.innerHTML = `<tr><td colspan="${LEADS_TABLE_COLUMNS.length}">${genericErrorState("เกิดข้อผิดพลาดในการโหลดข้อมูล Lead")}</td></tr>`;
    cardList.innerHTML = genericErrorState("เกิดข้อผิดพลาดในการโหลดข้อมูล Lead");
    if (window.lucide) lucide.createIcons();
    renderPagination();
    return;
  }

  const items = currentPageItems();
  if (!items.length) {
    tbody.innerHTML = `<tr><td colspan="${LEADS_TABLE_COLUMNS.length}">${genericEmptyState("ไม่พบ Lead ที่ตรงกับเงื่อนไข", "ลองปรับคำค้นหาหรือรีเซ็ตตัวกรอง")}</td></tr>`;
    cardList.innerHTML = genericEmptyState("ไม่พบ Lead ที่ตรงกับเงื่อนไข", "ลองปรับคำค้นหาหรือรีเซ็ตตัวกรอง");
    if (window.lucide) lucide.createIcons();
    renderPagination();
    return;
  }

  tbody.innerHTML = items.map((b) => businessRowHtml(b)).join("");
  cardList.innerHTML = items.map((b) => businessCardHtml(b)).join("");
  if (window.lucide) lucide.createIcons();
  renderPagination();
}

// businessRowHtml(), businessCardHtml(), websiteCellHtml(), phoneCellHtml()
// ถูกย้ายไปอยู่ที่ assets/js/app.js เพื่อให้ campaign-detail.js เรียกใช้ซ้ำได้
// โดยไม่ต้องโหลดทั้งไฟล์ leads.js (ซึ่งผูกกับ DOM ของหน้า leads.html โดยเฉพาะ)

// -----------------------------------------------------------------
// Pagination
// -----------------------------------------------------------------
function renderPagination() {
  const total = leadsState.filtered.length;
  const totalPages = Math.max(1, Math.ceil(total / leadsState.pageSize));
  if (leadsState.page > totalPages) leadsState.page = totalPages;

  const start = total === 0 ? 0 : (leadsState.page - 1) * leadsState.pageSize + 1;
  const end = Math.min(total, leadsState.page * leadsState.pageSize);
  const infoEl = document.getElementById("pagination-info");
  if (infoEl) infoEl.textContent = `${start}-${end} จาก ${formatNumber(total)} รายการ`;

  const controls = document.getElementById("pagination-controls");
  if (!controls) return;
  const pages = [];
  const maxButtons = 5;
  let from = Math.max(1, leadsState.page - Math.floor(maxButtons / 2));
  let to = Math.min(totalPages, from + maxButtons - 1);
  from = Math.max(1, to - maxButtons + 1);
  for (let p = from; p <= to; p++) pages.push(p);

  const btnClass = (active) => `w-8 h-8 rounded-lg text-sm font-medium ${active ? "bg-indigo-600 text-white" : "text-slate-600 hover:bg-slate-100"}`;
  controls.innerHTML = `
    <button id="page-prev" class="${btnClass(false)}" ${leadsState.page === 1 ? "disabled" : ""} aria-label="ก่อนหน้า"><i data-lucide="chevron-left" class="w-4 h-4 mx-auto"></i></button>
    ${pages.map((p) => `<button class="page-num-btn ${btnClass(p === leadsState.page)}" data-page="${p}">${p}</button>`).join("")}
    <button id="page-next" class="${btnClass(false)}" ${leadsState.page === totalPages ? "disabled" : ""} aria-label="ถัดไป"><i data-lucide="chevron-right" class="w-4 h-4 mx-auto"></i></button>
  `;
  if (window.lucide) lucide.createIcons();
  document.getElementById("page-prev").addEventListener("click", () => { if (leadsState.page > 1) { leadsState.page--; renderTable(); } });
  document.getElementById("page-next").addEventListener("click", () => { if (leadsState.page < totalPages) { leadsState.page++; renderTable(); } });
  controls.querySelectorAll(".page-num-btn").forEach((btn) => btn.addEventListener("click", () => { leadsState.page = parseInt(btn.dataset.page, 10); renderTable(); }));
}

// -----------------------------------------------------------------
// CSV Export
// -----------------------------------------------------------------
function exportLeadsToCsv(businesses) {
  if (!businesses.length) { showToast("ไม่มีข้อมูลสำหรับ Export", "warning"); return; }
  const headers = ["Company (TH)", "Company (EN)", "Website", "Phone", "จังหวัด", "ประเภทธุรกิจ", "เลขทะเบียนนิติบุคคล", "สถานะ Lead", "ความสมบูรณ์ของข้อมูล (%)", "วันที่ค้นพบ"];
  const rows = businesses.map((b) => [b.company_name_th, b.company_name_en, b.website || "-", b.phone || "-", b.province, b.business_type, b.registration_number, LEAD_STATUS_META[b.lead_status].label, b.data_completeness, b.created_at]);
  const csv = [headers, ...rows].map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\r\n");
  const blob = new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url; a.download = `leads-export-${Date.now()}.csv`; a.click();
  URL.revokeObjectURL(url);
  showToast(`Export ${businesses.length} รายการเรียบร้อยแล้ว`, "success");
}

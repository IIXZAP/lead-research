/**
 * sidebar.js
 * สร้างและควบคุม Sidebar หลัก: เปิด/ปิด, ย่อเหลือ Icon (Desktop),
 * Drawer Overlay (Mobile), Active State, และจดจำสถานะด้วย localStorage
 */

const SIDEBAR_COLLAPSE_KEY = "lcd_sidebar_collapsed";

// เมนูหลักที่เปิดใช้งานแล้วในระยะแรก
const NAV_ITEMS = [
  { key: "dashboard", label: "Dashboard", icon: "layout-dashboard", href: "dashboard.html" },
  { key: "campaigns", label: "Campaigns", icon: "target", href: "campaigns.html" },
  { key: "leads", label: "Leads", icon: "users", href: "leads.html" },
];

// เมนูที่เตรียมไว้สำหรับอนาคต (แสดงแบบ disabled พร้อม badge "Soon")
const NAV_ITEMS_FUTURE = [
  { key: "companies", label: "Companies", icon: "building-2" },
  { key: "reports", label: "Reports", icon: "bar-chart-3" },
  { key: "users", label: "Users", icon: "shield-user", adminOnly: true },
  { key: "settings", label: "Settings", icon: "settings" },
];

function isSidebarCollapsed() {
  return localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === "1";
}

function renderSidebar(activePage) {
  const user = getCurrentUser();
  if (!user) return;
  const collapsed = isSidebarCollapsed();

  const navHtml = NAV_ITEMS.map((item) => {
    const active = item.key === activePage;
    return `
      <a href="${item.href}"
         class="nav-item group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150
                ${active
                  ? "bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-md shadow-indigo-200"
                  : "text-slate-500 hover:bg-slate-100/80 hover:text-slate-900"}"
         data-nav-key="${item.key}" title="${item.label}">
        <span class="flex items-center justify-center w-6 h-6 shrink-0 ${active ? "text-white" : "text-slate-400 group-hover:text-indigo-600"} transition-colors">
          <i data-lucide="${item.icon}" class="w-[18px] h-[18px]"></i>
        </span>
        <span class="nav-label truncate">${item.label}</span>
      </a>`;
  }).join("");

  const futureHtml = NAV_ITEMS_FUTURE
    .filter((item) => !item.adminOnly || user.account_type === "admin")
    .map((item) => `
      <div class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 cursor-not-allowed select-none"
           title="${item.label} · เร็ว ๆ นี้">
        <span class="flex items-center justify-center w-6 h-6 shrink-0 text-slate-300">
          <i data-lucide="${item.icon}" class="w-[18px] h-[18px]"></i>
        </span>
        <span class="nav-label truncate">${item.label}</span>
        <span class="nav-label ml-auto text-[9px] font-semibold uppercase tracking-wider bg-slate-100 text-slate-400 rounded-full px-2 py-0.5">Soon</span>
      </div>`).join("");

  const sidebar = document.getElementById("sidebar");
  sidebar.innerHTML = `
    <div id="sidebar-inner" class="relative h-screen sticky top-0 flex flex-col bg-white border-r border-slate-200/80 shadow-[1px_0_0_0_rgba(0,0,0,0.02)] transition-all duration-200 ${collapsed ? "w-[76px]" : "w-64"}">

      <!-- Logo -->
      <div class="h-16 flex items-center gap-2.5 px-4 border-b border-slate-100 shrink-0">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center shrink-0 shadow-sm shadow-indigo-200">
          <i data-lucide="radar" class="w-[18px] h-[18px] text-white"></i>
        </div>
        <div class="nav-label min-w-0">
          <span class="block font-bold text-slate-800 tracking-tight truncate leading-tight">Lead Campaign</span>
          <span class="block text-[11px] text-slate-400 truncate leading-tight">Business Lead Finder</span>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-5 space-y-1">
        <div class="nav-label px-3 pb-2 text-[10px] font-bold uppercase tracking-widest text-slate-300">เมนูหลัก</div>
        ${navHtml}
        <div class="nav-label pt-5 pb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-300">เร็ว ๆ นี้</div>
        ${futureHtml}
      </nav>

      <!-- User card -->
      <div class="border-t border-slate-100 p-3 shrink-0">
        <div class="flex items-center gap-2.5 rounded-xl px-2 py-2 hover:bg-slate-50 transition-colors">
          <img src="${user.avatar}" alt="${escapeHTML(user.name)}" class="w-9 h-9 rounded-full object-cover shrink-0 ring-2 ring-indigo-50 shadow-sm" />
          <div class="nav-label min-w-0 flex-1">
            <p class="text-sm font-semibold text-slate-800 truncate leading-tight">${escapeHTML(user.name)}</p>
            <p class="text-[11px] text-slate-400 truncate leading-tight mt-0.5">${accountTypeLabel(user.account_type)}</p>
          </div>
          <button id="sidebar-logout-btn" class="nav-label text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg p-1.5 shrink-0 transition-colors" title="ออกจากระบบ" aria-label="ออกจากระบบ">
            <i data-lucide="log-out" class="w-4 h-4"></i>
          </button>
        </div>
        <button id="sidebar-collapse-btn"
                class="hidden lg:flex mt-1.5 w-full items-center justify-center gap-2 rounded-lg px-2 py-1.5 text-[11px] font-medium text-slate-400 hover:bg-slate-50 hover:text-indigo-600 transition-colors">
          <i data-lucide="${collapsed ? "panel-left-open" : "panel-left-close"}" class="w-3.5 h-3.5"></i>
          <span class="nav-label">ย่อเมนู</span>
        </button>
      </div>

      <!-- Floating collapse toggle (desktop) -->
      <button id="sidebar-edge-toggle"
              class="hidden lg:flex absolute -right-3 top-[70px] w-6 h-6 rounded-full bg-white border border-slate-200 shadow-md items-center justify-center text-slate-400 hover:text-indigo-600 hover:border-indigo-200 transition-colors z-10"
              title="ย่อ/ขยายเมนู" aria-label="ย่อ/ขยายเมนู">
        <i data-lucide="chevron-left" class="w-3.5 h-3.5 transition-transform ${collapsed ? "rotate-180" : ""}"></i>
      </button>
    </div>

    <!-- Mobile overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-[2px] z-30 lg:hidden hidden"></div>
  `;

  applyCollapsedClass(collapsed);
  if (window.lucide) lucide.createIcons();

  document.getElementById("sidebar-logout-btn").addEventListener("click", (e) => { e.preventDefault(); logout(); });
  const collapseBtn = document.getElementById("sidebar-collapse-btn");
  if (collapseBtn) collapseBtn.addEventListener("click", toggleSidebarCollapse);
  document.getElementById("sidebar-edge-toggle").addEventListener("click", toggleSidebarCollapse);
  document.getElementById("sidebar-overlay").addEventListener("click", closeMobileSidebar);
}

function applyCollapsedClass(collapsed) {
  const inner = document.getElementById("sidebar-inner");
  if (!inner) return;
  inner.classList.toggle("w-[76px]", collapsed);
  inner.classList.toggle("w-64", !collapsed);
  document.querySelectorAll(".nav-label").forEach((el) => el.classList.toggle("hidden", collapsed));
}

function toggleSidebarCollapse() {
  const next = !isSidebarCollapsed();
  localStorage.setItem(SIDEBAR_COLLAPSE_KEY, next ? "1" : "0");
  applyCollapsedClass(next);
  const collapseIcon = document.querySelector("#sidebar-collapse-btn i");
  if (collapseIcon) collapseIcon.setAttribute("data-lucide", next ? "panel-left-open" : "panel-left-close");
  const edgeIcon = document.querySelector("#sidebar-edge-toggle i");
  if (edgeIcon) edgeIcon.classList.toggle("rotate-180", next);
  if (window.lucide) lucide.createIcons();
}

function openMobileSidebar() {
  const inner = document.getElementById("sidebar-inner");
  const overlay = document.getElementById("sidebar-overlay");
  inner.classList.add("!fixed", "!inset-y-0", "!left-0", "!z-40", "!w-72", "shadow-2xl");
  overlay.classList.remove("hidden");
}

function closeMobileSidebar() {
  const inner = document.getElementById("sidebar-inner");
  const overlay = document.getElementById("sidebar-overlay");
  inner.classList.remove("!fixed", "!inset-y-0", "!left-0", "!z-40", "!w-72", "shadow-2xl");
  overlay.classList.add("hidden");
}

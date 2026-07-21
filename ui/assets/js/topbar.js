/**
 * topbar.js
 * สร้างและควบคุม Top Navigation: Toggle Sidebar, Breadcrumb, Search,
 * Notification, User Dropdown และ Dark Mode Toggle (Optional Feature)
 */

const BREADCRUMB_MAP = {
  dashboard: [{ label: "Dashboard" }],
  campaigns: [{ label: "Campaigns" }],
  "campaign-create": [{ label: "Campaigns", href: "campaigns.html" }, { label: "สร้าง Campaign" }],
  "campaign-detail": [{ label: "Campaigns", href: "campaigns.html" }, { label: "รายละเอียด Campaign" }],
  leads: [{ label: "Leads" }],
  "business-info": [{ label: "Leads", href: "leads.html" }, { label: "ข้อมูลบริษัท" }],
};

const MOCK_NOTIFICATIONS = [
  { icon: "check-circle-2", color: "text-emerald-500 bg-emerald-50", text: "Campaign “Lead โรงงานอาหาร กรุงเทพฯ” ประมวลผลเสร็จสิ้น", time: "15 นาทีที่แล้ว" },
  { icon: "alert-triangle", color: "text-rose-500 bg-rose-50", text: "Campaign “ผู้รับเหมาก่อสร้างในชลบุรี” ประมวลผลล้มเหลว", time: "1 ชั่วโมงที่แล้ว" },
  { icon: "user-plus", color: "text-indigo-500 bg-indigo-50", text: "มีบริษัทใหม่ถูกมอบหมายให้คุณ 3 รายการ", time: "เมื่อวาน" },
];

function renderTopbar(activePage, options = {}) {
  const user = getCurrentUser();
  if (!user) return;
  const crumbs = BREADCRUMB_MAP[activePage] || [{ label: activePage }];

  const topbar = document.getElementById("topbar");
  topbar.innerHTML = `
    <div class="h-16 bg-white/90 backdrop-blur-sm border-b border-slate-200/80 flex items-center gap-2 px-4 sm:px-6 sticky top-0 z-20 shadow-[0_1px_2px_rgba(15,23,42,0.03)]">

      <button id="topbar-menu-btn" class="lg:hidden flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors" aria-label="เปิดเมนู">
        <i data-lucide="menu" class="w-5 h-5"></i>
      </button>

      <nav aria-label="Breadcrumb" class="hidden sm:flex items-center text-sm min-w-0 mr-2">
        ${crumbs.map((c, i) => `
          ${i > 0 ? '<i data-lucide="chevron-right" class="w-3.5 h-3.5 mx-2 text-slate-300"></i>' : ""}
          ${c.href ? `<a href="${c.href}" class="text-slate-400 hover:text-indigo-600 truncate transition-colors">${c.label}</a>` : `<span class="text-slate-800 font-semibold truncate">${c.label}</span>`}
        `).join("")}
      </nav>

      <div class="flex-1"></div>

      <div class="relative hidden md:block w-72">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        <input id="topbar-search" type="text" placeholder="ค้นหา Campaign, บริษัท..."
               class="w-full pl-9.5 pr-3 py-2.5 text-sm rounded-xl border border-transparent bg-slate-100/80 focus:bg-white focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-300 outline-none transition-all placeholder:text-slate-400" />
      </div>

      <div class="flex items-center gap-1 sm:gap-1.5 ml-1">
        <button id="topbar-dark-btn" class="flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" title="สลับโหมดมืด/สว่าง" aria-label="สลับโหมดมืด/สว่าง">
          <i data-lucide="moon" class="w-[18px] h-[18px]"></i>
        </button>

        <div class="relative">
          <button id="topbar-notif-btn" class="relative flex items-center justify-center w-9 h-9 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" aria-label="การแจ้งเตือน">
            <i data-lucide="bell" class="w-[18px] h-[18px]"></i>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
          </button>
          <div id="topbar-notif-dropdown" class="hidden absolute right-0 mt-2.5 w-80 max-w-[90vw] bg-white rounded-2xl shadow-xl ring-1 ring-slate-100 overflow-hidden origin-top-right">
            <div class="px-4 py-3.5 border-b border-slate-100 flex items-center justify-between">
              <span class="font-semibold text-sm text-slate-800">การแจ้งเตือน</span>
              <span class="text-[11px] font-medium text-white bg-rose-500 rounded-full px-2 py-0.5">${MOCK_NOTIFICATIONS.length} ใหม่</span>
            </div>
            <div class="max-h-80 overflow-y-auto divide-y divide-slate-50">
              ${MOCK_NOTIFICATIONS.map((n) => `
                <div class="flex gap-3 px-4 py-3.5 hover:bg-slate-50 cursor-pointer transition-colors">
                  <span class="w-8 h-8 rounded-full ${n.color} flex items-center justify-center shrink-0">
                    <i data-lucide="${n.icon}" class="w-4 h-4"></i>
                  </span>
                  <div class="min-w-0">
                    <p class="text-sm text-slate-700 leading-snug">${n.text}</p>
                    <p class="text-xs text-slate-400 mt-1">${n.time}</p>
                  </div>
                </div>`).join("")}
            </div>
          </div>
        </div>

        <div class="w-px h-6 bg-slate-200 mx-1 hidden sm:block"></div>

        <div class="relative">
          <button id="topbar-user-btn" class="flex items-center gap-2 pl-1 pr-1.5 sm:pr-2 py-1 rounded-lg hover:bg-slate-100 transition-colors">
            <img src="${user.avatar}" class="w-8 h-8 rounded-full object-cover ring-2 ring-slate-100" alt="${escapeHTML(user.name)}" />
            <span class="hidden sm:block text-left">
              <span class="block text-sm font-semibold text-slate-800 leading-tight">${escapeHTML(user.name)}</span>
              <span class="block text-[11px] text-slate-400 leading-tight mt-0.5">${accountTypeLabel(user.account_type)}</span>
            </span>
            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 hidden sm:block"></i>
          </button>
          <div id="topbar-user-dropdown" class="hidden absolute right-0 mt-2.5 w-56 bg-white rounded-2xl shadow-xl ring-1 ring-slate-100 overflow-hidden py-1.5 origin-top-right">
            <div class="px-4 py-3 border-b border-slate-100">
              <p class="text-sm font-semibold text-slate-800 truncate">${escapeHTML(user.name)}</p>
              <p class="text-xs text-slate-400 truncate mt-0.5">${escapeHTML(user.email)}</p>
              <span class="inline-block mt-1.5 text-[10px] font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">${accountTypeLabel(user.account_type)}</span>
            </div>
            <button id="topbar-logout-btn" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 transition-colors">
              <i data-lucide="log-out" class="w-4 h-4"></i> ออกจากระบบ
            </button>
          </div>
        </div>
      </div>
    </div>
  `;

  if (window.lucide) lucide.createIcons();
  bindTopbarEvents(options);
}

function bindTopbarEvents(options) {
  document.getElementById("topbar-menu-btn").addEventListener("click", openMobileSidebar);
  document.getElementById("topbar-logout-btn").addEventListener("click", logout);

  const notifBtn = document.getElementById("topbar-notif-btn");
  const notifDropdown = document.getElementById("topbar-notif-dropdown");
  const userBtn = document.getElementById("topbar-user-btn");
  const userDropdown = document.getElementById("topbar-user-dropdown");

  notifBtn.addEventListener("click", (e) => { e.stopPropagation(); userDropdown.classList.add("hidden"); notifDropdown.classList.toggle("hidden"); });
  userBtn.addEventListener("click", (e) => { e.stopPropagation(); notifDropdown.classList.add("hidden"); userDropdown.classList.toggle("hidden"); });
  document.addEventListener("click", () => { notifDropdown.classList.add("hidden"); userDropdown.classList.add("hidden"); });

  document.getElementById("topbar-dark-btn").addEventListener("click", toggleDarkMode);
  applyStoredDarkMode();

  if (options.onSearch) {
    document.getElementById("topbar-search").addEventListener("input", debounce((e) => options.onSearch(e.target.value), 250));
  }
}

function toggleDarkMode() {
  const isDark = document.documentElement.classList.toggle("dark");
  localStorage.setItem("lcd_dark_mode", isDark ? "1" : "0");
  const icon = document.querySelector("#topbar-dark-btn i");
  if (icon) { icon.setAttribute("data-lucide", isDark ? "sun" : "moon"); if (window.lucide) lucide.createIcons(); }
}

function applyStoredDarkMode() {
  const isDark = localStorage.getItem("lcd_dark_mode") === "1";
  document.documentElement.classList.toggle("dark", isDark);
  const icon = document.querySelector("#topbar-dark-btn i");
  if (icon) { icon.setAttribute("data-lucide", isDark ? "sun" : "moon"); if (window.lucide) lucide.createIcons(); }
}

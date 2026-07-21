/**
 * dashboard.js
 * Render หน้า Dashboard: Summary Cards, Charts, Recent Campaigns, Top Leads
 * ข้อมูลทั้งหมดกรองตาม account_type ของผู้ใช้ปัจจุบัน
 */

let DASH_CHARTS = {};

document.addEventListener("DOMContentLoaded", () => {
  const user = checkAuthentication();
  if (!user) return;

  renderSidebar("dashboard");
  renderTopbar("dashboard");

  if (hasPermission("campaign.create")) document.getElementById("dashboard-create-btn").classList.remove("hidden");

  document.getElementById("dashboard-scope-desc").textContent =
    user.account_type === "admin" ? "ภาพรวมแคมเปญและ Lead ทั้งองค์กร"
    : user.account_type === "manager" ? "ภาพรวมแคมเปญและ Lead ของทีม"
    : "ภาพรวม Campaign และ Lead ที่ได้รับมอบหมาย";

  renderSummaryCardsSkeleton();

  setTimeout(() => {
    const allCampaigns = loadCampaignsFromStorage();
    const campaigns = filterCampaignsByScope(allCampaigns, user);
    const allBusinesses = loadBusinessesFromStorage();
    const businesses = filterBusinessesByScope(allBusinesses, user);

    renderSummaryCards(campaigns, businesses);
    renderDailyLeadsChart(businesses);
    renderCampaignStatusChart(campaigns);
    renderRecentCampaigns(campaigns);
    renderTopLeads(businesses);
  }, 500);
});

function renderSummaryCardsSkeleton() {
  document.getElementById("summary-cards").innerHTML = Array.from({ length: 4 }).map(() => `
    <div class="bg-white rounded-2xl ring-1 ring-slate-100 shadow-sm p-4">
      <div class="skeleton w-9 h-9 rounded-lg mb-3"></div>
      <div class="skeleton h-6 w-16 rounded mb-2"></div>
      <div class="skeleton h-3 w-24 rounded"></div>
    </div>
  `).join("");
}

function renderSummaryCards(campaigns, businesses) {
  const total = campaigns.length;
  const processing = campaigns.filter((c) => c.status === "processing" || c.status === "queued").length;
  const completed = campaigns.filter((c) => c.status === "completed" || c.status === "partially_completed").length;
  const totalLeads = businesses.length;

  const cards = [
    { label: "Campaign ทั้งหมด", value: total, icon: "target", color: "indigo", trend: "+12.5%", up: true },
    { label: "กำลังประมวลผล", value: processing, icon: "loader-circle", color: "amber", trend: "+2", up: true },
    { label: "เสร็จแล้ว", value: completed, icon: "check-circle-2", color: "emerald", trend: "+8.1%", up: true },
    { label: "Lead ที่ค้นพบทั้งหมด", value: totalLeads, icon: "users", color: "sky", trend: "+18.4%", up: true },
  ];
  const colorMap = { indigo: "bg-indigo-50 text-indigo-600", amber: "bg-amber-50 text-amber-600", emerald: "bg-emerald-50 text-emerald-600", sky: "bg-sky-50 text-sky-600" };

  document.getElementById("summary-cards").innerHTML = cards.map((c) => `
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

function renderDailyLeadsChart(businesses) {
  const days = [], counts = [];
  const base = new Date("2026-07-17");
  for (let i = 13; i >= 0; i--) {
    const d = new Date(base.getTime() - i * 86400000);
    const key = d.toISOString().slice(0, 10);
    days.push(d.toLocaleDateString("th-TH", { day: "numeric", month: "short" }));
    counts.push(businesses.filter((b) => b.created_at.slice(0, 10) === key).length);
  }
  const el = document.querySelector("#chart-leads-daily");
  if (DASH_CHARTS.daily) DASH_CHARTS.daily.destroy();
  DASH_CHARTS.daily = new ApexCharts(el, {
    chart: { type: "area", height: 280, toolbar: { show: false }, fontFamily: "Inter, Noto Sans Thai" },
    series: [{ name: "Lead ที่ค้นพบ", data: counts }],
    xaxis: { categories: days, labels: { style: { fontSize: "10px" } } },
    colors: ["#4f46e5"],
    stroke: { curve: "smooth", width: 2 },
    fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
    dataLabels: { enabled: false },
    grid: { borderColor: "#f1f5f9" },
  });
  DASH_CHARTS.daily.render();
}

function renderCampaignStatusChart(campaigns) {
  const counts = CAMPAIGN_STATUSES.map((s) => campaigns.filter((c) => c.status === s).length);
  const labels = CAMPAIGN_STATUSES.map((s) => CAMPAIGN_STATUS_META[s].label);
  const el = document.querySelector("#chart-campaign-status");
  if (DASH_CHARTS.status) DASH_CHARTS.status.destroy();
  DASH_CHARTS.status = new ApexCharts(el, {
    chart: { type: "donut", height: 280, fontFamily: "Inter, Noto Sans Thai" },
    series: counts,
    labels,
    colors: ["#94a3b8", "#3b82f6", "#f59e0b", "#fb923c", "#10b981", "#f43f5e", "#cbd5e1"],
    legend: { position: "bottom", fontSize: "11px", itemMargin: { horizontal: 6, vertical: 2 } },
    dataLabels: { enabled: false },
    plotOptions: { pie: { donut: { size: "65%", labels: { show: true, total: { show: true, label: "ทั้งหมด" } } } } },
  });
  DASH_CHARTS.status.render();
}

function renderRecentCampaigns(campaigns) {
  const recent = [...campaigns].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 6);
  const container = document.getElementById("recent-campaigns-list");
  if (!recent.length) { container.innerHTML = genericEmptyState("ยังไม่มี Campaign"); if (window.lucide) lucide.createIcons(); return; }
  container.innerHTML = recent.map((c) => `
    <a href="campaign-detail.html?id=${c.id}" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0 hover:bg-slate-50 -mx-2 px-2 rounded-lg transition-colors">
      <span class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
        <i data-lucide="target" class="w-4 h-4"></i>
      </span>
      <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-slate-700 truncate">${escapeHTML(c.title)}</p>
        <p class="text-xs text-slate-400 truncate">${c.target.province} · ${c.leads_found}/${c.maximum_leads} Lead · ${timeAgo(c.created_at)}</p>
      </div>
      ${campaignStatusBadge(c.status)}
    </a>
  `).join("");
  if (window.lucide) lucide.createIcons();
}

function renderTopLeads(businesses) {
  const top = [...businesses].sort((a, b) => b.lead_analysis.score - a.lead_analysis.score).slice(0, 6);
  const container = document.getElementById("top-leads-list");
  if (!top.length) { container.innerHTML = genericEmptyState("ยังไม่มี Lead"); if (window.lucide) lucide.createIcons(); return; }
  container.innerHTML = top.map((b) => `
    <a href="business-info.html?id=${b.id}" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0 hover:bg-slate-50 -mx-2 px-2 rounded-lg transition-colors">
      ${initialsAvatar(b.company_name_th)}
      <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-slate-700 truncate">${escapeHTML(b.company_name_th)}</p>
        <p class="text-xs text-slate-400 truncate">${escapeHTML(b.business_type)} · Score ${b.lead_analysis.score}</p>
      </div>
      <span class="text-xs font-semibold text-emerald-600 shrink-0">${b.lead_analysis.quality}</span>
    </a>
  `).join("");
  if (window.lucide) lucide.createIcons();
}

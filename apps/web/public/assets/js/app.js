/**
 * app.js
 * Helper กลางที่ใช้ร่วมกันหลายหน้า (Badge, Progress Bar, Empty/Error/Loading state ฯลฯ)
 * แยกจาก utils.js เพราะเป็น "UI rendering helper" ไม่ใช่ pure utility function
 */

function campaignStatusBadge(status) {
  const meta = CAMPAIGN_STATUS_META[status] || { label: status, color: "bg-slate-100 text-slate-600 ring-slate-500/20" };
  return `<span class="text-xs font-medium px-2 py-0.5 rounded-full ring-1 ${meta.color} whitespace-nowrap">${meta.label}</span>`;
}

function leadStatusBadge(status) {
  const meta = LEAD_STATUS_META[status] || { label: status, color: "bg-slate-100 text-slate-600" };
  return `<span class="text-xs font-medium px-2 py-0.5 rounded-full ${meta.color} whitespace-nowrap">${meta.label}</span>`;
}

function severityBadge(severity) {
  const map = {
    Low: "bg-slate-100 text-slate-600", Medium: "bg-amber-50 text-amber-700",
    High: "bg-orange-50 text-orange-700", Critical: "bg-rose-50 text-rose-700",
  };
  return `<span class="text-[11px] font-medium px-2 py-0.5 rounded-full ${map[severity] || map.Low}">${severity}</span>`;
}

function verificationBadge(status) {
  const map = {
    verified: { label: "Verified", color: "bg-emerald-50 text-emerald-700" },
    unverified: { label: "Unverified", color: "bg-amber-50 text-amber-700" },
    not_found: { label: "Not Found", color: "bg-slate-100 text-slate-500" },
  };
  const m = map[status] || map.not_found;
  return `<span class="text-[11px] font-medium px-2 py-0.5 rounded-full ${m.color}">${m.label}</span>`;
}

function dataSourceBadge(source) {
  const map = {
    DBD: "bg-blue-50 text-blue-700", "Company Website": "bg-indigo-50 text-indigo-700",
    "Google Search": "bg-slate-100 text-slate-600", Manual: "bg-amber-50 text-amber-700", "AI Analysis": "bg-violet-50 text-violet-700",
  };
  return `<span class="text-[11px] font-medium px-2 py-0.5 rounded-full ${map[source] || "bg-slate-100 text-slate-600"}">${source}</span>`;
}

/** Progress bar พร้อม label เปอร์เซ็นต์ ใช้กับ Campaign progress */
function progressBarHtml(percent, label) {
  return `
    <div>
      ${label ? `<div class="flex items-center justify-between text-xs text-slate-500 mb-1"><span>${escapeHTML(label)}</span><span class="font-medium text-slate-700">${percent}%</span></div>` : ""}
      <div class="progress-track h-2 w-full">
        <div class="progress-fill h-full" style="width:${Math.max(percent, 2)}%"></div>
      </div>
    </div>
  `;
}

/** สร้าง Initial Avatar เมื่อไม่มีโลโก้บริษัท */
function initialsAvatar(name, sizeClass = "w-9 h-9 text-xs") {
  const initials = String(name || "?").trim().slice(0, 2).toUpperCase();
  return `<span class="${sizeClass} rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-semibold shrink-0">${escapeHTML(initials)}</span>`;
}

/** Data completeness แบบวงแหวนเล็ก ๆ ในตาราง */
function completenessRing(percent) {
  const color = percent >= 80 ? "#10b981" : percent >= 50 ? "#f59e0b" : "#f43f5e";
  const r = 14, c = 2 * Math.PI * r;
  return `
    <div class="relative w-9 h-9 shrink-0" title="ความสมบูรณ์ของข้อมูล ${percent}%">
      <svg viewBox="0 0 36 36" class="w-9 h-9 -rotate-90">
        <circle cx="18" cy="18" r="${r}" stroke="#f1f5f9" stroke-width="4" fill="none" />
        <circle cx="18" cy="18" r="${r}" stroke="${color}" stroke-width="4" fill="none"
                stroke-dasharray="${c}" stroke-dashoffset="${c * (1 - percent / 100)}" stroke-linecap="round" />
      </svg>
      <span class="absolute inset-0 flex items-center justify-center text-[9px] font-semibold text-slate-600">${percent}</span>
    </div>
  `;
}

function genericEmptyState(message, sub) {
  return `
    <div class="flex flex-col items-center justify-center py-14 text-center">
      <i data-lucide="inbox" class="w-9 h-9 text-slate-300 mb-3"></i>
      <p class="text-slate-500 font-medium">${escapeHTML(message)}</p>
      ${sub ? `<p class="text-sm text-slate-400 mt-1">${escapeHTML(sub)}</p>` : ""}
    </div>
  `;
}

function genericErrorState(message, retryFnName) {
  return `
    <div class="flex flex-col items-center justify-center py-14 text-center">
      <i data-lucide="server-crash" class="w-9 h-9 text-rose-300 mb-3"></i>
      <p class="text-slate-600 font-medium">${escapeHTML(message)}</p>
      <button onclick="${retryFnName || "location.reload()"}" class="mt-3 text-sm text-indigo-600 hover:text-indigo-700 font-medium">ลองใหม่อีกครั้ง</button>
    </div>
  `;
}

function permissionDeniedState(message) {
  return `
    <div class="flex flex-col items-center justify-center py-14 text-center">
      <i data-lucide="lock" class="w-9 h-9 text-slate-300 mb-3"></i>
      <p class="text-slate-600 font-medium">${escapeHTML(message || "คุณไม่มีสิทธิ์เข้าถึงส่วนนี้")}</p>
      <p class="text-sm text-slate-400 mt-1">กรุณาติดต่อผู้ดูแลระบบหากคิดว่านี่เป็นข้อผิดพลาด</p>
    </div>
  `;
}

function skeletonRows(colCount, rowCount = 5) {
  return Array.from({ length: rowCount }).map(() => `
    <tr>${Array.from({ length: colCount }).map(() => `<td class="px-4 py-3"><div class="skeleton h-4 w-full rounded"></div></td>`).join("")}</tr>
  `).join("");
}

// -----------------------------------------------------------------
// Business/Lead row & card renderers — ใช้ร่วมกันระหว่าง leads.js (Directory
// รวมทุก Campaign) และ campaign-detail.js (Tab Leads ภายใน Campaign เดียว)
// -----------------------------------------------------------------
function websiteCellHtml(website) {
  if (!website) return `<span class="text-slate-400 text-xs">ไม่พบเว็บไซต์</span>`;
  return `<a href="${website}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-xs font-medium">${escapeHTML(shortDomain(website))} <i data-lucide="external-link" class="w-3 h-3"></i></a>`;
}

function phoneCellHtml(b) {
  if (!b.phone) return `<span class="text-slate-400 text-xs">ไม่พบเบอร์โทร</span>`;
  const extra = b.phone_secondary_count > 0 ? ` <span class="text-slate-400">+${b.phone_secondary_count} เบอร์</span>` : "";
  return `<a href="tel:${b.phone}" class="text-slate-700 hover:text-indigo-600 text-xs font-medium">${escapeHTML(b.phone)}</a>${extra}`;
}

function businessRowHtml(b) {
  return `
    <tr class="lead-row transition-colors" data-business-id="${b.id}">
      <td class="px-4 py-3 max-w-[220px]">
        <div class="flex items-center gap-2.5">
          ${initialsAvatar(b.company_name_th)}
          <div class="min-w-0">
            <a href="business-info.html?id=${b.id}" class="font-medium text-slate-800 hover:text-indigo-600 truncate block" title="${escapeHTML(b.company_name_th)}">${escapeHTML(b.company_name_th)}</a>
            ${b.company_name_en ? `<p class="text-xs text-slate-400 truncate">${escapeHTML(b.company_name_en)}</p>` : ""}
          </div>
        </div>
      </td>
      <td class="px-4 py-3 whitespace-nowrap">${websiteCellHtml(b.website)}</td>
      <td class="px-4 py-3 whitespace-nowrap">${phoneCellHtml(b)}</td>
      <td class="px-4 py-3 whitespace-nowrap">${escapeHTML(b.province)}</td>
      <td class="px-4 py-3 whitespace-nowrap">${escapeHTML(b.business_type)}</td>
      <td class="px-4 py-3 whitespace-nowrap text-slate-500" title="${escapeHTML(b.registration_number)}">${escapeHTML(b.registration_number)}</td>
      <td class="px-4 py-3 whitespace-nowrap">${leadStatusBadge(b.lead_status)}</td>
      <td class="px-4 py-3 whitespace-nowrap">${completenessRing(b.data_completeness)}</td>
      <td class="px-4 py-3 whitespace-nowrap text-slate-500">${formatThaiDate(b.created_at)}</td>
      <td class="px-4 py-3 text-right">
        <a href="business-info.html?id=${b.id}" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 border border-indigo-100 hover:bg-indigo-50 rounded-lg px-2.5 py-1.5">
          <i data-lucide="eye" class="w-3.5 h-3.5"></i> ดูข้อมูล
        </a>
      </td>
    </tr>
  `;
}

function businessCardHtml(b) {
  return `
    <div class="bg-white rounded-xl ring-1 ring-slate-100 shadow-sm p-4" data-business-id="${b.id}">
      <div class="flex items-start gap-3">
        ${initialsAvatar(b.company_name_th)}
        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-2">
            <a href="business-info.html?id=${b.id}" class="font-medium text-slate-800 truncate">${escapeHTML(b.company_name_th)}</a>
            ${leadStatusBadge(b.lead_status)}
          </div>
          ${b.company_name_en ? `<p class="text-xs text-slate-400 truncate mt-0.5">${escapeHTML(b.company_name_en)}</p>` : ""}
          <div class="grid grid-cols-2 gap-y-1 mt-3 text-xs text-slate-500">
            <span><i data-lucide="map-pin" class="w-3 h-3 inline mr-1"></i>${escapeHTML(b.province)}</span>
            <span><i data-lucide="building-2" class="w-3 h-3 inline mr-1"></i>${escapeHTML(b.business_type)}</span>
            <span>${websiteCellHtml(b.website)}</span>
            <span>${phoneCellHtml(b)}</span>
          </div>
          <div class="flex items-center justify-between mt-3">
            <span class="text-xs text-slate-400">ค้นพบ ${formatThaiDate(b.created_at)}</span>
            <a href="business-info.html?id=${b.id}" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 border border-indigo-100 hover:bg-indigo-50 rounded-lg px-2.5 py-1.5">
              <i data-lucide="eye" class="w-3.5 h-3.5"></i> ดูข้อมูล
            </a>
          </div>
        </div>
      </div>
    </div>
  `;
}

/** Activity timeline item ใช้ร่วมกันหลายหน้า (Campaign Activity Logs, Business Activity Timeline) */
function activityTimelineHtml(events) {
  if (!events.length) return genericEmptyState("ยังไม่มีกิจกรรม");
  return events.map((e, i) => {
    const dotColor = e.status === "failed" ? "bg-rose-500" : e.status === "cancelled" ? "bg-slate-400" : "bg-indigo-500";
    return `
      <div class="flex gap-3">
        <div class="flex flex-col items-center">
          <span class="w-8 h-8 rounded-full ${dotColor} bg-opacity-10 flex items-center justify-center shrink-0" style="background-color:${e.status === "failed" ? "#fee2e2" : e.status === "cancelled" ? "#f1f5f9" : "#eef2ff"}">
            <i data-lucide="${e.icon || "circle"}" class="w-4 h-4" style="color:${e.status === "failed" ? "#e11d48" : e.status === "cancelled" ? "#94a3b8" : "#4f46e5"}"></i>
          </span>
          ${i < events.length - 1 ? '<span class="w-px flex-1 bg-slate-100 my-1"></span>' : ""}
        </div>
        <div class="pb-5 min-w-0">
          <p class="text-sm text-slate-700">${escapeHTML(e.text)}</p>
          <p class="text-xs text-slate-400 mt-0.5">${escapeHTML(e.user)} · ${formatThaiDate(e.at, true)} ${e.source ? `· ${dataSourceLikeBadge(e.source)}` : ""}</p>
        </div>
      </div>
    `;
  }).join("");
}

function dataSourceLikeBadge(source) {
  const map = { system: "System", manual: "Manual", "python-agent": "Python Lead Service", dbd: "DBD" };
  return `<span class="text-slate-400">${map[source] || source}</span>`;
}

/** Pipeline / Progress Timeline แนวตั้ง (ใช้ใน Campaign Overview Tab) */
function pipelineTimelineHtml(pipeline) {
  return pipeline.map((step, i) => {
    const stateMeta = {
      completed: { color: "#10b981", bg: "#ecfdf5", icon: "check" },
      processing: { color: "#f59e0b", bg: "#fffbeb", icon: "loader-circle" },
      failed: { color: "#f43f5e", bg: "#fff1f2", icon: "x" },
      waiting: { color: "#94a3b8", bg: "#f8fafc", icon: "circle" },
    }[step.state];
    const stateLabel = { completed: "Completed", processing: "Processing", failed: "Failed", waiting: "Waiting" }[step.state];
    return `
      <div class="flex gap-3">
        <div class="flex flex-col items-center">
          <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 ${step.state === "processing" ? "animate-pulse" : ""}" style="background-color:${stateMeta.bg}">
            <i data-lucide="${stateMeta.icon}" class="w-4 h-4" style="color:${stateMeta.color}"></i>
          </span>
          ${i < pipeline.length - 1 ? '<span class="w-px flex-1 bg-slate-100 my-1"></span>' : ""}
        </div>
        <div class="pb-5 min-w-0 flex-1">
          <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-medium text-slate-700">${step.step}. ${escapeHTML(step.label)}</p>
            <span class="text-[11px] font-medium shrink-0" style="color:${stateMeta.color}">${stateLabel}</span>
          </div>
        </div>
      </div>
    `;
  }).join("");
}

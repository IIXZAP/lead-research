/**
 * sidebar.js (Laravel version)
 * -----------------------------------------------------------------
 * Markup ของ Sidebar ย้ายไป render ฝั่ง Server ที่ resources/views/partials/sidebar.blade.php แล้ว
 * ไฟล์นี้เหลือเฉพาะ "interaction" ซึ่ง logic คงเดิมจาก Demo ทุกฟังก์ชัน:
 * - ย่อ/ขยายเมนู (Desktop) + จดจำสถานะด้วย localStorage (key เดิม)
 * - Drawer Overlay (Mobile)
 * - Logout (submit ฟอร์ม #logout-form ที่มี CSRF token แทน mock logout เดิม)
 */

const SIDEBAR_COLLAPSE_KEY = "lcd_sidebar_collapsed";

function isSidebarCollapsed() {
  return localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === "1";
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

/** ออกจากระบบผ่านฟอร์ม POST /logout (Laravel) — แทน logout() ฝั่ง mock เดิม */
function submitLogout(e) {
  if (e) e.preventDefault();
  const form = document.getElementById("logout-form");
  if (form) form.submit();
}

document.addEventListener("DOMContentLoaded", () => {
  const logoutBtn = document.getElementById("sidebar-logout-btn");
  if (logoutBtn) logoutBtn.addEventListener("click", submitLogout);

  const collapseBtn = document.getElementById("sidebar-collapse-btn");
  if (collapseBtn) collapseBtn.addEventListener("click", toggleSidebarCollapse);

  const edgeToggle = document.getElementById("sidebar-edge-toggle");
  if (edgeToggle) edgeToggle.addEventListener("click", toggleSidebarCollapse);

  const overlay = document.getElementById("sidebar-overlay");
  if (overlay) overlay.addEventListener("click", closeMobileSidebar);
});

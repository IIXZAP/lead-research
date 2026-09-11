/**
 * topbar.js (Laravel version)
 * -----------------------------------------------------------------
 * Markup ของ Topbar ย้ายไป render ฝั่ง Server ที่ resources/views/partials/topbar.blade.php แล้ว
 * ไฟล์นี้เหลือเฉพาะ "interaction" ซึ่ง logic คงเดิมจาก Demo:
 * - เปิด Mobile Sidebar
 * - Notification / User dropdown (คลิกนอกพื้นที่เพื่อปิด)
 * - Dark Mode toggle + จดจำสถานะ (key เดิม lcd_dark_mode)
 * - Search: เดิมรับ options.onSearch จากแต่ละหน้า → เปลี่ยนเป็น hook กลาง
 *   หน้าที่ต้องการรับค่า search ให้กำหนด window.onTopbarSearch = (value) => {...}
 *   หรือ listen CustomEvent "topbar:search" บน document
 */

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

document.addEventListener("DOMContentLoaded", () => {
  const menuBtn = document.getElementById("topbar-menu-btn");
  if (menuBtn) menuBtn.addEventListener("click", openMobileSidebar);

  const logoutBtn = document.getElementById("topbar-logout-btn");
  if (logoutBtn) logoutBtn.addEventListener("click", submitLogout);

  const notifBtn = document.getElementById("topbar-notif-btn");
  const notifDropdown = document.getElementById("topbar-notif-dropdown");
  const userBtn = document.getElementById("topbar-user-btn");
  const userDropdown = document.getElementById("topbar-user-dropdown");

  if (notifBtn && notifDropdown && userBtn && userDropdown) {
    notifBtn.addEventListener("click", (e) => { e.stopPropagation(); userDropdown.classList.add("hidden"); notifDropdown.classList.toggle("hidden"); });
    userBtn.addEventListener("click", (e) => { e.stopPropagation(); notifDropdown.classList.add("hidden"); userDropdown.classList.toggle("hidden"); });
    document.addEventListener("click", () => { notifDropdown.classList.add("hidden"); userDropdown.classList.add("hidden"); });
  }

  const darkBtn = document.getElementById("topbar-dark-btn");
  if (darkBtn) darkBtn.addEventListener("click", toggleDarkMode);
  applyStoredDarkMode();

  const searchInput = document.getElementById("topbar-search");
  if (searchInput) {
    searchInput.addEventListener("input", debounce((e) => {
      const value = e.target.value;
      if (typeof window.onTopbarSearch === "function") window.onTopbarSearch(value);
      document.dispatchEvent(new CustomEvent("topbar:search", { detail: { value } }));
    }, 250));
  }
});

/**
 * toast.js
 * ระบบแจ้งเตือนแบบ Toast (มุมขวาบน) — showToast()
 */

function ensureToastContainer() {
  let container = document.getElementById("toast-container");
  if (!container) {
    container = document.createElement("div");
    container.id = "toast-container";
    container.className = "fixed top-4 right-4 z-[9999] flex flex-col gap-2 w-[calc(100%-2rem)] sm:w-96";
    container.setAttribute("aria-live", "polite");
    document.body.appendChild(container);
  }
  return container;
}

const TOAST_STYLES = {
  success: { icon: "check-circle", bar: "bg-emerald-500", iconColor: "text-emerald-500" },
  error:   { icon: "x-circle",     bar: "bg-rose-500",    iconColor: "text-rose-500" },
  warning: { icon: "alert-triangle", bar: "bg-amber-500", iconColor: "text-amber-500" },
  info:    { icon: "info",         bar: "bg-sky-500",     iconColor: "text-sky-500" },
};

function showToast(message, type = "info", duration = 3500) {
  const container = ensureToastContainer();
  const style = TOAST_STYLES[type] || TOAST_STYLES.info;

  const toast = document.createElement("div");
  toast.className = "relative overflow-hidden bg-white shadow-lg rounded-xl ring-1 ring-slate-200 flex items-start gap-3 p-4 pr-8 translate-x-full opacity-0 transition-all duration-300";
  toast.innerHTML = `
    <div class="absolute left-0 top-0 bottom-0 w-1 ${style.bar}"></div>
    <i data-lucide="${style.icon}" class="w-5 h-5 mt-0.5 shrink-0 ${style.iconColor}"></i>
    <p class="text-sm text-slate-700 leading-snug">${escapeHTML(message)}</p>
    <button class="absolute top-2 right-2 text-slate-400 hover:text-slate-600" aria-label="ปิดการแจ้งเตือน">
      <i data-lucide="x" class="w-4 h-4"></i>
    </button>
  `;
  container.appendChild(toast);
  if (window.lucide) lucide.createIcons();

  requestAnimationFrame(() => toast.classList.remove("translate-x-full", "opacity-0"));

  const remove = () => { toast.classList.add("translate-x-full", "opacity-0"); setTimeout(() => toast.remove(), 300); };
  toast.querySelector("button").addEventListener("click", remove);
  setTimeout(remove, duration);
}

/**
 * Confirmation Modal แบบใช้ซ้ำได้ทั้งระบบ (สร้าง DOM ครั้งแรกแบบ lazy)
 * @param {{title:string, message:string, confirmText?:string, cancelText?:string, danger?:boolean, onConfirm:Function}} opts
 */
function showConfirmModal(opts) {
  let root = document.getElementById("global-confirm-modal");
  if (!root) {
    root = document.createElement("div");
    root.id = "global-confirm-modal";
    root.className = "hidden fixed inset-0 z-[70] flex items-center justify-center p-4";
    root.innerHTML = `
      <div class="modal-backdrop absolute inset-0 bg-slate-900/50 opacity-0"></div>
      <div class="modal-panel relative bg-white w-full max-w-sm rounded-2xl shadow-xl p-6 opacity-0 scale-95"></div>
    `;
    document.body.appendChild(root);
  }
  const panel = root.querySelector(".modal-panel");
  const iconWrap = opts.danger ? "bg-rose-50 text-rose-500" : "bg-indigo-50 text-indigo-600";
  const confirmBtnClass = opts.danger ? "bg-rose-600 hover:bg-rose-700" : "bg-indigo-600 hover:bg-indigo-700";

  panel.innerHTML = `
    <div class="w-11 h-11 rounded-full ${iconWrap} flex items-center justify-center mb-4">
      <i data-lucide="${opts.danger ? "alert-triangle" : "help-circle"}" class="w-5 h-5"></i>
    </div>
    <h3 class="font-semibold text-slate-800 mb-1.5">${escapeHTML(opts.title)}</h3>
    <p class="text-sm text-slate-500 mb-5 whitespace-pre-line">${escapeHTML(opts.message)}</p>
    <div class="flex justify-end gap-2">
      <button id="global-confirm-cancel" class="text-sm font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg px-4 py-2">${opts.cancelText || "ยกเลิก"}</button>
      <button id="global-confirm-ok" class="text-sm font-medium text-white ${confirmBtnClass} rounded-lg px-4 py-2">${opts.confirmText || "ยืนยัน"}</button>
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
  document.getElementById("global-confirm-cancel").onclick = close;
  backdrop.onclick = close;
  document.getElementById("global-confirm-ok").onclick = () => { opts.onConfirm(); close(); };
}

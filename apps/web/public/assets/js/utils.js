/**
 * utils.js
 * ฟังก์ชันช่วยเหลือทั่วไปที่ใช้ร่วมกันหลายหน้า
 * (escapeHTML, format, validate, debounce ฯลฯ)
 */

/** Escape ข้อมูลก่อนแสดงใน HTML เพื่อป้องกัน XSS — ห้ามใช้ innerHTML กับข้อมูลดิบโดยตรง */
function escapeHTML(str) {
  if (str === null || str === undefined) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function formatCurrency(num) {
  if (num === null || num === undefined || num === "") return "-";
  return new Intl.NumberFormat("th-TH", { maximumFractionDigits: 0 }).format(num) + " บาท";
}

function formatNumber(num) {
  return new Intl.NumberFormat("th-TH").format(num);
}

/** แปลง Date เป็นรูปแบบไทยอ่านง่าย เช่น 17 ก.ค. 2569 */
function formatThaiDate(dateStr, withTime = false) {
  if (!dateStr) return "-";
  const d = new Date(String(dateStr).replace(" ", "T"));
  if (isNaN(d)) return dateStr;
  const opts = withTime
    ? { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }
    : { day: "2-digit", month: "short", year: "numeric" };
  return d.toLocaleDateString("th-TH", opts);
}

function timeAgo(dateStr) {
  if (!dateStr) return "-";
  const now = new Date("2026-07-17T12:00:00");
  const d = new Date(String(dateStr).replace(" ", "T"));
  const diffMin = Math.round((now - d) / 60000);
  if (diffMin < 1) return "เมื่อสักครู่";
  if (diffMin < 60) return `${diffMin} นาทีที่แล้ว`;
  const diffHr = Math.round(diffMin / 60);
  if (diffHr < 24) return `${diffHr} ชั่วโมงที่แล้ว`;
  const diffDay = Math.round(diffHr / 24);
  return `${diffDay} วันที่แล้ว`;
}

function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validatePhone(phone) {
  return /^[0-9\-+()\s]{9,15}$/.test(phone);
}

function validateUrl(url) {
  if (!url) return true;
  try {
    const u = new URL(url);
    return u.protocol === "http:" || u.protocol === "https:";
  } catch (e) {
    return false;
  }
}

/** ดึง Domain แบบสั้นจาก URL เต็ม เพื่อแสดงในตาราง */
function shortDomain(url) {
  if (!url) return "";
  try {
    const u = new URL(url);
    return u.hostname.replace(/^www\./, "");
  } catch (e) {
    return url;
  }
}

function debounce(fn, delay = 300) {
  let timer = null;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}

/** สุ่ม id รูปแบบ PREFIX-YEAR-NNN แบบต่อเนื่องจาก array เดิม */
function generateSequentialId(prefix, existingItems) {
  const year = "2026";
  const maxNum = existingItems.reduce((max, item) => {
    const m = item.id.match(/(\d+)$/);
    return m ? Math.max(max, parseInt(m[1], 10)) : max;
  }, 0);
  return `${prefix}-${year}-${String(maxNum + 1).padStart(3, "0")}`;
}

/** Linear congruential generator แบบง่าย ให้ผลลัพธ์เดิมทุกครั้ง (deterministic mock data) */
function seededRandom(seed) {
  let s = seed;
  return function () {
    s = (s * 9301 + 49297) % 233280;
    return s / 233280;
  };
}

function pad(num, size) { return String(num).padStart(size, "0"); }

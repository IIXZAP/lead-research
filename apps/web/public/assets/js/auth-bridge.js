/**
 * auth-bridge.js (Laravel version)
 * -----------------------------------------------------------------
 * แทนที่ assets/js/auth.js ของ Demo (mock login/session ใน localStorage)
 * เมื่อรันบน Laravel: การยืนยันตัวตนเป็นหน้าที่ของ middleware ฝั่ง Server แล้ว
 * ไฟล์นี้แค่ส่งต่อข้อมูลผู้ใช้ปัจจุบันที่ Blade inject ไว้ใน window.LCD_CURRENT_USER
 * ให้ฟังก์ชันเดิม (getCurrentUser / checkAuthentication) ที่ permissions.js
 * และสคริปต์รายหน้า (leads.js ฯลฯ) เรียกใช้อยู่ ทำงานได้โดยไม่ต้องแก้โค้ดเหล่านั้น
 */

function getCurrentUser() {
  return window.LCD_CURRENT_USER || null;
}

function checkAuthentication() {
  // Server (middleware 'auth') คุมการ redirect ไปหน้า login แล้ว
  // ฝั่ง client จึงคืนค่า user ตรง ๆ — ถ้าไม่มีแปลว่า Blade ไม่ได้ inject ซึ่งไม่ควรเกิดบนหน้า protected
  return getCurrentUser();
}

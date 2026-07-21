/**
 * auth.js
 * ระบบ Authentication แบบจำลอง (Mock Login)
 * -----------------------------------------------------------------
 * ในระบบจริง: login() ต้องเรียก POST /api/login (Laravel Sanctum)
 * แล้วเก็บ token แทนการเก็บ user object ตรง ๆ แบบนี้
 * checkAuthentication() ควร verify token กับ Server ทุกครั้งที่จำเป็น
 * ห้ามเชื่อถือ account_type จาก Client เพียงอย่างเดียวเมื่อเชื่อมต่อระบบจริง
 */

const SESSION_KEY = "lcd_session";

function login(email, password, remember) {
  const user = MOCK_USERS.find(
    (u) => u.email.toLowerCase() === String(email).toLowerCase() && u.password === password
  );
  if (!user) return { success: false, message: "อีเมลหรือรหัสผ่านไม่ถูกต้อง" };

  const sessionUser = { id: user.id, name: user.name, email: user.email, account_type: user.account_type, avatar: user.avatar };
  const storage = remember ? localStorage : sessionStorage;
  storage.setItem(SESSION_KEY, JSON.stringify(sessionUser));
  const other = remember ? sessionStorage : localStorage;
  other.removeItem(SESSION_KEY);

  return { success: true, user: sessionUser };
}

function logout() {
  localStorage.removeItem(SESSION_KEY);
  sessionStorage.removeItem(SESSION_KEY);
  window.location.href = "login.html";
}

function getCurrentUser() {
  const raw = localStorage.getItem(SESSION_KEY) || sessionStorage.getItem(SESSION_KEY);
  if (!raw) return null;
  try { return JSON.parse(raw); } catch (e) { return null; }
}

function checkAuthentication() {
  const user = getCurrentUser();
  if (!user) { window.location.href = "login.html"; return null; }
  return user;
}

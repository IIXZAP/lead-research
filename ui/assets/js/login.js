/**
 * login.js
 * ควบคุมพฤติกรรมหน้า Login: Validation, แสดง/ซ่อนรหัสผ่าน, Demo Account, Loading State
 */

document.addEventListener("DOMContentLoaded", () => {
  if (window.lucide) lucide.createIcons();

  if (getCurrentUser()) { window.location.href = "dashboard.html"; return; }

  const form = document.getElementById("login-form");
  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("password");
  const toggleBtn = document.getElementById("toggle-password");
  const loginBtn = document.getElementById("login-btn");
  const loginBtnText = document.getElementById("login-btn-text");
  const spinner = document.getElementById("login-spinner");
  const formAlert = document.getElementById("form-alert");
  const formAlertText = document.getElementById("form-alert-text");

  toggleBtn.addEventListener("click", () => {
    const isPassword = passwordInput.type === "password";
    passwordInput.type = isPassword ? "text" : "password";
    toggleBtn.querySelector("i").setAttribute("data-lucide", isPassword ? "eye-off" : "eye");
    if (window.lucide) lucide.createIcons();
  });

  document.querySelectorAll(".demo-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      emailInput.value = btn.dataset.demo;
      passwordInput.value = "123456";
      clearFieldError(emailInput);
      clearFieldError(passwordInput);
    });
  });

  function showFieldError(input, message) {
    const errEl = input.parentElement.parentElement.querySelector(".field-error");
    input.classList.add("border-rose-400", "ring-rose-100");
    if (errEl) { errEl.textContent = message; errEl.classList.remove("hidden"); }
  }
  function clearFieldError(input) {
    const errEl = input.parentElement.parentElement.querySelector(".field-error");
    input.classList.remove("border-rose-400", "ring-rose-100");
    if (errEl) { errEl.textContent = ""; errEl.classList.add("hidden"); }
  }
  [emailInput, passwordInput].forEach((el) => el.addEventListener("input", () => clearFieldError(el)));

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    formAlert.classList.add("hidden");
    let hasError = false;
    if (!emailInput.value.trim()) { showFieldError(emailInput, "กรุณากรอกอีเมลหรือชื่อผู้ใช้งาน"); hasError = true; }
    if (!passwordInput.value.trim()) { showFieldError(passwordInput, "กรุณากรอกรหัสผ่าน"); hasError = true; }
    if (hasError) return;

    loginBtn.disabled = true;
    loginBtnText.textContent = "กำลังเข้าสู่ระบบ...";
    spinner.classList.remove("hidden");

    setTimeout(() => {
      const result = login(emailInput.value.trim(), passwordInput.value, document.getElementById("remember").checked);
      if (!result.success) {
        loginBtn.disabled = false;
        loginBtnText.textContent = "เข้าสู่ระบบ";
        spinner.classList.add("hidden");
        formAlertText.textContent = result.message;
        formAlert.classList.remove("hidden");
        if (window.lucide) lucide.createIcons();
        return;
      }
      loginBtnText.textContent = "สำเร็จ กำลังนำเข้าสู่ระบบ...";
      window.location.href = "dashboard.html";
    }, 600);
  });
});

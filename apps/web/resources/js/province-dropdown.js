import { PROVINCES_TH } from "./data/provinces.js";

function escapeHTML(str) {
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
}

// resources/js/province-dropdown.js
function renderProvinceOptions(
    term,
    dropdown,
    searchInput,
    hiddenInput,
    clearError,
) {
    const t = (term || "").trim();
    const options = ["ทุกจังหวัด", ...PROVINCES_TH].filter(
        (p) => !t || p.includes(t),
    );

    if (!options.length) {
        dropdown.innerHTML = `<div class="px-3 py-2 text-sm text-slate-400">ไม่พบจังหวัดที่ค้นหา</div>`;
        dropdown.classList.remove("hidden");
        return;
    }

    dropdown.innerHTML = options
        .map(
            (p) => `
        <button type="button"
                class="province-option w-full text-left px-3 py-2 text-sm text-slate-600 hover:bg-indigo-50 hover:text-indigo-700"
                data-province="${escapeHTML(p)}">
            ${escapeHTML(p)}
        </button>
    `,
        )
        .join("");

    dropdown.classList.remove("hidden");

    dropdown.querySelectorAll(".province-option").forEach((btn) => {
        btn.addEventListener("click", () => {
            searchInput.value = btn.dataset.province;
            // "ทุกจังหวัด" ควรส่งเป็นค่าว่าง (หมายถึงไม่กรอง) ไม่ใช่ข้อความนี้ตรงๆ
            hiddenInput.value =
                btn.dataset.province === "ทุกจังหวัด"
                    ? ""
                    : btn.dataset.province;
            dropdown.classList.add("hidden");
            if (clearError) clearError();
        });
    });
}

export function initProvinceDropdown() {
    const searchInput = document.getElementById("field-province-search");
    const hiddenInput = document.getElementById("field-province");
    const dropdown = document.getElementById("province-dropdown");

    if (!searchInput || !hiddenInput || !dropdown) return;

    const clearError = () => {
        const errEl = document.querySelector('[data-error-for="province"]');
        if (errEl) errEl.classList.add("hidden");
    };

    searchInput.addEventListener("input", () => {
        renderProvinceOptions(
            searchInput.value,
            dropdown,
            searchInput,
            hiddenInput,
            clearError,
        );
        // ถ้าผู้ใช้พิมพ์เอง ให้ล้างค่า hidden ไว้ก่อน จนกว่าจะเลือกจาก dropdown จริง
        hiddenInput.value = "";
    });

    searchInput.addEventListener("focus", () => {
        renderProvinceOptions(
            searchInput.value,
            dropdown,
            searchInput,
            hiddenInput,
            clearError,
        );
    });

    document.addEventListener("click", (e) => {
        if (
            !e.target.closest("#field-province-search") &&
            !e.target.closest("#province-dropdown")
        ) {
            dropdown.classList.add("hidden");
        }
    });
}

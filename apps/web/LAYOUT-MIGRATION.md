# Layout Migration — Demo (HTML/JS) → Laravel Blade

ขอบเขตรอบนี้: **เฉพาะ Layout กลาง** (`layouts/app.blade.php`, Sidebar, Topbar/Navbar, Footer และการย้าย Assets)
ยังไม่แปลงเนื้อหา Dashboard, Leads, Campaign ตามที่ตกลง

## ไฟล์ที่ได้

```text
apps/web/
├── resources/views/
│   ├── layouts/app.blade.php        ← โครงหลัก (head + flex + main + scripts) จาก 6 หน้าเดิม
│   ├── partials/sidebar.blade.php   ← จาก assets/js/sidebar.js (renderSidebar)
│   ├── partials/topbar.blade.php    ← จาก assets/js/topbar.js (renderTopbar)
│   ├── partials/footer.blade.php    ← ใหม่ (Demo เดิมไม่มี footer ในหน้าหลัก)
│   └── dashboard.blade.php          ← หน้าตัวอย่างทดสอบ layout (placeholder)
├── public/assets/
│   ├── css/app.css                  ← คัดลอกเดิม 100% (Fonts Google @import อยู่ในนี้)
│   ├── js/
│   │   ├── utils.js                 ← คัดลอกเดิม 100%
│   │   ├── toast.js                 ← คัดลอกเดิม 100%
│   │   ├── app.js                   ← คัดลอกเดิม 100% (UI helper ใช้ตอนแปลงหน้า content)
│   │   ├── sidebar.js               ← ตัดส่วน render ออก เหลือ interaction (logic เดิม)
│   │   └── topbar.js                ← ตัดส่วน render ออก เหลือ interaction (logic เดิม)
│   ├── images/                      ← เตรียมไว้ (Demo ไม่มีรูป local — avatar มาจาก pravatar.cc)
│   └── fonts/                       ← เตรียมไว้ (Demo ใช้ Google Fonts ผ่าน CDN)
└── LAYOUT-MIGRATION.md
```

## วิธีใช้งานในหน้าใหม่

```blade
@extends('layouts.app')

@section('page', 'campaigns')          {{-- ใช้กำหนด active menu + breadcrumb --}}
@section('title', 'Campaigns')
@section('main-class', 'space-y-5 max-w-[1600px]')  {{-- คง layout เดิมของแต่ละหน้า --}}

@section('content')
  ...
@endsection
```

ค่า `main-class` ต่อหน้า (ตาม Demo เดิม):

| หน้า | main-class |
|---|---|
| dashboard | `space-y-6 max-w-[1600px]` |
| campaigns / leads | `space-y-5 max-w-[1600px]` |
| campaign-create | `max-w-4xl` |
| campaign-detail | `space-y-5 max-w-[1600px]` |
| business-info | `space-y-5 max-w-[1400px]` |

## Route ที่ Layout อ้างอิง (ใส่ใน routes/web.php)

```php
Route::middleware(['auth'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    // เตรียมชื่อ route ไว้ — Sidebar จะ fallback เป็น url('/campaigns') ถ้ายังไม่มี
    // Route::get('/campaigns', ...)->name('campaigns.index');
    // Route::get('/leads', ...)->name('leads.index');
});
```

Sidebar/Topbar ตรวจ `Route::has()` ก่อนเสมอ จึง**รันได้ทันทีแม้ route อื่นยังไม่ถูกสร้าง**

## สิ่งที่ "คงเดิม 100%"

- DOM structure, id, class name ทุกจุดของ Sidebar/Topbar (ถอดจาก template string ใน JS ตรง ๆ)
- localStorage key เดิมทั้งหมด: `lcd_sidebar_collapsed`, `lcd_dark_mode` — สถานะย่อเมนู/dark mode ของผู้ใช้เดิมยังใช้ได้
- พฤติกรรม: ย่อเมนู desktop, edge toggle, mobile drawer + overlay, dropdown แจ้งเตือน/ผู้ใช้ (คลิกนอกเพื่อปิด), dark mode toggle
- Tailwind config, ฟอนต์ Inter + Noto Sans Thai, Lucide icons, app.css

## สิ่งที่ "เปลี่ยน" (พร้อมเหตุผล)

1. **Sidebar/Topbar render ฝั่ง Server** — เดิม JS สร้าง HTML ทั้งก้อนหลัง DOMContentLoaded (จอว่างแวบหนึ่ง) ตอนนี้ HTML มาพร้อมหน้าเลย, JS เหลือเฉพาะ interaction
2. **ข้อมูลผู้ใช้มาจาก `auth()->user()`** — มี fallback เป็น object demo (System Admin) เพื่อให้ preview ได้ก่อนต่อ Auth จริง ต่อ Sanctum/Breeze เมื่อไหร่ ลบ fallback ได้เลย
3. **Logout เป็นฟอร์ม `POST /logout` + CSRF** — ปุ่มเดิม (id เดิม) submit ฟอร์มกลาง `#logout-form` แทน mock `logout()`
4. **Search ใน Topbar** — เดิมรับ `options.onSearch` ต่อหน้า → เปลี่ยนเป็น `window.onTopbarSearch(value)` หรือ CustomEvent `topbar:search`
5. **สถานะย่อเมนู apply ก่อน paint** — inline script ท้าย sidebar partial อ่าน localStorage ทันที ไม่ต้องรอ DOMContentLoaded (เดิมทำตอน render JS)
6. **Dark mode ใส่ class ตั้งแต่ `<head>`** — กันหน้ากระพริบขาวก่อนสลับเป็นมืด
7. **Footer เป็นของใหม่** — Demo หน้าหลักเดิมไม่มี ถ้าต้องการเหมือนเดิมเป๊ะให้ลบ `@include('partials.footer')` ออกจาก layout
8. **Notification ใน Topbar ยังเป็นชุด mock เดิม** hardcode ใน Blade — รอเปลี่ยนเป็นข้อมูลจริงเมื่อทำระบบ notifications
9. **ไม่ย้าย `mock-data.js`, `auth.js`, `permissions.js` เข้า public/** — สามไฟล์นี้เป็นของฝั่ง mock ที่จะถูกแทนด้วย Laravel Auth + Policy + API (ตาม README ของ Demo เอง)

## ขั้นถัดไป (แนะนำ)

1. แปลงเนื้อหาหน้า Dashboard → Blade + ข้อมูลจริง (ApexCharts push ผ่าน `@push('head')` — มีตัวอย่างใน dashboard.blade.php)
2. แตก Blade Components: status-badge, progress-bar, summary-card, business-row ฯลฯ (map จากฟังก์ชันใน app.js)
3. ย้าย Tailwind Play CDN → Vite build + `tailwind.config.js` (config พร้อมย้ายแล้ว เพราะเหมือนกันทุกหน้า)
4. Layout สำหรับหน้า Login แยกเป็น `layouts/guest.blade.php` (โครงต่างจากหน้าอื่นทั้งหมด)

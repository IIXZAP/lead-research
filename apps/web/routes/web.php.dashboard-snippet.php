<?php

/*
|--------------------------------------------------------------------------
| Dashboard route — เพิ่มลงใน routes/web.php ของโปรเจกต์
|--------------------------------------------------------------------------
| ข้อกำหนดข้อ 12 + 15: มี Named Route dashboard.index และ Backend ต้องมี
| middleware ป้องกัน (ไม่พึ่งการซ่อนปุ่มฝั่ง UI เพียงอย่างเดียว)
|
| middleware 'auth' = ต้องล็อกอินก่อน
| ถ้าต้องการจำกัดสิทธิ์การ "สร้าง Campaign" ให้ทำที่ route ของ campaigns.create
| ด้วย Gate/Policy หรือ middleware('can:campaign.create') — ห้ามเชื่อ account_type จาก client
*/

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
});

/*
| ถ้าโปรเจกต์ยังไม่มี named route เหล่านี้ การ์ด/ลิงก์ในหน้า Dashboard จะ fallback
| เป็น path ตรงโดยอัตโนมัติ (routeOr ใน Controller กันหน้าเว็บล่ม):
|   - campaigns.index   → /campaigns
|   - campaigns.create  → /campaigns/create
|   - campaigns.show    → /campaigns/{campaign}
|   - leads.index       → /leads
|   - business.show     → /businesses/{business}
*/

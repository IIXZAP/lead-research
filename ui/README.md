# Lead Campaign Dashboard — Front-end Demo

Dashboard สำหรับสร้าง Campaign ค้นหา Lead ลูกค้าธุรกิจ (Business Lead Generation)
สไตล์ Modern SaaS/CRM สร้างด้วย HTML5 + Tailwind CSS + Vanilla JavaScript
พร้อม Mock Data ใช้งานได้ทันทีโดยไม่ต้องมี Backend, Database หรือ Python Service จริง

## วิธี Run Project

ไม่ต้องติดตั้งอะไรเพิ่ม เพราะทุก Library โหลดผ่าน CDN (Tailwind, Lucide Icons, ApexCharts)

```bash
cd lead-campaign-dashboard
python3 -m http.server 8080
# เปิด http://localhost:8080/login.html
```

หรือเปิด `login.html` ตรง ๆ ด้วย Browser / VS Code "Live Server" ก็ได้

## Demo Account

| Role | Email | Password |
|---|---|---|
| Admin | admin@example.com | 123456 |
| Manager | manager@example.com | 123456 |
| Sale | sale@example.com | 123456 |

## โครงสร้างไฟล์

```text
lead-campaign-dashboard/
├── login.html                    หน้า Login
├── dashboard.html                 ภาพรวม Campaign + Lead
├── campaigns.html                 รายการ Campaign (Search/Filter/Sort/Pagination/Bulk)
├── campaign-create.html           Wizard สร้าง/แก้ไข Campaign (4 Steps)
├── campaign-detail.html           รายละเอียด Campaign (Overview/Leads/Search Criteria/Activity Logs)
├── leads.html                     Lead Directory รวมทุก Campaign
├── business-info.html             ข้อมูลเชิงลึกของบริษัท (DBD/Contact/Website/Lead Analysis/Timeline)
├── assets/
│   ├── css/app.css                Font, Skeleton, Progress bar, Dark mode, Transition
│   ├── js/
│   │   ├── mock-data.js           Mock Users, Campaigns (10), Businesses/Leads (24)
│   │   ├── auth.js                login() / logout() / getCurrentUser() / checkAuthentication()
│   │   ├── permissions.js         hasPermission() + ตาราง Role → Permission + scope filters
│   │   ├── utils.js                escapeHTML, format, validate, debounce ฯลฯ
│   │   ├── toast.js                showToast() + showConfirmModal()
│   │   ├── app.js                  Badge/Progress/Empty/Error state + business row/card renderer ที่ใช้ร่วมกัน
│   │   ├── sidebar.js              renderSidebar() — collapse/drawer/active state
│   │   ├── topbar.js               renderTopbar() — breadcrumb/search/notification/user menu/dark mode
│   │   ├── login.js                Logic หน้า Login
│   │   ├── dashboard.js            Logic หน้า Dashboard
│   │   ├── campaigns.js            Logic หน้า Campaign List
│   │   ├── campaign-form.js        Logic Wizard สร้าง/แก้ไข Campaign
│   │   ├── campaign-detail.js      Logic หน้า Campaign Detail (4 Tabs)
│   │   ├── leads.js                Logic หน้า Lead Directory
│   │   └── business-info.js        Logic หน้า Business Information (6 Tabs)
│   └── images/
└── README.md
```

## สิทธิ์การใช้งานตาม account_type

| ความสามารถ | admin | manager | sale |
|---|:---:|:---:|:---:|
| สร้าง Campaign | ✅ | ✅ | ❌ |
| ดู Campaign | ทั้งหมด | ของทีม | ที่ได้รับมอบหมาย |
| แก้ไข/ลบ Campaign | ✅ / ✅ | ✅ / ❌ | ❌ / ❌ |
| เริ่ม/หยุด/ลองใหม่ Campaign | ✅ | ✅ | ❌ |
| ดู Lead / Business | ทั้งหมด | ทั้งหมด | ที่ได้รับมอบหมาย |
| มอบหมาย Lead ให้ Sale | ✅ | ✅ | ❌ |
| จัดการผู้ใช้งาน | ✅ | ❌ | ❌ |

`hasPermission(permissionName)` ใน `permissions.js` ถูกเรียกใช้ทั้งตอน **render UI** (ซ่อน/แสดงปุ่ม)
และ **ก่อนทำ Action จริง** (ก่อน submit/ลบ/มอบหมาย) เพื่อไม่ให้การป้องกันเป็นเพียงการซ่อนด้วย CSS

## จุดที่ต้องแก้ไขเมื่อเชื่อมต่อ Backend จริง

### Laravel (PHP) + MySQL — ฝั่ง Web/API หลัก

1. **auth.js** — แทนที่ `login()` ด้วย `POST /api/login` (Laravel Sanctum) แล้วเก็บ token แทน user object ตรง ๆ
   `checkAuthentication()` ควร verify token กับ `GET /api/me` แทนการเช็ค localStorage เฉย ๆ
2. **permissions.js** — Policy/Gate ฝั่ง Laravel ต้องตรวจสอบ `account_type` และสิทธิ์ซ้ำทุก Endpoint
   ห้ามเชื่อถือค่า `account_type` ที่ส่งมาจาก Client
3. **mock-data.js** — แทนที่ `loadCampaignsFromStorage()` / `loadBusinessesFromStorage()` ด้วยการเรียก REST API
   คง shape ของ object เดิมไว้ (ดูตัวอย่างด้านล่าง) เพื่อลดการแก้โค้ดหน้าอื่น

### Python FastAPI — ฝั่งประมวลผล Lead

Python Service มีหน้าที่: แปลงคำสั่ง/เงื่อนไข Campaign, เรียก DBD/Search API, Crawl เว็บไซต์, วิเคราะห์ Lead ด้วย AI
แล้ว Callback ผลกลับมาที่ Laravel ทีละ Stage (ดู `pipeline` ใน mock-data.js ที่จำลอง 7 Stage นี้ไว้แล้ว)

### API ที่ต้องเตรียม (mapping จากฟังก์ชัน Mock Service ปัจจุบัน)

| Mock Function (ไฟล์) | Future REST Endpoint | หมายเหตุ |
|---|---|---|
| `fetchCampaigns()` (campaigns.js) | `GET /api/v1/campaigns` | รองรับ query params: status, province, business_type, creator, date_from |
| `fetchCampaignDetail()` (campaign-detail.js) | `GET /api/v1/campaigns/{id}` | ต้องตรวจ Campaign Ownership ก่อนคืนค่า |
| Tab Leads ใน campaign-detail.js | `GET /api/v1/campaigns/{id}/leads` | รองรับ pagination/filter เหมือนกับ `GET /api/v1/leads` |
| `mockCreateCampaignApiCall()` (campaign-form.js) | `POST /api/v1/campaigns` | Payload shape ดูตัวอย่างด้านล่าง |
| แก้ไข Campaign ใน `submitCampaign()` | `PUT /api/v1/campaigns/{id}` | เฉพาะสถานะ draft/queued เท่านั้น |
| Action Start/Pause/Retry/Delete (campaigns.js, campaign-detail.js) | `POST /api/v1/campaigns/{id}/start` `.../pause` `.../retry` และ `DELETE /api/v1/campaigns/{id}` | ต้องตรวจสถานะปัจจุบันก่อนอนุญาต Action |
| `fetchLeadsList()` (leads.js) | `GET /api/v1/leads` | รวม Business/Lead จากทุก Campaign ที่ผู้ใช้มีสิทธิ์เห็น |
| `fetchBusinessInformation()` (business-info.js) | `GET /api/v1/businesses/{id}` | ข้อมูลจาก DBD + Website Analysis + Lead Analysis |
| Assign/Edit ใน business-info.js | `PATCH /api/v1/businesses/{id}/assign` และ `PATCH /api/v1/businesses/{id}` | |

### ตัวอย่าง Request/Response

**POST /api/v1/campaigns** (สร้าง Campaign)
```json
{
  "title": "Lead โรงงานผลิตอาหาร กรุงเทพฯ และปริมณฑล",
  "target": { "country": "TH", "province": "กรุงเทพมหานคร", "search_radius_km": 25 },
  "business_target": {
    "registered_business": "บริษัทจำกัด",
    "business_type": "โรงงานผลิตอาหาร",
    "business_keywords": ["อาหารแปรรูป", "OEM"],
    "description": "ค้นหาโรงงานผลิตอาหารที่มีเว็บไซต์และมีโอกาสต้องการปรับปรุงเว็บไซต์หรือระบบขาย"
  },
  "maximum_leads": 100,
  "required_fields": { "website": true, "phone": true, "email": false, "address": true, "registration_number": true },
  "duplicate_handling": "skip"
}
```

**Response**
```json
{
  "id": "CMP-2026-011",
  "status": "queued",
  "progress": 0,
  "created_at": "2026-07-17T12:00:00Z"
}
```

**GET /api/v1/businesses/{id}** (ตัวอย่าง Response ย่อ)
```json
{
  "id": "BUS-2026-001",
  "campaign_id": "CMP-2026-001",
  "company_name_th": "บริษัท ตัวอย่าง ฟู้ด จำกัด",
  "legal_entity": { "registration_number": "0105566123456", "status": "ยังดำเนินกิจการอยู่" },
  "website_info": { "url": "https://examplefood.co.th", "status": "Online", "ssl": true },
  "lead_analysis": { "score": 82, "quality": "High", "confidence_score": 0.87 }
}
```

## Security (แนวทางที่เตรียมไว้ แม้เป็น Front-end Demo)

- ทุกจุดที่แสดงข้อมูลจาก object ใช้ `escapeHTML()` ก่อนเสมอ ไม่ใช้ `innerHTML` กับข้อมูลดิบโดยตรง
- Validate Email/Phone/URL ทั้งฝั่ง Client (UX) — ฝั่ง Server ต้อง validate ซ้ำเสมอ
- ป้องกัน Double Submit ด้วย `wizardState.submitting` flag ระหว่างสร้าง Campaign
- Campaign Ownership ตรวจสอบผ่าน `canAccessCampaign()` ก่อนแสดงหรือแก้ไขข้อมูลทุกครั้ง
- ข้อมูลจาก DBD ต้องแสดงแหล่งที่มาและวันที่อัปเดตเสมอ (ดู Data Source Badge ในหน้า Business Information)
- ข้อมูลที่ AI วิเคราะห์ (Lead Analysis, AI Business Summary) ถูกแยกป้ายกำกับชัดเจนว่าไม่ใช่ข้อมูลทางการ
- เตรียมตำแหน่งสำหรับ CSRF Token และ API Authentication Header (`Authorization: Bearer <token>`) เมื่อเชื่อมต่อจริง

## หมายเหตุการออกแบบ

- ข้อมูล Campaign/Business ถูกเก็บใน `localStorage` (key: `lcd_campaigns`, `lcd_businesses`) เพื่อให้ทดลอง
  สร้าง/แก้ไข/ลบ แล้วข้อมูลคงอยู่ข้ามการ reload หน้า ล้าง localStorage ของเบราว์เซอร์เพื่อรีเซ็ตกลับค่าเริ่มต้น
- วันที่ปัจจุบันของระบบถูก fix ไว้ที่ `2026-07-17` เพื่อให้ Mock Data (Progress, Activity Timeline, Follow-up)
  สาธิตสถานการณ์ต่าง ๆ ได้แน่นอนไม่ว่าจะเปิดวันไหน — ในระบบจริงให้ใช้เวลาปัจจุบันจริงจาก Server
- `businessRowHtml()` / `businessCardHtml()` ใน `app.js` ถูกออกแบบให้ใช้ร่วมกันระหว่างหน้า Leads Directory
  และ Tab "Leads" ภายใน Campaign Detail เพื่อลดโค้ดซ้ำซ้อน

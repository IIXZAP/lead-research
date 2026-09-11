/**
 * mock-data.js
 * ข้อมูลจำลองทั้งหมด: Users, Campaigns, Businesses (Leads)
 * -----------------------------------------------------------------
 * เมื่อเชื่อมต่อ Backend จริง ให้แทนที่ฟังก์ชัน loadCampaignsFromStorage(),
 * loadBusinessesFromStorage() และค่าคงที่ MOCK_USERS ด้วยการเรียก REST API
 * ผ่านฟังก์ชันใน mock-service.js (ดูหัวข้อ API Preparation ใน README)
 * แต่คง "shape" ของ object ให้เหมือนเดิมเพื่อไม่ต้องแก้หน้าอื่น
 */

// ---------------------------------------------------------------
// USERS (Mock Login)
// ---------------------------------------------------------------
const MOCK_USERS = [
  { id: 1, name: "System Admin",  email: "admin@example.com",   password: "123456", account_type: "admin",   avatar: "https://i.pravatar.cc/150?img=12" },
  { id: 2, name: "Sale Manager",  email: "manager@example.com", password: "123456", account_type: "manager", avatar: "https://i.pravatar.cc/150?img=32" },
  { id: 3, name: "Sale User",     email: "sale@example.com",    password: "123456", account_type: "sale",    avatar: "https://i.pravatar.cc/150?img=47" },
  { id: 4, name: "Sale User 2",   email: "sale2@example.com",   password: "123456", account_type: "sale",    avatar: "https://i.pravatar.cc/150?img=21" },
];

// ---------------------------------------------------------------
// ENUMS
// ---------------------------------------------------------------
const CAMPAIGN_STATUS_META = {
  draft:                { label: "Draft",                color: "bg-slate-100 text-slate-600 ring-slate-500/20" },
  queued:                { label: "Queued",               color: "bg-blue-50 text-blue-700 ring-blue-600/20" },
  processing:            { label: "Processing",           color: "bg-amber-50 text-amber-700 ring-amber-600/20" },
  partially_completed:   { label: "Partially Completed",  color: "bg-orange-50 text-orange-700 ring-orange-600/20" },
  completed:             { label: "Completed",            color: "bg-emerald-50 text-emerald-700 ring-emerald-600/20" },
  failed:                { label: "Failed",                color: "bg-rose-50 text-rose-700 ring-rose-600/20" },
  cancelled:             { label: "Cancelled",             color: "bg-slate-200 text-slate-500 ring-slate-500/20" },
};
const CAMPAIGN_STATUSES = Object.keys(CAMPAIGN_STATUS_META);

const LEAD_STATUS_META = {
  new:            { label: "New",         color: "bg-slate-100 text-slate-600" },
  reviewed:       { label: "Reviewed",    color: "bg-sky-50 text-sky-700" },
  contacted:      { label: "Contacted",   color: "bg-indigo-50 text-indigo-700" },
  qualified:      { label: "Qualified",   color: "bg-violet-50 text-violet-700" },
  follow_up:      { label: "Follow Up",   color: "bg-amber-50 text-amber-700" },
  proposal:       { label: "Proposal",    color: "bg-purple-50 text-purple-700" },
  won:            { label: "Won",         color: "bg-emerald-50 text-emerald-700" },
  lost:           { label: "Lost",        color: "bg-rose-50 text-rose-700" },
  rejected:       { label: "Rejected",    color: "bg-slate-200 text-slate-500" },
};
const LEAD_STATUSES = Object.keys(LEAD_STATUS_META);

const PROVINCES_TH = [
  "กรุงเทพมหานคร", "สมุทรปราการ", "นนทบุรี", "ปทุมธานี", "พระนครศรีอยุธยา",
  "ชลบุรี", "ระยอง", "ฉะเชิงเทรา", "สมุทรสาคร", "นครปฐม",
  "เชียงใหม่", "เชียงราย", "ลำปาง", "ขอนแก่น", "นครราชสีมา",
  "อุดรธานี", "อุบลราชธานี", "สุราษฎร์ธานี", "ภูเก็ต", "สงขลา",
  "นครศรีธรรมราช", "ประจวบคีรีขันธ์", "เพชรบุรี", "ราชบุรี", "กาญจนบุรี",
];

const REGISTERED_BUSINESS_TYPES = [
  "บริษัทจำกัด", "บริษัทมหาชนจำกัด", "ห้างหุ้นส่วนจำกัด", "ห้างหุ้นส่วนสามัญนิติบุคคล",
  "วิสาหกิจชุมชน", "ผู้ประกอบการรายบุคคล", "ไม่จำกัดรูปแบบนิติบุคคล",
];

const BUSINESS_TYPES = [
  "โรงงานผลิตอาหาร", "ร้านอาหาร", "โรงแรมและที่พัก", "อสังหาริมทรัพย์", "ก่อสร้าง",
  "โลจิสติกส์และขนส่ง", "คลังสินค้า", "ค้าปลีก", "ค้าส่ง", "โรงงานอุตสาหกรรม",
  "เครื่องสำอาง", "การแพทย์และคลินิก", "การศึกษา", "เทคโนโลยี", "บริการด้านการตลาด",
  "บริการด้านบัญชี", "นำเข้าและส่งออก", "ธุรกิจ E-commerce", "อื่น ๆ",
];

const DUPLICATE_HANDLING_OPTIONS = [
  { value: "skip", label: "ข้ามบริษัทที่มีอยู่แล้ว" },
  { value: "update", label: "อัปเดตข้อมูลบริษัทเดิม" },
  { value: "new_lead", label: "เพิ่มเป็น Lead ใหม่" },
  { value: "review", label: "ขอให้ผู้ใช้ตรวจสอบก่อน" },
];

const WEBSITE_PROBLEM_POOL = [
  { text: "เว็บไซต์โหลดช้า", severity: "Medium" },
  { text: "ไม่รองรับ Mobile", severity: "High" },
  { text: "ไม่มี SSL", severity: "Critical" },
  { text: "ไม่มี Meta Description", severity: "Low" },
  { text: "ไม่พบ Google Analytics", severity: "Low" },
  { text: "ไม่มี Contact Form", severity: "Medium" },
  { text: "เว็บไซต์มี Error", severity: "High" },
  { text: "เว็บไซต์ไม่ได้อัปเดตนาน", severity: "Medium" },
  { text: "ไม่พบ Call to Action", severity: "Medium" },
  { text: "ไม่พบข้อมูลติดต่อที่ชัดเจน", severity: "High" },
];

const RECOMMENDED_SERVICES_POOL = ["Website Redesign", "SEO", "CRM Integration", "E-commerce System", "Digital Marketing", "Brand Identity"];
const PAIN_POINT_POOL = ["เว็บไซต์ไม่รองรับมือถือ", "โหลดช้า", "ไม่มีระบบเก็บ Lead", "ไม่มี SSL", "ข้อมูลติดต่อไม่ชัดเจน", "ไม่มีระบบขายออนไลน์"];

// ---------------------------------------------------------------
// Campaign Titles / Keywords pool (สำหรับสร้างชื่อ Campaign ให้หลากหลาย)
// ---------------------------------------------------------------
const CAMPAIGN_TEMPLATES = [
  { title: "Lead โรงงานผลิตอาหาร กรุงเทพฯ และปริมณฑล", type: "โรงงานผลิตอาหาร", keywords: ["อาหารแปรรูป", "OEM", "โรงงานมาตรฐาน GMP"] },
  { title: "ค้นหาร้านอาหารในเชียงใหม่ที่ยังไม่มีเว็บไซต์", type: "ร้านอาหาร", keywords: ["ร้านอาหาร", "เว็บไซต์ร้านอาหาร", "เดลิเวอรี่"] },
  { title: "Lead โรงแรมและที่พักภูเก็ต", type: "โรงแรมและที่พัก", keywords: ["โรงแรม", "รีสอร์ท", "จองห้องพักออนไลน์"] },
  { title: "บริษัทอสังหาริมทรัพย์ นนทบุรี-ปทุมธานี", type: "อสังหาริมทรัพย์", keywords: ["คอนโด", "บ้านจัดสรร", "นายหน้า"] },
  { title: "ผู้รับเหมาก่อสร้างในชลบุรี", type: "ก่อสร้าง", keywords: ["รับเหมาก่อสร้าง", "วัสดุก่อสร้าง"] },
  { title: "โลจิสติกส์และคลังสินค้า สมุทรปราการ", type: "โลจิสติกส์และขนส่ง", keywords: ["ขนส่งสินค้า", "คลังสินค้า", "Fleet"] },
  { title: "ร้านค้าปลีกในขอนแก่นที่ต้องการระบบ POS", type: "ค้าปลีก", keywords: ["ค้าปลีก", "POS", "สต๊อกสินค้า"] },
  { title: "โรงงานอุตสาหกรรมในระยอง", type: "โรงงานอุตสาหกรรม", keywords: ["นิคมอุตสาหกรรม", "โรงงาน", "ISO"] },
  { title: "คลินิกความงามและเครื่องสำอาง กรุงเทพฯ", type: "เครื่องสำอาง", keywords: ["คลินิกความงาม", "เครื่องสำอาง", "ผิวพรรณ"] },
  { title: "ธุรกิจ E-commerce ที่ต้องการระบบ CRM", type: "ธุรกิจ E-commerce", keywords: ["E-commerce", "CRM", "ขายออนไลน์"] },
];

function generateMockCampaigns(count = 10) {
  const rand = seededRandom(101);
  const campaigns = [];
  const now = new Date("2026-07-17T12:00:00");
  const creators = MOCK_USERS.filter((u) => u.account_type !== "sale");

  for (let i = 0; i < count; i++) {
    const tpl = CAMPAIGN_TEMPLATES[i % CAMPAIGN_TEMPLATES.length];
    const status = CAMPAIGN_STATUSES[Math.floor(rand() * CAMPAIGN_STATUSES.length)];
    const creator = creators[Math.floor(rand() * creators.length)];
    const maxLeads = [25, 50, 100, 250, 500][Math.floor(rand() * 5)];

    let progress, leadsFound;
    if (status === "draft" || status === "queued") { progress = 0; leadsFound = 0; }
    else if (status === "completed") { progress = 100; leadsFound = Math.floor(maxLeads * (0.7 + rand() * 0.3)); }
    else if (status === "partially_completed") { progress = 100; leadsFound = Math.floor(maxLeads * (0.3 + rand() * 0.3)); }
    else if (status === "processing") { progress = Math.floor(10 + rand() * 80); leadsFound = Math.floor(maxLeads * (progress / 100) * 0.8); }
    else if (status === "failed") { progress = Math.floor(rand() * 60); leadsFound = Math.floor(maxLeads * (progress / 100) * 0.5); }
    else { progress = Math.floor(rand() * 50); leadsFound = Math.floor(maxLeads * (progress / 100) * 0.5); } // cancelled

    const createdDaysAgo = Math.floor(rand() * 40);
    const createdAt = new Date(now.getTime() - createdDaysAgo * 86400000);
    const updatedAt = new Date(createdAt.getTime() + Math.floor(rand() * createdDaysAgo + 1) * 3600000 * 4);

    // sale ที่ถูกมอบหมายให้ดูแล Campaign นี้ (ใช้ scope สิทธิ์ของ role sale)
    const salesUsers = MOCK_USERS.filter((u) => u.account_type === "sale");
    const assignedCount = 1 + Math.floor(rand() * salesUsers.length);
    const assignedUserIds = salesUsers.slice(0, assignedCount).map((u) => u.id);

    campaigns.push({
      id: `CMP-2026-${pad(i + 1, 3)}`,
      title: tpl.title,
      target: {
        country: "TH",
        province: PROVINCES_TH[Math.floor(rand() * PROVINCES_TH.length)],
        search_radius_km: [5, 10, 25, 50, 100, 200][Math.floor(rand() * 6)],
      },
      business_target: {
        registered_business: rand() > 0.15 ? REGISTERED_BUSINESS_TYPES[Math.floor(rand() * (REGISTERED_BUSINESS_TYPES.length - 1))] : "",
        business_type: tpl.type,
        business_keywords: tpl.keywords,
        description: `ค้นหาบริษัทประเภท ${tpl.type} ที่มีศักยภาพเป็นลูกค้าเป้าหมาย โดยเน้นพื้นที่ ${PROVINCES_TH[Math.floor(rand() * PROVINCES_TH.length)]} และบริเวณใกล้เคียง มีเว็บไซต์หรือช่องทางติดต่อที่ตรวจสอบได้ และมีแนวโน้มต้องการปรับปรุงระบบขายหรือการตลาดออนไลน์`,
      },
      maximum_leads: maxLeads,
      leads_found: leadsFound,
      required_fields: {
        website: rand() > 0.2, phone: rand() > 0.1, email: rand() > 0.5, address: rand() > 0.3,
        registration_number: rand() > 0.3, directors: rand() > 0.7, registered_capital: rand() > 0.7,
      },
      duplicate_handling: DUPLICATE_HANDLING_OPTIONS[Math.floor(rand() * DUPLICATE_HANDLING_OPTIONS.length)].value,
      status,
      progress,
      assigned_user_ids: assignedUserIds,
      created_by: { id: creator.id, name: creator.name, account_type: creator.account_type },
      created_at: createdAt.toISOString().slice(0, 19).replace("T", " "),
      updated_at: updatedAt.toISOString().slice(0, 19).replace("T", " "),
      pipeline: buildCampaignPipeline(status, progress, rand),
      activity_logs: buildCampaignActivityLogs(status, creator, createdAt),
    });
  }
  return campaigns;
}

const PIPELINE_STEPS = [
  "รับคำสั่ง Campaign", "ค้นหาบริษัท", "ตรวจสอบเว็บไซต์",
  "ดึงข้อมูลนิติบุคคลจาก DBD", "ตรวจสอบข้อมูลติดต่อ", "บันทึก Lead", "เสร็จสิ้น",
];

function buildCampaignPipeline(status, progress, rand) {
  const stepCount = PIPELINE_STEPS.length;
  let doneSteps;
  if (status === "draft") doneSteps = 0;
  else if (status === "queued") doneSteps = 0;
  else if (status === "completed") doneSteps = stepCount;
  else if (status === "failed") doneSteps = Math.floor((progress / 100) * (stepCount - 1));
  else doneSteps = Math.floor((progress / 100) * stepCount);

  return PIPELINE_STEPS.map((label, i) => {
    let state;
    if (status === "failed" && i === doneSteps) state = "failed";
    else if (i < doneSteps) state = "completed";
    else if (i === doneSteps && (status === "processing" || status === "queued")) state = "processing";
    else state = "waiting";
    return { step: i + 1, label, state };
  });
}

function buildCampaignActivityLogs(status, creator, createdAt) {
  const logs = [
    { icon: "plus-circle", text: "สร้าง Campaign", user: creator.name, at: createdAt.toISOString().slice(0, 19).replace("T", " "), source: "system", status: "completed" },
  ];
  if (status !== "draft") {
    const t2 = new Date(createdAt.getTime() + 3600000);
    logs.push({ icon: "play", text: "เริ่มประมวลผล Campaign", user: creator.name, at: t2.toISOString().slice(0, 19).replace("T", " "), source: "system", status: "completed" });
  }
  if (["processing", "completed", "partially_completed", "failed"].includes(status)) {
    const t3 = new Date(createdAt.getTime() + 7200000);
    logs.push({ icon: "search", text: "ค้นหาบริษัทจากพื้นที่เป้าหมาย", user: "Python Lead Service", at: t3.toISOString().slice(0, 19).replace("T", " "), source: "python-agent", status: "completed" });
  }
  if (status === "failed") {
    const t4 = new Date(createdAt.getTime() + 10800000);
    logs.push({ icon: "alert-triangle", text: "เกิดข้อผิดพลาดขณะดึงข้อมูลจาก DBD (Timeout)", user: "Python Lead Service", at: t4.toISOString().slice(0, 19).replace("T", " "), source: "python-agent", status: "failed" });
  }
  if (status === "completed" || status === "partially_completed") {
    const t5 = new Date(createdAt.getTime() + 14400000);
    logs.push({ icon: "check-circle-2", text: "ประมวลผล Campaign เสร็จสิ้น", user: "Python Lead Service", at: t5.toISOString().slice(0, 19).replace("T", " "), source: "python-agent", status: "completed" });
  }
  if (status === "cancelled") {
    const t6 = new Date(createdAt.getTime() + 5400000);
    logs.push({ icon: "x-octagon", text: "ผู้ใช้งานยกเลิก Campaign", user: creator.name, at: t6.toISOString().slice(0, 19).replace("T", " "), source: "manual", status: "cancelled" });
  }
  return logs;
}

// ---------------------------------------------------------------
// COMPANIES / BUSINESSES (Lead ที่ค้นพบภายใน Campaign)
// ---------------------------------------------------------------
const COMPANY_NAME_POOL = [
  ["บริษัท ตัวอย่าง ฟู้ด จำกัด", "EXAMPLE FOOD COMPANY LIMITED", "https://examplefood.co.th"],
  ["บริษัท สยาม โลจิสติกส์ จำกัด", "SIAM LOGISTICS CO., LTD.", "https://siamlogistics.co.th"],
  ["ห้างหุ้นส่วนจำกัด รุ่งเรืองก่อสร้าง", "RUNGRUANG CONSTRUCTION LTD., PART.", null],
  ["บริษัท กรีนฟาร์ม ออร์แกนิค จำกัด", "GREEN FARM ORGANIC CO., LTD.", "https://greenfarmorganic.co.th"],
  ["บริษัท เชียงใหม่ คราฟท์ จำกัด", "CHIANGMAI CRAFT CO., LTD.", "https://cmcraft.co.th"],
  ["บริษัท ภูเก็ต รีสอร์ท กรุ๊ป จำกัด", "PHUKET RESORT GROUP CO., LTD.", "https://phuketresortgroup.com"],
  ["ห้างหุ้นส่วนจำกัด วัสดุก่อสร้างมั่นคง", "MANKONG MATERIALS LTD., PART.", null],
  ["บริษัท ไทย เทคโนโลยี โซลูชั่น จำกัด", "THAI TECHNOLOGY SOLUTION CO., LTD.", "https://thaitechsolution.co.th"],
  ["บริษัท เอเชีย คอสเมติกส์ จำกัด", "ASIA COSMETICS CO., LTD.", "https://asiacosmetics.co.th"],
  ["บริษัท ขอนแก่น ค้าปลีก จำกัด", "KHONKAEN RETAIL CO., LTD.", null],
  ["บริษัท อีสเทิร์น แมนูแฟคเจอริ่ง จำกัด", "EASTERN MANUFACTURING CO., LTD.", "https://easternmfg.co.th"],
  ["บริษัท ระยอง อินดัสเทรียล จำกัด", "RAYONG INDUSTRIAL CO., LTD.", "https://rayongindustrial.co.th"],
  ["ห้างหุ้นส่วนจำกัด ครัวคุณยาย", "KHRUA KHUNYAI LTD., PART.", null],
  ["บริษัท สมาร์ท อีคอมเมิร์ซ จำกัด", "SMART ECOMMERCE CO., LTD.", "https://smartecommerce.co.th"],
  ["บริษัท นนทบุรี พร็อพเพอร์ตี้ จำกัด", "NONTHABURI PROPERTY CO., LTD.", "https://nonthaburiproperty.co.th"],
  ["บริษัท พัทยา ฮอสพิทาลิตี้ จำกัด", "PATTAYA HOSPITALITY CO., LTD.", "https://pattayahospitality.com"],
  ["ห้างหุ้นส่วนจำกัด อะไหล่ไทยรุ่งเรือง", "THAI RUNGRUANG PARTS LTD., PART.", null],
  ["บริษัท คลินิก สกิน แคร์ จำกัด", "CLINIC SKINCARE CO., LTD.", "https://clinicskincare.co.th"],
  ["บริษัท สงขลา ซีฟู้ด เอ็กซ์พอร์ต จำกัด", "SONGKHLA SEAFOOD EXPORT CO., LTD.", "https://songkhlaseafood.co.th"],
  ["บริษัท บางนา ดิจิทัล มาร์เก็ตติ้ง จำกัด", "BANGNA DIGITAL MARKETING CO., LTD.", "https://bangnadigital.co.th"],
  ["บริษัท เพชรบุรี วิสาหกิจชุมชน จำกัด", "PHETCHABURI COMMUNITY ENTERPRISE", null],
  ["บริษัท อุดรธานี ขนส่งด่วน จำกัด", "UDONTHANI EXPRESS CO., LTD.", "https://udonexpress.co.th"],
  ["บริษัท กาญจนบุรี อุตสาหกรรมไม้ จำกัด", "KANCHANABURI WOOD INDUSTRY CO., LTD.", "https://kanwood.co.th"],
];

const DIRECTOR_NAME_POOL = [
  "สมชาย ใจดี", "วรรณา ศรีสุข", "ปิยะ รุ่งเรือง", "สุดา มั่นคง", "ธนกร วงศ์สกุล",
  "กัญญา อารีย์", "อนุชา ทองแท้", "รัตนา บุญมี",
];

function generateMockBusinesses(campaigns, count = 24) {
  const rand = seededRandom(202);
  const businesses = [];
  const now = new Date("2026-07-17T12:00:00");
  const salesUsers = MOCK_USERS.filter((u) => u.account_type === "sale");

  for (let i = 0; i < count; i++) {
    const [nameTh, nameEn, website] = COMPANY_NAME_POOL[i % COMPANY_NAME_POOL.length];
    // ผูก business เข้ากับ campaign แบบกระจาย (เฉพาะ campaign ที่ไม่ใช่ draft/queued)
    const eligibleCampaigns = campaigns.filter((c) => c.leads_found > 0);
    const campaign = eligibleCampaigns[i % eligibleCampaigns.length] || campaigns[0];

    const hasWebsite = !!website;
    const hasPhone = rand() > 0.15;
    const hasEmail = rand() > 0.35;
    const legalStatus = rand() > 0.1 ? "ยังดำเนินกิจการอยู่" : "เลิกกิจการแล้ว";
    const entityType = REGISTERED_BUSINESS_TYPES[Math.floor(rand() * (REGISTERED_BUSINESS_TYPES.length - 2))];
    const province = campaign.target.province;
    const businessType = campaign.business_target.business_type;
    const registeredCapital = [1000000, 2000000, 5000000, 10000000, 20000000, 50000000][Math.floor(rand() * 6)];
    const regDate = new Date(now.getTime() - Math.floor(rand() * 3000 + 200) * 86400000);
    const discoveredAt = new Date(now.getTime() - Math.floor(rand() * 20) * 86400000 - Math.floor(rand() * 20) * 3600000);

    const leadStatus = LEAD_STATUSES[Math.floor(rand() * LEAD_STATUSES.length)];
    const dataFields = [hasWebsite, hasPhone, hasEmail, true, rand() > 0.3, rand() > 0.5, rand() > 0.6];
    const completeness = Math.round((dataFields.filter(Boolean).length / dataFields.length) * 100);

    const problemCount = hasWebsite ? Math.floor(rand() * 4) : 0;
    const shuffledProblems = [...WEBSITE_PROBLEM_POOL].sort(() => rand() - 0.5).slice(0, problemCount);

    const leadScore = Math.round(40 + rand() * 55);
    const pains = [...PAIN_POINT_POOL].sort(() => rand() - 0.5).slice(0, 2 + Math.floor(rand() * 2));
    const services = [...RECOMMENDED_SERVICES_POOL].sort(() => rand() - 0.5).slice(0, 1 + Math.floor(rand() * 2));

    const directors = rand() > 0.25
      ? Array.from({ length: 1 + Math.floor(rand() * 3) }).map(() => ({
          name: DIRECTOR_NAME_POOL[Math.floor(rand() * DIRECTOR_NAME_POOL.length)],
          position: rand() > 0.5 ? "กรรมการผู้มีอำนาจลงนาม" : "กรรมการ",
          signing_authority: rand() > 0.4,
          start_date: regDate.toISOString().slice(0, 10),
        }))
      : [];

    businesses.push({
      id: `BUS-2026-${pad(i + 1, 3)}`,
      campaign_id: campaign.id,
      company_name_th: nameTh,
      company_name_en: nameEn,
      website: hasWebsite ? website : null,
      phone: hasPhone ? `0${[2, 3][Math.floor(rand() * 2)]}-${Math.floor(rand() * 900 + 100)}-${Math.floor(rand() * 9000 + 1000)}` : null,
      phone_secondary_count: hasPhone && rand() > 0.7 ? Math.floor(rand() * 2) + 1 : 0,
      email: hasEmail ? `contact@${(nameEn.match(/[A-Z]+/g) || ["example"]).join("").toLowerCase().slice(0, 10) || "example"}.co.th` : null,
      province,
      business_type: businessType,
      registration_number: `010${Math.floor(rand() * 9000000000000 + 1000000000000)}`.slice(0, 13),
      legal_status: legalStatus,
      registered_capital: registeredCapital,
      registration_date: regDate.toISOString().slice(0, 10),
      lead_status: leadStatus,
      data_completeness: completeness,
      source: ["DBD", hasWebsite ? "Company Website" : null, "Google Search"].filter(Boolean),
      assigned_user_id: salesUsers[i % salesUsers.length].id,
      assigned_user_name: salesUsers[i % salesUsers.length].name,
      created_at: discoveredAt.toISOString().slice(0, 19).replace("T", " "),

      legal_entity: {
        registration_number: `010${Math.floor(rand() * 9000000000000 + 1000000000000)}`.slice(0, 13),
        company_name_th: nameTh,
        company_name_en: nameEn,
        entity_type: entityType,
        status: legalStatus,
        registration_date: regDate.toISOString().slice(0, 10),
        dissolution_date: legalStatus === "เลิกกิจการแล้ว" ? new Date(regDate.getTime() + 1000 * 86400000).toISOString().slice(0, 10) : null,
        province,
        business_objective_code: `${10000 + Math.floor(rand() * 9999)}`,
        business_objective: `การประกอบธุรกิจประเภท ${businessType} และกิจกรรมที่เกี่ยวข้อง`,
        updated_at: now.toISOString().slice(0, 19).replace("T", " "),
      },
      capital: {
        registered_capital: registeredCapital,
        paid_up_capital: Math.round(registeredCapital * (0.5 + rand() * 0.5)),
        currency: "THB",
        updated_at: now.toISOString().slice(0, 19).replace("T", " "),
      },
      address: {
        house_no: `${Math.floor(rand() * 200 + 1)}`,
        building: rand() > 0.5 ? "อาคารสำนักงานใหญ่" : "",
        floor: rand() > 0.5 ? `${Math.floor(rand() * 20 + 1)}` : "",
        moo: rand() > 0.5 ? `${Math.floor(rand() * 10 + 1)}` : "",
        soi: rand() > 0.4 ? `ซอย ${Math.floor(rand() * 20 + 1)}` : "",
        road: "ถนนสุขุมวิท",
        sub_district: "บางนา", district: "บางนา", province, postal_code: `1${Math.floor(rand() * 9000 + 1000)}`,
      },
      directors,
      contact: {
        website: hasWebsite ? website : null,
        phone: hasPhone ? `0${[2, 3][Math.floor(rand() * 2)]}-${Math.floor(rand() * 900 + 100)}-${Math.floor(rand() * 9000 + 1000)}` : null,
        mobile: rand() > 0.4 ? `08${Math.floor(rand() * 9)}-${Math.floor(rand() * 900 + 100)}-${Math.floor(rand() * 9000 + 1000)}` : null,
        email: hasEmail ? `contact@${nameEn.split(" ")[0].toLowerCase()}.co.th` : null,
        line_id: rand() > 0.6 ? `@${nameEn.split(" ")[0].toLowerCase()}` : null,
        facebook: rand() > 0.5 ? `facebook.com/${nameEn.split(" ")[0].toLowerCase()}` : null,
        social_media: rand() > 0.7 ? "Instagram" : null,
        contact_person: rand() > 0.5 ? DIRECTOR_NAME_POOL[Math.floor(rand() * DIRECTOR_NAME_POOL.length)] : null,
        verification_status: hasPhone && hasWebsite ? "verified" : (hasPhone || hasWebsite) ? "unverified" : "not_found",
      },
      website_info: hasWebsite ? {
        url: website,
        status: rand() > 0.15 ? "Online" : "Offline",
        http_status: rand() > 0.15 ? 200 : 500,
        ssl: rand() > 0.3,
        mobile_friendly: rand() > 0.4,
        page_speed: Math.round(30 + rand() * 65),
        last_checked: now.toISOString().slice(0, 19).replace("T", " "),
        technology: ["WordPress", "Shopify", "Wix", "Custom PHP", "Laravel"][Math.floor(rand() * 5)],
        facebook_pixel: rand() > 0.6,
        google_analytics: rand() > 0.5,
        contact_form: rand() > 0.5,
        seo_title: `${nameTh} | ${businessType}`,
        meta_description: rand() > 0.4 ? `${nameTh} ผู้ให้บริการด้าน ${businessType} คุณภาพสูง` : null,
      } : null,
      website_problems: shuffledProblems,
      lead_analysis: {
        score: leadScore,
        quality: leadScore >= 75 ? "High" : leadScore >= 55 ? "Medium" : "Low",
        opportunity: services[0],
        pain_points: pains,
        recommended_services: services,
        suggested_sales_message: `เรียน ${nameTh} เราสังเกตเห็นว่าธุรกิจของท่านอาจได้ประโยชน์จากการปรับปรุง ${services[0]} เพื่อเพิ่มโอกาสทางการขายออนไลน์`,
        confidence_score: Math.round((0.6 + rand() * 0.35) * 100) / 100,
      },
      activity: buildBusinessActivityTimeline(nameTh, hasWebsite, discoveredAt, salesUsers[i % salesUsers.length]),
    });
  }
  return businesses;
}

function buildBusinessActivityTimeline(nameTh, hasWebsite, discoveredAt, assignedUser) {
  const t = (offsetHr) => new Date(discoveredAt.getTime() + offsetHr * 3600000).toISOString().slice(0, 19).replace("T", " ");
  const timeline = [
    { icon: "search", text: "พบข้อมูลบริษัทจาก Google Search", user: "Python Lead Service", at: t(0), source: "python-agent", status: "completed" },
    { icon: "landmark", text: "ดึงข้อมูลจาก DBD สำเร็จ", user: "Python Lead Service", at: t(1), source: "dbd", status: "completed" },
  ];
  if (hasWebsite) timeline.push({ icon: "globe", text: "ตรวจพบ Website และวิเคราะห์ข้อมูลเรียบร้อย", user: "Python Lead Service", at: t(2), source: "python-agent", status: "completed" });
  timeline.push({ icon: "phone-call", text: "ตรวจสอบเบอร์โทรติดต่อ", user: "Python Lead Service", at: t(3), source: "python-agent", status: "completed" });
  timeline.push({ icon: "user-plus", text: "เพิ่มเป็น Lead ในระบบ", user: "Python Lead Service", at: t(4), source: "system", status: "completed" });
  timeline.push({ icon: "user-check", text: `มอบหมายให้ ${assignedUser.name}`, user: "System", at: t(5), source: "system", status: "completed" });
  return timeline;
}

// ---------------------------------------------------------------
// Storage helpers (mock persistence) — TODO: แทนที่ด้วย mock-service.js เมื่อมี API จริง
// ---------------------------------------------------------------
function loadCampaignsFromStorage() {
  const raw = localStorage.getItem("lcd_campaigns");
  if (raw) { try { return JSON.parse(raw); } catch (e) { /* regenerate */ } }
  const generated = generateMockCampaigns(10);
  localStorage.setItem("lcd_campaigns", JSON.stringify(generated));
  return generated;
}
function saveCampaignsToStorage(campaigns) { localStorage.setItem("lcd_campaigns", JSON.stringify(campaigns)); }

function loadBusinessesFromStorage() {
  const raw = localStorage.getItem("lcd_businesses");
  if (raw) { try { return JSON.parse(raw); } catch (e) { /* regenerate */ } }
  const campaigns = loadCampaignsFromStorage();
  const generated = generateMockBusinesses(campaigns, 24);
  localStorage.setItem("lcd_businesses", JSON.stringify(generated));
  return generated;
}
function saveBusinessesToStorage(list) { localStorage.setItem("lcd_businesses", JSON.stringify(list)); }


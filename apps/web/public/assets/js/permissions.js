/**
 * permissions.js
 * ระบบตรวจสอบสิทธิ์แบบกลาง (Front-end demo)
 * -----------------------------------------------------------------
 * !! สำคัญ !! นี่คือการตรวจสอบสิทธิ์ฝั่ง Client เพื่อควบคุม UI/UX เท่านั้น
 * เมื่อเชื่อมต่อ Backend จริง (Laravel) ทุก Endpoint ต้องตรวจสอบ account_type
 * และ Permission ซ้ำด้วย Policy/Gate ฝั่ง Server เสมอ ห้ามเชื่อถือค่าจาก Front-end
 */

const PERMISSIONS_MAP = {
  admin: [
    "campaign.create", "campaign.view_all", "campaign.edit", "campaign.delete",
    "campaign.duplicate", "campaign.process", "campaign.pause", "campaign.retry",
    "lead.view_all", "lead.assign", "lead.add_to_lead",
    "business.view", "user.manage",
  ],
  manager: [
    "campaign.create", "campaign.view_team", "campaign.edit",
    "campaign.duplicate", "campaign.process", "campaign.pause", "campaign.retry",
    "lead.view_team", "lead.assign", "lead.add_to_lead",
    "business.view",
  ],
  sale: [
    "campaign.view_assigned",
    "lead.view_assigned", "lead.add_to_lead",
    "business.view",
  ],
};

/**
 * ตรวจสอบว่าผู้ใช้ปัจจุบันมีสิทธิ์ที่ระบุหรือไม่
 * @param {string} permissionName เช่น "campaign.delete"
 */
function hasPermission(permissionName) {
  const user = getCurrentUser();
  if (!user) return false;
  return PERMISSIONS_MAP[user.account_type]?.includes(permissionName) || false;
}

function isAdmin() {
  const user = getCurrentUser();
  return !!user && user.account_type === "admin";
}

/** กรอง Campaign ตามสิทธิ์การมองเห็นของผู้ใช้ปัจจุบัน */
function filterCampaignsByScope(campaigns, user) {
  if (!user) return [];
  if (user.account_type === "admin") return campaigns;
  if (user.account_type === "manager") return campaigns; // mock: manager ดูแลทีมเดียวทั้งหมด
  return campaigns.filter((c) => (c.assigned_user_ids || []).includes(user.id));
}

/** กรอง Business/Lead ตามสิทธิ์การมองเห็นของผู้ใช้ปัจจุบัน */
function filterBusinessesByScope(businesses, user) {
  if (!user) return [];
  if (user.account_type === "admin" || user.account_type === "manager") return businesses;
  return businesses.filter((b) => b.assigned_user_id === user.id);
}

/** ตรวจสอบว่าผู้ใช้เป็นเจ้าของ/มีสิทธิ์เข้าถึง Campaign รายตัวหรือไม่ (Campaign Ownership) */
function canAccessCampaign(campaign, user) {
  if (!user) return false;
  if (user.account_type === "admin" || user.account_type === "manager") return true;
  return (campaign.assigned_user_ids || []).includes(user.id);
}

function accountTypeLabel(type) {
  return { admin: "ผู้ดูแลระบบ", manager: "ผู้จัดการฝ่ายขาย", sale: "พนักงานขาย" }[type] || type;
}

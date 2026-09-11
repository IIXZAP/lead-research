/**
 * dashboard.js (Laravel version)
 * -----------------------------------------------------------------
 * เดิม dashboard.js ของ Demo render ทุกอย่างด้วย JS (cards, charts, lists)
 * ตอนนี้ cards/lists ถูก render ฝั่ง Server (Blade @foreach) แล้ว
 * ไฟล์นี้จึงเหลือเฉพาะ "กราฟ" (ApexCharts) ซึ่ง config คงเดิมทุกบรรทัด
 * ต่างเพียงรับข้อมูลจาก window.LCD_DASHBOARD (ป้อนโดย Controller ผ่าน @json)
 * แทนการคำนวณจาก mock-data.js ในเบราว์เซอร์
 */
(function () {
  const data = window.LCD_DASHBOARD || {
    daily: { categories: [], counts: [] },
    status: { labels: [], counts: [], colors: [] },
  };

  const DASH_CHARTS = {};

  function renderDailyLeadsChart() {
    const el = document.querySelector("#chart-leads-daily");
    if (!el || typeof ApexCharts === "undefined") return;
    if (DASH_CHARTS.daily) DASH_CHARTS.daily.destroy();
    DASH_CHARTS.daily = new ApexCharts(el, {
      chart: { type: "area", height: 280, toolbar: { show: false }, fontFamily: "Inter, Noto Sans Thai" },
      series: [{ name: "Lead ที่ค้นพบ", data: data.daily.counts }],
      xaxis: { categories: data.daily.categories, labels: { style: { fontSize: "10px" } } },
      colors: ["#4f46e5"],
      stroke: { curve: "smooth", width: 2 },
      fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
      dataLabels: { enabled: false },
      grid: { borderColor: "#f1f5f9" },
    });
    DASH_CHARTS.daily.render();
  }

  function renderCampaignStatusChart() {
    const el = document.querySelector("#chart-campaign-status");
    if (!el || typeof ApexCharts === "undefined") return;
    if (DASH_CHARTS.status) DASH_CHARTS.status.destroy();
    DASH_CHARTS.status = new ApexCharts(el, {
      chart: { type: "donut", height: 280, fontFamily: "Inter, Noto Sans Thai" },
      series: data.status.counts,
      labels: data.status.labels,
      colors: data.status.colors,
      legend: { position: "bottom", fontSize: "11px", itemMargin: { horizontal: 6, vertical: 2 } },
      dataLabels: { enabled: false },
      plotOptions: { pie: { donut: { size: "65%", labels: { show: true, total: { show: true, label: "ทั้งหมด" } } } } },
    });
    DASH_CHARTS.status.render();
  }

  document.addEventListener("DOMContentLoaded", () => {
    renderDailyLeadsChart();
    renderCampaignStatusChart();
  });
})();

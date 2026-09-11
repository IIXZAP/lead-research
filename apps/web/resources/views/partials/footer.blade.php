{{--
  partials/footer.blade.php
  หมายเหตุ: หน้า Dashboard ใน Demo เดิม "ไม่มี" footer (มีเฉพาะข้อความ © ในหน้า Login)
  จึงออกแบบให้เรียบและกลืนกับพื้นหลัง bg-slate-50 เดิม ไม่กระทบหน้าตาโดยรวม
  หากต้องการให้เหมือน Demo 100% สามารถลบ @include('partials.footer') ใน layout ออกได้
--}}
<footer id="app-footer" class="px-4 sm:px-6 py-4 text-center">
  <p class="text-xs text-slate-400">© {{ date('Y') }} Lead Campaign Dashboard. All rights reserved.</p>
</footer>

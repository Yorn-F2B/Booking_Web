Patch này sửa lỗi: có toast realtime nhưng /admin/bookings phải F5 mới thấy dữ liệu mới.

Cách dùng:
1. Giải nén zip vào root project Laravel.
2. Mở PowerShell ở root project.
3. Chạy:
   Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
   .\apply_realtime_index_refresh_patch.ps1
4. Chạy lại:
   npm run dev
   php artisan optimize:clear
   php artisan reverb:start --debug --host=127.0.0.1 --port=8081

Patch sẽ backup file cũ vào thư mục _realtime_index_refresh_backup_YYYYMMDD_HHMMSS.

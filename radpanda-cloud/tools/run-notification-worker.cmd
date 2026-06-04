@echo off
setlocal
cd /d C:\xampp\htdocs
if not exist C:\xampp\htdocs\radpanda-cloud\storage\logs mkdir C:\xampp\htdocs\radpanda-cloud\storage\logs
echo [%date% %time%] Notification Worker started>> C:\xampp\htdocs\radpanda-cloud\storage\logs\notification-worker.log
"C:\xampp\php\php.exe" "C:\xampp\htdocs\radpanda\includes\system-notification-worker.php" >> C:\xampp\htdocs\radpanda-cloud\storage\logs\notification-worker.log 2>&1
set RC=%ERRORLEVEL%
echo [%date% %time%] Notification Worker exit %RC%>> C:\xampp\htdocs\radpanda-cloud\storage\logs\notification-worker.log
exit /b %RC%

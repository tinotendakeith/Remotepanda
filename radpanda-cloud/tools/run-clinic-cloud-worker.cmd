@echo off
setlocal
cd /d C:\xampp\htdocs
if not exist C:\xampp\htdocs\radpanda-cloud\storage\logs mkdir C:\xampp\htdocs\radpanda-cloud\storage\logs
echo [%date% %time%] Clinic Cloud Worker started>> C:\xampp\htdocs\radpanda-cloud\storage\logs\clinic-cloud-worker.log
"C:\xampp\php\php.exe" "C:\xampp\htdocs\radpanda\includes\report-cloud-worker.php" 5 10 >> C:\xampp\htdocs\radpanda-cloud\storage\logs\clinic-cloud-worker.log 2>&1
set RC=%ERRORLEVEL%
echo [%date% %time%] Clinic Cloud Worker exit %RC%>> C:\xampp\htdocs\radpanda-cloud\storage\logs\clinic-cloud-worker.log
exit /b %RC%

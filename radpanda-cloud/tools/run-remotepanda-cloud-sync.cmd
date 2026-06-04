@echo off
setlocal
cd /d C:\xampp\htdocs
if not exist C:\xampp\htdocs\radpanda-cloud\storage\logs mkdir C:\xampp\htdocs\radpanda-cloud\storage\logs
echo [%date% %time%] Remotepanda Cloud Sync started>> C:\xampp\htdocs\radpanda-cloud\storage\logs\remotepanda-cloud-sync.log
"C:\xampp\php\php.exe" "C:\xampp\htdocs\remotepanda\includes\cloud-sync-worker.php" 5 10 >> C:\xampp\htdocs\radpanda-cloud\storage\logs\remotepanda-cloud-sync.log 2>&1
set RC=%ERRORLEVEL%
echo [%date% %time%] Remotepanda Cloud Sync exit %RC%>> C:\xampp\htdocs\radpanda-cloud\storage\logs\remotepanda-cloud-sync.log
exit /b %RC%

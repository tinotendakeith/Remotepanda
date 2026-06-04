@echo off
setlocal
cd /d C:\xampp\htdocs
if not exist C:\xampp\htdocs\radpanda-cloud\storage\logs mkdir C:\xampp\htdocs\radpanda-cloud\storage\logs
echo [%date% %time%] Image Detection Worker started>> C:\xampp\htdocs\radpanda-cloud\storage\logs\image-detection-worker.log
"C:\xampp\php\php.exe" "C:\xampp\htdocs\radpanda\includes\image-detection-worker.php" 25 >> C:\xampp\htdocs\radpanda-cloud\storage\logs\image-detection-worker.log 2>&1
set RC=%ERRORLEVEL%
echo [%date% %time%] Image Detection Worker exit %RC%>> C:\xampp\htdocs\radpanda-cloud\storage\logs\image-detection-worker.log
exit /b %RC%

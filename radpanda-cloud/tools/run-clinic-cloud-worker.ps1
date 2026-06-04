$ErrorActionPreference = 'Stop'
Set-Location 'C:\xampp\htdocs'
$logDir = 'C:\xampp\htdocs\radpanda-cloud\storage\logs'
New-Item -ItemType Directory -Force -Path $logDir | Out-Null
$logFile = Join-Path $logDir 'clinic-cloud-worker.log'
Add-Content -Path $logFile -Value ("[{0}] Clinic Cloud Worker started" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))
& 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\radpanda\includes\report-cloud-worker.php' 5 10 2>&1 | ForEach-Object {
    Add-Content -Path $logFile -Encoding UTF8 -Value $_
}
$rc = $LASTEXITCODE
Add-Content -Path $logFile -Value ("[{0}] Clinic Cloud Worker exit {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $rc)
exit $rc

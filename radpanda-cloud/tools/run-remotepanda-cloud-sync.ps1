$ErrorActionPreference = 'Stop'
Set-Location 'C:\xampp\htdocs'
$logDir = 'C:\xampp\htdocs\radpanda-cloud\storage\logs'
New-Item -ItemType Directory -Force -Path $logDir | Out-Null
$logFile = Join-Path $logDir 'remotepanda-cloud-sync.log'
Add-Content -Path $logFile -Value ("[{0}] Remotepanda Cloud Sync started" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'))
& 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\remotepanda\includes\cloud-sync-worker.php' 5 10 2>&1 | ForEach-Object {
    Add-Content -Path $logFile -Encoding UTF8 -Value $_
}
$rc = $LASTEXITCODE
Add-Content -Path $logFile -Value ("[{0}] Remotepanda Cloud Sync exit {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $rc)
exit $rc

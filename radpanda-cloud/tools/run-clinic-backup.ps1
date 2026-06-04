param(
    [string]$BackupRoot = "C:\xampp\htdocs\radpanda\storage\backups",
    [int]$RetentionDays = 14
)

$ErrorActionPreference = "Stop"

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$radpandaRoot = "C:\xampp\htdocs\radpanda"
$mysqlDump = "C:\xampp\mysql\bin\mysqldump.exe"
$databaseName = "radpandaco_appointment"
$backupDir = Join-Path $BackupRoot $timestamp
$zipPath = Join-Path $BackupRoot ("radpanda-clinic-backup-{0}.zip" -f $timestamp)
$manifestPath = Join-Path $backupDir "manifest.json"
$sqlPath = Join-Path $backupDir ($databaseName + ".sql")

New-Item -ItemType Directory -Force -Path $BackupRoot | Out-Null
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

if (!(Test-Path $mysqlDump)) {
    throw "mysqldump was not found at $mysqlDump"
}

& $mysqlDump -uroot --single-transaction --routines --triggers --result-file="$sqlPath" $databaseName
if ($LASTEXITCODE -ne 0) {
    throw "mysqldump failed with exit code $LASTEXITCODE"
}

$includePaths = @(
    (Join-Path $radpandaRoot "extensions\pdf"),
    (Join-Path $radpandaRoot "uploads"),
    (Join-Path $radpandaRoot "logs")
) | Where-Object { Test-Path $_ }

$manifest = [ordered]@{
    created_at = (Get-Date).ToString("s")
    database = $databaseName
    database_dump = $sqlPath
    included_paths = $includePaths
    note = "Orthanc image storage is not included here. Back up Orthanc storage/index separately if it is not PostgreSQL-backed."
}
$manifest | ConvertTo-Json -Depth 4 | Set-Content -Path $manifestPath -Encoding UTF8

$itemsToZip = @($sqlPath, $manifestPath) + $includePaths
Compress-Archive -Path $itemsToZip -DestinationPath $zipPath -Force

Remove-Item -LiteralPath $backupDir -Recurse -Force

$cutoff = (Get-Date).AddDays(-1 * [Math]::Max(1, $RetentionDays))
Get-ChildItem -Path $BackupRoot -Filter "radpanda-clinic-backup-*.zip" -File |
    Where-Object { $_.LastWriteTime -lt $cutoff } |
    Remove-Item -Force

Write-Output ("Backup created: {0}" -f $zipPath)

param(
    [string]$BackupPath = "",
    [string]$BackupRoot = "C:\xampp\htdocs\radpanda\storage\backups",
    [string]$DrillDatabase = "radpanda_restore_drill",
    [string]$ResultPath = "C:\xampp\htdocs\radpanda\storage\backups\restore-drill-last.json",
    [switch]$KeepDrillDatabase,
    [switch]$KeepExtracted
)

$ErrorActionPreference = "Stop"

function Write-DrillResult {
    param([hashtable]$Result)
    $dir = Split-Path -Parent $ResultPath
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
    }
    $Result | ConvertTo-Json -Depth 6 | Set-Content -Path $ResultPath -Encoding UTF8
}

$startedAt = Get-Date
$result = [ordered]@{
    ok = $false
    started_at = $startedAt.ToString("s")
    finished_at = ""
    backup_path = ""
    drill_database = $DrillDatabase
    sql_file = ""
    manifest_file = ""
    table_count = 0
    row_checks = @{}
    drill_database_kept = $false
    message = ""
}

try {
    if ($BackupPath -eq "") {
        $latest = Get-ChildItem -Path $BackupRoot -Filter "radpanda-clinic-backup-*.zip" -File |
            Sort-Object LastWriteTime -Descending |
            Select-Object -First 1
        if ($null -eq $latest) {
            throw "No clinic backup archive was found in $BackupRoot"
        }
        $BackupPath = $latest.FullName
    }

    if (!(Test-Path $BackupPath)) {
        throw "Backup archive does not exist: $BackupPath"
    }

    $mysql = "C:\xampp\mysql\bin\mysql.exe"
    if (!(Test-Path $mysql)) {
        throw "mysql.exe was not found at $mysql"
    }

    $extractRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("radpanda-restore-drill-" + (Get-Date -Format "yyyyMMdd-HHmmss"))
    New-Item -ItemType Directory -Force -Path $extractRoot | Out-Null
    Expand-Archive -Path $BackupPath -DestinationPath $extractRoot -Force

    $manifest = Get-ChildItem -Path $extractRoot -Filter "manifest.json" -Recurse -File | Select-Object -First 1
    if ($null -eq $manifest) {
        throw "manifest.json was not found inside the backup archive."
    }

    $sql = Get-ChildItem -Path $extractRoot -Filter "radpandaco_appointment.sql" -Recurse -File | Select-Object -First 1
    if ($null -eq $sql) {
        throw "radpandaco_appointment.sql was not found inside the backup archive."
    }
    if ($sql.Length -lt 1024) {
        throw "Database dump is unexpectedly small: $($sql.Length) bytes."
    }

    & $mysql -uroot -e "DROP DATABASE IF EXISTS ``$DrillDatabase``; CREATE DATABASE ``$DrillDatabase`` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
    if ($LASTEXITCODE -ne 0) {
        throw "Could not create restore drill database."
    }

    Get-Content -LiteralPath $sql.FullName | & $mysql -uroot $DrillDatabase
    if ($LASTEXITCODE -ne 0) {
        throw "Could not import backup SQL into restore drill database."
    }

    $tableCountRaw = & $mysql -uroot -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DrillDatabase';"
    $tableCount = [int]($tableCountRaw | Select-Object -First 1)
    if ($tableCount -lt 5) {
        throw "Restore drill database imported only $tableCount tables."
    }

    $checks = @{}
    foreach ($table in @("events", "study", "users", "system_settings")) {
        $existsRaw = & $mysql -uroot -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DrillDatabase' AND table_name='$table';"
        $exists = [int]($existsRaw | Select-Object -First 1)
        if ($exists -gt 0) {
            $countRaw = & $mysql -uroot -N -B -e "SELECT COUNT(*) FROM ``$DrillDatabase``.``$table``;"
            $checks[$table] = [int]($countRaw | Select-Object -First 1)
        } else {
            $checks[$table] = "missing"
        }
    }

    $result.ok = $true
    $result.backup_path = $BackupPath
    $result.sql_file = $sql.Name
    $result.manifest_file = $manifest.Name
    $result.table_count = $tableCount
    $result.row_checks = $checks
    $result.drill_database_kept = [bool]$KeepDrillDatabase
    $result.message = if ($KeepDrillDatabase) {
        "Restore drill passed. Backup imported into $DrillDatabase without touching the live database."
    } else {
        "Restore drill passed. Backup imported and validated without touching the live database. Drill database was removed after validation."
    }
} catch {
    $result.message = $_.Exception.Message
    throw
} finally {
    if (!$KeepDrillDatabase -and (Test-Path $mysql)) {
        try {
            & $mysql -uroot -e "DROP DATABASE IF EXISTS ``$DrillDatabase``;"
        } catch {
            $result.message = $result.message + " Drill cleanup warning: " + $_.Exception.Message
        }
    }
    $result.finished_at = (Get-Date).ToString("s")
    Write-DrillResult -Result $result
    if (!$KeepExtracted -and $extractRoot -and (Test-Path $extractRoot)) {
        Remove-Item -LiteralPath $extractRoot -Recurse -Force
    }
}

Write-Output $result.message

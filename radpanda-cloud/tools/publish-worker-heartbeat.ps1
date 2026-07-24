param(
    [Parameter(Mandatory = $true)][string]$WorkerKey,
    [Parameter(Mandatory = $true)][int]$ExitCode,
    [hashtable]$Metrics = @{}
)

$baseUrl = [Environment]::GetEnvironmentVariable('RADPANDA_CLOUD_BASE_URL', 'Machine')
if (-not $baseUrl) { $baseUrl = $env:RADPANDA_CLOUD_BASE_URL }
$syncKey = [Environment]::GetEnvironmentVariable('RADPANDA_CLOUD_SYNC_KEY', 'Machine')
if (-not $syncKey) { $syncKey = $env:RADPANDA_CLOUD_SYNC_KEY }
$clinicId = [Environment]::GetEnvironmentVariable('RADPANDA_CLINIC_ID', 'Machine')
if (-not $clinicId) { $clinicId = $env:RADPANDA_CLINIC_ID }
$nodeUid = [Environment]::GetEnvironmentVariable('RADPANDA_NODE_UID', 'Machine')
if (-not $nodeUid) { $nodeUid = $env:RADPANDA_NODE_UID }
if (-not $nodeUid) { $nodeUid = $env:COMPUTERNAME }

if (-not $baseUrl -or -not $syncKey -or -not $clinicId) {
    Write-Warning 'Worker heartbeat skipped. Configure RADPANDA_CLOUD_BASE_URL, RADPANDA_CLOUD_SYNC_KEY and RADPANDA_CLINIC_ID.'
    return
}

$payload = @{
    clinic_id = $clinicId
    node_uid = $nodeUid
    worker_key = $WorkerKey
    status = $(if ($ExitCode -eq 0) { 'ok' } else { 'error' })
    last_error = $(if ($ExitCode -eq 0) { '' } else { "Worker exited with code $ExitCode" })
    metrics = $Metrics
} | ConvertTo-Json -Depth 5

$endpoint = $baseUrl.TrimEnd('/') + '/api/worker-heartbeat.php'
try {
    Invoke-RestMethod -Method Post -Uri $endpoint -ContentType 'application/json' -Headers @{
        'X-Radpanda-Sync-Key' = $syncKey
    } -Body $payload -TimeoutSec 20 | Out-Null
} catch {
    Write-Warning ("Worker heartbeat failed: {0}" -f $_.Exception.Message)
}

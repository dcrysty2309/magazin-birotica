param(
    [string]$FtpHost = $env:STAGING_FTP_HOST,
    [string]$FtpUser = $env:STAGING_FTP_USER,
    [string]$FtpPassword = $env:STAGING_FTP_PASSWORD,
    [string]$TargetUrl = "https://notix.ro",
    [string]$SqlFile = "database\\exports\\latest-office-local.sql",
    [string]$LocalUrl = "http://localhost:8080",
    [string]$RemoteSqlFileName = "staging-sync-db.sql",
    [string]$RemoteRunnerFileName = "staging-sync-db-runner.php",
    [switch]$KeepRemoteRunner
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($FtpHost) -or [string]::IsNullOrWhiteSpace($FtpUser) -or [string]::IsNullOrWhiteSpace($FtpPassword)) {
    throw "Lipsesc credentialele FTP pentru staging."
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$templatePath = Join-Path $repoRoot "tools\\templates\\staging-db-sync-runner.php.tpl"
$sqlPath = Join-Path $repoRoot $SqlFile

if (!(Test-Path -LiteralPath $templatePath)) {
    throw "Lipseste template-ul runner: $templatePath"
}

if (!(Test-Path -LiteralPath $sqlPath)) {
    throw "Fisierul SQL nu exista: $sqlPath"
}

$tokenBytes = New-Object byte[] 24
[System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($tokenBytes)
$syncToken = ([System.BitConverter]::ToString($tokenBytes) -replace '-', '').ToLowerInvariant()

$tmpRoot = Join-Path $repoRoot "tmp\\staging-sync"
if (!(Test-Path -LiteralPath $tmpRoot)) {
    New-Item -ItemType Directory -Path $tmpRoot -Force | Out-Null
}

$runnerLocalPath = Join-Path $tmpRoot $RemoteRunnerFileName
$templateContents = Get-Content -LiteralPath $templatePath -Raw
$runnerContents = $templateContents.Replace('__SYNC_TOKEN__', $syncToken).Replace('__SYNC_SQL_FILE__', $RemoteSqlFileName).Replace('__SYNC_FROM_URL__', $LocalUrl).Replace('__SYNC_TO_URL__', $TargetUrl)
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($runnerLocalPath, $runnerContents, $utf8NoBom)

$sqlFileInfo = Get-Item -LiteralPath $sqlPath

Write-Host "Sync staging DB:"
Write-Host " - Target: $TargetUrl"
Write-Host " - SQL:    $($sqlFileInfo.FullName)"
Write-Host " - Size:   $($sqlFileInfo.Length) bytes"

$remoteSqlUrl = "ftp://$FtpHost/$RemoteSqlFileName"
$remoteRunnerUrl = "ftp://$FtpHost/$RemoteRunnerFileName"

Write-Host "Uploading SQL..."
& curl.exe --ssl-reqd --ftp-create-dirs --user "${FtpUser}:${FtpPassword}" -T $sqlPath $remoteSqlUrl | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw "Upload SQL esuat."
}

Write-Host "Uploading runner..."
& curl.exe --ssl-reqd --ftp-create-dirs --user "${FtpUser}:${FtpPassword}" -T $runnerLocalPath $remoteRunnerUrl | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw "Upload runner esuat."
}

$encodedToken = [System.Uri]::EscapeDataString($syncToken)
$encodedSql = [System.Uri]::EscapeDataString($RemoteSqlFileName)
$encodedFrom = [System.Uri]::EscapeDataString($LocalUrl)
$encodedTo = [System.Uri]::EscapeDataString($TargetUrl)
$syncUrl = "$TargetUrl/$RemoteRunnerFileName?token=$encodedToken&sql=$encodedSql&from=$encodedFrom&to=$encodedTo&cleanup_sql=1"

Write-Host "Running remote import..."
$response = Invoke-RestMethod -Uri $syncUrl -Method Get -TimeoutSec 600

if (-not $response.success) {
    throw ("Sync DB a esuat: " + ($response.message | Out-String))
}

Write-Host "Remote import finalizat."
Write-Host (" - Site URL: " + $response.data.siteurl)
Write-Host (" - Home:     " + $response.data.home)
Write-Host (" - Import:   " + $response.data.import_seconds + " sec")
Write-Host (" - Rows:     " + $response.data.replace_report.rows_updated)
Write-Host (" - Cells:    " + $response.data.replace_report.cells_updated)

if (-not $KeepRemoteRunner) {
    Write-Host "Deleting remote runner..."
    & curl.exe --ssl-reqd --user "${FtpUser}:${FtpPassword}" -Q "DELE $RemoteRunnerFileName" "ftp://$FtpHost/" | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Nu am putut sterge runner-ul remote. Verifica manual."
    }
}

Write-Host "Smoke check..."
$checkoutResponse = Invoke-WebRequest -Uri "$TargetUrl/checkout/" -Method Get -TimeoutSec 120
$casesResponse = Invoke-WebRequest -Uri "$TargetUrl/checkout-test-cases/" -Method Get -TimeoutSec 120

Write-Host (" - Checkout:            " + $checkoutResponse.StatusCode)
Write-Host (" - Checkout test cases: " + $casesResponse.StatusCode)
Write-Host "Sync DB staging finalizat."

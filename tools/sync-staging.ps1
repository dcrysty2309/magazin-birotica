param(
    [ValidateSet("home-local", "office-local")]
    [string]$SourceEnvironment = "office-local",
    [string]$FtpHost = $env:STAGING_FTP_HOST,
    [string]$FtpUser = $env:STAGING_FTP_USER,
    [string]$FtpPassword = $env:STAGING_FTP_PASSWORD,
    [string]$Label = "manual",
    [switch]$ExportDatabase
)

$ErrorActionPreference = "Stop"

function Assert-Success {
    param(
        [string]$StepName
    )

    if ($LASTEXITCODE -ne 0) {
        throw "$StepName a esuat cu exit code $LASTEXITCODE."
    }
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$exportScript = Join-Path $PSScriptRoot "export-sync-db.ps1"
$deployScript = Join-Path $PSScriptRoot "deploy-staging.ps1"
$syncDbScript = Join-Path $PSScriptRoot "sync-staging-db.ps1"
$manifestScript = Join-Path $PSScriptRoot "new-sync-manifest.ps1"

$sqlFile = "database/exports/latest-$SourceEnvironment.sql"

if ($ExportDatabase) {
    & powershell -ExecutionPolicy Bypass -File $exportScript `
        -SourceEnvironment $SourceEnvironment `
        -Label $Label `
        -CreateLatestAlias
    Assert-Success -StepName "Export DB"
}

& powershell -ExecutionPolicy Bypass -File $deployScript `
    -FtpHost $FtpHost `
    -FtpUser $FtpUser `
    -FtpPassword $FtpPassword
Assert-Success -StepName "Deploy staging theme"

& powershell -ExecutionPolicy Bypass -File $syncDbScript `
    -FtpHost $FtpHost `
    -FtpUser $FtpUser `
    -FtpPassword $FtpPassword `
    -SqlFile $sqlFile
Assert-Success -StepName "Sync staging DB"

& powershell -ExecutionPolicy Bypass -File $manifestScript `
    -SourceEnvironment $SourceEnvironment `
    -TargetEnvironment "staging" `
    -Label $Label `
    -DbExportFile $sqlFile
Assert-Success -StepName "Manifest sync"

Write-Host "Sync complet local -> staging finalizat."

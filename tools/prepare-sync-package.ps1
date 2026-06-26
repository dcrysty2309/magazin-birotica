param(
    [ValidateSet("home-local", "office-local", "staging")]
    [string]$SourceEnvironment,
    [ValidateSet("home-local", "office-local", "staging")]
    [string]$TargetEnvironment,
    [string]$Label = "manual",
    [switch]$IncludeDatabase,
    [switch]$BuildStagingPackage
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($SourceEnvironment) -or [string]::IsNullOrWhiteSpace($TargetEnvironment)) {
    throw "Parametrii -SourceEnvironment si -TargetEnvironment sunt obligatorii."
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$exportScript = Join-Path $PSScriptRoot "export-sync-db.ps1"
$manifestScript = Join-Path $PSScriptRoot "new-sync-manifest.ps1"
$buildScript = Join-Path $PSScriptRoot "build-staging-package.ps1"

$dbExportFile = ""
$safeLabel = ($Label -replace '[^a-zA-Z0-9._-]', '-').Trim('-')
if ([string]::IsNullOrWhiteSpace($safeLabel)) {
    $safeLabel = "manual"
}
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"

if ($IncludeDatabase) {
    & powershell -ExecutionPolicy Bypass -File $exportScript `
        -SourceEnvironment $SourceEnvironment `
        -Label $Label `
        -CreateLatestAlias

    $dbExportFile = "database/exports/latest-$SourceEnvironment.sql"
}

if ([string]::IsNullOrWhiteSpace($dbExportFile)) {
    & powershell -ExecutionPolicy Bypass -File $manifestScript `
        -SourceEnvironment $SourceEnvironment `
        -TargetEnvironment $TargetEnvironment `
        -Label $Label
}
else {
    & powershell -ExecutionPolicy Bypass -File $manifestScript `
        -SourceEnvironment $SourceEnvironment `
        -TargetEnvironment $TargetEnvironment `
        -Label $Label `
        -DbExportFile $dbExportFile
}

if ($BuildStagingPackage) {
    $outputRoot = "build\\sync-packages\\staging-package-$timestamp-$safeLabel"
    $zipPath = "build\\sync-packages\\staging-package-$timestamp-$safeLabel.zip"

    & powershell -ExecutionPolicy Bypass -File $buildScript `
        -OutputRoot $outputRoot `
        -ZipPath $zipPath
}

Write-Host "Sync package pregatit."
Write-Host " - Source: $SourceEnvironment"
Write-Host " - Target: $TargetEnvironment"
Write-Host " - Label:  $Label"
Write-Host " - DB:     $(if ($IncludeDatabase) { 'included' } else { 'not included' })"
Write-Host " - Build:  $(if ($BuildStagingPackage) { 'created' } else { 'not created' })"

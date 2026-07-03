param(
    [string]$FtpHost = $env:STAGING_FTP_HOST,
    [string]$FtpUser = $env:STAGING_FTP_USER,
    [string]$FtpPassword = $env:STAGING_FTP_PASSWORD,
    [string]$TargetUrl = "https://notix.ro",
    [string]$RemoteThemePath = "/wp-content/themes/papetarie-storefront",
    [string]$LocalThemePath = "wp-content/themes/papetarie-storefront",
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($FtpHost) -or [string]::IsNullOrWhiteSpace($FtpUser) -or [string]::IsNullOrWhiteSpace($FtpPassword)) {
    throw "Lipsesc credențialele de staging. Setează STAGING_FTP_HOST, STAGING_FTP_USER și STAGING_FTP_PASSWORD sau pasează parametrii explicit."
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$localThemeRoot = Join-Path $repoRoot $LocalThemePath

if (!(Test-Path -LiteralPath $localThemeRoot)) {
    throw "Tema locală nu există: $localThemeRoot"
}

$excludedPrefixes = @(
    'tools\'
)

$files = Get-ChildItem -LiteralPath $localThemeRoot -Recurse -File |
    Where-Object {
        $relativePath = $_.FullName.Substring($localThemeRoot.Length).TrimStart('\')
        if ($_.Name -like '*.codex-*') {
            return $false
        }
        foreach ($prefix in $excludedPrefixes) {
            if ($relativePath.StartsWith($prefix, [System.StringComparison]::OrdinalIgnoreCase)) {
                return $false
            }
        }

        return $true
    } |
    Sort-Object FullName
if ($files.Count -eq 0) {
    throw "Nu există fișiere de deploy în tema locală."
}

Write-Host "Deploy staging theme:"
Write-Host " - Local:  $localThemeRoot"
Write-Host " - Remote: $RemoteThemePath"
Write-Host " - Files:  $($files.Count)"

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($localThemeRoot.Length).TrimStart('\')
    $remotePath = ($RemoteThemePath.TrimEnd('/') + '/' + ($relativePath -replace '\\', '/'))
    $remoteUrl = "ftp://$FtpHost$remotePath"

    if ($DryRun) {
        Write-Host "[DRY RUN] $relativePath -> $remoteUrl"
        continue
    }

    Write-Host "Uploading $relativePath"
    & curl.exe --ssl-reqd --ftp-create-dirs --user "${FtpUser}:${FtpPassword}" -T $file.FullName $remoteUrl | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Upload eșuat pentru $relativePath"
    }
}

if (-not $DryRun) {
    Write-Host "Smoke check staging:"
    $stamp = Get-Date -Format "yyyyMMddHHmmss"
    foreach ($path in @("/checkout/", "/checkout-test-cases/")) {
        $url = "$TargetUrl$path?v=$stamp"
        try {
            $response = Invoke-WebRequest -Uri $url -Method Get -TimeoutSec 120 -Headers @{ 'Cache-Control' = 'no-cache' }
            Write-Host " - $path => $($response.StatusCode)"
        }
        catch {
            throw "Smoke check eșuat pentru $path: $($_.Exception.Message)"
        }
    }
}

Write-Host "Deploy staging finalizat."

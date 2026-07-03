param(
    [string]$FtpHost = $env:STAGING_FTP_HOST,
    [string]$FtpUser = $env:STAGING_FTP_USER,
    [string]$FtpPassword = $env:STAGING_FTP_PASSWORD,
    [string]$TargetUrl = "https://notix.ro",
    [string]$RemoteThemePath = "/wp-content/themes/papetarie-storefront",
    [string]$LocalThemePath = "wp-content/themes/papetarie-storefront",
    [string]$RemotePluginPath = "/wp-content/plugins",
    [string]$LocalPluginPath = "wp-content/plugins",
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($FtpHost) -or [string]::IsNullOrWhiteSpace($FtpUser) -or [string]::IsNullOrWhiteSpace($FtpPassword)) {
    throw "Lipsesc credențialele de staging. Setează STAGING_FTP_HOST, STAGING_FTP_USER și STAGING_FTP_PASSWORD sau pasează parametrii explicit."
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$localThemeRoot = Join-Path $repoRoot $LocalThemePath
$localPluginRoot = Join-Path $repoRoot $LocalPluginPath

if (!(Test-Path -LiteralPath $localThemeRoot)) {
    throw "Tema locală nu există: $localThemeRoot"
}

function Upload-Tree {
    param(
        [Parameter(Mandatory = $true)]
        [string]$LocalRoot,
        [Parameter(Mandatory = $true)]
        [string]$RemoteRoot,
        [Parameter(Mandatory = $true)]
        [string]$Label
    )

    if (!(Test-Path -LiteralPath $LocalRoot)) {
        throw "$Label local nu există: $LocalRoot"
    }

    $excludedPrefixes = @(
        'tools\'
    )

    $files = Get-ChildItem -LiteralPath $LocalRoot -Recurse -File |
        Where-Object {
            $relativePath = $_.FullName.Substring($LocalRoot.Length).TrimStart('\')
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
        throw "Nu există fișiere de deploy în $Label."
    }

    Write-Host "Deploy staging $Label:"
    Write-Host " - Local:  $LocalRoot"
    Write-Host " - Remote: $RemoteRoot"
    Write-Host " - Files:  $($files.Count)"

    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring($LocalRoot.Length).TrimStart('\')
        $remotePath = ($RemoteRoot.TrimEnd('/') + '/' + ($relativePath -replace '\\', '/'))
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
}

Upload-Tree -LocalRoot $localThemeRoot -RemoteRoot $RemoteThemePath -Label "theme"
Upload-Tree -LocalRoot $localPluginRoot -RemoteRoot $RemotePluginPath -Label "plugins"

if (-not $DryRun) {
    Write-Host "Smoke check staging:"
    $stamp = Get-Date -Format "yyyyMMddHHmmss"
    foreach ($path in @("/checkout/", "/checkout-test-cases/")) {
        $url = "{0}{1}?v={2}" -f $TargetUrl.TrimEnd('/'), $path, $stamp
        $attempt = 0
        $lastError = $null
        while ($attempt -lt 3) {
            $attempt++
            try {
                $statusCode = & curl.exe -L --silent --show-error --output NUL --write-out "%{http_code}" --header "Cache-Control: no-cache" $url
                if ($LASTEXITCODE -ne 0) {
                    throw "curl.exe a returnat exit code $LASTEXITCODE."
                }

                if ([string]::IsNullOrWhiteSpace($statusCode)) {
                    throw "curl.exe nu a returnat un status code."
                }

                if ($statusCode -notmatch '^[23][0-9]{2}$') {
                    throw "Status HTTP neașteptat: $statusCode"
                }

                Write-Host " - $path => $statusCode"
                $lastError = $null
                break
            }
            catch {
                $lastError = $_.Exception.Message
                if ($attempt -lt 3) {
                    Start-Sleep -Seconds 5
                }
            }
        }

        if ($lastError) {
            throw "Smoke check eșuat pentru $path după 3 încercări: $lastError"
        }
    }
}

Write-Host "Deploy staging finalizat."

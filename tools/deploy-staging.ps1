param(
    [string]$FtpHost = $env:STAGING_FTP_HOST,
    [string]$FtpUser = $env:STAGING_FTP_USER,
    [string]$FtpPassword = $env:STAGING_FTP_PASSWORD,
    [string]$TargetUrl = "https://notix.ro",
    [string]$RemoteThemePath = "/wp-content/themes/papetarie-storefront",
    [string]$LocalThemePath = "wp-content/themes/papetarie-storefront",
    [string]$RemotePluginPath = "/wp-content/plugins",
    [string]$LocalPluginPath = "wp-content/plugins",
    [string]$PackageZipPath = "",
    [string]$RemotePackageZipFileName = "staging-package.zip",
    [string]$RemotePackageRunnerFileName = "staging-package-deploy-runner.php",
    [switch]$KeepRemoteRunner,
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($FtpHost) -or [string]::IsNullOrWhiteSpace($FtpUser) -or [string]::IsNullOrWhiteSpace($FtpPassword)) {
    throw "Lipsesc credențialele de staging. Setează STAGING_FTP_HOST, STAGING_FTP_USER și STAGING_FTP_PASSWORD sau pasează parametrii explicit."
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$localThemeRoot = Join-Path $repoRoot $LocalThemePath
$localPluginRoot = Join-Path $repoRoot $LocalPluginPath
$templateRoot = Join-Path $PSScriptRoot "templates"

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

    Write-Host "Deploy staging ${Label}:"
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

function New-DeployToken {
    $tokenBytes = New-Object byte[] 24
    [System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($tokenBytes)
    return ([System.BitConverter]::ToString($tokenBytes) -replace '-', '').ToLowerInvariant()
}

function Upload-SingleFile {
    param(
        [Parameter(Mandatory = $true)]
        [string]$LocalPath,
        [Parameter(Mandatory = $true)]
        [string]$RemotePath,
        [Parameter(Mandatory = $true)]
        [string]$Label
    )

    if (!(Test-Path -LiteralPath $LocalPath)) {
        throw "$Label local nu există: $LocalPath"
    }

    if ($DryRun) {
        Write-Host "[DRY RUN] $Label -> ftp://$FtpHost$RemotePath"
        return
    }

    Write-Host "Uploading $Label"
    & curl.exe --ssl-reqd --ftp-create-dirs --user "${FtpUser}:${FtpPassword}" -T $LocalPath ("ftp://$FtpHost$RemotePath") | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Upload eșuat pentru $Label"
    }
}

if ([string]::IsNullOrWhiteSpace($PackageZipPath)) {
    Upload-Tree -LocalRoot $localThemeRoot -RemoteRoot $RemoteThemePath -Label "theme"
    Upload-Tree -LocalRoot $localPluginRoot -RemoteRoot $RemotePluginPath -Label "plugins"
}
else {
    $packageLocalPath = Join-Path $repoRoot $PackageZipPath
    if (!(Test-Path -LiteralPath $packageLocalPath)) {
        throw "Pachetul ZIP nu există: $packageLocalPath"
    }

    $packageTemplatePath = Join-Path $templateRoot "staging-package-deploy-runner.php.tpl"
    if (!(Test-Path -LiteralPath $packageTemplatePath)) {
        throw "Lipsește template-ul runner pentru pachet: $packageTemplatePath"
    }

    $syncToken = New-DeployToken
    $runnerLocalPath = Join-Path $env:TEMP $RemotePackageRunnerFileName
    $templateContents = Get-Content -LiteralPath $packageTemplatePath -Raw
    $runnerContents = $templateContents.Replace('__PACKAGE_TOKEN__', $syncToken).Replace('__PACKAGE_ZIP_FILE__', $RemotePackageZipFileName)
    $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($runnerLocalPath, $runnerContents, $utf8NoBom)

    try {
        Upload-SingleFile -LocalPath $packageLocalPath -RemotePath "/$RemotePackageZipFileName" -Label "package zip"
        Upload-SingleFile -LocalPath $runnerLocalPath -RemotePath "/$RemotePackageRunnerFileName" -Label "package runner"

        if (-not $DryRun) {
            $encodedToken = [System.Uri]::EscapeDataString($syncToken)
            $encodedZip = [System.Uri]::EscapeDataString($RemotePackageZipFileName)
            $offset = 0
            $batchSize = 20
            $done = $false

            Write-Host "Running package extraction..."
            while (-not $done) {
                $packageUrl = "$TargetUrl/$RemotePackageRunnerFileName?token=$encodedToken&zip=$encodedZip&offset=$offset&batch=$batchSize&cleanup_zip=1&cleanup_runner=1"
                $response = Invoke-RestMethod -Uri $packageUrl -Method Get -TimeoutSec 1200

                if (-not $response.success) {
                    throw ("Pachetul ZIP a esuat la extragere: " + ($response.message | Out-String))
                }

                if ($response.data -and $response.data.next_offset -ne $null) {
                    $offset = [int]$response.data.next_offset
                }

                if ($response.data -and $response.data.done -ne $null) {
                    $done = [bool]$response.data.done
                }
                else {
                    $done = $true
                }

                Write-Host (" - extracted: {0}/{1}" -f $response.data.processed_files, $response.data.total_files)
            }

            Write-Host "Package extraction finalizat."
            Write-Host (" - ZIP:      " + $response.data.zip_file)
            Write-Host (" - Import:   " + $response.data.import_seconds + " sec")
        }
    }
    finally {
        if (Test-Path -LiteralPath $runnerLocalPath) {
            Remove-Item -LiteralPath $runnerLocalPath -Force
        }
    }
}

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

param(
    [string]$FtpHost = $env:STAGING_FTP_HOST,
    [string]$FtpUser = $env:STAGING_FTP_USER,
    [string]$FtpPassword = $env:STAGING_FTP_PASSWORD,
    [string]$TargetUrl = "https://notix.ro",
    # NOTA: radacina contului FTP (memoreaz) ESTE deja docroot-ul WordPress
    # (wp-load.php, wp-config.php etc. stau direct acolo). Exista un subfolder
    # "public_html" separat, orfan, care contine DOAR un wp-content vechi/dus
    # (fara wp-load.php) - un artefact stray, nu docroot-ul live. Nu adauga
    # niciun prefix "/public_html" aici, altfel deploy-ul ajunge in acel
    # folder mort si site-ul live nu vede niciodata schimbarile (verificat
    # direct 2026-07-27: fisierele ajungeau corect pe disc in acel folder,
    # dar wp-admin continua sa serveasca versiunea veche pt ca PHP-ul real
    # ruleaza din radacina FTP, nu din subfolderul "public_html").
    [string]$RemoteThemePath = "/wp-content/themes/papetarie-storefront",
    [string]$LocalThemePath = "wp-content/themes/papetarie-storefront",
    [string]$RemotePluginPath = "/wp-content/plugins",
    [string]$LocalPluginPath = "wp-content/plugins",
    [string]$PackageZipPath = "",
    [string]$RemotePackageBasePath = "/wp-content/themes/papetarie-storefront/tools",
    [string]$RemotePackageZipFileName = "staging-package.zip",
    [string]$RemotePackageRunnerFileName = "staging-package-deploy-runner.php",
    [switch]$SkipPlugins,
    [switch]$KeepRemoteRunner,
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

$NullSink = if ($env:OS -eq 'Windows_NT') { 'NUL' } else { '/dev/null' }

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
        'tools/'
    )

    $files = Get-ChildItem -LiteralPath $LocalRoot -Recurse -File |
        Where-Object {
            $relativePath = ($_.FullName.Substring($LocalRoot.Length).TrimStart('\', '/')) -replace '\\', '/'
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
        $relativePath = ($file.FullName.Substring($LocalRoot.Length).TrimStart('\', '/')) -replace '\\', '/'
        $remotePath = ($RemoteRoot.TrimEnd('/') + '/' + $relativePath)
        $remoteUrl = "ftp://$FtpHost$remotePath"

        if ($DryRun) {
            Write-Host "[DRY RUN] $relativePath -> $remoteUrl"
            continue
        }

        Write-Host "Uploading $relativePath"
        & curl --ssl-reqd --ftp-create-dirs --ftp-skip-pasv-ip --retry 3 --retry-delay 2 --user "${FtpUser}:${FtpPassword}" -T $file.FullName $remoteUrl | Out-Null
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

function New-DeployUrl {
    param(
        [Parameter(Mandatory = $true)]
        [string]$BaseUrl,
        [Parameter(Mandatory = $true)]
        [string]$RelativePath,
        [Parameter(Mandatory = $false)]
        [hashtable]$QueryParameters = $null
    )

    $normalizedBaseUrl = $BaseUrl.Trim().TrimEnd('/')
    $normalizedPath = '/' + $RelativePath.Trim().TrimStart('/')
    $builder = [System.UriBuilder]::new($normalizedBaseUrl + $normalizedPath)

    if ($QueryParameters -and $QueryParameters.Count -gt 0) {
        $pairs = foreach ($key in $QueryParameters.Keys) {
            $value = [string]$QueryParameters[$key]
            '{0}={1}' -f [System.Uri]::EscapeDataString([string]$key), [System.Uri]::EscapeDataString($value)
        }
        $builder.Query = ($pairs -join '&')
    }

    return $builder.Uri.AbsoluteUri
}

function Assert-RemoteFileMatchesLocal {
    param(
        [Parameter(Mandatory = $true)]
        [string]$LocalPath,
        [Parameter(Mandatory = $true)]
        [string]$RemotePath,
        [Parameter(Mandatory = $true)]
        [string]$Label
    )

    if (!(Test-Path -LiteralPath $LocalPath)) {
        throw "Nu pot verifica $Label deoarece lipsește fișierul local: $LocalPath"
    }

    $localHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $LocalPath).Hash
    $probeUrl = New-DeployUrl -BaseUrl $TargetUrl -RelativePath $RemotePath -QueryParameters ([ordered]@{
        probe = New-DeployToken
        v = (Get-Date -Format 'yyyyMMddHHmmss')
    })

    $tempPath = Join-Path ([System.IO.Path]::GetTempPath()) ("deploy-verify-" + [System.IO.Path]::GetRandomFileName())

    try {
        & curl --silent --show-error --fail --location --output $tempPath --url $probeUrl
        if ($LASTEXITCODE -ne 0) {
            throw "Verificarea remote pentru $Label a eșuat (curl exit code $LASTEXITCODE)."
        }

        $remoteHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $tempPath).Hash
        if ($localHash -ne $remoteHash) {
            throw @"
Verificarea remote pentru $Label nu corespunde cu fișierul local.
 - Local:  $LocalPath
 - Remote: $probeUrl
 - Local SHA256:  $localHash
 - Remote SHA256: $remoteHash
"@
        }

        Write-Host "Verified $Label matches live content."
    }
    finally {
        if (Test-Path -LiteralPath $tempPath) {
            Remove-Item -LiteralPath $tempPath -Force
        }
    }
}

function Assert-ServerFilesystemMatchesLocal {
    param(
        [Parameter(Mandatory = $true)]
        [string]$LocalPath,
        [Parameter(Mandatory = $true)]
        [string]$RemoteHash,
        [Parameter(Mandatory = $true)]
        [string]$Label
    )

    if (!(Test-Path -LiteralPath $LocalPath)) {
        throw "Nu pot verifica $Label deoarece lipsește fișierul local: $LocalPath"
    }

    $localHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $LocalPath).Hash
    if ($localHash -ne $RemoteHash) {
        throw @"
Verificarea pe filesystem pentru $Label nu corespunde cu fișierul local.
 - Local:  $LocalPath
 - Local SHA256:  $localHash
 - Server SHA256: $RemoteHash
"@
    }

    Write-Host "Verified $Label matches server filesystem."
}

function Write-FilesystemCheckDebug {
    param(
        [Parameter(Mandatory = $true)]
        $FilesystemCheck,
        [Parameter(Mandatory = $true)]
        [string]$Label
    )

    Write-Host ("Filesystem check for " + $Label + ":")
    foreach ($property in @('path', 'exists', 'base_dir', 'runner_dir', 'sha256', 'size', 'mtime')) {
        if ($null -ne $FilesystemCheck.$property) {
            Write-Host (" - " + $property + ": " + $FilesystemCheck.$property)
        }
    }
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
    & curl --ssl-reqd --ftp-create-dirs --ftp-skip-pasv-ip --retry 3 --retry-delay 2 --user "${FtpUser}:${FtpPassword}" -T $LocalPath ("ftp://$FtpHost$RemotePath") | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "Upload eșuat pentru $Label"
    }
}

if ([string]::IsNullOrWhiteSpace($PackageZipPath)) {
    Upload-Tree -LocalRoot $localThemeRoot -RemoteRoot $RemoteThemePath -Label "theme"

    if (-not $SkipPlugins) {
        Upload-Tree -LocalRoot $localPluginRoot -RemoteRoot $RemotePluginPath -Label "plugins"
    }

    if (-not $DryRun) {
        # FTP root IS the docroot here, so $RemoteThemePath is already a valid
        # public URL path as-is (no prefix to strip). Kept as a no-op replace
        # in case a caller still passes an explicit /public_html-prefixed path.
        $publicThemePath = $RemoteThemePath -replace '^/public_html', ''
        $localStylePath = Join-Path $localThemeRoot "style.css"
        Assert-RemoteFileMatchesLocal -LocalPath $localStylePath -RemotePath ($publicThemePath.TrimEnd('/') + '/style.css') -Label "theme stylesheet"
    }
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
    $runnerLocalPath = Join-Path ([System.IO.Path]::GetTempPath()) $RemotePackageRunnerFileName
    $templateContents = Get-Content -LiteralPath $packageTemplatePath -Raw
    $runnerContents = $templateContents.Replace('__PACKAGE_TOKEN__', $syncToken).Replace('__PACKAGE_ZIP_FILE__', $RemotePackageZipFileName)
    $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($runnerLocalPath, $runnerContents, $utf8NoBom)

    try {
        $remoteRunnerPath = ($RemotePackageBasePath.TrimEnd('/') + '/' + $RemotePackageRunnerFileName)
        $remoteZipPath = ($RemotePackageBasePath.TrimEnd('/') + '/' + $RemotePackageZipFileName)

        Upload-SingleFile -LocalPath $packageLocalPath -RemotePath $remoteZipPath -Label "package zip"
        Upload-SingleFile -LocalPath $runnerLocalPath -RemotePath $remoteRunnerPath -Label "package runner"

        if (-not $DryRun) {
            $encodedToken = [System.Uri]::EscapeDataString($syncToken)
            $encodedZip = [System.Uri]::EscapeDataString($RemotePackageZipFileName)
            $offset = 0
            $batchSize = 20
            $done = $false

            Write-Host "Running package extraction..."
            while (-not $done) {
                $packageUrl = New-DeployUrl -BaseUrl $TargetUrl -RelativePath $remoteRunnerPath -QueryParameters ([ordered]@{
                    token = $encodedToken
                    zip = $encodedZip
                    offset = $offset
                    batch = $batchSize
                    cleanup_zip = 1
                    cleanup_runner = 1
                })
                Write-Host " - package URL: $packageUrl"
                $curlOutput = & curl --silent --show-error --fail --location --max-time 1200 --url $packageUrl
                if ($LASTEXITCODE -ne 0) {
                    throw "curl a returnat exit code $LASTEXITCODE la extragerea pachetului."
                }

                if ([string]::IsNullOrWhiteSpace($curlOutput)) {
                    throw "Runner-ul de pachet nu a returnat niciun răspuns."
                }

                $response = $curlOutput | ConvertFrom-Json
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
            if ($response.data.opcache_reset) {
                Write-Host (" - OPCache available: " + $response.data.opcache_reset.available)
                Write-Host (" - OPCache reset:     " + $response.data.opcache_reset.result)
            }

            $localPackagePath = Join-Path $repoRoot $PackageZipPath
            if ($response.data.zip_checks -and $response.data.zip_checks.package_zip -and $response.data.zip_checks.package_zip.exists -and $response.data.zip_checks.package_zip.sha256) {
                $localPackageHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $localPackagePath).Hash
                if ($localPackageHash -ne $response.data.zip_checks.package_zip.sha256) {
                    throw @"
Verificarea pe filesystem pentru pachetul ZIP nu corespunde cu fișierul local.
 - Local:  $localPackagePath
 - Local SHA256:  $localPackageHash
 - Server SHA256: $($response.data.zip_checks.package_zip.sha256)
"@
                }

                Write-Host "Verified package ZIP matches server filesystem."
            }
            else {
                Write-Host "Filesystem hash verificare indisponibilă pentru ZIP; continui cu verificarea style.css."
            }

            $localStylePath = Join-Path $localThemeRoot "style.css"
            if ($response.data.filesystem_checks -and $response.data.filesystem_checks.theme_style_css -and $response.data.filesystem_checks.theme_style_css.exists -and $response.data.filesystem_checks.theme_style_css.sha256) {
                Write-FilesystemCheckDebug -FilesystemCheck $response.data.filesystem_checks.theme_style_css -Label "theme stylesheet"
                Assert-ServerFilesystemMatchesLocal -LocalPath $localStylePath -RemoteHash $response.data.filesystem_checks.theme_style_css.sha256 -Label "theme stylesheet"
            }
            else {
                if ($response.data.filesystem_checks -and $response.data.filesystem_checks.theme_style_css) {
                    Write-FilesystemCheckDebug -FilesystemCheck $response.data.filesystem_checks.theme_style_css -Label "theme stylesheet"
                }
                Write-Host "Filesystem hash verificare indisponibilă din runner; sar peste verificarea HTTP pentru style.css și continui cu smoke check."
            }

            if (-not $KeepRemoteRunner) {
                Write-Host "Deleting remote runner/zip..."
                & curl --ssl-reqd --user "${FtpUser}:${FtpPassword}" -Q "DELE $remoteRunnerPath" "ftp://$FtpHost/" | Out-Null
                & curl --ssl-reqd --user "${FtpUser}:${FtpPassword}" -Q "DELE $remoteZipPath" "ftp://$FtpHost/" | Out-Null
            }
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
        $url = New-DeployUrl -BaseUrl $TargetUrl -RelativePath $path -QueryParameters ([ordered]@{
            v = $stamp
        })
        $attempt = 0
        $lastError = $null
        while ($attempt -lt 3) {
            $attempt++
            try {
                $statusCode = & curl -L --silent --show-error --output $NullSink --write-out "%{http_code}" --header "Cache-Control: no-cache" --url $url
                if ($LASTEXITCODE -ne 0) {
                    throw "curl a returnat exit code $LASTEXITCODE."
                }

                if ([string]::IsNullOrWhiteSpace($statusCode)) {
                    throw "curl nu a returnat un status code."
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

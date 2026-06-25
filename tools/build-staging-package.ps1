param(
    [string]$OutputRoot = "build\staging-package",
    [string]$ZipPath = "build\staging-package.zip"
)

$ErrorActionPreference = "Stop"

function Reset-Directory {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path
    )

    if (Test-Path -LiteralPath $Path) {
        try {
            Remove-Item -LiteralPath $Path -Recurse -Force
        }
        catch {
            $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
            $archivedPath = "$Path-old-$timestamp"
            Rename-Item -LiteralPath $Path -NewName (Split-Path -Leaf $archivedPath)
        }
    }

    New-Item -ItemType Directory -Path $Path | Out-Null
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$wordpressRoot = Join-Path $repoRoot "wordpress"
$rootWpContent = Join-Path $repoRoot "wp-content"
$packageRoot = Join-Path $repoRoot $OutputRoot
$zipFile = Join-Path $repoRoot $ZipPath

if (!(Test-Path -LiteralPath $wordpressRoot)) {
    throw "Nu exista directorul wordpress: $wordpressRoot"
}

if (!(Test-Path -LiteralPath $rootWpContent)) {
    throw "Nu exista directorul wp-content din radacina: $rootWpContent"
}

Reset-Directory -Path $packageRoot

Get-ChildItem -LiteralPath $wordpressRoot -Force | ForEach-Object {
    $sourcePath = $_.FullName
    $destinationPath = Join-Path $packageRoot $_.Name

    if ($_.PSIsContainer -and $_.Name -eq "wp-content") {
        return
    }

    Copy-Item -LiteralPath $sourcePath -Destination $destinationPath -Recurse -Force
}

$packageWpContent = Join-Path $packageRoot "wp-content"
$packageThemes = Join-Path $packageWpContent "themes"

New-Item -ItemType Directory -Path $packageWpContent -Force | Out-Null
New-Item -ItemType Directory -Path $packageThemes -Force | Out-Null

$wordpressUploads = Join-Path $wordpressRoot "wp-content\uploads"
$wordpressLanguages = Join-Path $wordpressRoot "wp-content\languages"
$wordpressStorefront = Join-Path $wordpressRoot "wp-content\themes\storefront"

if (Test-Path -LiteralPath $wordpressUploads) {
    Copy-Item -LiteralPath $wordpressUploads -Destination $packageWpContent -Recurse -Force
}

if (Test-Path -LiteralPath $wordpressLanguages) {
    Copy-Item -LiteralPath $wordpressLanguages -Destination $packageWpContent -Recurse -Force
}

if (Test-Path -LiteralPath $wordpressStorefront) {
    Copy-Item -LiteralPath $wordpressStorefront -Destination $packageThemes -Recurse -Force
}

Copy-Item -LiteralPath (Join-Path $rootWpContent "plugins") -Destination $packageWpContent -Recurse -Force
Copy-Item -LiteralPath (Join-Path $rootWpContent "themes\papetarie-storefront") -Destination $packageThemes -Recurse -Force

if (Test-Path -LiteralPath (Join-Path $rootWpContent "themes\papetarie-store")) {
    Copy-Item -LiteralPath (Join-Path $rootWpContent "themes\papetarie-store") -Destination $packageThemes -Recurse -Force
}

foreach ($path in @(
    (Join-Path $packageRoot ".env"),
    (Join-Path $packageRoot "wp-content\debug.log"),
    (Join-Path $packageRoot "error_log")
)) {
    if (Test-Path -LiteralPath $path) {
        Remove-Item -LiteralPath $path -Force
    }
}

$zipDirectory = Split-Path -Parent $zipFile
if (!(Test-Path -LiteralPath $zipDirectory)) {
    New-Item -ItemType Directory -Path $zipDirectory | Out-Null
}

if (Test-Path -LiteralPath $zipFile) {
    Remove-Item -LiteralPath $zipFile -Force
}

Compress-Archive -Path (Join-Path $packageRoot "*") -DestinationPath $zipFile -Force

Write-Host "Pachet staging creat:"
Write-Host " - Folder: $packageRoot"
Write-Host " - Zip: $zipFile"

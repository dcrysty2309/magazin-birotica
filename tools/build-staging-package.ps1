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

if (!(Test-Path -LiteralPath $rootWpContent)) {
    throw "Nu exista directorul wp-content din radacina: $rootWpContent"
}

Reset-Directory -Path $packageRoot

$packageWpContent = Join-Path $packageRoot "wp-content"
$packageThemes = Join-Path $packageWpContent "themes"
$packagePlugins = Join-Path $packageWpContent "plugins"
$customPluginWhitelist = @(
    # Add custom plugins here only when staging should receive them from Git.
)

New-Item -ItemType Directory -Path $packageWpContent -Force | Out-Null
New-Item -ItemType Directory -Path $packageThemes -Force | Out-Null
Copy-Item -LiteralPath (Join-Path $rootWpContent "themes\papetarie-storefront") -Destination $packageThemes -Recurse -Force

if ($customPluginWhitelist.Count -gt 0) {
    New-Item -ItemType Directory -Path $packagePlugins -Force | Out-Null

    foreach ($pluginSlug in $customPluginWhitelist) {
        $pluginSource = Join-Path $rootWpContent ("plugins\" + $pluginSlug)
        if (!(Test-Path -LiteralPath $pluginSource)) {
            throw "Pluginul custom definit pentru deploy nu exista: $pluginSource"
        }

        Copy-Item -LiteralPath $pluginSource -Destination $packagePlugins -Recurse -Force
    }
}

foreach ($path in @(
    (Join-Path $packageRoot ".env"),
    (Join-Path $packageRoot "wp-content\debug.log"),
    (Join-Path $packageRoot "wp-content\wc-logs"),
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

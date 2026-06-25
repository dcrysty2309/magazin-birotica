param(
    [string]$OutputPath = "database\wordpress-staging.sql"
)

$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $repoRoot ".env"
$targetFile = Join-Path $repoRoot $OutputPath

if (!(Test-Path -LiteralPath $envFile)) {
    throw "Nu exista fisierul .env in proiect."
}

$envValues = @{}
Get-Content -LiteralPath $envFile | ForEach-Object {
    if ($_ -match '^\s*#' -or $_ -notmatch '=') {
        return
    }

    $parts = $_ -split '=', 2
    $envValues[$parts[0].Trim()] = $parts[1].Trim()
}

$dbName = $envValues["MYSQL_DATABASE"]
$dbUser = $envValues["MYSQL_USER"]
$dbPass = $envValues["MYSQL_PASSWORD"]

if ([string]::IsNullOrWhiteSpace($dbName) -or [string]::IsNullOrWhiteSpace($dbUser) -or $dbPass -eq $null) {
    throw "Valorile MYSQL_DATABASE, MYSQL_USER sau MYSQL_PASSWORD lipsesc din .env."
}

$targetDir = Split-Path -Parent $targetFile
if (!(Test-Path -LiteralPath $targetDir)) {
    New-Item -ItemType Directory -Path $targetDir | Out-Null
}

if (Test-Path -LiteralPath $targetFile) {
    Remove-Item -LiteralPath $targetFile -Force
}

Push-Location $repoRoot
try {
    & docker compose exec -T db mariadb-dump "--user=$dbUser" "--password=$dbPass" $dbName | Out-File -LiteralPath $targetFile -Encoding utf8
}
finally {
    Pop-Location
}

Write-Host "Export DB creat: $targetFile"

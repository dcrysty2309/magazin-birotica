param(
    [ValidateSet("home-local", "office-local", "staging")]
    [string]$SourceEnvironment,
    [string]$OutputDirectory = "database\\exports",
    [string]$Label = "manual",
    [switch]$CreateLatestAlias
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($SourceEnvironment)) {
    throw "Parametrul -SourceEnvironment este obligatoriu."
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $repoRoot ".env"

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

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$safeLabel = ($Label -replace '[^a-zA-Z0-9._-]', '-').Trim('-')
if ([string]::IsNullOrWhiteSpace($safeLabel)) {
    $safeLabel = "manual"
}

$outputRoot = Join-Path $repoRoot $OutputDirectory
if (!(Test-Path -LiteralPath $outputRoot)) {
    New-Item -ItemType Directory -Path $outputRoot | Out-Null
}

$gitBranch = (& git -C $repoRoot rev-parse --abbrev-ref HEAD).Trim()
$gitCommit = (& git -C $repoRoot rev-parse HEAD).Trim()
$gitShortCommit = (& git -C $repoRoot rev-parse --short HEAD).Trim()
$gitStatus = (& git -C $repoRoot status --short)

$baseName = "dbsync-{0}-{1}-{2}-{3}" -f $SourceEnvironment, $timestamp, $gitShortCommit, $safeLabel
$sqlPath = Join-Path $outputRoot ($baseName + ".sql")
$metaPath = Join-Path $outputRoot ($baseName + ".json")

Push-Location $repoRoot
try {
    $dumpContents = & docker compose exec -T db mariadb-dump "--user=$dbUser" "--password=$dbPass" $dbName | Out-String
    $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($sqlPath, $dumpContents, $utf8NoBom)
}
finally {
    Pop-Location
}

if (!(Test-Path -LiteralPath $sqlPath)) {
    throw "Exportul SQL nu a fost creat."
}

$sqlFile = Get-Item -LiteralPath $sqlPath
if ($sqlFile.Length -le 0) {
    Remove-Item -LiteralPath $sqlPath -Force -ErrorAction SilentlyContinue
    throw "Exportul SQL este gol."
}

$meta = [ordered]@{
    sync_type = "database_export"
    source_environment = $SourceEnvironment
    created_at = (Get-Date).ToString("s")
    label = $safeLabel
    sql_file = $sqlFile.Name
    sql_path = $sqlPath
    sql_size_bytes = $sqlFile.Length
    git_branch = $gitBranch
    git_commit = $gitCommit
    git_short_commit = $gitShortCommit
    git_worktree_clean = [string]::IsNullOrWhiteSpace(($gitStatus -join ""))
}

$meta | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $metaPath -Encoding UTF8

if ($CreateLatestAlias) {
    $latestSql = Join-Path $outputRoot ("latest-{0}.sql" -f $SourceEnvironment)
    $latestMeta = Join-Path $outputRoot ("latest-{0}.json" -f $SourceEnvironment)

    Copy-Item -LiteralPath $sqlPath -Destination $latestSql -Force
    Copy-Item -LiteralPath $metaPath -Destination $latestMeta -Force
}

Write-Host "Export DB creat:"
Write-Host " - SQL:  $sqlPath"
Write-Host " - META: $metaPath"

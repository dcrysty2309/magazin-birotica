param(
    [ValidateSet("home-local", "office-local", "staging")]
    [string]$SourceEnvironment,
    [ValidateSet("home-local", "office-local", "staging")]
    [string]$TargetEnvironment,
    [string]$Label = "manual",
    [string]$DbExportFile = "",
    [string]$OutputDirectory = "docs\\deployment\\manifests"
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($SourceEnvironment) -or [string]::IsNullOrWhiteSpace($TargetEnvironment)) {
    throw "Parametrii -SourceEnvironment si -TargetEnvironment sunt obligatorii."
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$outputRoot = Join-Path $repoRoot $OutputDirectory

if (!(Test-Path -LiteralPath $outputRoot)) {
    New-Item -ItemType Directory -Path $outputRoot | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$safeLabel = ($Label -replace '[^a-zA-Z0-9._-]', '-').Trim('-')
if ([string]::IsNullOrWhiteSpace($safeLabel)) {
    $safeLabel = "manual"
}

$gitBranch = (& git -C $repoRoot rev-parse --abbrev-ref HEAD).Trim()
$gitCommit = (& git -C $repoRoot rev-parse HEAD).Trim()
$gitShortCommit = (& git -C $repoRoot rev-parse --short HEAD).Trim()
$gitCommitMessage = (& git -C $repoRoot log -1 --pretty=%s).Trim()
$gitStatus = (& git -C $repoRoot status --short)
$worktreeClean = [string]::IsNullOrWhiteSpace(($gitStatus -join ""))

$manifestName = "sync-{0}-{1}-to-{2}-{3}-{4}.md" -f $timestamp, $SourceEnvironment, $TargetEnvironment, $gitShortCommit, $safeLabel
$manifestPath = Join-Path $outputRoot $manifestName

$dbRequired = if ([string]::IsNullOrWhiteSpace($DbExportFile)) { "no" } else { "yes" }
$dbImportRequired = if ($dbRequired -eq "yes") { "yes" } else { "no" }

$content = @"
# Sync Manifest

- Sync ID: $timestamp-$gitShortCommit-$safeLabel
- Date: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")
- Source Environment: $SourceEnvironment
- Target Environment: $TargetEnvironment
- Trigger: $safeLabel

## Code

- Git Branch: $gitBranch
- Git Commit SHA: $gitCommit
- Git Commit Message: $gitCommitMessage
- Working Tree Clean: $(if ($worktreeClean) { "yes" } else { "no" })

## Database

- DB Export Required: $dbRequired
- DB Export File: $(if ($DbExportFile) { $DbExportFile } else { "-" })
- DB Export Source: $(if ($DbExportFile) { $SourceEnvironment } else { "-" })
- DB Export Timestamp: $(if ($DbExportFile) { Get-Date -Format "yyyy-MM-dd HH:mm:ss" } else { "-" })
- DB Import Required: $dbImportRequired
- DB Import Target: $TargetEnvironment

## Deploy

- Code Deploy Required: yes
- Deploy Target: $TargetEnvironment
- Deploy Method: git + script/manual
- Cache Clear Required: yes
- Permalink Flush Required: if DB or routing changed

## Test Context

- Checkout URL: $(if ($TargetEnvironment -eq "staging") { "https://memoreaza.ro/checkout/" } else { "http://localhost:8080/checkout/" })
- QA Index URL: $(if ($TargetEnvironment -eq "staging") { "https://memoreaza.ro/checkout-test-cases/" } else { "http://localhost:8080/checkout-test-cases/" })
- Test Users: de completat
- Products in Cart: de completat
- Guest / Logged-in scenarios: de completat

## Validated Flows

- [ ] guest checkout
- [ ] logged-in no address
- [ ] logged-in one address
- [ ] logged-in multiple addresses
- [ ] shipping method
- [ ] billing step
- [ ] payment step

## Notes

- Manifest generat automat. Completează manual pașii de QA și rezultatele reale.

## Result

- Sync Status: pending
- Verified By:
- Verified On:
"@

Set-Content -LiteralPath $manifestPath -Value $content -Encoding UTF8

Write-Host "Manifest creat: $manifestPath"

# Read-only verification of upstream source and exact original skill content.
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$Root = 'P:\Tugas PPL MBG'
$Cache = Join-Path $Root '.anti-slop-upstream'
$SkillBase = Join-Path $Root '.agents\skills'
$Commit = '9685ca7eac70283d083fc23baa9a88e7d3cd93c4'
$Names = @('antislop', 'antislop-ui', 'antislop-copywriting', 'antislop-human', 'antislop-layoutmobile', 'antislop-code')
if (-not (Test-Path -LiteralPath (Join-Path $Cache '.git'))) { throw 'UPSTREAM NOT INSTALLED: source Git clone missing.' }
$Actual = (& git -C $Cache rev-parse HEAD).Trim()
if ($Actual -ne $Commit) { throw "FAIL: expected $Commit but found $Actual" }
$FilesChecked = 0
foreach ($Name in $Names) {
  $Source = Join-Path $Cache "skills\$Name"
  $Dest = Join-Path $SkillBase $Name
  if (-not (Test-Path -LiteralPath $Dest)) { throw "FAIL: missing original skill $Name" }
  $SourceFiles = @(Get-ChildItem -LiteralPath $Source -Recurse -File)
  $DestFiles = @(Get-ChildItem -LiteralPath $Dest -Recurse -File)
  if ($SourceFiles.Count -ne $DestFiles.Count) { throw "FAIL: file count differs in $Name" }
  foreach ($Sf in $SourceFiles) {
    $Rel = $Sf.FullName.Substring($Source.Length + 1)
    $Df = Join-Path $Dest $Rel
    if (-not (Test-Path -LiteralPath $Df -PathType Leaf)) { throw "FAIL: missing $Df" }
    if ((Get-FileHash -LiteralPath $Sf.FullName -Algorithm SHA256).Hash -ne (Get-FileHash -LiteralPath $Df -Algorithm SHA256).Hash) { throw "FAIL: SHA256 differs for $Name/$Rel" }
    $FilesChecked++
  }
  Write-Host "PASS original skill: $Name"
}
if (-not (Test-Path -LiteralPath (Join-Path $Root 'AGENTS.md') -PathType Leaf)) { throw 'FAIL: project root AGENTS.md missing.' }
$Router = Get-Content -LiteralPath (Join-Path $Root 'AGENTS.md') -Raw
if ($Router -notmatch 'antislop/SKILL.md' -or $Router -notmatch 'MBG-Track-Documentation-v4-RepoFirst') { throw 'FAIL: root AGENTS.md missing original upstream + v4 design pointers; merge manually.' }
Write-Host "PASS: original commit $Actual, 6 skills, $FilesChecked files byte-identical."
Write-Host 'NOTE: this checks installation, NOT whether the app passes visual quality or upstream Delivery Gate.'

# Install the exact upstream original anti-slop skills into an existing Laravel project.
# DOES NOT edit any MBG-Track-Documentation folder. Git + network required.
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$Root = 'P:\Tugas PPL MBG'
$Documentation = Join-Path $Root 'MBG-Track-Documentation-v4-RepoFirst'
$Repo = 'https://github.com/miqdadbadjuber/anti-slop'
$Commit = '9685ca7eac70283d083fc23baa9a88e7d3cd93c4'
$Cache = Join-Path $Root '.anti-slop-upstream'
$SkillBase = Join-Path $Root '.agents\skills'
$Names = @('antislop', 'antislop-ui', 'antislop-copywriting', 'antislop-human', 'antislop-layoutmobile', 'antislop-code')

if (-not (Test-Path -LiteralPath (Join-Path $Root 'artisan') -PathType Leaf)) { throw 'Laravel root not recognized; refusing install.' }
if (-not (Test-Path -LiteralPath $Documentation -PathType Container)) { throw 'v4 documentation not in expected location; refusing install.' }
if (-not (Get-Command git -ErrorAction SilentlyContinue)) { throw 'Git is required; nothing installed.' }

# Prevent unexpected symlink redirects for protected paths and install paths.
foreach ($Path in @($Cache, (Join-Path $Root '.agents'), $SkillBase)) {
  if (Test-Path -LiteralPath $Path) {
    $Item = Get-Item -LiteralPath $Path -Force
    if (($Item.Attributes -band [IO.FileAttributes]::ReparsePoint) -ne 0) { throw "Refusing reparse/symlink install path: $Path" }
  }
}

if (-not (Test-Path -LiteralPath $Cache)) {
  Write-Host 'Cloning the original upstream anti-slop repository into an isolated cache...'
  & git clone --quiet --no-checkout $Repo $Cache
  if ($LASTEXITCODE -ne 0) { throw 'Git clone failed.' }
} elseif (-not (Test-Path -LiteralPath (Join-Path $Cache '.git'))) {
  throw 'Existing upstream cache is not a Git checkout. Refusing to overwrite it.'
}

$CommitType = (& git -C $Cache cat-file -t $Commit)
if ($LASTEXITCODE -ne 0 -or $CommitType.Trim() -ne 'commit') { throw 'Pinned commit not available in upstream checkout; stop without modifying app.' }
& git -C $Cache checkout --detach --quiet $Commit
if ($LASTEXITCODE -ne 0) { throw 'Pinned checkout failed.' }
$Actual = (& git -C $Cache rev-parse HEAD).Trim()
if ($Actual -ne $Commit) { throw "Unexpected upstream SHA $Actual" }

# Preflight ALL skill targets before copying anything. Existing differing skills stop the installation.
foreach ($Name in $Names) {
  $Source = Join-Path $Cache "skills\$Name"
  $Dest = Join-Path $SkillBase $Name
  if (-not (Test-Path -LiteralPath (Join-Path $Source 'SKILL.md'))) { throw "Missing upstream original skill: $Name" }
  if (Test-Path -LiteralPath $Dest) {
    $ExistingFiles = @(Get-ChildItem -LiteralPath $Dest -Recurse -File)
    $SourceFiles = @(Get-ChildItem -LiteralPath $Source -Recurse -File)
    if ($ExistingFiles.Count -ne $SourceFiles.Count) { throw "Collision at $Dest (file counts differ). Refusing overwrite." }
    foreach ($Sf in $SourceFiles) {
      $Rel = $Sf.FullName.Substring($Source.Length + 1)
      $Df = Join-Path $Dest $Rel
      if (-not (Test-Path -LiteralPath $Df -PathType Leaf)) { throw "Collision at $Dest (missing file $Rel)." }
      if ((Get-FileHash -LiteralPath $Sf.FullName -Algorithm SHA256).Hash -ne (Get-FileHash -LiteralPath $Df -Algorithm SHA256).Hash) { throw "Collision at $Df (contents differ)." }
    }
  }
}

New-Item -ItemType Directory -Path $SkillBase -Force | Out-Null
foreach ($Name in $Names) {
  $Source = Join-Path $Cache "skills\$Name"
  $Dest = Join-Path $SkillBase $Name
  if (-not (Test-Path -LiteralPath $Dest)) {
    Copy-Item -LiteralPath $Source -Destination $Dest -Recurse
    Write-Host "Installed original upstream skill: $Name"
  } else { Write-Host "Already identical: $Name" }
}

$EntryPairs = @(
  @('AGENTS.md', 'ROOT-AGENTS.md'),
  @('GEMINI.md', 'ROOT-GEMINI.md'),
  @('CLAUDE.md', 'ROOT-CLAUDE.md')
)
foreach ($Pair in $EntryPairs) {
  $Dest = Join-Path $Root $Pair[0]
  $Source = Join-Path $Documentation ("templates\" + $Pair[1])
  if (-not (Test-Path -LiteralPath $Dest)) {
    Copy-Item -LiteralPath $Source -Destination $Dest
    Write-Host "Created root entrypoint: $Dest"
  } else {
    Write-Warning "Existing root entrypoint preserved: $Dest. Ensure it manually references original antislop skills and v4 DESIGN.md."
  }
}

# Exclude protected documentation and cloned source from accidental git add. Only modifies Laravel-root .gitignore.
$GitIgnore = Join-Path $Root '.gitignore'
if (Test-Path -LiteralPath $GitIgnore) {
  $IgnoreText = Get-Content -LiteralPath $GitIgnore -Raw
  $Entries = @('/.anti-slop-upstream/', '/MBG-Track-Documentation/', '/MBG-Track-Documentation-v3/', '/MBG-Track-Documentation-v4-RepoFirst/')
  foreach ($Entry in $Entries) {
    if (-not ($IgnoreText -split '[\r\n]+' | Where-Object { $_.Trim() -eq $Entry })) {
      Add-Content -LiteralPath $GitIgnore -Value $Entry
      Write-Host "Added gitignore entry: $Entry"
    }
  }
}

Write-Host "Installed from original Git commit: $Actual"
Write-Host 'NEXT: run Check-AntiSlop-Integration.ps1; open a fresh Antigravity session.'

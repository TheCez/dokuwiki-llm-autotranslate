<#
.SYNOPSIS
  Dispatch a coder/tester subagent as its own live Claude Code session in a new Herdr tab.

.DESCRIPTION
  Orchestration mechanism for this project (see ../../CLAUDE.md). Each subagent:
    - runs in its OWN Herdr tab (observable live, not a pane split),
    - uses model 'sonnet' by default (subagents are always Sonnet; only the orchestrator is Opus),
    - runs with --dangerously-skip-permissions (autonomous),
    - receives its full task prompt AT LAUNCH by reading the brief file as claude's positional
      prompt arg (Get-Content -Raw) so long briefs survive Windows shell quoting,
    - is detected complete via `herdr agent wait --status idle` (NOT text matching, because a
      brief may itself contain any sentinel text),
    - has its tab CLOSED automatically once complete.

  Not wmux, not a2a - pure Herdr-native CLI.

.EXAMPLE
  ./run-subagent.ps1 -Brief ../briefs/slice2-coder.md -Label coder-slice2 -Cwd C:\path\to\worktree
#>
param(
  [Parameter(Mandatory=$true)][string]$Brief,
  [Parameter(Mandatory=$true)][string]$Label,
  [string]$Cwd = (Get-Location).Path,
  [int]$TimeoutSec = 1800,
  [ValidateSet('sonnet','opus','haiku')][string]$Model = 'sonnet'
)
$ErrorActionPreference = 'Stop'

# Stable symlinked launcher (version-independent); fall back to PATH.
$H = "C:\Users\ajayc\AppData\Local\Programs\Herdr\bin\herdr.exe"
if (-not (Test-Path $H)) { $H = (Get-Command herdr.exe -ErrorAction Stop).Source }

$briefFull = (Resolve-Path $Brief).Path

# 1. new tab
$tj   = & $H tab create --cwd $Cwd --label $Label --focus | ConvertFrom-Json
$pane = $tj.result.root_pane.pane_id
$tab  = $tj.result.tab.tab_id
Write-Host "[subagent] label=$Label model=$Model pane=$pane tab=$tab"

# 2. launch claude with the brief supplied as the initial prompt at launch time
$cmd = "claude --model $Model --dangerously-skip-permissions (Get-Content -Raw '$briefFull')"
& $H pane run $pane $cmd | Out-Null

# 3. wait for it to start working (best-effort) then for it to finish (idle)
try { & $H agent wait $pane --status working --timeout 60000 | Out-Null } catch {}
& $H agent wait $pane --status idle --timeout ($TimeoutSec * 1000) | Out-Null

# 4. capture the visible transcript tail
$out = & $H pane read $pane --source recent --lines 160 | Out-String

# 5. close the tab now that the task is done
& $H tab close $tab | Out-Null
Write-Host "[subagent] label=$Label done; closed tab=$tab"
Write-Host "----- transcript tail -----"
Write-Host $out

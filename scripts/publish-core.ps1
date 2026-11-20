#Requires -Version 5.1
<#
.SYNOPSIS
  将 packages/core subtree 拆到独立仓并打 SemVer tag（默认 1.0.0）。

.DESCRIPTION
  按 docs/architecture/packagist.md：
  1) （可选）在 packages/core 跑测试
  2) git subtree split --prefix=packages/core -b split/core
  3) push 到一个或多个 remote 的主分支（默认 main），并推送正式 tag vX.Y.Z
  4) 本仓额外打镜像 tag core-vX.Y.Z

  版本不写进 composer.json，以 git tag 为准。
  Packagist 建议注册 GitHub 仓；Gitee 可作为镜像同步。

.PARAMETER Version
  SemVer，不含 v 前缀。默认 1.0.0

.PARAMETER Remotes
  独立仓 remote 名列表。默认 @('starship-core')（建议指向 GitHub）。
  可同时推多个，例如：-Remotes starship-core,starship-core-gitee

.PARAMETER Branch
  独立仓目标分支。默认 main（GitHub 空仓默认分支）

.PARAMETER SkipTests
  跳过 composer test

.PARAMETER DryRun
  只 split + 本地 tag，不 push

.EXAMPLE
  ./scripts/publish-core.ps1 -Version 1.0.0 -DryRun

.EXAMPLE
  # 仅 GitHub（Packagist 推荐）
  git remote add starship-core git@github.com:alin40404/starship-core.git
  ./scripts/publish-core.ps1 -Version 1.0.0

.EXAMPLE
  # 同时推 GitHub + Gitee
  git remote add starship-core git@github.com:alin40404/starship-core.git
  git remote add starship-core-gitee git@gitee.com:allanchan004/starship-core.git
  ./scripts/publish-core.ps1 -Version 1.0.0 -Remotes starship-core,starship-core-gitee
#>
[CmdletBinding()]
param(
    [string] $Version = '1.0.0',
    [string[]] $Remotes = @('starship-core'),
    [string] $Branch = 'main',
    [switch] $SkipTests,
    [switch] $DryRun
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

if ($Version -notmatch '^\d+\.\d+\.\d+([+-].+)?$') {
    throw "Version 须为 SemVer（如 1.0.0），当前：$Version"
}

# 兼容 -Remotes "a,b" 与 -Remotes a,b
$Remotes = @($Remotes | ForEach-Object { $_ -split ',' } | ForEach-Object { $_.Trim() } | Where-Object { $_ })

$phpCandidates = @(
    'D:\phpstudy_pro\Extensions\php\php8.3.11nts',
    'D:\phpstudy_pro\Extensions\php\php8.3.14nts'
)
foreach ($dir in $phpCandidates) {
    if (Test-Path (Join-Path $dir 'php.exe')) {
        $env:Path = "$dir;$env:Path"
        break
    }
}

Write-Host "==> repo: $repoRoot"
Write-Host "==> version: $Version  remotes: $($Remotes -join ', ')  branch: $Branch  dryRun: $DryRun"

$status = git status --porcelain
if ($status) {
    Write-Warning "工作区有未提交变更。建议先提交再发包，以免 split 漏掉改动。"
    $status | Select-Object -First 20 | ForEach-Object { Write-Host "  $_" }
}

if (-not $SkipTests) {
    Write-Host '==> composer test (packages/core)'
    Push-Location (Join-Path $repoRoot 'packages\core')
    try {
        composer test
        if ($LASTEXITCODE -ne 0) { throw "测试失败，exit=$LASTEXITCODE" }
    } finally {
        Pop-Location
    }
}

Write-Host '==> git subtree split --prefix=packages/core -b split/core'
$existing = git branch --list split/core
if ($existing) {
    git branch -D split/core | Out-Null
}
git subtree split --prefix=packages/core -b split/core
if ($LASTEXITCODE -ne 0) { throw "subtree split 失败" }

$monoTag = "core-v$Version"
$pkgTag = "v$Version"

Write-Host "==> annotate tags on split/core: $monoTag + $pkgTag"
foreach ($tagName in @($monoTag, $pkgTag)) {
    $oldTag = git tag -l $tagName
    if ($oldTag) {
        Write-Warning "本地已有 tag $tagName，将删除后重建"
        git tag -d $tagName | Out-Null
    }
    git tag -a $tagName split/core -m "starship-admin/core $Version"
    if ($LASTEXITCODE -ne 0) { throw "打 tag $tagName 失败" }
}

if ($DryRun) {
    Write-Host ''
    Write-Host 'DryRun 完成。未 push。后续：'
    foreach ($remote in $Remotes) {
        Write-Host "  git push $remote split/core:$Branch"
        Write-Host "  git push $remote $pkgTag"
        Write-Host "  git push $remote $monoTag"
    }
    Write-Host 'Packagist 请 Submit GitHub 仓 URL（推荐）。'
    exit 0
}

$configured = git remote
$missing = @($Remotes | Where-Object { $configured -notcontains $_ })
if ($missing.Count -gt 0) {
    throw @"
未找到 remote：$($missing -join ', ')

一次性准备（GitHub 为主，Gitee 可选镜像）：

  # 1. GitHub 建空仓 https://github.com/alin40404/starship-core
  git remote add starship-core git@github.com:alin40404/starship-core.git

  # 2.（可选）Gitee 建同名空仓并镜像
  git remote add starship-core-gitee git@gitee.com:allanchan004/starship-core.git

然后：
  ./scripts/publish-core.ps1 -Version $Version -Remotes starship-core,starship-core-gitee
"@
}

foreach ($remote in $Remotes) {
    Write-Host "==> push $remote split/core:$Branch"
    git push $remote "split/core:${Branch}"
    if ($LASTEXITCODE -ne 0) { throw "push $remote $Branch 失败" }

    Write-Host "==> push $remote tags $pkgTag $monoTag"
    git push $remote $pkgTag
    if ($LASTEXITCODE -ne 0) { throw "push $remote $pkgTag 失败" }
    git push $remote $monoTag
    if ($LASTEXITCODE -ne 0) { throw "push $remote $monoTag 失败" }
}

Write-Host ''
Write-Host '已推送。接下来：'
Write-Host '  1. Packagist → Submit → 填 GitHub 仓 URL（https://github.com/alin40404/starship-core）'
Write-Host '  2. 打开 GitHub → Packagist 自动更新（Service Hook / Packagist GitHub App）'
Write-Host "  3. 下游：composer require starship-admin/core:^$($Version.Split('.')[0]).0"
Write-Host ''
Write-Host '说明：Gitee 镜像不参与 Packagist 注册亦可；Composer 只认 Packagist 上登记的那个 Git 源。'

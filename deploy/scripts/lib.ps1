#Requires -Version 5.1
$ErrorActionPreference = 'Stop'

$script:DeployRoot    = $PSScriptRoot
$script:BackendDir    = Split-Path -Parent (Split-Path -Parent $DeployRoot)
$script:ExtractScript = Join-Path $DeployRoot 'extract.sh'
$script:Config        = . (Join-Path $DeployRoot 'config.ps1')

function Get-DeployConfig { return $script:Config }

function Write-DeployStep([string]$Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Write-DeploySuccess([string]$Message) {
    Write-Host $Message -ForegroundColor Green
}

function Write-DeployWarn([string]$Message) {
    Write-Host $Message -ForegroundColor Yellow
}

function Write-DeployError([string]$Message) {
    Write-Host $Message -ForegroundColor Red
}

function Test-ShouldExcludeBackendPath {
    param(
        [string]$RelativePath,
        [bool]$IsDirectory,
        # app    = code only (exclude vendor)
        # full   = code + vendor
        # vendor = vendor tree only
        [ValidateSet('app', 'full', 'vendor')]
        [string]$Mode = 'app'
    )

    $path = ($RelativePath -replace '\\', '/').Trim('/')
    if (-not $path) { return $false }

    if ($Mode -eq 'vendor') {
        if ($path -ne 'vendor' -and -not $path.StartsWith('vendor/')) { return $true }
    }

    $excludeDirs = @(
        '.git', 'node_modules', '.idea', '.vscode', 'tests', 'deploy', 'docs',
        'storage/logs',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views'
    )
    if ($Mode -eq 'app') {
        $excludeDirs = @('vendor') + $excludeDirs
    }

    foreach ($dir in $excludeDirs) {
        if ($path -eq $dir -or $path.StartsWith("$dir/")) { return $true }
    }

    if ($IsDirectory) { return $false }

    $fileName = [IO.Path]::GetFileName($path)
    $excludeFiles = @(
        '.gitignore', '.env', '.env.backup', '.phpunit.result.cache',
        'Homestead.json', 'Homestead.yaml', 'auth.json',
        'PersonalAccessToken.txt', 'npm-debug.log', 'yarn-error.log'
    )

    if ($excludeFiles -contains $fileName) { return $true }
    if ($path -like '.env.*') { return $true }
    if ($path -like '*.log') { return $true }

    # Heavy geo DBs — always via extras upload (option 3)
    if ($path -like 'database/ip2location/*.BIN') { return $true }
    if ($path -like 'vendor/ip2location/*/data/*.BIN') { return $true }

    if ($Mode -eq 'full' -or $Mode -eq 'vendor') {
        if ($path -eq 'vendor/laravel/pint' -or $path.StartsWith('vendor/laravel/pint/')) { return $true }
        if ($path -match '(?i)^vendor/[^/]+/[^/]+/(tests|test|docs|doc|examples|example|\.github)(/|$)') { return $true }
        if ($path -match '(?i)^vendor/.+\.(md|markdown|rst|phpt)$') { return $true }
    }

    return $false
}

function New-BackendZip {
    param(
        [string]$SourceDir,
        [string]$ZipPath,
        [ValidateSet('app', 'full', 'vendor')]
        [string]$Mode = 'app'
    )

    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem

    if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }

    $sourceFull = (Resolve-Path $SourceDir).Path.TrimEnd('\', '/')
    $level     = [System.IO.Compression.CompressionLevel]::Fastest
    $zipStream = [System.IO.File]::Create($ZipPath)
    $zip       = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)
    $copyBuf   = New-Object byte[] (1024 * 1024)
    $state     = @{ Files = 0; Bytes = 0L; LastReportSec = 0 }
    $sw        = [System.Diagnostics.Stopwatch]::StartNew()
    $stack     = New-Object System.Collections.Generic.Stack[string]
    $stack.Push($sourceFull)

    Write-Host "   packing mode=$Mode (Fastest)..." -ForegroundColor DarkGray

    try {
        while ($stack.Count -gt 0) {
            $currentDir = $stack.Pop()
            foreach ($entryPath in [System.IO.Directory]::EnumerateFileSystemEntries($currentDir)) {
                $isDir = ([System.IO.File]::GetAttributes($entryPath) -band [System.IO.FileAttributes]::Directory) -ne 0
                $relativeFs = $entryPath.Substring($sourceFull.Length + 1)
                if (Test-ShouldExcludeBackendPath -RelativePath $relativeFs -IsDirectory $isDir -Mode $Mode) { continue }

                if ($isDir) {
                    $stack.Push($entryPath)
                    continue
                }

                $relative = $relativeFs -replace '\\', '/'
                $entry = $zip.CreateEntry($relative, $level)
                $out   = $entry.Open()
                $in    = [System.IO.File]::OpenRead($entryPath)
                try {
                    while ($true) {
                        $n = $in.Read($copyBuf, 0, $copyBuf.Length)
                        if ($n -le 0) { break }
                        $out.Write($copyBuf, 0, $n)
                        $state.Bytes += $n
                    }
                }
                finally {
                    $in.Close()
                    $out.Close()
                }

                $state.Files++
                $elapsed = $sw.Elapsed.TotalSeconds
                if (($state.Files % 250) -eq 0 -or ($elapsed - $state.LastReportSec) -ge 2) {
                    $rate = if ($elapsed -gt 0) { $state.Files / $elapsed } else { 0 }
                    Write-Host ("   ... {0} files / {1} ({2:N0} files/s)" -f `
                        $state.Files, (Format-DeployBytes $state.Bytes), $rate) -ForegroundColor DarkGray
                    $state.LastReportSec = $elapsed
                }
            }
        }
    }
    finally {
        $zip.Dispose()
        $zipStream.Close()
    }

    $sw.Stop()
    Write-Host ("   packed {0} files ({1}) in {2:N1}s" -f `
        $state.Files, (Format-DeployBytes $state.Bytes), $sw.Elapsed.TotalSeconds) -ForegroundColor DarkGray

    return $state.Files
}

function Get-PlinkPath {
    $cmd = Get-Command plink -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    $default = 'C:\Program Files\PuTTY\plink.exe'
    if (Test-Path $default) { return $default }
    throw 'plink not found. Install PuTTY from https://www.putty.org/'
}

function Get-PscpPath {
    $cmd = Get-Command pscp -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    $default = 'C:\Program Files\PuTTY\pscp.exe'
    if (Test-Path $default) { return $default }
    throw 'pscp not found. Install PuTTY from https://www.putty.org/'
}

function Test-UseSshKey {
    return $script:Config.SshKeyPath -and (Test-Path $script:Config.SshKeyPath)
}

function Get-PlinkCommonArgs {
    $c = $script:Config
    return @('-batch', '-hostkey', $c.SshHostKey, '-ssh', "$($c.SshUser)@$($c.SshHost)", '-P', $c.SshPort, '-pw', $c.SshPass)
}

function ConvertTo-UnixRemoteCommand {
    param([string]$Command)

    # PowerShell here-strings on Windows are CRLF; bash then treats `set -e\r` / `fi\r` as syntax errors.
    return (($Command + '') -replace "`r`n", "`n" -replace "`r", "`n").TrimEnd()
}

function Invoke-RemoteCommand {
    param(
        [string]$Command,
        [switch]$AllowFailure
    )

    $Command = ConvertTo-UnixRemoteCommand $Command
    $c = $script:Config
    if (Test-UseSshKey) {
        & ssh -p $c.SshPort -i $c.SshKeyPath -o StrictHostKeyChecking=accept-new "$($c.SshUser)@$($c.SshHost)" $Command
    }
    else {
        $plink = Get-PlinkPath
        $args  = @(Get-PlinkCommonArgs) + @($Command)
        & $plink @args
    }

    if (-not $AllowFailure -and $LASTEXITCODE -ne 0) {
        throw "Remote command failed (exit $LASTEXITCODE): $Command"
    }
    return $LASTEXITCODE
}

function Invoke-RemoteCommandText {
    param([string]$Command)

    $Command = ConvertTo-UnixRemoteCommand $Command
    $c = $script:Config
    $prev = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        if (Test-UseSshKey) {
            $raw = & ssh -p $c.SshPort -i $c.SshKeyPath -o StrictHostKeyChecking=accept-new `
                "$($c.SshUser)@$($c.SshHost)" $Command 2>&1 | Out-String
        }
        else {
            $plink = Get-PlinkPath
            $raw = & $plink @(Get-PlinkCommonArgs) $Command 2>&1 | Out-String
        }
        $code = [int]$LASTEXITCODE
    }
    finally {
        $ErrorActionPreference = $prev
    }

    return [pscustomobject]@{
        ExitCode = $code
        Text     = (($raw + '') -replace '\r', '').Trim()
    }
}

function Get-RemoteFileSizeBytes {
    param([string]$RemotePath)

    $r = Invoke-RemoteCommandText "stat -c%s -- '$RemotePath' 2>/dev/null || echo NOFILE"
    if ($r.Text -match '(?m)^(\d+)$') {
        return [int64]$Matches[1]
    }
    return -1L
}

function Get-UnixLineEndingFile {
    param([string]$Path)

    $text = [System.IO.File]::ReadAllText($Path)
    $text = $text -replace "`r`n", "`n" -replace "`r", "`n"
    $tempPath = [System.IO.Path]::Combine(
        [System.IO.Path]::GetTempPath(),
        [System.IO.Path]::GetRandomFileName() + [IO.Path]::GetExtension($Path)
    )
    $utf8NoBom = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllText($tempPath, $text, $utf8NoBom)
    return $tempPath
}

function Format-DeployBytes([long]$Bytes) {
    if ($Bytes -ge 1GB) { return ('{0:N2} GB' -f ($Bytes / 1GB)) }
    if ($Bytes -ge 1MB) { return ('{0:N2} MB' -f ($Bytes / 1MB)) }
    if ($Bytes -ge 1KB) { return ('{0:N1} KB' -f ($Bytes / 1KB)) }
    return "$Bytes B"
}

function Format-DeploySpeed([double]$BytesPerSec) {
    if ($BytesPerSec -le 0) { return '--/s' }
    return "$(Format-DeployBytes ([long]$BytesPerSec))/s"
}

function ConvertTo-ProcessArgumentList {
    param([string[]]$Arguments)

    $parts = foreach ($arg in $Arguments) {
        if ($null -eq $arg) { '""'; continue }
        $text = [string]$arg
        if ($text -match '[\s"&<>|^]') {
            '"' + ($text -replace '"', '\"') + '"'
        }
        else {
            $text
        }
    }
    return ($parts -join ' ')
}

function Invoke-PscpOrScp {
    param(
        [string]$LocalPath,
        [string]$RemotePath,
        [string]$ProgressLabel = 'upload',
        [switch]$Quiet
    )

    # Use real pscp/scp with console inherited — live % / speed / ETA from the tool itself.
    # Do NOT redirect stdout/stderr (that buffers and breaks live progress + caused fake 0→100 bars).
    $c = $script:Config
    $fileSize = [long](Get-Item -LiteralPath $LocalPath).Length
    $sw = [System.Diagnostics.Stopwatch]::StartNew()

    if (Test-UseSshKey) {
        $exe = (Get-Command scp -ErrorAction Stop).Source
        $argList = @(
            '-P', "$($c.SshPort)",
            '-i', $c.SshKeyPath,
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ServerAliveInterval=15',
            '-o', 'ServerAliveCountMax=8',
            '-o', 'TCPKeepAlive=yes',
            $LocalPath,
            "$($c.SshUser)@$($c.SshHost):$RemotePath"
        )
    }
    else {
        $exe = Get-PscpPath
        $argList = @(
            '-batch',
            '-hostkey', $c.SshHostKey,
            '-P', "$($c.SshPort)",
            '-pw', $c.SshPass,
            $LocalPath,
            "$($c.SshUser)@$($c.SshHost):$RemotePath"
        )
    }

    if (-not $Quiet) {
        Write-Host "   $ProgressLabel — live transfer ($(Format-DeployBytes $fileSize)):" -ForegroundColor DarkGray
    }

    $argString = ConvertTo-ProcessArgumentList -Arguments $argList
    $proc = Start-Process -FilePath $exe -ArgumentList $argString -Wait -PassThru -NoNewWindow
    $code = [int]$proc.ExitCode
    $sw.Stop()

    if (-not $Quiet) {
        if ($code -eq 0) {
            $avg = if ($sw.Elapsed.TotalSeconds -gt 0) { $fileSize / $sw.Elapsed.TotalSeconds } else { 0 }
            Write-Host ("   $ProgressLabel ok in {0:N1}s  avg {1}" -f $sw.Elapsed.TotalSeconds, (Format-DeploySpeed $avg)) -ForegroundColor DarkGray
        }
        else {
            Write-Host "   $ProgressLabel failed (exit $code)" -ForegroundColor Red
        }
    }

    return $code
}

function Send-RemoteFileDirect {
    param(
        [string]$LocalPath,
        [string]$RemotePath,
        [int]$MaxAttempts = 3,
        [string]$ProgressLabel = 'upload',
        [switch]$Quiet,
        [switch]$VerifySize
    )

    $localSize = [long](Get-Item -LiteralPath $LocalPath).Length
    $attempt = 0
    $lastExit = 1

    while ($attempt -lt $MaxAttempts) {
        $attempt++
        if ($attempt -gt 1) {
            $waitSec = [math]::Min(20, 4 * ($attempt - 1))
            Write-Host ''
            Write-DeployWarn "Retry $attempt/$MaxAttempts in ${waitSec}s (exit $lastExit) — $ProgressLabel"
            Start-Sleep -Seconds $waitSec
            try { [void](Invoke-RemoteCommand "rm -f -- '$RemotePath'" -AllowFailure) } catch { }
        }

        $lastExit = [int](Invoke-PscpOrScp `
            -LocalPath $LocalPath `
            -RemotePath $RemotePath `
            -ProgressLabel $ProgressLabel `
            -Quiet:$Quiet)

        if ($lastExit -ne 0) { continue }

        if ($VerifySize) {
            $remoteSize = Get-RemoteFileSizeBytes -RemotePath $RemotePath
            if ($remoteSize -ne $localSize) {
                Write-DeployWarn "$ProgressLabel size mismatch: remote=$remoteSize local=$localSize"
                $lastExit = 2
                continue
            }
            if (-not $Quiet) {
                Write-Host "   verified $(Format-DeployBytes $remoteSize)" -ForegroundColor DarkGray
            }
        }

        return
    }

    throw "Upload failed after $MaxAttempts attempts: $LocalPath -> $RemotePath (last exit $lastExit)"
}

function Split-DeployFileChunks {
    param(
        [string]$SourcePath,
        [string]$ChunkDir,
        [int]$ChunkSizeBytes
    )

    if (Test-Path $ChunkDir) { Remove-Item $ChunkDir -Recurse -Force }
    New-Item -ItemType Directory -Path $ChunkDir | Out-Null

    $buffer = New-Object byte[] $ChunkSizeBytes
    $in = [System.IO.File]::OpenRead($SourcePath)
    $index = 0
    $chunks = New-Object System.Collections.Generic.List[string]

    try {
        while ($true) {
            $read = $in.Read($buffer, 0, $buffer.Length)
            if ($read -le 0) { break }

            $chunkPath = Join-Path $ChunkDir ("part-{0:D3}" -f $index)
            $out = [System.IO.File]::Create($chunkPath)
            try { $out.Write($buffer, 0, $read) }
            finally { $out.Close() }

            [void]$chunks.Add($chunkPath)
            $index++
        }
    }
    finally {
        $in.Close()
    }

    if ($chunks.Count -eq 0) {
        throw "Failed to split file into chunks: $SourcePath"
    }
    return $chunks
}

function Send-RemoteFileChunked {
    param(
        [string]$LocalPath,
        [string]$RemotePath,
        [int]$ChunkSizeMb = 8,
        [string]$ProgressLabel = 'upload'
    )

    $localSize = [long](Get-Item -LiteralPath $LocalPath).Length
    $sizeMb = [math]::Round($localSize / 1MB, 2)
    $chunkBytes = [int]($ChunkSizeMb * 1MB)
    $chunkDir = Join-Path $env:TEMP ("zanburak-upload-chunks-" + [Guid]::NewGuid().ToString('N'))
    $remotePartDir = "/tmp/zanburak-upload-parts-$([Guid]::NewGuid().ToString('N').Substring(0, 8))"
    $totalSw = [System.Diagnostics.Stopwatch]::StartNew()

    Write-Host "   file: $sizeMb MB | split into ${ChunkSizeMb} MB parts" -ForegroundColor DarkGray

    try {
        $chunks = @(Split-DeployFileChunks -SourcePath $LocalPath -ChunkDir $chunkDir -ChunkSizeBytes $chunkBytes)
        Write-Host "   total parts: $($chunks.Count)" -ForegroundColor DarkGray

        [void](Invoke-RemoteCommand "rm -rf -- '$remotePartDir' '$RemotePath'; mkdir -p -- '$remotePartDir'")

        $remotePartPaths = New-Object System.Collections.Generic.List[string]
        $i = 0
        foreach ($chunk in $chunks) {
            $i++
            $name = [IO.Path]::GetFileName($chunk)
            $remoteChunk = "$remotePartDir/$name"
            $chunkSize = [long](Get-Item -LiteralPath $chunk).Length
            $chunkMb = [math]::Round($chunkSize / 1MB, 2)
            $label = "part $i/$($chunks.Count)"

            Write-Host ""
            Write-Host "   ---- $ProgressLabel / $label ($chunkMb MB) ----" -ForegroundColor Yellow

            Send-RemoteFileDirect `
                -LocalPath $chunk `
                -RemotePath $remoteChunk `
                -MaxAttempts 4 `
                -ProgressLabel $label `
                -VerifySize

            [void]$remotePartPaths.Add($remoteChunk)
        }

        Write-Host ""
        Write-Host "   joining $($chunks.Count) parts on server..." -ForegroundColor DarkGray
        $partsArg = ($remotePartPaths -join ' ')
        [void](Invoke-RemoteCommand "cat -- $partsArg > '$RemotePath'")

        $actual = Get-RemoteFileSizeBytes -RemotePath $RemotePath
        if ($actual -ne $localSize) {
            $listing = (Invoke-RemoteCommandText "ls -la -- '$remotePartDir'").Text
            throw "Join size mismatch: remote=$actual local=$localSize`n$listing"
        }

        [void](Invoke-RemoteCommand "rm -rf -- '$remotePartDir'")
        $totalSw.Stop()
        $avg = if ($totalSw.Elapsed.TotalSeconds -gt 0) { $localSize / $totalSw.Elapsed.TotalSeconds } else { 0 }
        Write-Host ("   $ProgressLabel complete: $sizeMb MB in {0:N1}s  avg {1}" -f $totalSw.Elapsed.TotalSeconds, (Format-DeploySpeed $avg)) -ForegroundColor Green
    }
    finally {
        if (Test-Path $chunkDir) { Remove-Item $chunkDir -Recurse -Force -ErrorAction SilentlyContinue }
        try { [void](Invoke-RemoteCommand "rm -rf -- '$remotePartDir'" -AllowFailure) } catch { }
    }
}

function Send-RemoteFile {
    param(
        [string]$LocalPath,
        [string]$RemotePath,
        [int]$MaxAttempts = 3,
        [int]$ChunkThresholdMb = 48,
        [int]$ChunkSizeMb = 32,
        [string]$ProgressLabel = 'upload'
    )

    if (-not (Test-Path $LocalPath)) { throw "Local file not found: $LocalPath" }

    $sizeMb = (Get-Item -LiteralPath $LocalPath).Length / 1MB
    if ($sizeMb -gt $ChunkThresholdMb) {
        Send-RemoteFileChunked -LocalPath $LocalPath -RemotePath $RemotePath -ChunkSizeMb $ChunkSizeMb -ProgressLabel $ProgressLabel
        return
    }

    Send-RemoteFileDirect -LocalPath $LocalPath -RemotePath $RemotePath -MaxAttempts $MaxAttempts -ProgressLabel $ProgressLabel -VerifySize
}

function Test-BackendProject {
    if (-not (Test-Path $script:BackendDir)) {
        throw "Backend folder not found: $($script:BackendDir)"
    }
    if (-not (Test-Path (Join-Path $script:BackendDir 'composer.json'))) {
        throw "composer.json not found in zanburak-backend."
    }
    if (-not (Test-Path (Join-Path $script:BackendDir 'composer.lock'))) {
        throw "composer.lock not found in zanburak-backend."
    }
    if (-not (Test-Path (Join-Path $script:BackendDir 'production-env.txt'))) {
        throw "production-env.txt not found in zanburak-backend."
    }
}

function Get-LocalZipPath {
    return Join-Path $env:TEMP $script:Config.ZipName
}

function Get-LocalVendorZipPath {
    return Join-Path $env:TEMP $script:Config.VendorZipName
}

function Get-BackendDeployExtraFiles {
    $extras = New-Object System.Collections.ArrayList

    $ip2Dir = Join-Path $script:BackendDir 'database\ip2location'
    if (Test-Path $ip2Dir) {
        foreach ($bin in @(Get-ChildItem -Path $ip2Dir -Filter '*.BIN' -File -ErrorAction SilentlyContinue)) {
            [void]$extras.Add([pscustomobject]@{
                Label          = "ip2location/$($bin.Name)"
                LocalPath      = $bin.FullName
                RemoteRelative = "database/ip2location/$($bin.Name)"
            })
        }
    }

    Write-Output -NoEnumerate @($extras.ToArray())
}

function Test-RemoteExtraNeedsUpload {
    param(
        [string]$RemoteRelative,
        [long]$LocalSize
    )

    $c = $script:Config
    $remotePath = "$($c.RemoteDir)/$RemoteRelative"
    $remoteSize = Get-RemoteFileSizeBytes -RemotePath $remotePath
    if ($remoteSize -lt 0) { return $true }
    return ($remoteSize -ne $LocalSize)
}

function Invoke-BackendExtrasUpload {
    param([switch]$Force)

    $extras = Get-BackendDeployExtraFiles
    if ($null -eq $extras) { $extras = @() }
    $extras = @($extras)
    if ($extras.Count -eq 0) {
        Write-DeployWarn "No large extra files to upload."
        return
    }

    $c = $script:Config
    $pending = New-Object System.Collections.Generic.List[object]
    foreach ($extra in $extras) {
        $fileSize = [int64](Get-Item -LiteralPath $extra.LocalPath).Length
        if (-not $Force -and -not (Test-RemoteExtraNeedsUpload -RemoteRelative $extra.RemoteRelative -LocalSize $fileSize)) {
            Write-Host "   skip $($extra.Label) — already on server ($(Format-DeployBytes $fileSize))" -ForegroundColor DarkGray
            continue
        }
        [void]$pending.Add($extra)
    }

    if ($pending.Count -eq 0) {
        Write-DeploySuccess "Large extras already on server — skipped upload."
        return
    }

    $totalBytes = 0L
    foreach ($extra in $pending) {
        $totalBytes += [int64](Get-Item -LiteralPath $extra.LocalPath).Length
    }

    Write-Host "   extras: $($pending.Count)/$($extras.Count) files / $(Format-DeployBytes $totalBytes)" -ForegroundColor DarkGray

    $i = 0
    foreach ($extra in $pending) {
        $i++
        $remotePath = "$($c.RemoteDir)/$($extra.RemoteRelative)"
        $remoteDir  = ($remotePath -replace '/[^/]+$', '')
        $fileSize   = [int64](Get-Item -LiteralPath $extra.LocalPath).Length
        $sizeMb     = [math]::Round($fileSize / 1MB, 2)

        Write-Host ""
        Write-Host "   ==== extra $i/$($pending.Count): $($extra.Label) ($sizeMb MB) ====" -ForegroundColor Yellow

        [void](Invoke-RemoteCommand "mkdir -p '$remoteDir'")
        Send-RemoteFile `
            -LocalPath $extra.LocalPath `
            -RemotePath $remotePath `
            -ChunkThresholdMb 48 `
            -ChunkSizeMb 32 `
            -ProgressLabel "extra $i/$($pending.Count)"
    }

    $u = $c.WebUser
    $g = $c.WebGroup
    [void](Invoke-RemoteCommand "chown -R ${u}:${g} $($c.RemoteDir)/database/ip2location 2>/dev/null || true")
    Write-DeploySuccess "Large extras uploaded."
}

function Invoke-BackendZip {
    param(
        [ValidateSet('app', 'full')]
        [string]$Mode = 'app'
    )

    Test-BackendProject
    if ($Mode -eq 'full' -and -not (Test-Path (Join-Path $script:BackendDir 'vendor\autoload.php'))) {
        throw "vendor/autoload.php not found. Run composer install locally first."
    }

    $label = if ($Mode -eq 'full') { 'app + vendor' } else { 'app only' }
    Write-DeployStep "Creating backend zip ($label)"

    $zipPath   = Get-LocalZipPath
    $fileCount = New-BackendZip -SourceDir $script:BackendDir -ZipPath $zipPath -Mode $Mode
    $zipSizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)

    Write-Host "   files in zip: $fileCount"
    Write-Host "   zip path: $zipPath ($zipSizeMb MB)"
    return $zipPath
}

function Invoke-VendorZip {
    Test-BackendProject
    if (-not (Test-Path (Join-Path $script:BackendDir 'vendor\autoload.php'))) {
        throw "vendor/autoload.php not found. Run composer install locally first."
    }

    Write-DeployStep "Creating vendor zip"
    $zipPath   = Get-LocalVendorZipPath
    $fileCount = New-BackendZip -SourceDir $script:BackendDir -ZipPath $zipPath -Mode 'vendor'
    $zipSizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
    Write-Host "   files in zip: $fileCount"
    Write-Host "   zip path: $zipPath ($zipSizeMb MB)"
    return $zipPath
}

function Invoke-BackendUpload {
    param([string]$ZipPath)

    if (-not $ZipPath) { $ZipPath = Get-LocalZipPath }
    if (-not (Test-Path $ZipPath)) { throw "Zip file not found. Run create zip first." }

    $c = $script:Config
    Write-DeployStep "Uploading package ($($c.SshHost):$($c.SshPort))"
    Send-RemoteFile -LocalPath $ZipPath -RemotePath $c.RemoteZip -ChunkThresholdMb 96 -ChunkSizeMb 32 -ProgressLabel 'core'
}

function Invoke-BackendExtract {
    param(
        [ValidateSet('app', 'full')]
        [string]$Mode = 'app'
    )

    Write-DeployStep "Extracting on server ($Mode) → $($script:Config.RemoteDir)"

    $unixExtractScript = Get-UnixLineEndingFile -Path $script:ExtractScript
    try {
        Send-RemoteFile -LocalPath $unixExtractScript -RemotePath '/tmp/backend-extract.sh' -ProgressLabel 'extract-script'
    }
    finally {
        Remove-Item $unixExtractScript -Force -ErrorAction SilentlyContinue
    }

    $result = Invoke-RemoteCommandText "bash /tmp/backend-extract.sh $Mode"
    if ($result.Text) {
        Write-Host $result.Text
    }
    [void](Invoke-RemoteCommand 'rm -f /tmp/backend-extract.sh' -AllowFailure)
    if ($result.ExitCode -ne 0) {
        throw "Extract failed (exit $($result.ExitCode)). See remote output above."
    }
    Write-DeploySuccess "Package extracted on server ($Mode)."
}

function Invoke-VendorExtract {
    Write-DeployStep "Extracting vendor on server ($($script:Config.RemoteDir)/vendor)"

    $vendorScript = Join-Path $script:DeployRoot 'extract-vendor.sh'
    $unix = Get-UnixLineEndingFile -Path $vendorScript
    try {
        Send-RemoteFile -LocalPath $unix -RemotePath '/tmp/backend-extract-vendor.sh' -ProgressLabel 'vendor-extract-script'
    }
    finally {
        Remove-Item $unix -Force -ErrorAction SilentlyContinue
    }

    $result = Invoke-RemoteCommandText 'bash /tmp/backend-extract-vendor.sh'
    if ($result.Text) {
        Write-Host $result.Text
    }
    [void](Invoke-RemoteCommand 'rm -f /tmp/backend-extract-vendor.sh' -AllowFailure)
    if ($result.ExitCode -ne 0) {
        throw "Vendor extract failed (exit $($result.ExitCode)). See remote output above."
    }
    Write-DeploySuccess "Vendor extracted on server."
}

function Invoke-AppBackendDeploy {
    # Fast daily deploy: app code only, keep vendor/storage on server
    Test-BackendProject
    $zipPath = Invoke-BackendZip -Mode 'app'
    $completed = $false
    try {
        Invoke-BackendUpload -ZipPath $zipPath
        Invoke-BackendExtract -Mode 'app'
        $completed = $true
    }
    catch {
        if (Test-Path $zipPath) {
            Write-DeployWarn "Local zip kept for retry: $zipPath"
        }
        throw
    }
    finally {
        if ($completed) {
            Write-DeployStep "Cleaning up local zip"
            Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
        }
    }

    Write-DeploySuccess "App deploy completed."
    Write-DeploySuccess "Remote path: $($script:Config.RemoteDir)"
}

function Invoke-FullBackendDeploy {
    # Everything: app + vendor zip, then heavy BIN extras
    Test-BackendProject
    $zipPath = Invoke-BackendZip -Mode 'full'
    $completed = $false
    try {
        Invoke-BackendUpload -ZipPath $zipPath
        Invoke-BackendExtract -Mode 'full'
        Write-DeployStep "Uploading heavy extras (ip2location BIN)"
        Invoke-BackendExtrasUpload
        $completed = $true
    }
    catch {
        if (Test-Path $zipPath) {
            Write-DeployWarn "Local zip kept for retry: $zipPath"
        }
        throw
    }
    finally {
        if ($completed) {
            Write-DeployStep "Cleaning up local zip"
            Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
        }
    }

    Write-DeploySuccess "Full deploy completed."
    Write-DeploySuccess "Remote path: $($script:Config.RemoteDir)"
    Write-DeployWarn "Next: [21] install services → [22] start → [6] migrate → [14] rebuild cache"
}

function Invoke-VendorHeavyDeploy {
    # Vendor package + ip2location BIN files (no app wipe)
    Test-BackendProject
    $zipPath = Invoke-VendorZip
    $completed = $false
    try {
        $c = $script:Config
        Write-DeployStep "Uploading vendor package"
        Send-RemoteFile -LocalPath $zipPath -RemotePath $c.RemoteVendorZip -ChunkThresholdMb 96 -ChunkSizeMb 32 -ProgressLabel 'vendor'
        Invoke-VendorExtract
        Write-DeployStep "Uploading heavy extras (ip2location BIN)"
        Invoke-BackendExtrasUpload -Force
        $completed = $true
    }
    catch {
        if (Test-Path $zipPath) {
            Write-DeployWarn "Local vendor zip kept for retry: $zipPath"
        }
        throw
    }
    finally {
        if ($completed) {
            Write-DeployStep "Cleaning up local vendor zip"
            Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
        }
    }

    Write-DeploySuccess "Vendor + heavy files uploaded."
}

function Repair-RemotePermissions {
    $c = $script:Config
    $t = $c.RemoteDir
    $u = $c.WebUser
    $g = $c.WebGroup

    Write-DeployStep "Fixing file permissions ($u on storage, bootstrap/cache, database)"
    $cmd = "chown -R ${u}:${g} $t/storage $t/bootstrap/cache $t/database && chown ${u}:${g} $t/.env 2>/dev/null; chmod -R ug+rwx $t/storage $t/bootstrap/cache; chmod -R ug+rwX $t/database"
    Invoke-RemoteCommand $cmd
}

function Invoke-RemoteComposer {
    param([string]$ComposerArgs)

    Repair-RemotePermissions

    $c = $script:Config
    $cmd = "cd $($c.RemoteDir) && sudo -u $($c.WebUser) composer $ComposerArgs"
    Write-DeployStep "composer $ComposerArgs"
    Invoke-RemoteCommand $cmd
}

function Invoke-RemoteArtisan {
    param([string]$ArtisanArgs)

    Repair-RemotePermissions

    $c = $script:Config
    $cmd = "cd $($c.RemoteDir) && sudo -u $($c.WebUser) $($c.PhpPath) artisan $ArtisanArgs"
    Write-DeployStep "artisan $ArtisanArgs"
    Invoke-RemoteCommand $cmd
}

function Get-BackendSeeders {
    $seedersDir = Join-Path $script:BackendDir 'database\seeders'
    if (-not (Test-Path $seedersDir)) { return @() }

    return Get-ChildItem -Path $seedersDir -Filter '*Seeder.php' |
        Where-Object { $_.BaseName -ne 'DatabaseSeeder' } |
        Sort-Object Name |
        ForEach-Object { $_.BaseName }
}

function Get-MeilisearchStartScriptContent {
    $c = $script:Config
    $tplPath = Join-Path $DeployRoot 'meilisearch-start.sh'
    if (-not (Test-Path $tplPath)) { throw "Meilisearch start script not found: $tplPath" }

    $content = [System.IO.File]::ReadAllText($tplPath)
    $content = $content.Replace('{{REMOTE_DIR}}', $c.RemoteDir)
    $content = $content.Replace('{{MEILI_BIN}}', $c.MeilisearchBin)
    $content = $content.Replace('{{MEILI_DATA_PATH}}', $c.MeilisearchDataPath)
    return $content
}

function Sync-MeilisearchStartScript {
    $c = $script:Config
    $remoteScript = "$($c.RemoteDir)/bin/meilisearch-start.sh"

    Write-DeployStep "Syncing meilisearch start script (reads key from .env at runtime)"

    $content  = Get-MeilisearchStartScriptContent
    $tempFile = [System.IO.Path]::GetTempFileName()
    $utf8NoBom = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllText($tempFile, $content, $utf8NoBom)

    try {
        Invoke-RemoteCommand "mkdir -p $($c.RemoteDir)/bin"
        Send-RemoteFile -LocalPath $tempFile -RemotePath $remoteScript
        Invoke-RemoteCommand "chmod +x $remoteScript && chown $($c.WebUser):$($c.WebGroup) $remoteScript"
    }
    finally {
        Remove-Item $tempFile -Force -ErrorAction SilentlyContinue
    }
}

function Get-ServiceDefinition {
    param([string]$ServiceName)

    return $script:Config.Services |
        Where-Object { $_.Name -eq $ServiceName } |
        Select-Object -First 1
}

function Test-IsSystemPackageService {
    param([string]$ServiceName)

    $svc = Get-ServiceDefinition -ServiceName $ServiceName
    return [bool]($svc -and $svc.SystemPackage)
}

function Ensure-SystemPackageService {
    param(
        [string]$ServiceName,
        [string]$PackageName
    )

    if (-not $PackageName) { $PackageName = $ServiceName }

    Write-DeployStep "Ensuring system package service: $ServiceName (apt: $PackageName)"

    # Install package if missing, then enable + start the distro unit.
    $cmd = @"
set -e
export DEBIAN_FRONTEND=noninteractive
if ! dpkg -s $PackageName >/dev/null 2>&1; then
  apt-get update -qq
  apt-get install -y $PackageName
fi
systemctl enable $ServiceName
systemctl restart $ServiceName
systemctl is-active --quiet $ServiceName
"@

    Invoke-RemoteCommand $cmd
    Write-DeploySuccess "System package service $ServiceName is installed and running."
}

function Get-SystemdServiceContent {
    param([string]$ServiceName)

    $c       = $script:Config
    $tplPath = Join-Path $DeployRoot "systemd\$ServiceName.service"
    if (-not (Test-Path $tplPath)) { throw "Service template not found: $tplPath" }

    $content = [System.IO.File]::ReadAllText($tplPath)
    $content = $content.Replace('{{REMOTE_DIR}}', $c.RemoteDir)
    $content = $content.Replace('{{PHP_PATH}}', $c.PhpPath)
    $content = $content.Replace('{{WEB_USER}}', $c.WebUser)
    $content = $content.Replace('{{WEB_GROUP}}', $c.WebGroup)
    $content = $content.Replace('{{MEILI_BIN}}', $c.MeilisearchBin)
    $content = $content.Replace('{{MEILI_DATA_PATH}}', $c.MeilisearchDataPath)

    return $content
}

function Install-SystemdService {
    param([string]$ServiceName)

    $svc = Get-ServiceDefinition -ServiceName $ServiceName
    if ($svc -and $svc.SystemPackage) {
        Ensure-SystemPackageService -ServiceName $ServiceName -PackageName $svc.Package
        return
    }

    Write-DeployStep "Installing/updating systemd service: $ServiceName"

    if ($ServiceName -eq 'meilisearch') {
        $c = $script:Config
        Invoke-RemoteCommand "mkdir -p $($c.MeilisearchDataPath) && chown -R $($c.WebUser):$($c.WebGroup) $($c.MeilisearchDataPath)"
        Sync-MeilisearchStartScript
    }

    $content  = Get-SystemdServiceContent -ServiceName $ServiceName
    $tempFile = [System.IO.Path]::GetTempFileName()
    $utf8NoBom = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllText($tempFile, $content, $utf8NoBom)

    $remoteTmp = "/tmp/$ServiceName.service"
    try {
        Send-RemoteFile -LocalPath $tempFile -RemotePath $remoteTmp
        Invoke-RemoteCommand "mv $remoteTmp /etc/systemd/system/$ServiceName.service && chmod 644 /etc/systemd/system/$ServiceName.service"
        Invoke-RemoteCommand "systemctl daemon-reload"
        Invoke-RemoteCommand "systemctl enable $ServiceName"
        Write-DeploySuccess "Service $ServiceName installed and enabled."
    }
    finally {
        Remove-Item $tempFile -Force -ErrorAction SilentlyContinue
    }
}

function Install-AllSystemdServices {
    foreach ($svc in $script:Config.Services) {
        Install-SystemdService -ServiceName $svc.Name
    }
    Write-DeploySuccess "All systemd services installed/updated."
}

function Invoke-SystemdAction {
    param(
        [string]$Action,
        [string[]]$ServiceNames
    )

    if (-not $ServiceNames -or $ServiceNames.Count -eq 0) {
        $ServiceNames = $script:Config.Services | ForEach-Object { $_.Name }
    }

    foreach ($name in $ServiceNames) {
        Write-DeployStep "systemctl $Action $name"
        Invoke-RemoteCommand "systemctl $Action $name"
    }
}

function Get-SystemdStatus {
    param([string[]]$ServiceNames)

    if (-not $ServiceNames -or $ServiceNames.Count -eq 0) {
        $ServiceNames = $script:Config.Services | ForEach-Object { $_.Name }
    }

    foreach ($name in $ServiceNames) {
        Write-Host ""
        Write-Host "--- $name ---" -ForegroundColor Magenta
        Invoke-RemoteCommand "systemctl status $name --no-pager -l" -AllowFailure
    }
}

function Show-SystemdLogs {
    param(
        [string]$ServiceName,
        [int]$Lines = 50
    )

    Write-DeployStep "Logs for $ServiceName (last $Lines lines)"
    Invoke-RemoteCommand "journalctl -u $ServiceName -n $Lines --no-pager"
}

function Test-RemoteServiceExists {
    param([string]$ServiceName)

    # Distro units (e.g. redis-server) live under /lib/systemd; custom ones under /etc.
    $exit = Invoke-RemoteCommand "systemctl cat $ServiceName >/dev/null 2>&1" -AllowFailure
    return $exit -eq 0
}

function Ensure-SystemdServices {
    param([string[]]$ServiceNames)

    if (-not $ServiceNames -or $ServiceNames.Count -eq 0) {
        $ServiceNames = $script:Config.Services | ForEach-Object { $_.Name }
    }

    foreach ($name in $ServiceNames) {
        if (Test-IsSystemPackageService -ServiceName $name) {
            $svc = Get-ServiceDefinition -ServiceName $name
            Ensure-SystemPackageService -ServiceName $name -PackageName $svc.Package
            continue
        }

        $exists = Test-RemoteServiceExists -ServiceName $name
        if ($exists) {
            Write-Host "   Service $name already exists." -ForegroundColor DarkGray
        }
        else {
            Write-DeployWarn "Service $name not found - creating..."
            Install-SystemdService -ServiceName $name
        }
    }
}

function Start-SystemdServices {
    param([string[]]$ServiceNames)
    Ensure-SystemdServices -ServiceNames $ServiceNames

    $names = if ($ServiceNames -and $ServiceNames.Count -gt 0) {
        $ServiceNames
    } else {
        $script:Config.Services | ForEach-Object { $_.Name }
    }

    if ($names -contains 'meilisearch') {
        Sync-MeilisearchStartScript
    }

    Invoke-SystemdAction -Action 'start' -ServiceNames $ServiceNames
    Write-DeploySuccess "Services started."
}

function Restart-SystemdServices {
    param([string[]]$ServiceNames)
    Ensure-SystemdServices -ServiceNames $ServiceNames

    $names = if ($ServiceNames -and $ServiceNames.Count -gt 0) {
        $ServiceNames
    } else {
        $script:Config.Services | ForEach-Object { $_.Name }
    }

    if ($names -contains 'meilisearch') {
        Sync-MeilisearchStartScript
    }

    Invoke-SystemdAction -Action 'restart' -ServiceNames $ServiceNames
    Write-DeploySuccess "Services restarted."
}

function Stop-SystemdServices {
    param([string[]]$ServiceNames)
    Invoke-SystemdAction -Action 'stop' -ServiceNames $ServiceNames
    Write-DeploySuccess "Services stopped."
}

function Invoke-PostDeployOptimize {
    $t = $script:Config.RemoteDir
    $u = $script:Config.WebUser
    $g = $script:Config.WebGroup
    Write-DeployStep "Ensuring Laravel storage directories exist"
    $mkdirCmd = @"
mkdir -p $t/storage/framework/cache/data $t/storage/framework/sessions $t/storage/framework/views $t/storage/framework/testing $t/storage/logs $t/storage/app/public $t/bootstrap/cache && chown -R ${u}:${g} $t/storage $t/bootstrap/cache && chmod -R ug+rwx $t/storage $t/bootstrap/cache
"@
    [void](Invoke-RemoteCommand $mkdirCmd.Trim())

    Invoke-RemoteArtisan 'config:cache'
    Invoke-RemoteArtisan 'route:cache'
    Invoke-RemoteArtisan 'view:cache'
    Write-DeploySuccess "Laravel caches rebuilt."
}


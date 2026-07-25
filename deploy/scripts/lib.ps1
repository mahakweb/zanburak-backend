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
        [bool]$IsDirectory
    )

    $path = ($RelativePath -replace '\\', '/').Trim('/')
    if (-not $path) { return $false }

    $excludeDirs = @('.git', 'node_modules', '.idea', '.vscode', 'tests', 'deploy')
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
    if ($path -match '^storage/logs/.*\.log$') { return $true }

    return $false
}

function Get-BackendFilesForZip {
    param([string]$SourceDir)

    $sourceFull = (Resolve-Path $SourceDir).Path.TrimEnd('\', '/')
    $files      = New-Object System.Collections.Generic.List[System.IO.FileInfo]

    function Walk([string]$CurrentDir) {
        foreach ($item in Get-ChildItem -Path $CurrentDir -Force) {
            $relative = $item.FullName.Substring($sourceFull.Length + 1)
            if (Test-ShouldExcludeBackendPath -RelativePath $relative -IsDirectory $item.PSIsContainer) { continue }
            if ($item.PSIsContainer) { Walk $item.FullName }
            else { [void]$files.Add($item) }
        }
    }

    Walk $SourceDir
    return $files
}

function New-BackendZip {
    param(
        [string]$SourceDir,
        [string]$ZipPath
    )

    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem

    if (Test-Path $ZipPath) { Remove-Item $ZipPath -Force }

    $sourceFull = (Resolve-Path $SourceDir).Path.TrimEnd('\', '/')
    $zipStream  = [System.IO.File]::Create($ZipPath)
    $zip        = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)
    $fileCount  = 0

    try {
        foreach ($file in Get-BackendFilesForZip -SourceDir $SourceDir) {
            $relative = $file.FullName.Substring($sourceFull.Length + 1) -replace '\\', '/'
            $entry    = $zip.CreateEntry($relative, [System.IO.Compression.CompressionLevel]::Optimal)
            $out      = $entry.Open()
            $in       = [System.IO.File]::OpenRead($file.FullName)
            try { $in.CopyTo($out) }
            finally { $in.Close(); $out.Close() }
            $fileCount++
        }
    }
    finally {
        $zip.Dispose()
        $zipStream.Close()
    }

    return $fileCount
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

function Invoke-RemoteCommand {
    param(
        [string]$Command,
        [switch]$AllowFailure
    )

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

function Send-RemoteFile {
    param(
        [string]$LocalPath,
        [string]$RemotePath
    )

    if (-not (Test-Path $LocalPath)) { throw "Local file not found: $LocalPath" }

    $c = $script:Config
    if (Test-UseSshKey) {
        & scp -P $c.SshPort -i $c.SshKeyPath -o StrictHostKeyChecking=accept-new $LocalPath "$($c.SshUser)@$($c.SshHost):$RemotePath"
    }
    else {
        $pscp = Get-PscpPath
        & $pscp -batch -hostkey $c.SshHostKey -P $c.SshPort -pw $c.SshPass $LocalPath "$($c.SshUser)@$($c.SshHost):$RemotePath"
    }

    if ($LASTEXITCODE -ne 0) { throw "Upload failed: $LocalPath -> $RemotePath" }
    Write-Host "   uploaded: $RemotePath" -ForegroundColor DarkGray
}

function Test-BackendProject {
    if (-not (Test-Path $script:BackendDir)) {
        throw "Backend folder not found: $($script:BackendDir)"
    }
    if (-not (Test-Path (Join-Path $script:BackendDir 'vendor'))) {
        throw "vendor folder not found. Run composer install in zanburak-backend first."
    }
    if (-not (Test-Path (Join-Path $script:BackendDir 'production-env.txt'))) {
        throw "production-env.txt not found in zanburak-backend."
    }
}

function Get-LocalZipPath {
    return Join-Path $env:TEMP $script:Config.ZipName
}

function Invoke-BackendZip {
    Test-BackendProject
    Write-DeployStep "Creating backend zip"

    $zipPath   = Get-LocalZipPath
    $fileCount = New-BackendZip -SourceDir $script:BackendDir -ZipPath $zipPath
    $zipSizeMb = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)

    Write-Host "   files in zip: $fileCount"
    Write-Host "   zip path: $zipPath ($zipSizeMb MB)"
    return $zipPath
}

function Invoke-BackendUpload {
    param([string]$ZipPath)

    if (-not $ZipPath) { $ZipPath = Get-LocalZipPath }
    if (-not (Test-Path $ZipPath)) { throw "Zip file not found. Run 'Create zip' first." }

    $c = $script:Config
    Write-DeployStep "Uploading to server ($($c.SshHost):$($c.SshPort))"
    Send-RemoteFile -LocalPath $ZipPath -RemotePath $c.RemoteZip
}

function Invoke-BackendExtract {
    Write-DeployStep "Extracting on server ($($script:Config.RemoteDir))"

    $unixExtractScript = Get-UnixLineEndingFile -Path $script:ExtractScript
    try {
        Send-RemoteFile -LocalPath $unixExtractScript -RemotePath '/tmp/backend-extract.sh'
    }
    finally {
        Remove-Item $unixExtractScript -Force -ErrorAction SilentlyContinue
    }

    Invoke-RemoteCommand "chmod +x /tmp/backend-extract.sh && bash /tmp/backend-extract.sh && rm -f /tmp/backend-extract.sh"
}

function Invoke-FullBackendDeploy {
    Test-BackendProject
    $zipPath = Invoke-BackendZip
    try {
        Invoke-BackendUpload -ZipPath $zipPath
        Invoke-BackendExtract
    }
    finally {
        Write-DeployStep "Cleaning up local zip"
        Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
    }

    Write-DeploySuccess "Backend deploy completed successfully."
    Write-DeploySuccess "Remote path: $($script:Config.RemoteDir)"
    Write-DeployWarn "Next (from deploy menu): [20] install services → [21] start → [5] migrate → [13] rebuild cache"
    Write-DeployWarn "Required running: redis-server, laravel-reverb, laravel-scheduler, laravel-queue"
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
    Invoke-RemoteArtisan 'config:cache'
    Invoke-RemoteArtisan 'route:cache'
    Invoke-RemoteArtisan 'view:cache'
    Write-DeploySuccess "Laravel caches rebuilt."
}


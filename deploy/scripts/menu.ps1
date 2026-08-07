#Requires -Version 5.1
$ErrorActionPreference = 'Stop'

. (Join-Path $PSScriptRoot 'lib.ps1')

$Host.UI.RawUI.WindowTitle = 'Zanburak Backend Deploy Manager'

function Write-Banner {
    Clear-Host
    $c = Get-DeployConfig
    Write-Host ''
    Write-Host '  +============================================================+' -ForegroundColor DarkCyan
    Write-Host '  |         Zanburak - Backend Deploy Manager                  |' -ForegroundColor Cyan
    Write-Host '  +============================================================+' -ForegroundColor DarkCyan
    Write-Host "  |  Server: $($c.SshHost):$($c.SshPort)  |  Path: $($c.RemoteDir)" -ForegroundColor DarkGray
    Write-Host '  +============================================================+' -ForegroundColor DarkCyan
    Write-Host ''
}

function Write-Section([string]$Title) {
    Write-Host "  -- $Title --" -ForegroundColor Yellow
}

function Write-MenuItem([string]$Key, [string]$Label) {
    Write-Host ('  [{0}] {1}' -f $Key.PadLeft(2), $Label)
}

function Wait-Continue {
    Write-Host ''
    Write-Host '  Press Enter to return to menu...' -ForegroundColor DarkGray
    [void][Console]::ReadLine()
}

function Confirm-Dangerous([string]$Message) {
    Write-Host ''
    Write-Host "  [!] $Message" -ForegroundColor Red
    $answer = Read-Host '  Type YES to confirm'
    return ($answer -eq 'YES')
}

function Invoke-SafeAction {
    param(
        [scriptblock]$Action,
        [string]$DangerConfirm
    )

    if ($DangerConfirm -and -not (Confirm-Dangerous $DangerConfirm)) {
        Write-DeployWarn 'Operation cancelled.'
        Wait-Continue
        return
    }

    try {
        & $Action
    }
    catch {
        Write-DeployError $_.Exception.Message
    }

    Wait-Continue
}

function Show-ServiceSubMenu {
    param([string]$ActionLabel, [scriptblock]$Action)

    while ($true) {
        Write-Banner
        Write-Section "Services - $ActionLabel"
        Write-Host ''

        $services = (Get-DeployConfig).Services
        for ($i = 0; $i -lt $services.Count; $i++) {
            $num = ($i + 1).ToString()
            Write-MenuItem $num "$($services[$i].Label) ($($services[$i].Name))"
        }
        Write-MenuItem 'A' 'All services'
        Write-MenuItem '0' 'Back'
        Write-Host ''

        $choice = (Read-Host '  Choice').Trim().ToUpperInvariant()
        if ($choice -eq '0') { return }

        $names = @()
        if ($choice -eq 'A') {
            $names = @($services | ForEach-Object { $_.Name })
        }
        elseif ($choice -match '^\d+$') {
            $idx = [int]$choice - 1
            if ($idx -ge 0 -and $idx -lt $services.Count) {
                $names = @($services[$idx].Name)
            }
        }

        if ($names.Count -eq 0) {
            Write-DeployWarn 'Invalid choice.'
            Start-Sleep -Seconds 1
            continue
        }

        # Call action directly — do NOT wrap in Invoke-SafeAction's -Action
        # scriptblock. That parameter is also named $Action and shadows this
        # function's $Action, so restart/stop "All" silently did nothing.
        try {
            & $Action $names
        }
        catch {
            Write-DeployError $_.Exception.Message
        }
        Wait-Continue
    }
}

function Show-SeederMenu {
    while ($true) {
        Write-Banner
        Write-Section 'Run Single Seeder'
        Write-Host ''

        $seeders = Get-BackendSeeders
        if ($seeders.Count -eq 0) {
            Write-DeployWarn 'No seeders found.'
            Wait-Continue
            return
        }

        for ($i = 0; $i -lt $seeders.Count; $i++) {
            Write-MenuItem (($i + 1).ToString()) $seeders[$i]
        }
        Write-MenuItem '0' 'Back'
        Write-Host ''

        $choice = (Read-Host '  Seeder number').Trim()
        if ($choice -eq '0') { return }
        if ($choice -notmatch '^\d+$') { continue }

        $idx = [int]$choice - 1
        if ($idx -lt 0 -or $idx -ge $seeders.Count) { continue }

        $seeder = $seeders[$idx]
        Invoke-SafeAction -Action {
            Invoke-RemoteArtisan "db:seed --class=$seeder --force"
            Write-DeploySuccess "Seeder $seeder completed."
        }
    }
}

function Show-MainMenu {
    while ($true) {
        Write-Banner

        Write-Section 'DEPLOY'
        Write-MenuItem '1' 'Full deploy (zip -> upload -> extract -> replace)'
        Write-MenuItem '2' 'Create zip only'
        Write-MenuItem '3' 'Upload zip to server only'
        Write-MenuItem '4' 'Extract on server only'
        Write-Host ''

        Write-Section 'LARAVEL ARTISAN (remote)'
        Write-MenuItem '5' 'php artisan migrate --force'
        Write-MenuItem '6' 'php artisan migrate:fresh --force'
        Write-MenuItem '7' 'php artisan migrate:refresh --force'
        Write-MenuItem '8' 'php artisan db:seed --force (all)'
        Write-MenuItem '9' 'Run single seeder...'
        Write-MenuItem '10' 'migrate:fresh --seed --force'
        Write-MenuItem '11' 'migrate:refresh --seed --force'
        Write-MenuItem '12' 'storage:link'
        Write-MenuItem '13' 'Rebuild cache (config/route/view)'
        Write-MenuItem '14' 'optimize:clear'
        Write-Host ''

        Write-Section 'SERVER MAINTENANCE'
        Write-MenuItem '15' 'Fix file permissions (storage/database)'
        Write-MenuItem '16' 'queue:restart'
        Write-MenuItem '17' 'Post-deploy (permissions + rebuild cache)'
        Write-MenuItem '18' 'Sync meilisearch start script (.env key)'
        Write-MenuItem '19' 'composer dump-autoload (optimize)'
        Write-Host ''

        Write-Section 'SYSTEMD SERVICES (Redis / Queue / Reverb / Scheduler / Meilisearch)'
        Write-MenuItem '20' 'Install/create service unit files (incl. Redis apt package)'
        Write-MenuItem '21' 'Start services (creates if missing)'
        Write-MenuItem '22' 'Stop services'
        Write-MenuItem '23' 'Restart services'
        Write-MenuItem '24' 'Service status'
        Write-MenuItem '25' 'Manage single service (start/stop/restart/status/log)'
        Write-Host ''

        Write-MenuItem '0' 'Exit'
        Write-Host ''

        $choice = (Read-Host '  Enter option number').Trim()

        switch ($choice) {
            '0' { return }

            '1' {
                Invoke-SafeAction -Action { Invoke-FullBackendDeploy }
            }
            '2' {
                Invoke-SafeAction -Action {
                    $zip = Invoke-BackendZip
                    Write-DeploySuccess "Zip ready: $zip"
                }
            }
            '3' {
                Invoke-SafeAction -Action { Invoke-BackendUpload }
            }
            '4' {
                Invoke-SafeAction -Action { Invoke-BackendExtract }
            }

            '5' {
                Invoke-SafeAction -Action { Invoke-RemoteArtisan 'migrate --force' }
            }
            '6' {
                Invoke-SafeAction -DangerConfirm 'migrate:fresh will DROP ALL tables!' -Action {
                    Invoke-RemoteArtisan 'migrate:fresh --force'
                }
            }
            '7' {
                Invoke-SafeAction -DangerConfirm 'migrate:refresh will rollback and re-run all migrations!' -Action {
                    Invoke-RemoteArtisan 'migrate:refresh --force'
                }
            }
            '8' {
                Invoke-SafeAction -Action { Invoke-RemoteArtisan 'db:seed --force' }
            }
            '9' {
                Show-SeederMenu
            }
            '10' {
                Invoke-SafeAction -DangerConfirm 'migrate:fresh --seed will DROP ALL tables and re-seed!' -Action {
                    Invoke-RemoteArtisan 'migrate:fresh --seed --force'
                }
            }
            '11' {
                Invoke-SafeAction -DangerConfirm 'migrate:refresh --seed will rollback and re-seed!' -Action {
                    Invoke-RemoteArtisan 'migrate:refresh --seed --force'
                }
            }
            '12' {
                Invoke-SafeAction -Action { Invoke-RemoteArtisan 'storage:link' }
            }
            '13' {
                Invoke-SafeAction -Action { Invoke-PostDeployOptimize }
            }
            '14' {
                Invoke-SafeAction -Action { Invoke-RemoteArtisan 'optimize:clear' }
            }
            '15' {
                Invoke-SafeAction -Action {
                    Repair-RemotePermissions
                    Write-DeploySuccess 'Server permissions updated.'
                }
            }
            '16' {
                Invoke-SafeAction -Action { Invoke-RemoteArtisan 'queue:restart' }
            }
            '17' {
                Invoke-SafeAction -Action {
                    Repair-RemotePermissions
                    Invoke-PostDeployOptimize
                    Write-DeploySuccess 'Post-deploy maintenance completed.'
                }
            }
            '18' {
                Invoke-SafeAction -Action {
                    Sync-MeilisearchStartScript
                    Write-DeploySuccess 'Meilisearch start script synced from .env.'
                }
            }
            '19' {
                Invoke-SafeAction -Action { Invoke-RemoteComposer 'dump-autoload -o' }
            }

            '20' {
                Invoke-SafeAction -Action { Install-AllSystemdServices }
            }
            '21' {
                Invoke-SafeAction -Action { Start-SystemdServices }
            }
            '22' {
                Show-ServiceSubMenu -ActionLabel 'Stop' -Action {
                    param($names)
                    Stop-SystemdServices -ServiceNames $names
                }
            }
            '23' {
                Show-ServiceSubMenu -ActionLabel 'Restart' -Action {
                    param($names)
                    Restart-SystemdServices -ServiceNames $names
                }
            }
            '24' {
                Invoke-SafeAction -Action { Get-SystemdStatus }
            }
            '25' {
                Show-ServiceDetailMenu
            }

            default {
                Write-DeployWarn 'Invalid option. Try again.'
                Start-Sleep -Seconds 1
            }
        }
    }
}

function Show-ServiceDetailMenu {
    while ($true) {
        Write-Banner
        Write-Section 'Manage Single Service'
        Write-Host ''

        $services = (Get-DeployConfig).Services
        for ($i = 0; $i -lt $services.Count; $i++) {
            Write-MenuItem (($i + 1).ToString()) "$($services[$i].Label) ($($services[$i].Name))"
        }
        Write-MenuItem '0' 'Back'
        Write-Host ''

        $svcChoice = (Read-Host '  Service').Trim()
        if ($svcChoice -eq '0') { return }
        if ($svcChoice -notmatch '^\d+$') { continue }

        $idx = [int]$svcChoice - 1
        if ($idx -lt 0 -or $idx -ge $services.Count) { continue }

        $svcName = $services[$idx].Name
        $svcLabel = $services[$idx].Label

        while ($true) {
            Write-Banner
            Write-Section "$svcLabel - $svcName"
            Write-MenuItem '1' 'Install/create service unit file'
            Write-MenuItem '2' 'start'
            Write-MenuItem '3' 'stop'
            Write-MenuItem '4' 'restart'
            Write-MenuItem '5' 'status'
            Write-MenuItem '6' 'view logs'
            Write-MenuItem '0' 'Back'
            Write-Host ''

            $act = (Read-Host '  Action').Trim()
            switch ($act) {
                '0' { break }
                '1' { Invoke-SafeAction -Action { Install-SystemdService -ServiceName $svcName } }
                '2' { Invoke-SafeAction -Action { Start-SystemdServices -ServiceNames @($svcName) } }
                '3' { Invoke-SafeAction -Action { Stop-SystemdServices -ServiceNames @($svcName) } }
                '4' { Invoke-SafeAction -Action { Restart-SystemdServices -ServiceNames @($svcName) } }
                '5' { Invoke-SafeAction -Action { Get-SystemdStatus -ServiceNames @($svcName) } }
                '6' { Invoke-SafeAction -Action { Show-SystemdLogs -ServiceName $svcName } }
            }
        }
    }
}

Show-MainMenu

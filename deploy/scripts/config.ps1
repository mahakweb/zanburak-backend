# Zanburak Backend Deploy - Configuration
# Edit this file with your server details.

@{
    # -- SSH ------------------------------------------------------------------
    SshHost    = '87.107.12.60'
    SshPort    = 9011
    SshUser    = 'root'
    SshPass    = 'Milad@4970'
    SshKeyPath = ''   # Optional: SSH private key path (password auth skipped if set)
    SshHostKey = 'SHA256:7AnKcyOWmmrV4VnkAd9lcR7wjtbyD9hnW2Ksn0Mz3BI'

    # -- Server paths ---------------------------------------------------------
    RemoteDir     = '/var/www/zanburak-backend'
    RemoteZip     = '/tmp/zanburak-backend-deploy.zip'
    ZipName       = 'zanburak-backend-deploy.zip'
    PhpPath       = '/usr/bin/php'
    WebUser       = 'www-data'
    WebGroup      = 'www-data'

    # -- Meilisearch (systemd reads MEILISEARCH_KEY from server .env at runtime) -
    MeilisearchBin      = '/usr/local/bin/meilisearch'
    MeilisearchDataPath = '/var/lib/meilisearch'

    # -- systemd services -----------------------------------------------------
    # SystemPackage = $true  → install via apt (do not upload a custom unit)
    # Redis on the server is the Linux package `redis-server` (not the Windows portable).
    Services = @(
        @{ Name = 'redis-server';       Label = 'Redis Server'; SystemPackage = $true; Package = 'redis-server' }
        @{ Name = 'laravel-queue';      Label = 'Laravel Queue' }
        @{ Name = 'laravel-reverb';     Label = 'Laravel Reverb' }
        @{ Name = 'laravel-scheduler';  Label = 'Laravel Scheduler (schedule:work)' }
        @{ Name = 'meilisearch';        Label = 'Meilisearch' }
    )
}

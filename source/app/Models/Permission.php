<?php
namespace Pterodactyl\Models;
use Illuminate\Support\Collection;
class Permission extends Model
{
    public const RESOURCE_NAME = 'subuser_permission';
    public const ACTION_WEBSOCKET_CONNECT = 'websocket.connect';
    public const ACTION_CONTROL_CONSOLE = 'control.console';
    public const ACTION_CONTROL_START = 'control.start';
    public const ACTION_CONTROL_STOP = 'control.stop';
    public const ACTION_CONTROL_RESTART = 'control.restart';
    public const ACTION_DATABASE_READ = 'database.read';
    public const ACTION_DATABASE_CREATE = 'database.create';
    public const ACTION_DATABASE_UPDATE = 'database.update';
    public const ACTION_DATABASE_DELETE = 'database.delete';
    public const ACTION_DATABASE_VIEW_PASSWORD = 'database.view_password';
    public const ACTION_SCHEDULE_READ = 'schedule.read';
    public const ACTION_SCHEDULE_CREATE = 'schedule.create';
    public const ACTION_SCHEDULE_UPDATE = 'schedule.update';
    public const ACTION_SCHEDULE_DELETE = 'schedule.delete';
    public const ACTION_USER_READ = 'user.read';
    public const ACTION_USER_CREATE = 'user.create';
    public const ACTION_USER_UPDATE = 'user.update';
    public const ACTION_USER_DELETE = 'user.delete';
    public const ACTION_BACKUP_READ = 'backup.read';
    public const ACTION_BACKUP_CREATE = 'backup.create';
    public const ACTION_BACKUP_DELETE = 'backup.delete';
    public const ACTION_BACKUP_DOWNLOAD = 'backup.download';
    public const ACTION_BACKUP_RESTORE = 'backup.restore';
    public const ACTION_ALLOCATION_READ = 'allocation.read';
    public const ACTION_ALLOCATION_CREATE = 'allocation.create';
    public const ACTION_ALLOCATION_UPDATE = 'allocation.update';
    public const ACTION_ALLOCATION_DELETE = 'allocation.delete';
    public const ACTION_FILE_READ = 'file.read';
    public const ACTION_FILE_READ_CONTENT = 'file.read-content';
    public const ACTION_FILE_CREATE = 'file.create';
    public const ACTION_FILE_UPDATE = 'file.update';
    public const ACTION_FILE_DELETE = 'file.delete';
    public const ACTION_FILE_ARCHIVE = 'file.archive';
    public const ACTION_FILE_SFTP = 'file.sftp';
    public const ACTION_STARTUP_READ = 'startup.read';
    public const ACTION_STARTUP_UPDATE = 'startup.update';
    public const ACTION_STARTUP_DOCKER_IMAGE = 'startup.docker-image';
    public const ACTION_SETTINGS_RENAME = 'settings.rename';
    public const ACTION_SETTINGS_REINSTALL = 'settings.reinstall';
    public const ACTION_ACTIVITY_READ = 'activity.read';
    public $timestamps = false;
    protected $table = 'permissions';
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $casts = [
        'subuser_id' => 'integer',
    ];
    public static array $validationRules = [
        'subuser_id' => 'required|numeric|min:1',
        'permission' => 'required|string',
    ];
    protected static array $permissions = [
        'websocket' => [
            'description' => 'Allows the user to connect to the server websocket, giving them access to view console output and realtime server stats.',
            'keys' => [
                'connect' => 'Allows a user to connect to the websocket instance for a server to stream the console.',
            ],
        ],
        'control' => [
            'description' => 'Permissions that control a user\'s ability to control the power state of a server, or send commands.',
            'keys' => [
                'console' => 'Allows a user to send commands to the server instance via the console.',
                'start' => 'Allows a user to start the server if it is stopped.',
                'stop' => 'Allows a user to stop a server if it is running.',
                'restart' => 'Allows a user to perform a server restart. This allows them to start the server if it is offline, but not put the server in a completely stopped state.',
            ],
        ],
        'user' => [
            'description' => 'Permissions that allow a user to manage other subusers on a server. They will never be able to edit their own account, or assign permissions they do not have themselves.',
            'keys' => [
                'create' => 'Allows a user to create new subusers for the server.',
                'read' => 'Allows the user to view subusers and their permissions for the server.',
                'update' => 'Allows a user to modify other subusers.',
                'delete' => 'Allows a user to delete a subuser from the server.',
            ],
        ],
        'file' => [
            'description' => 'Permissions that control a user\'s ability to modify the filesystem for this server.',
            'keys' => [
                'create' => 'Allows a user to create additional files and folders via the Panel or direct upload.',
                'read' => 'Allows a user to view the contents of a directory, but not view the contents of or download files.',
                'read-content' => 'Allows a user to view the contents of a given file. This will also allow the user to download files.',
                'update' => 'Allows a user to update the contents of an existing file or directory.',
                'delete' => 'Allows a user to delete files or directories.',
                'archive' => 'Allows a user to archive the contents of a directory as well as decompress existing archives on the system.',
                'sftp' => 'Allows a user to connect to SFTP and manage server files using the other assigned file permissions.',
            ],
        ],
        'backup' => [
            'description' => 'Permissions that control a user\'s ability to generate and manage server backups.',
            'keys' => [
                'create' => 'Allows a user to create new backups for this server.',
                'read' => 'Allows a user to view all backups that exist for this server.',
                'delete' => 'Allows a user to remove backups from the system.',
                'download' => 'Allows a user to download a backup for the server. Danger: this allows a user to access all files for the server in the backup.',
                'restore' => 'Allows a user to restore a backup for the server. Danger: this allows the user to delete all of the server files in the process.',
            ],
        ],
        'allocation' => [
            'description' => 'Permissions that control a user\'s ability to modify the port allocations for this server.',
            'keys' => [
                'read' => 'Allows a user to view all allocations currently assigned to this server. Users with any level of access to this server can always view the primary allocation.',
                'create' => 'Allows a user to assign additional allocations to the server.',
                'update' => 'Allows a user to change the primary server allocation and attach notes to each allocation.',
                'delete' => 'Allows a user to delete an allocation from the server.',
            ],
        ],
        'startup' => [
            'description' => 'Permissions that control a user\'s ability to view this server\'s startup parameters.',
            'keys' => [
                'read' => 'Allows a user to view the startup variables for a server.',
                'update' => 'Allows a user to modify the startup variables for the server.',
                'docker-image' => 'Allows a user to modify the Docker image used when running the server.',
            ],
        ],
        'database' => [
            'description' => 'Permissions that control a user\'s access to the database management for this server.',
            'keys' => [
                'create' => 'Allows a user to create a new database for this server.',
                'read' => 'Allows a user to view the database associated with this server.',
                'update' => 'Allows a user to rotate the password on a database instance. If the user does not have the view_password permission they will not see the updated password.',
                'delete' => 'Allows a user to remove a database instance from this server.',
                'view_password' => 'Allows a user to view the password associated with a database instance for this server.',
            ],
        ],
        'schedule' => [
            'description' => 'Permissions that control a user\'s access to the schedule management for this server.',
            'keys' => [
                'create' => 'Allows a user to create new schedules for this server.', 
                'read' => 'Allows a user to view schedules and the tasks associated with them for this server.', 
                'update' => 'Allows a user to update schedules and schedule tasks for this server.', 
                'delete' => 'Allows a user to delete schedules for this server.', 
            ],
        ],
        'settings' => [
            'description' => 'Permissions that control a user\'s access to the settings for this server.',
            'keys' => [
                'rename' => 'Allows a user to rename this server and change the description of it.',
                'reinstall' => 'Allows a user to trigger a reinstall of this server.',
            ],
        ],
        'activity' => [
            'description' => 'Permissions that control a user\'s access to the server activity logs.',
            'keys' => [
                'read' => 'Allows a user to view the activity logs for the server.',
            ],
        ],
        'addon' => [
            'description' => 'Permissions that control access to various server addons and management tools.',
            'keys' => [
                'minecraft-configuration' => 'Allows a user to access Minecraft server configuration tools.',
                'minecraft-version-changer' => 'Allows a user to change the Minecraft server version.',
                'minecraft-plugin-installer' => 'Allows a user to install and manage Minecraft plugins.',
                'minecraft-mod-installer' => 'Allows a user to install and manage Minecraft mods.',
                'minecraft-modpack-installer' => 'Allows a user to install and manage Minecraft modpacks.',
                'minecraft-world-manager' => 'Allows a user to manage Minecraft worlds.',
                'minecraft-player-manager' => 'Allows a user to manage Minecraft players (whitelist, ops, bans).',
                'minecraft-bedrock-addon-installer' => 'Allows a user to install and manage Minecraft Bedrock addons.',
                'minecraft-bedrock-version-changer' => 'Allows a user to change the Minecraft Bedrock server version.',
                'minecraft-votifier-tester' => 'Allows a user to test Votifier connections for Minecraft servers.',
                'ark-mod-installer' => 'Allows a user to install and manage ARK: Survival Evolved mods.',
                'hytale-mod-installer' => 'Allows a user to install and manage Hytale mods.',
                'fastdl-manager' => 'Allows a user to manage FastDL files and configuration.',
                'server-type-changer' => 'Allows a user to change the server type (nest/egg).',
                'subdomain-manager' => 'Allows a user to manage subdomains for the server.',
                'staff-request' => 'Allows a user to access staff request functionality.',
                'server-importer' => 'Allows a user to import servers and configurations.',
                'server-splitter' => 'Allows a user to split server resources and configurations.',
                'template-installer' => 'Allows a user to install templates or create servers based on prebuilt templates.',
                'server-wiper' => 'Allows a user to wipe a server including files and configuration.',
                'arma-reforger-read' => 'Allows a user to view the Arma Reforger mod manager and installed mods.',
                'arma-reforger-install' => 'Allows a user to install mods through the Arma Reforger mod manager.',
                'arma-reforger-remove' => 'Allows a user to remove mods through the Arma Reforger mod manager.',
                'arma-reforger-create-collection' => 'Allows a user to create and share Arma Reforger mod collections for this server.',
                'arma-reforger-admin-tools-read' => 'Allows a user to view the Arma Reforger Admin Tools UI and settings.',
                'arma-reforger-admin-tools-edit' => 'Allows a user to edit Arma Reforger Admin Tools settings and manage bans/priority/etc.',
                'reverse-proxy' => 'Allows a user to manage Reverse Proxies.',
                'arma-reforger-write' => 'Allows a user to save Arma Reforger Mod Manager settings (webhooks etc).',
            ],
        ],
    ];
    public static function permissions(): Collection
    {
        return Collection::make(self::$permissions);
    }
}

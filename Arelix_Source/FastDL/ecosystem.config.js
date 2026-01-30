module.exports = {
  apps: [{
    name: 'fastdl-sync',
    script: './fastdl_sync.js',

    // Script arguments
    args: '--volumes-root /var/lib/pterodactyl/volumes --web-root /var/www/fastdl --interval 2 --concurrency 4',

    // PM2 options
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '2G',

    // Environment variables
    env: {
      NODE_ENV: 'production',
      VOLUMES_ROOT: '/var/lib/pterodactyl/volumes',
      FASTDL_WEB_ROOT: '/var/www/fastdl',

      // Logging configuration
      // Note: Console logging is ALWAYS enabled and captured by PM2
      // Set ENABLE_FILE_LOGGING to 'true' if you also want a separate log file
      LOG_LEVEL: 'debug',           // debug, info, warn, error
      ENABLE_FILE_LOGGING: 'true', // Set to 'true' to enable file logging
      LOG_FILE: './fastdl_sync.log'
    },

    // PM2 log files (these capture ALL console output)
    // Use: pm2 logs fastdl-sync
    error_file: './logs/pm2-error.log',
    out_file: './logs/pm2-out.log',
    log_file: './logs/pm2-combined.log',
    time: true,

    // Log rotation (optional - requires pm2-logrotate)
    merge_logs: true,
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z'
  }]
};
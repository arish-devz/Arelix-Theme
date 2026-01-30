'use strict';

const { isMainThread, parentPort, Worker, workerData } = require('worker_threads');
const fs = require('fs');
const fsp = fs.promises;
const path = require('path');
const crypto = require('crypto');
const { spawn } = require('child_process');

const META_FILE = '.fastdl_meta.json';

// Files/folders to exclude from sync (system files that shouldn't be downloaded by clients)
const EXCLUDE_PATTERNS = [
  '.git', '.svn', 'node_modules',           // Version control & dependencies
  '.log', '.db', '.sqlite', '.sql',         // Logs & databases
  'cfg', 'config', 'addons', 'lua',         // Server configs & scripts (not for download)
  'bin', 'platform',                        // Binaries
  META_FILE                                 // Our metadata file
];

// Game-specific configurations - UNIVERSAL: Syncs ALL files from game directories
const GAME_CONFIGS = {
  garrysmod: {
    folder: 'garrysmod',
    extensions: null,  // null = sync ALL file types
    dirs: null         // null = sync ALL directories (except excluded ones)
  },
  cstrike: {
    folder: 'cstrike',
    extensions: null,  // null = sync ALL file types
    dirs: null         // null = sync ALL directories (except excluded ones)
  },
  csgo: {
    folder: 'csgo',
    extensions: null,  // null = sync ALL file types
    dirs: null         // null = sync ALL directories (except excluded ones)
  },
  cs2: {
    folder: 'game/csgo',
    extensions: null,  // null = sync ALL file types
    dirs: null         // null = sync ALL directories (except excluded ones)
  }
};

// ============================================================================
// LOGGING SYSTEM
// ============================================================================
class Logger {
  constructor(name = 'main') {
    this.name = name;
    this.logFile = process.env.LOG_FILE || './fastdl_sync.log';
    this.enableFileLogging = process.env.ENABLE_FILE_LOGGING === 'true';
    this.logLevel = process.env.LOG_LEVEL || 'info';

    this.levels = {
      debug: 0,
      info: 1,
      warn: 2,
      error: 3
    };

    this.colors = {
      debug: '\x1b[36m',
      info: '\x1b[32m',
      warn: '\x1b[33m',
      error: '\x1b[31m',
      reset: '\x1b[0m'
    };
  }

  getTimestamp() {
    const now = new Date();
    return now.toISOString();
  }

  shouldLog(level) {
    return this.levels[level] >= this.levels[this.logLevel];
  }

  async writeToFile(message) {
    if (!this.enableFileLogging) return;

    try {
      await fsp.appendFile(this.logFile, message + '\n', 'utf8');
    } catch (err) {
      console.error('Failed to write to log file:', err);
    }
  }

  format(level, message, data = null) {
    const timestamp = this.getTimestamp();
    const prefix = `[${timestamp}] [${level.toUpperCase()}] [${this.name}]`;

    let msg = `${prefix} ${message}`;

    if (data) {
      if (typeof data === 'object') {
        msg += '\n' + JSON.stringify(data, null, 2);
      } else {
        msg += ` ${data}`;
      }
    }

    return msg;
  }

  log(level, message, data = null) {
    if (!this.shouldLog(level)) return;

    const formattedMsg = this.format(level, message, data);
    const coloredMsg = `${this.colors[level]}${formattedMsg}${this.colors.reset}`;

    if (level === 'error') {
      console.error(coloredMsg);
    } else {
      console.log(coloredMsg);
    }

    if (this.enableFileLogging) {
      this.writeToFile(formattedMsg);
    }
  }

  debug(message, data = null) {
    this.log('debug', message, data);
  }

  info(message, data = null) {
    this.log('info', message, data);
  }

  warn(message, data = null) {
    this.log('warn', message, data);
  }

  error(message, data = null) {
    this.log('error', message, data);
  }
}

// ============================================================================
// MAIN THREAD
// ============================================================================
if (isMainThread) {
  const logger = new Logger('main');
  const argv = require('process').argv.slice(2);
  const opts = parseArgs(argv);

  const VOLUMES_ROOT = opts.volumesRoot || process.env.VOLUMES_ROOT || '/var/lib/pterodactyl/volumes';
  const WEB_ROOT = opts.webRoot || process.env.FASTDL_WEB_ROOT || '/var/www/fastdl';
  const INTERVAL_MS = (Number(opts.interval) || 5) * 60 * 1000;
  const CONCURRENCY = Number(opts.concurrency) || 4;

  logger.info('FastDL Sync Service Starting', {
    volumesRoot: VOLUMES_ROOT,
    webRoot: WEB_ROOT,
    intervalMs: INTERVAL_MS,
    intervalMinutes: INTERVAL_MS / 60000,
    concurrency: CONCURRENCY,
    logLevel: logger.logLevel,
    fileLogging: logger.enableFileLogging,
    logFile: logger.enableFileLogging ? logger.logFile : 'disabled',
    consoleLogging: 'enabled (captured by PM2)'
  });

  logger.info('='.repeat(80));
  logger.info('Console logging is ENABLED - all logs will appear in PM2');
  logger.info('File logging is ' + (logger.enableFileLogging ? 'ENABLED' : 'DISABLED'));
  logger.info('Use "pm2 logs fastdl-sync" to view these logs');
  logger.info('='.repeat(80));

  const workers = new Map();
  const stats = {
    totalScans: 0,
    totalServers: 0,
    totalSyncs: 0,
    errors: 0,
    startTime: new Date()
  };

  // Graceful shutdown
  process.on('SIGINT', async () => {
    logger.info('Received SIGINT, shutting down gracefully...');
    await shutdown();
  });

  process.on('SIGTERM', async () => {
    logger.info('Received SIGTERM, shutting down gracefully...');
    await shutdown();
  });

  async function shutdown() {
    logger.info('Stopping all workers...');

    for (const [uuid, worker] of workers.entries()) {
      logger.debug(`Terminating worker for ${uuid}`);
      worker.postMessage({ type: 'stop' });
    }

    // Wait a bit for workers to stop gracefully
    await new Promise(resolve => setTimeout(resolve, 2000));

    const uptime = Math.floor((Date.now() - stats.startTime.getTime()) / 1000);
    logger.info('Shutdown complete', {
      uptime: `${uptime}s`,
      totalScans: stats.totalScans,
      totalServers: stats.totalServers,
      totalSyncs: stats.totalSyncs,
      errors: stats.errors
    });

    process.exit(0);
  }

  (async function mainLoop() {
    try {
      logger.info('Starting initial scan...');
      await scanAndSync();
    } catch (err) {
      logger.error('Initial scan failed', err);
      stats.errors++;
    }

    setInterval(async () => {
      try {
        await scanAndSync();
      } catch (err) {
        logger.error('Periodic scan failed', err);
        stats.errors++;
      }
    }, INTERVAL_MS);
  })();

  async function scanAndSync() {
    const scanStart = Date.now();
    stats.totalScans++;

    logger.debug(`Starting scan #${stats.totalScans}`);

    const entries = await listDirs(VOLUMES_ROOT);
    const found = new Set();
    let newServers = 0;
    let existingServers = 0;

    for (const name of entries) {
      const full = path.join(VOLUMES_ROOT, name);

      // Check for each game type
      for (const [gameType, config] of Object.entries(GAME_CONFIGS)) {
        const gameFolder = path.join(full, config.folder);

        if (await exists(gameFolder)) {
          const workerKey = `${name}:${gameType}`;
          found.add(workerKey);

          if (!workers.has(workerKey)) {
            newServers++;
            logger.info(`Discovered new ${gameType} server: ${name}`);

            const worker = new Worker(__filename, {
              workerData: {
                uuid: name,
                serverPath: full,
                webRoot: WEB_ROOT,
                concurrency: CONCURRENCY,
                gameType: gameType
              }
            });

            worker.on('message', (m) => {
              if (m && m.type === 'log') {
                const level = m.level || 'info';
                logger.log(level, `[${name}:${gameType}] ${m.msg}`, m.data);
              } else if (m && m.type === 'error') {
                logger.error(`[${name}:${gameType}] ${m.err}`);
                stats.errors++;
              } else if (m && m.type === 'sync_complete') {
                stats.totalSyncs++;
                logger.info(`[${name}:${gameType}] Sync completed`, m.stats);
              }
            });

            worker.on('exit', (code) => {
              if (code !== 0) {
                logger.warn(`Worker ${workerKey} exited with code ${code}`);
              } else {
                logger.debug(`Worker ${workerKey} exited normally`);
              }
              workers.delete(workerKey);
            });

            worker.on('error', (err) => {
              logger.error(`Worker ${workerKey} encountered error`, err);
              stats.errors++;
            });

            workers.set(workerKey, worker);
            worker.postMessage({ type: 'sync' });
          } else {
            existingServers++;
            const w = workers.get(workerKey);
            w.postMessage({ type: 'sync' });
            logger.debug(`Triggered sync for existing ${gameType} server: ${name}`);
          }
        }
      }
    }

    // Clean up workers for removed servers
    let removedServers = 0;
    for (const [uuid, worker] of workers.entries()) {
      if (!found.has(uuid)) {
        removedServers++;
        logger.warn(`Server ${uuid} no longer exists, stopping worker`);
        worker.postMessage({ type: 'stop' });
        workers.delete(uuid);
      }
    }

    stats.totalServers = found.size;
    const scanDuration = Date.now() - scanStart;

    logger.info(`Scan #${stats.totalScans} completed in ${scanDuration}ms`, {
      totalServers: found.size,
      newServers,
      existingServers,
      removedServers,
      activeWorkers: workers.size
    });
  }

  function parseArgs(argv) {
    const out = {};
    for (let i = 0; i < argv.length; i++) {
      const a = argv[i];
      if (a === '--volumes-root' && argv[i + 1]) { out.volumesRoot = argv[++i]; }
      else if (a === '--web-root' && argv[i + 1]) { out.webRoot = argv[++i]; }
      else if (a === '--interval' && argv[i + 1]) { out.interval = argv[++i]; }
      else if (a === '--concurrency' && argv[i + 1]) { out.concurrency = argv[++i]; }
    }
    return out;
  }

  async function listDirs(root) {
    try {
      const names = await fsp.readdir(root);
      const out = [];
      for (const n of names) {
        const p = path.join(root, n);
        if ((await fsp.stat(p)).isDirectory()) out.push(n);
      }
      return out;
    } catch (err) {
      logger.error('Failed to read volumes root', { root, error: err.message });
      return [];
    }
  }

  async function exists(p) {
    try {
      await fsp.access(p);
      return true;
    } catch (e) {
      return false;
    }
  }

} else {
  const { uuid, serverPath, webRoot, concurrency, gameType } = workerData;
  const logger = new Logger(`${uuid}:${gameType}`);
  const gameConfig = GAME_CONFIGS[gameType];
  const gameRoot = path.join(serverPath, gameConfig.folder);
  const targetRoot = path.join(webRoot, uuid, gameType);
  const limit = Math.max(1, concurrency || 4);
  let isSyncing = false;

  parentPort.on('message', (msg) => {
    if (!msg || !msg.type) return;

    if (msg.type === 'sync') {
      if (!isSyncing) {
        performSync().catch(err => {
          parentPort.postMessage({
            type: 'error',
            err: `Sync failed: ${String(err)}`
          });
        });
      } else {
        parentPort.postMessage({
          type: 'log',
          level: 'debug',
          msg: 'Sync already in progress, skipping'
        });
      }
    } else if (msg.type === 'stop') {
      parentPort.postMessage({
        type: 'log',
        level: 'info',
        msg: 'Stopping worker thread'
      });
      process.exit(0);
    }
  });

  async function performSync() {
    const syncStart = Date.now();
    isSyncing = true;

    const stats = {
      filesScanned: 0,
      filesCompressed: 0,
      filesSkipped: 0,
      filesRemoved: 0,
      errors: 0,
      bytesProcessed: 0
    };

    parentPort.postMessage({
      type: 'log',
      level: 'info',
      msg: 'Starting sync operation'
    });

    try {
      await fsp.mkdir(targetRoot, { recursive: true });

      const metaPath = path.join(targetRoot, META_FILE);
      let meta = {};

      try {
        meta = JSON.parse(await fsp.readFile(metaPath, 'utf8') || '{}');
        parentPort.postMessage({
          type: 'log',
          level: 'debug',
          msg: `Loaded metadata for ${Object.keys(meta).length} files`
        });
      } catch (e) {
        meta = {};
        parentPort.postMessage({
          type: 'log',
          level: 'debug',
          msg: 'No existing metadata found, starting fresh'
        });
      }

      const foundFiles = new Map();

      // Scan for files - Universal mode: scan all dirs if dirs is null
      const dirsToScan = gameConfig.dirs || ['']; // If null, scan from root (empty string = gameRoot)

      for (const dname of dirsToScan) {
        const sourceDir = dname ? path.join(gameRoot, dname) : gameRoot;

        if (await exists(sourceDir)) {
          parentPort.postMessage({
            type: 'log',
            level: 'debug',
            msg: `Scanning directory: ${dname || 'root'}`
          });

          for await (const file of walkFiles(sourceDir)) {
            const ext = path.extname(file).toLowerCase();
            const fileName = path.basename(file);
            const relativePath = path.relative(gameRoot, file);

            // Check if file should be excluded
            let shouldExclude = false;
            for (const pattern of EXCLUDE_PATTERNS) {
              if (relativePath.includes(pattern) || fileName.includes(pattern)) {
                shouldExclude = true;
                break;
              }
            }

            if (shouldExclude) continue;

            // Universal mode: if extensions is null, accept all files; otherwise check extension
            if (gameConfig.extensions !== null && !gameConfig.extensions.has(ext)) continue;

            const rel = relativePath.split(path.sep).join('/');
            const stat = await fsp.stat(file);
            foundFiles.set(rel, { abs: file, stat });
            stats.filesScanned++;
            stats.bytesProcessed += stat.size;
          }
        }
      }

      const dirCount = gameConfig.dirs ? gameConfig.dirs.length : 'all';
      parentPort.postMessage({
        type: 'log',
        level: 'info',
        msg: `Scanned ${stats.filesScanned} files across ${dirCount} directories`
      });
      // Remove deleted files
      for (const rel of Object.keys(meta)) {
        if (!foundFiles.has(rel)) {
          const destCompressed = path.join(targetRoot, rel + '.bz2');
          const destUncompressed = path.join(targetRoot, rel);

          try {
            await fsp.unlink(destCompressed);
            stats.filesRemoved++;
          } catch (e) { /* ignore */ }

          try {
            await fsp.unlink(destUncompressed);
          } catch (e) { /* ignore */ }

          parentPort.postMessage({
            type: 'log',
            level: 'debug',
            msg: `Removed deleted file: ${rel}`
          });

          delete meta[rel];
        }
      }

      // Build task list
      const tasks = [];
      for (const [rel, info] of foundFiles.entries()) {
        const prev = meta[rel];
        if (prev && prev.mtime === info.stat.mtimeMs && prev.size === info.stat.size) {
          stats.filesSkipped++;
          continue;
        }
        tasks.push({ rel, abs: info.abs, stat: info.stat });
      }

      if (tasks.length > 0) {
        parentPort.postMessage({
          type: 'log',
          level: 'info',
          msg: `Processing ${tasks.length} changed files (${stats.filesSkipped} skipped)`
        });
      } else {
        parentPort.postMessage({
          type: 'log',
          level: 'info',
          msg: `No files need updating (${stats.filesSkipped} unchanged)`
        });
      }

      // Process tasks
      await runWithConcurrency(tasks, limit, async (task) => {
        const src = task.abs;
        const rel = task.rel;
        const destCompressed = path.join(targetRoot, rel + '.bz2');
        const destUncompressed = path.join(targetRoot, rel);

        try {
          await fsp.mkdir(path.dirname(destCompressed), { recursive: true });

          const hash = await sha1File(src);
          const prev = meta[rel];

          // Check if both compressed and uncompressed files exist
          const compressedExists = await exists(destCompressed);
          const uncompressedExists = await exists(destUncompressed);

          if (prev && prev.hash === hash && compressedExists && uncompressedExists) {
            meta[rel] = { hash, mtime: task.stat.mtimeMs, size: task.stat.size };
            stats.filesSkipped++;
            parentPort.postMessage({
              type: 'log',
              level: 'debug',
              msg: `Hash unchanged: ${rel}`
            });
            return;
          }

          // Create compressed version
          const tmpCompressed = destCompressed + '.tmp';
          await compressBzip2(src, tmpCompressed);
          await fsp.rename(tmpCompressed, destCompressed);

          // Also copy uncompressed version for clients that don't support bz2
          await fsp.copyFile(src, destUncompressed);

          const compressedSize = (await fsp.stat(destCompressed)).size;
          const ratio = ((1 - compressedSize / task.stat.size) * 100).toFixed(1);

          stats.filesCompressed++;
          meta[rel] = { hash, mtime: task.stat.mtimeMs, size: task.stat.size };

          parentPort.postMessage({
            type: 'log',
            level: 'debug',
            msg: `Synced: ${rel} (compressed: ${ratio}% reduction)`
          });
        } catch (err) {
          stats.errors++;
          parentPort.postMessage({
            type: 'error',
            err: `Failed to sync ${rel}: ${err.message}`
          });

          try {
            await fsp.unlink(destCompressed + '.tmp');
          } catch (e) { }
        }
      });

      // Save metadata
      try {
        await fsp.writeFile(metaPath, JSON.stringify(meta, null, 2), 'utf8');
        parentPort.postMessage({
          type: 'log',
          level: 'debug',
          msg: 'Metadata saved successfully'
        });
      } catch (e) {
        parentPort.postMessage({
          type: 'error',
          err: `Failed to write metadata: ${e.message}`
        });
      }

      const syncDuration = Date.now() - syncStart;
      const mbProcessed = (stats.bytesProcessed / 1024 / 1024).toFixed(2);

      stats.durationMs = syncDuration;
      stats.mbProcessed = mbProcessed;

      parentPort.postMessage({
        type: 'sync_complete',
        stats
      });

    } catch (err) {
      parentPort.postMessage({
        type: 'error',
        err: `Sync operation failed: ${err.message}`
      });
    } finally {
      isSyncing = false;
    }
  }

  async function exists(p) {
    try {
      await fsp.access(p);
      return true;
    } catch (e) {
      return false;
    }
  }

  async function* walkFiles(dir) {
    const entries = await fsp.readdir(dir, { withFileTypes: true });
    for (const ent of entries) {
      const full = path.join(dir, ent.name);
      if (ent.isDirectory()) {
        yield* walkFiles(full);
      } else if (ent.isFile()) {
        yield full;
      }
    }
  }

  function sha1File(filePath) {
    return new Promise((resolve, reject) => {
      const hash = crypto.createHash('sha1');
      const rs = fs.createReadStream(filePath);
      rs.on('error', reject);
      rs.on('data', (c) => hash.update(c));
      rs.on('end', () => resolve(hash.digest('hex')));
    });
  }

  function runWithConcurrency(items, limit, fn) {
    return new Promise((resolve, reject) => {
      const results = [];
      let idx = 0, active = 0;

      function next() {
        if (idx >= items.length && active === 0) return resolve(results);

        while (active < limit && idx < items.length) {
          const i = idx++;
          active++;

          Promise.resolve(fn(items[i], i)).then((res) => {
            results[i] = res;
            active--;
            next();
          }).catch((err) => {
            reject(err);
          });
        }
      }

      next();
    });
  }

  async function compressBzip2(srcPath, destTmp) {
    return new Promise((resolve, reject) => {
      const rs = fs.createReadStream(srcPath);
      const ws = fs.createWriteStream(destTmp, { flags: 'w' });

      let child;
      try {
        child = spawn('bzip2', ['-c'], { stdio: ['pipe', 'pipe', 'inherit'] });
      } catch (err) {
        child = null;
      }

      if (child) {
        let handled = false;

        child.on('error', async (err) => {
          if (handled) return;
          handled = true;
          parentPort.postMessage({
            type: 'log',
            level: 'warn',
            msg: 'bzip2 CLI not available, using JS fallback'
          });

          try {
            await compressWithJs(srcPath, destTmp);
            resolve();
          } catch (e) {
            reject(e);
          }
        });

        child.stdout.pipe(ws);
        rs.pipe(child.stdin);

        child.on('close', (code) => {
          if (handled) return;
          handled = true;

          if (code === 0) {
            resolve();
          } else {
            parentPort.postMessage({
              type: 'log',
              level: 'warn',
              msg: `bzip2 failed with code ${code}, using JS fallback`
            });
            compressWithJs(srcPath, destTmp).then(resolve).catch(reject);
          }
        });

        ws.on('error', (e) => handled ? null : reject(e));
        rs.on('error', (e) => handled ? null : reject(e));
      } else {
        parentPort.postMessage({
          type: 'log',
          level: 'debug',
          msg: 'Using JS fallback compressor (compressjs)'
        });
        compressWithJs(srcPath, destTmp).then(resolve).catch(reject);
      }
    });
  }

  async function compressWithJs(srcPath, destTmp) {
    let Bzip2;
    try {
      const c = require('compressjs');
      Bzip2 = c.Bzip2 || c.bzip2 || c;

      if (!Bzip2 || (typeof Bzip2.compressFile !== 'function' && typeof Bzip2.compress !== 'function')) {
        throw new Error('compressjs does not expose a Bzip2 compression function');
      }
    } catch (e) {
      throw new Error('compressjs module not installed; run `npm install compressjs` to enable JS fallback');
    }

    const data = await fsp.readFile(srcPath);
    const inputArr = Array.prototype.slice.call(data);

    let compressedArr;
    if (typeof Bzip2.compressFile === 'function') {
      compressedArr = Bzip2.compressFile(inputArr);
    } else if (typeof Bzip2.compress === 'function') {
      compressedArr = Bzip2.compress(inputArr);
    } else {
      throw new Error('Unsupported compressjs API for Bzip2');
    }

    const buf = Buffer.from(compressedArr);
    await fsp.writeFile(destTmp, buf);
  }
}
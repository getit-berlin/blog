<?php
declare(strict_types=1);

/**
 * ftp-deploy.php — upload the migrated markdown to FTP and list what is there.
 *
 * Run from WSL:
 *   php ftp-deploy.php list
 *   php ftp-deploy.php upload --dry-run
 *   php ftp-deploy.php upload
 */

// --------------------------------------------------------------- credentials
// All sensitive configuration is loaded from .env file in the project root.
// See .env for the list of required environment variables.

function loadEnv(): void
{
    $envFile = __DIR__ . '/.env';
    if (!is_file($envFile)) {
        fwrite(STDERR, ".env file not found: " . $envFile . "\n");
        exit(1);
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        // Skip comments
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        // Only set if not already defined in the environment
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

loadEnv();

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    // Handle boolean strings
    if (is_bool($default) && is_string($value)) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    // Handle integer defaults
    if (is_int($default) && is_string($value)) {
        return (int) $value;
    }

    return $value;
}

// All configuration values — read from environment variables
function ftpHost(): string
{
    return env('FTP_HOST', '');
}

function ftpUser(): string
{
    return env('FTP_USER', '');
}

function ftpPort(): int
{
    return env('FTP_PORT', 21);
}

function ftpTls(): bool
{
    return env('FTP_TLS', true);
}

function ftpTlsVerifyHost(): bool
{
    return env('FTP_TLS_VERIFY_HOST', false);
}

function remoteDir(): string
{
    return env('FTP_REMOTE_DIR', '/content');
}

function localDir(): string
{
    $dir = env('FTP_LOCAL_DIR', 'content');
    return __DIR__ . '/' . $dir;
}

function fileGlob(): string
{
    return env('FTP_FILE_GLOB', '*.md');
}

function assetGlobs(): array
{
    $globs = env('FTP_ASSET_GLOBS', '*.png,*.jpg,*.jpeg,*.gif,*.webp');
    return array_map('trim', explode(',', (string) $globs));
}

// --------------------------------------------------------------------- setup

function ftpPassword(): string
{
    $pass = env('FTP_PASS', '');

    if ($pass === '') {
        fwrite(STDERR, "FTP_PASS not set in .env file.\n");
        exit(1);
    }

    return (string) $pass;
}

function baseUrl(): string
{
    return sprintf('ftp://%s:%d', ftpHost(), ftpPort());
}

/**
 * @return resource|CurlHandle
 */
function ftpHandle(string $url)
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_USERPWD        => ftpUser() . ':' . ftpPassword(),
        CURLOPT_USE_SSL        => ftpTls() ? CURLUSESSL_TRY : CURLUSESSL_NONE,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => ftpTlsVerifyHost() ? 2 : 0,
        CURLOPT_FTP_USE_EPSV   => true,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT        => 300,
    ]);

    return $ch;
}

// --------------------------------------------------------------------- local

/** @return array<string,string> relative path => absolute path */
function localFiles(bool $includeAssets = false): array
{
    $dir = localDir();
    if (!is_dir($dir)) {
        fwrite(STDERR, 'Source directory not found: ' . $dir . "\n");
        exit(1);
    }

    $files = [];
    $walk  = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );

    $globs = $includeAssets ? array_merge([fileGlob()], assetGlobs()) : [fileGlob()];

    foreach ($walk as $entry) {
        if (!$entry->isFile()) {
            continue;
        }

        $match = false;
        foreach ($globs as $glob) {
            if (fnmatch($glob, $entry->getFilename())) {
                $match = true;
                break;
            }
        }
        if (!$match) {
            continue;
        }
        $rel = str_replace('\\', '/', substr($entry->getPathname(), strlen($dir) + 1));
        $files[$rel] = $entry->getPathname();
    }

    ksort($files);

    return $files;
}

// -------------------------------------------------------------------- upload

function commandUpload(bool $dryRun, bool $all = false, ?string $remoteDir = null): int
{
    $remoteDir ??= remoteDir();
    $files = localFiles($all);

    if ($files === []) {
        fwrite(STDERR, 'No ' . fileGlob() . ' files under ' . localDir() . "\n");
        return 1;
    }

    printf(
        "%s %d file(s) to ftp://%s%s/\n\n",
        $dryRun ? 'Would upload' : 'Uploading',
        count($files),
        ftpHost(),
        rtrim($remoteDir, "/")
    );

    $done = 0;
    $failed = 0;
    $bytes = 0;

    foreach ($files as $rel => $abs) {
        $size   = (int) filesize($abs);
        $remote = rtrim($remoteDir, "/") . "/" . $rel;

        if ($dryRun) {
            printf("  [dry-run] %-58s %7d bytes\n", $rel, $size);
            $done++;
            $bytes += $size;
            continue;
        }

        $stream = fopen($abs, 'rb');
        if ($stream === false) {
            printf("  FAIL      %-58s cannot open local file\n", $rel);
            $failed++;
            continue;
        }

        $ch = ftpHandle(baseUrl() . $remote);
        curl_setopt_array($ch, [
            CURLOPT_UPLOAD                  => true,
            CURLOPT_INFILE                  => $stream,
            CURLOPT_INFILESIZE              => $size,
            CURLOPT_FTP_CREATE_MISSING_DIRS => CURLFTP_CREATE_DIR,
        ]);

        $ok    = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($stream);

        if ($ok === false) {
            printf("  FAIL      %-58s %s\n", $rel, $error);
            $failed++;
            continue;
        }

        printf("  ok        %-58s %7d bytes\n", $rel, $size);
        $done++;
        $bytes += $size;
    }

    printf("\n%d ok, %d failed, %s bytes\n", $done, $failed, number_format($bytes));

    return $failed === 0 ? 0 : 1;
}

// ---------------------------------------------------------------------- list

function commandList(string $dir = '', int $depth = 0): int
{
    $dir ??= remoteDir();
    if ($depth === 0) {
        printf("ftp://%s%s/\n", ftpHost(), rtrim($dir, '/'));
    }

    $ch = ftpHandle(baseUrl() . rtrim($dir, '/') . '/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $raw   = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        fwrite(STDERR, str_repeat('  ', $depth + 1) . 'FAIL ' . $dir . ': ' . $error . "\n");
        return 1;
    }

    $indent = str_repeat('  ', $depth + 1);

    foreach (preg_split('/\r?\n/', trim((string) $raw)) as $line) {
        if ($line === '') {
            continue;
        }

        // Unix-style LIST: perms links owner group size date... name
        $parts = preg_split('/\s+/', $line, 9);
        if ($parts === false || count($parts) < 9) {
            echo $indent . $line . "\n";
            continue;
        }

        $name  = $parts[8];
        $size  = $parts[4];
        $isDir = str_starts_with($line, 'd');

        if ($name === '.' || $name === '..') {
            continue;
        }

        if ($isDir) {
            echo $indent . $name . "/\n";
            commandList(rtrim($dir, '/') . '/' . $name, $depth + 1);
            continue;
        }

        printf("%s%-56s %10s\n", $indent, $name, $size);
    }

    return 0;
}

// ---------------------------------------------------------------------- main

$argv    = $_SERVER['argv'];
$command = $argv[1] ?? '';
$dryRun  = in_array('--dry-run', $argv, true);

switch ($command) {
    case 'upload':
        $to = null;
        foreach ($argv as $a) {
            if (str_starts_with($a, "--to=")) {
                $to = substr($a, 5);
            }
        }
        exit(commandUpload($dryRun, in_array("--all", $argv, true), $to));

    case 'list':
        $target = $argv[2] ?? remoteDir();
        exit(commandList(str_starts_with($target, '-') ? remoteDir() : $target));

    case 'put':
        // php ftp-deploy.php put <local-file> [remote-path]
        $local = $argv[2] ?? '';
        if ($local === '' || !is_file($local)) {
            fwrite(STDERR, "Usage: php " . basename(__FILE__) . " put <local-file> [remote-path]\n");
            exit(1);
        }
        $remote = $argv[3] ?? ('/' . basename($local));
        $size   = (int) filesize($local);
        $stream = fopen($local, 'rb');

        $ch = ftpHandle(baseUrl() . $remote);
        curl_setopt_array($ch, [
            CURLOPT_UPLOAD                  => true,
            CURLOPT_INFILE                  => $stream,
            CURLOPT_INFILESIZE              => $size,
            CURLOPT_FTP_CREATE_MISSING_DIRS => CURLFTP_CREATE_DIR,
        ]);
        $ok  = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($stream);

        if ($ok === false) {
            printf("FAIL %s -> %s : %s\n", $local, $remote, $err);
            exit(1);
        }
        printf("ok   %s -> %s (%d bytes)\n", $local, $remote, $size);
        exit(0);

    case 'rm':
        // php ftp-deploy.php rm <remote-path>
        $target = $argv[2] ?? '';
        if ($target === '') {
            fwrite(STDERR, "Usage: php " . basename(__FILE__) . " rm <remote-path>\n");
            exit(1);
        }
        $ch = ftpHandle(baseUrl() . '/');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_QUOTE          => ['DELE ' . ltrim($target, '/')],
            CURLOPT_NOBODY         => true,
        ]);
        curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        printf("%s %s%s\n", $err === '' ? 'deleted' : 'FAIL', $target, $err === '' ? '' : ' : ' . $err);
        exit($err === '' ? 0 : 1);

    case 'raw':
        // Dump the unparsed LIST response for one directory (debugging).
        $target = $argv[2] ?? remoteDir();
        $ch = ftpHandle(baseUrl() . rtrim($target, '/') . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        printf("dir=%s ftp_code=%s err=%s bytes=%d\n---\n", $target, $code, $err ?: '-', strlen((string) $raw));
        echo $raw;
        exit($raw === false ? 1 : 0);

    default:
        $me = basename(__FILE__);
        echo "Usage:\n";
        echo "  php {$me} list                 list the remote directory tree\n";
        echo "  php {$me} upload --dry-run     show what would be uploaded\n";
        echo "  php {$me} upload               upload " . fileGlob() . " from " . basename(localDir()) . "/\n";
        exit(1);
}

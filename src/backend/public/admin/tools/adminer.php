<?php

declare(strict_types=1);

use Adminer\Adminer;
use Dotenv\Dotenv;

$backendRoot = dirname(__DIR__, 3);

require_once $backendRoot . '/vendor/autoload.php';
require_once __DIR__ . '/../includes/auth.php';

$dotenv = Dotenv::createImmutable($backendRoot);
$dotenv->safeLoad();

require_super_admin();

$adminerConfig = buildAdminerConnectionConfig($backendRoot);

$_GET['driver'] = $adminerConfig['driver'];

if ($adminerConfig['driver'] === 'sqlite') {
    $_GET['sqlite'] = $adminerConfig['server'];
} else {
    $_GET['server'] = $adminerConfig['server'];
}

if ($adminerConfig['username'] !== '') {
    $_GET['username'] = $adminerConfig['username'];
}

if ($adminerConfig['database'] !== '') {
    $_GET['db'] = $adminerConfig['database'];
}

enforceConsoleSecurityHeaders();

function buildAdminerConnectionConfig(string $backendRoot): array
{
    $driver = strtolower((string) ($_ENV['DB_CONNECTION'] ?? 'sqlite'));

    if ($driver === 'sqlite') {
        $database = trim((string) ($_ENV['DB_DATABASE'] ?? 'storage/library.db'));
        $absolutePath = resolveDatabasePath($backendRoot, $database);

        ensureSqliteDatabaseExists($absolutePath);

        return [
            'driver' => 'sqlite',
            'server' => $absolutePath,
            'username' => '',
            'password' => '',
            'database' => '',
            'label' => basename($absolutePath),
        ];
    }

    $host = trim((string) ($_ENV['DB_HOST'] ?? '127.0.0.1'));
    $port = trim((string) ($_ENV['DB_PORT'] ?? '3306'));
    $database = trim((string) ($_ENV['DB_DATABASE'] ?? 'library'));
    $username = trim((string) ($_ENV['DB_USERNAME'] ?? 'root'));
    $password = (string) ($_ENV['DB_PASSWORD'] ?? '');

    return [
        'driver' => 'server',
        'server' => sprintf('%s:%s', $host, $port),
        'username' => $username,
        'password' => $password,
        'database' => $database,
        'label' => $database,
    ];
}

function resolveDatabasePath(string $backendRoot, string $relativePath): string
{
    $cleaned = ltrim($relativePath, '/');
    $candidate = $relativePath !== '' && $relativePath[0] === DIRECTORY_SEPARATOR
        ? $relativePath
        : rtrim($backendRoot, '/') . '/' . $cleaned;

    return realpath($candidate) ?: $candidate;
}

function ensureSqliteDatabaseExists(string $path): void
{
    if (is_file($path)) {
        return;
    }

    $directory = dirname($path);

    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }

    touch($path);
}

function enforceConsoleSecurityHeaders(): void
{
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store, no-cache, must-revalidate');
}

function adminer_object()
{
    global $adminerConfig;

    if (! class_exists('LMSAdminer', false)) {
        class LMSAdminer extends Adminer
        {
            public function __construct(private array $config)
            {
            }

            public function name()
            {
                return 'Library DB Console';
            }

            public function credentials()
            {
                return [
                    $this->config['server'],
                    $this->config['username'],
                    $this->config['password'],
                ];
            }

            public function database()
            {
                return $this->config['database'];
            }

            public function login($login, $password)
            {
                return true;
            }

            public function loginForm()
            {
                $label = $this->config['label'];
                $autoDb = $this->config['database'] ?: basename($this->config['server']);
                $payload = json_encode([
                    'driver' => $this->config['driver'],
                    'server' => $this->config['server'],
                    'username' => $this->config['username'],
                    'password' => $this->config['password'],
                    'database' => $autoDb,
                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                $payload = $payload !== false ? addslashes($payload) : '';
                $token = addslashes(\Adminer\get_token());
                $nonce = htmlspecialchars(\Adminer\get_nonce(), ENT_QUOTES, 'UTF-8');
                echo '<section class="lms-adminer-login-note"><strong>Authenticated</strong><p>Super administrators are auto-signed into Adminer for <code>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</code>. If the console does not appear automatically, reload the page.</p></section>';
                echo '<script nonce="' . $nonce . '">(function(){
const form=document.querySelector("#content form");
if(!form||form.dataset.autoLogin==="done"){return;}
form.dataset.autoLogin="done";
const config=JSON.parse(\'' . $payload . '\');
const authFields={driver:config.driver,server:config.server,username:config.username||"",password:config.password||"",db:config.database||""};
Object.entries(authFields).forEach(function(entry){var key=entry[0],value=entry[1];var input=document.createElement("input");input.type="hidden";input.name="auth["+key+"]";input.value=value;form.appendChild(input);});
var token=document.createElement("input");token.type="hidden";token.name="token";token.value=\'' . $token . '\';form.appendChild(token);
var permanent=document.createElement("input");permanent.type="hidden";permanent.name="auth[permanent]";permanent.value="1";form.appendChild(permanent);
form.submit();})();</script>';
            }

            public function head($context = null)
            {
                parent::head($context);

                $css = <<<'CSS'
:root {
    --lms-brand: #0062ff;
    --lms-brand-soft: #e7f0ff;
    --lms-border: #d0d7de;
    --lms-surface: #f8fafc;
}
body {
    font-family: 'Inter', 'Segoe UI', sans-serif;
    background: var(--lms-surface);
}
#menu, #content {
    border-radius: 12px;
    border: 1px solid var(--lms-border);
    background: #fff;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}
.logout, .jush-sql {
    color: var(--lms-brand);
}
.links a, .tabs a {
    border-radius: 999px;
    padding-inline: 14px;
    border-color: transparent;
}
.links a.active, .tabs a.active {
    background: var(--lms-brand);
    color: #fff;
}
.jush-sqlkey, .jush-sqlcomment {
    color: #475569;
}
.lms-adminer-login-note {
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 12px;
    background: var(--lms-brand-soft);
    border: 1px solid var(--lms-border);
}
.lms-adminer-login-note strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #0f172a;
}
CSS;

                echo '<style>' . $css . '</style>';
            }
        }
    }

    return new LMSAdminer($adminerConfig);
}

require __DIR__ . '/adminer-core.php';

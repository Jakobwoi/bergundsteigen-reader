<?php
require("config.php");
require("viewer.php");
require("fetcher.php");
createDB($db);
$db->query("USE bergundsteigen");
$stmt = $db->prepare("SELECT * FROM sessions WHERE sessionId = :sessionId");
if (isset($_COOKIE["sessionId"])) {
$stmt->execute(['sessionId' => $_COOKIE["sessionId"]]);
}
if (!$stmt->fetch()) {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="My Realm"');
        die('Unauthorized access.');
    } else {
        if ($_SERVER['PHP_AUTH_USER'] !== 'admin' || $_SERVER['PHP_AUTH_PW'] !== $adminpw) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="My Realm"');
            die('Unauthorized access.');
        } else {
            $sessionId = bin2hex(random_bytes(16));
            setcookie("sessionId", $sessionId);
            $stmt = $db->prepare("INSERT INTO sessions (sessionId) VALUES (:sessionId)");
            $stmt->execute(['sessionId' => $sessionId]);
        }
    }
}
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'create_db':
            createDB($db);
            break;
        case 'update_index':
            updateDB($db);
            break;
        case 'logout':
            $stmt = $db->prepare("DELETE FROM sessions WHERE sessionId = :sessionId");
            $stmt->execute(['sessionId' => $_COOKIE["sessionId"]]);
            setcookie("sessionId", "");
        case 'update_tags':
            updateTags($db);
            break;
    }
}
?>
<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Bergundsteigen Admin Panel</title>
    <link rel='stylesheet' href='main.css'>
</head>
<body>
    <h1>Bergundsteigen Admin Panel</h1>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=create_db'">Datenbank erstellen</button>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=update_index'">Artikelindex aktualisieren</button>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=update_tags'">Tags aktualisieren</button>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=logout'">Logout</button>
</body>
</html>
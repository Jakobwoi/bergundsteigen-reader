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
            break;
        case 'update_tags':
            updateTags($db);
            break;
        case 'update_issues':
            updateIssues($db);
            break;
        case 'update_author_articles':
            updateAuthorArticles($db);
            break;
        case 'update_all':
            updateDB($db);
            updateTags($db);
            updateIssues($db);
            updateAuthorArticles($db);
            break;
        case 'clear_db':
            clearDB($db);
            break;
        case 'clearsessions':
            clearsessions($db);
            break;
        
    }
}

function clearDB(PDO $db) {
    if ($db->query("SELECT DATABASE()")->fetchColumn() != "bergundsteigen") {
        $db->exec("USE bergundsteigen");
    }
    $db->exec("DELETE * FROM articles WHERE Headline");
    $db->exec("DELETE * FROM tags WHERE Tag");
    $db->exec("DELETE * FROM issues WHERE IssueNo");
    $db->exec("DELETE * FROM authors WHERE Author");
    $db->exec("DELETE * FROM sessions WHERE sessionId");
}
function clearsessions(PDO $db) {
    if ($db->query("SELECT DATABASE()")->fetchColumn() != "bergundsteigen") {
        $db->exec("USE bergundsteigen");
    }
    $db->exec("DELETE * FROM sessions WHERE sessionId");
}
?>
<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Bergundsteigen Admin Panel</title>
    <link rel='stylesheet' href='main.css'>
    <script>
        function confirmAction(action) {
            if (confirm("Are you sure you want to " + action + "?")) {
                window.location.href = 'admin.php?action=' + action;
            }
        }
    </script>
</head>
<body>
    <h1>Bergundsteigen Admin Panel</h1>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=create_db'">Datenbank erstellen</button>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=update_index'">Artikelindex aktualisieren</button>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=update_tags'">Tags aktualisieren</button>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=update_issues'">Hefte aktualisieren</button>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=update_author_articles'">Autoren-Artikel aktualisieren</button>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=update_all'">Alle aktualisieren</button>
    <button class="admin-btn" onclick="confirmAction('clear_db')">Datenbank leeren</button>
    <button class="admin-btn" onclick="confirmAction('clearsessions')">Alle Sitzungen löschen</button>
    <button class="admin-btn" onclick="window.location.href='admin.php?action=logout'">Logout</button>
</body>
</html>
<?php
require("viewer.php");
require("config.php");
?>
<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>BergundSteigen</title>
    <script src='main.js'></script>
    <link rel='stylesheet' href='style.css'>
</head>
<body>
    <div class="sidenav" id="articles">
        <?php
        $articleList = getArticleList($db);
        foreach ($articleList as $article) {
            echo "<a onclick='loadArticle(null, " . $article["id"] . ")'>" . $article["Headline"] . "</a>";
        }
        ?>
    </div>
    <div class="main" id="articleContent">
        <?php
        if (isset($_GET["hash"]) and $_GET["hash"] != "") {
            $article = getArticle($db, $_GET["hash"]);
            echo "<h1>" . $article["Headline"] . "</h1>";
            echo "<p>" . $article["Content"] . "</p>";
        }  elseif (isset($_GET["id"]) and $_GET["id"] != "") {
            $article = getArticle($db, id: $_GET["id"]);
            echo "<h1>" . $article["Headline"] . "</h1>";
            echo "<p>" . $article["Content"] . "</p>";
        } else {
            echo "<h1>BergundSteigen Reader</h1><p>Bitte wählen Sie einen Artikel aus der Liste auf der linken Seite.</p>";
        }
        ?>
    </div>
</body>
</html>
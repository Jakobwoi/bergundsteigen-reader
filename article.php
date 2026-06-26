<?php
require("viewer.php");
require("config.php");
if (isset($_GET["offline"])) {
    $offline = boolval($_GET["offline"]);
} else {
    $offline = false;
} 
if (isset($_GET["hash"])) {
    $article = getArticle($db, hash: $_GET["hash"], offline: $offline);
} elseif (isset($_GET["id"])) {
    $article = getArticle($db, id: $_GET["id"], offline: $offline);
} else {
    die("No article specified");
}
echo $article["Content"];
?>
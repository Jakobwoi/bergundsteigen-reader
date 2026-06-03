<?php
function getArticleList(PDO $db) {
    if ($db->query("SELECT DATABASE()")->fetchColumn() != "bergundsteigen") {
        $db->exec("USE bergundsteigen");
    }
    $entries = $db->query("SELECT Headline, Outline, Author, IssueNo, Tags, Date FROM articles ORDER BY Date DESC")->fetchAll(PDO::FETCH_ASSOC);
    for ($i = 0; $i < count($entries); $i++) {
        $entries[$i]["Tags"] = explode(",", $entries[$i]["Tags"]);
    }
    return $entries;
}
function getArticle(PDO $db, $headline) {
    if ($db->query("SELECT DATABASE()")->fetchColumn() != "bergundsteigen") {
        $db->exec("USE bergundsteigen");
    }
    $entry = $db->prepare("SELECT * FROM articles WHERE Headline = ?");
    $entry->execute([$headline]);
    $entry = $entry->fetch(PDO::FETCH_ASSOC);
    if ($entry) {
        $imgPath = $entry["Date"] . "_" . str_replace(" ", "_", $entry["Headline"]);
        $imgUrl = urlencode($imgPath);
        $entry["Tags"] = explode(",", $entry["Tags"]);
        $imgs = scanDir($imgPath);
        foreach ($imgs as $img) {
            if ($img == "." || $img == "..") { // unix current and parent dir
                continue;
            }
            $imgId = intval(str_replace("image-", "", pathinfo($img, PATHINFO_FILENAME)))+1;
            $ref = "image-".$imgId."-src";
            $entry["Content"] = str_replace($ref, $imgUrl."/".$img, $entry["Content"]);
        }
        return $entry;
    }
}
?>
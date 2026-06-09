<?php
function getArticleList(PDO $db, $searchKey = "", $Tag = "", $author = "", $issueNo = "",$onlyHeadlines = false) {
    if ($db->query("SELECT DATABASE()")->fetchColumn() != "bergundsteigen") {
        $db->exec("USE bergundsteigen");
    }
    if ($onlyHeadlines) {
        $query = "SELECT id, Headline, Hash, Outline, Author, IssueNo, Tags, Date FROM articles WHERE Headline LIKE ? AND Tags LIKE ? AND Author LIKE ? AND IssueNo LIKE ? ORDER BY Date DESC";
    } else {
        $query = "SELECT id, Headline, Hash, Outline, Author, IssueNo, Tags, Date FROM articles WHERE Content LIKE ? AND Tags LIKE ? AND Author LIKE ? AND IssueNo LIKE ? ORDER BY Date DESC";
    }
    $stmt = $db->prepare($query);
    $entries = $stmt->execute(["%{$searchKey}%", "%{$Tag}%", "%{$author}%", "%{$issueNo}%"]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    for ($i = 0; $i < count($entries); $i++) {
        $entries[$i]["Tags"] = explode(",", $entries[$i]["Tags"]);
    }
    return $entries;
}
function getArticle(PDO $db, $headline = null, $id = null, $hash = null, $offline = false) {
    if ($db->query("SELECT DATABASE()")->fetchColumn() != "bergundsteigen") {
        $db->exec("USE bergundsteigen");
    }
    $entry = $db->prepare("SELECT * FROM articles WHERE Headline = ? OR Id = ? OR Hash = ?");
    $entry->execute([$headline, $id, $hash]);
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
            $imgId = intval(str_replace("image-", "", pathinfo($img, PATHINFO_FILENAME)))+1;// bad code cause I wrote bad code before ;-)
            $ref = "image-".$imgId."-src";
            if ($offline) {
                $image = file_get_contents($imgPath."/".$img);
                $imgbase64 = base64_encode($image);
                $entry["Content"] = str_replace($ref, "data:image/jpeg;base64,".$imgbase64, $entry["Content"]);
            } else {
                $entry["Content"] = str_replace($ref, $imgUrl."/".$img, $entry["Content"]);
            }
        }
        $entry["Content"] = "<h1>".$entry["Headline"]."</h1>".$entry["Content"];
        return $entry;
    }
}
?>
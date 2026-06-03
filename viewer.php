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
?>
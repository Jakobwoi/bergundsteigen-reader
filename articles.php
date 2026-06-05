<?php
require("viewer.php");
$db = new PDO("mysql:host=localhost", "root", "root");
?>
<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Artikel</title>
    <link rel='stylesheet' href='style.css'>
    <script src='main.js'></script>
</head>
<body>
<table>
    <tr>
        <th onclick="sortTable(this.parentNode.parentNode, 0, true)">Bild</th>
        <th onclick="sortTable(this.parentNode.parentNode, 1, true)">Headline</th>
        <th onclick="sortTable(this.parentNode.parentNode, 2, true)">Outline</th>
        <th onclick="sortTable(this.parentNode.parentNode, 3, true)">Author</th>
        <th onclick="sortTable(this.parentNode.parentNode, 4, true)">IssueNo</th>
        <th onclick="sortTable(this.parentNode.parentNode, 5, true)">Tags</th>
        <th onclick="sortTable(this.parentNode.parentNode, 6, true)">Date</th>
    </tr>
    <?php
    $articleList = getArticleList($db);
    foreach ($articleList as $article) {
        $imgPath = $article["Date"] . "_" . str_replace(" ", "_", $article["Headline"]) . "/title-image.jpg";
        if (file_exists($imgPath)) {
            $pathParts = explode('/', $imgPath);
            $encodedParts = array_map('rawurlencode', $pathParts);
            $imgUrl = implode('/', $encodedParts);

            echo "<tr>";
            echo "<td><img src='" . $imgUrl . "' alt='Artikelbild' style='width:100px; height:auto;'></td>";
        } else {
            echo "<tr><td>kein Bild</td>";
        }
        echo "<td>" . $article["Headline"] . "</td>";
        echo "<td>" . $article["Outline"] . "</td>";
        echo "<td>" . $article["Author"] . "</td>";
        if ($article["IssueNo"] == -1) {
            echo "<td> nur Online </td>";
        }else {
            echo "<td>" . $article["IssueNo"] . "</td>";
        }
        echo "<td>" . implode(", ", $article["Tags"]) . "</td>";
        echo "<td>" . $article["Date"] . "</td>";
        echo "</tr>";
    }
    ?>
</table>
</body>
</html>
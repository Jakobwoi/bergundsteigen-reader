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
    <script>
        let sortDirections = [true, true, true, true, true, true, true]; // initial sort directions for each column
    </script>
</head>
<body>
<table>
    <tr>
        <th onclick="sortTable(this.parentNode.parentNode, 0, sortDirections[0])">Bild</th>
        <th onclick="sortTable(this.parentNode.parentNode, 1, sortDirections[1])">Headline</th>
        <th onclick="sortTable(this.parentNode.parentNode, 2, sortDirections[2])">Outline</th>
        <th onclick="sortTable(this.parentNode.parentNode, 3, sortDirections[3])">Author</th>
        <th onclick="sortTable(this.parentNode.parentNode, 4, sortDirections[4])">IssueNo</th>
        <th onclick="sortTable(this.parentNode.parentNode, 5, sortDirections[5])">Tags</th>
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
        echo "<td><a href='reader.php?id=" . $article["id"] . "'>" . $article["Headline"] . "</a></td>";
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
<table style="display: none;">
    <?php 
    $articleList = getArticleList($db);
    $i = 0;
    echo "<tr>\n";
    foreach ($articleList as $article) {
        if ($i >= 5) {
            echo "</tr>\n";
            echo "<tr>\n";
            $i = 0;
        }
        $imgPath = $article["Date"] . "_" . str_replace(" ", "_", $article["Headline"]) . "/title-image.jpg";
        if (file_exists($imgPath)) {
            $pathParts = explode('/', $imgPath);
            $encodedParts = array_map('rawurlencode', $pathParts);
            $imgUrl = implode('/', $encodedParts);
            } else {
            $imgUrl = "placeholder.jpg";
        }
        echo "<td class='article-cell'>
        <div class='article-content'>\n
        <div><img src='" . $imgUrl . "' alt='Artikelbild' style='width:90%; height:auto;'></div>\n
        <div><a href='reader.php?id=" . $article["id"] . "'>\n" . $article["Headline"] . "</a></div>\n
        </div></td>\n";
        $i++;
    } ?>
</table>

</body>
</html>
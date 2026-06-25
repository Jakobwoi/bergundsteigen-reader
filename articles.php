<?php
require("viewer.php");
$db = new PDO("mysql:host=localhost", "root", "root");
if (($_GET['search'] ?? false) || ($_GET['author'] ?? false) || ($_GET['issue-number'] ?? false) || ($_GET['date-range'] ?? false) || ($_GET['tags'] ?? false)) {
    $search = $_GET['search'];
    $onlyHeadlines = isset($_GET['only-headlines']) ?? false;
    $issueNumber = $_GET['issue-number'] ?? "";
    $dateRange = $_GET['date-range'] ?? "";
    $dateRange = explode(" to ", $dateRange);
    if (count($dateRange) == 2) {
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];
    } else {
        $startDate = $dateRange[0];
        $endDate = $dateRange[0];
    }
    $tags = $_GET['tags'] ?? "";
    $author = $_GET['author'] ?? "";

    $articleList = getArticleList($db, $search, $tags, $author, $issueNumber, $onlyHeadlines, $startDate, $endDate);
} else {
    $articleList = getArticleList($db);
}
?>
<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Artikel</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <link rel='stylesheet' href='style.css'>
    <script src='main.js'></script>
    <script>
        let sortDirections = [true, true, true, true, true, true, true]; // initial sort directions for each column
        if (document.cookie.split('; ').find(row => row.startsWith('layout='))?.split('=')[1] === 'list') {
            document.addEventListener("DOMContentLoaded", function() {
                switchLayout();
            });
        }
    </script>
</head>
<body>
<div class="header">
    <h1>Artikel</h1>
    <div id="search-container">
        <form id="search-form" method="GET" action="articles.php">
            <div>
            <input type="text" id="search-input" name="search" placeholder="Suche..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <input type="text" id="datePicker" name="date-range" placeholder="Datum auswählen" value="<?php
            if (isset($_GET['date-range'])) {
                echo htmlspecialchars($_GET['date-range']);
            } else {
                $oldestArticleDate = $db->query("SELECT MIN(Date) FROM articles")->fetchColumn();
                $latestArticleDate = $db->query("SELECT MAX(Date) FROM articles")->fetchColumn();
                echo htmlspecialchars($oldestArticleDate . " to " . $latestArticleDate);
            }?>">
            <script>
                flatpickr("#datePicker", {
                    mode: "range",
                    maxDate: "today"
                });
            </script>
            <select input="text" name="author" id="author-dropdown">
                <?php
                $allAuthors = $db->query("SELECT Name, ArticleCount FROM authors ORDER BY ArticleCount DESC")->fetchAll(PDO::FETCH_ASSOC);
                if (!$_GET['author'] || ($_GET['author'] ?? false) === 'all') {
                    echo "<option value='all' selected>Alle Autoren</option>";
                } else {
                    echo "<option value='all'>Alle Autoren</option>";
                }
                foreach ($allAuthors as $author) {
                    if ($author['Name'] === $_GET['author'] ?? false) {
                        echo "<option value='" . htmlspecialchars($author['Name']) . "' selected>" . htmlspecialchars($author['Name']) . " (" . $author['ArticleCount'] . ")</option>";
                    } else {
                        echo "<option value='" . htmlspecialchars($author['Name']) . "'>" . htmlspecialchars($author['Name']) ." (" . $author['ArticleCount'] . ")</option>";
                    }
                }
                ?>
            </select>
            <select input="text" name="issue-number" id="issue-number-dropdown">
                <option value="">Alle Hefte</option>
                <?php
                $allIssues = $db->query("SELECT IssueNo FROM issues")->fetchAll(PDO::FETCH_COLUMN);

                sort($allIssues);
                foreach ($allIssues as $issue) {
                    if ($issue === $_GET['issue-number'] ?? '' && $issue !== -1) {
                        echo "<option value='" . htmlspecialchars($issue) . "' selected>" . htmlspecialchars($issue) . "</option>";
                    } elseif ($issue === -1){
                        if ($issue === $_GET['issue-number'] ?? '') {
                            echo "<option value='" . htmlspecialchars($issue) . "' selected>nur Online</option>";
                        } else {
                            echo "<option value='" . htmlspecialchars($issue) . "'>nur Online</option>";
                        }
                    } else {
                        echo "<option value='" . htmlspecialchars($issue) . "'>" . htmlspecialchars($issue) . "</option>";
                    }
                }
                ?>
            </select>
            <select name="tags" id="tags-dropdown">
                <?php
                $allTags = $db->query("SELECT Tag FROM tags")->fetchAll(PDO::FETCH_COLUMN);

                sort($allTags);
                if (!$_GET['tags'] || ($_GET['tags'] ?? false) === 'all') {
                    echo "<option value='all' selected>Alle Tags</option>";
                } else {
                    echo "<option value='all'>Alle Tags</option>";
                }
                foreach ($allTags as $tag) {
                    if ($tag === $_GET['tags'] ?? '') {
                        echo "<option value='" . htmlspecialchars($tag) . "' selected>" . htmlspecialchars($tag) . "</option>";
                    } else {
                        echo "<option value='" . htmlspecialchars($tag) . "'>" . htmlspecialchars($tag) . "</option>";
                    }
                }
                ?>
            </select>
            <input type="submit" value="Suchen"> </div> <br>
            <div>
            <input type="checkbox" id="only-headlines" name="only-headlines" value="1" <?php echo isset($_GET['only-headlines']) ? 'checked' : ''; ?>>
            <label for="only-headlines">Nur Überschrift durchsuchen</label>
            </div>
        </form>
    </div>
</div>
<table id="article-list" style="display: none;">
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
<div id="article-grid">
    <?php
    $i = 0;
    foreach ($articleList as $article) {
        $imgPath = $article["Date"] . "_" . str_replace(" ", "_", $article["Headline"]) . "/title-image.jpg";
        if (file_exists($imgPath)) {
            $pathParts = explode('/', $imgPath);
            $encodedParts = array_map('rawurlencode', $pathParts);
            $imgUrl = implode('/', $encodedParts);
            } else {
            $imgUrl = "placeholder.jpg";
        }
        echo "<div class='article-grid-item' onclick='window.location.href=\"reader.php?id=" . $article["id"] . "\"'>\n
        <div class='article-image-div'><img src='" . $imgUrl . "' alt='Artikelbild' style='width:90%; height:auto;'></div>\n
        <div class='article-title'><a href='reader.php?id=" . $article["id"] . "'>\n" . $article["Headline"] . "</a></div>\n
        </div>\n";
        $i++;
    } ?>
</div>

<a href="#" id="floating-btn" onclick="switchLayout()">
  &#x25A4;
</a>

</body>
</html>
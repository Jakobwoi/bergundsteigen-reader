<?php
function fetchArchive($offset = 0, $type = "artikel", $year = "", $search = "", $order = "desc")
{
    $url = "https://www.bergundsteigen.com/wp-admin/admin-ajax.php";

    $headers = array(
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0",
    );
    $data = array(
        "action" => "filterArchiv",
        "offset" => $offset,
        "type" => $type,
        "year" => $year,
        "search" => $search,
        "order" => $order
    );

    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, $url);
    curl_setopt($request, CURLOPT_POST, true);
    curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($request, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($request);
    curl_close($request);

    $json = json_decode($result);
    $htmlDom = Dom\HTMLDocument::createFromString($json->data, LIBXML_NOERROR);
    $articles = array();

    foreach ($htmlDom->getElementsByTagName("article") as $articleHTML) {
        $article = array(
            "headline" => "",
            "url" => "",
            "outline" => "",
            "image" => "",
            "tags" => array(),
            "date" => "",
            "read_time" => "",
            "author" => ""
        );
        // fetch title and url of the article
        $title = $articleHTML->getElementsByClassName("clamp clamp-2")->item(0)->getElementsByTagName("a")->item(0);
        $link = $title->getAttribute("href");
        $titleString = $title->textContent;
        $article["headline"] = $titleString;
        $article["url"] = $link;

        // fetch outline of the article
        $outline = $articleHTML->getElementsByTagName("p")->item(0)->textContent;
        $article["outline"] = $outline;

        // fetch image of the article
        $img = $articleHTML->getElementsByTagName("img")->item(0);
        $largestSrc = getLargestSrcsetFromImgElement($img);
        $article["image"] = $largestSrc;

        // fetch tags of the article
        foreach ($articleHTML->getElementsByClassName("cat") as $cat) {
            $article["tags"][] = $cat->getElementsByTagName("span")->item(0)->textContent;
        }

        // fetch date and read time of the article
        $date_readtime = $articleHTML->getElementsByClassName("info list-info")->item(0)->getElementsByTagName("span")->item(0)->textContent;
        $date_readtime = explode("-", $date_readtime);

        $dateString = trim($date_readtime[0]);
        $article["date"] = parseDate($dateString);

        if (isset($date_readtime[1])) {
            $read_time = trim(explode("min", $date_readtime[1])[0]);
            $article["read_time"] = $read_time;
        }

        // fetch author of the article
        if (!$articleHTML->getElementsByClassName("info-item author")->item(0) || !$articleHTML->getElementsByClassName("info-item author")->item(0)->getElementsByTagName("a")->item(0)) {
            $authorName = null;
            $authorUrl = null;
        } else {
            $authorName = $articleHTML->getElementsByClassName("info-item author")->item(0)->getElementsByTagName("a")->item(0)->textContent;
            $authorUrl = $articleHTML->getElementsByClassName("info-item author")->item(0)->getElementsByTagName("a")->item(0)->getAttribute("href");
        }
        $author = array(
            "name" => trim($authorName, " \t\n\r\0\x0B\xC2\xA0"),
            "url" => $authorUrl
        );
        $article["author"] = $author;

        array_push($articles, $article);
    }
    $dataset = array(
        "articles" => $articles,
        "total" => $json->total, // number of matching articles
        "count" => $json->count // number of returned articles should always be 6
    );
    return $dataset;
}

function fetchArticle($url)
{

    $headers = array(
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0",
    );
    // highlight colors extracted from css of bergundsteigen and converted to hex
    $highlightColors = array(
        "green" => "#bbe0a766",
        "orange" => "#fca95266",
        "blue" => "#e2eef3",
        "purple" => "#8677d433"
    );

    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, $url);
    curl_setopt($request, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($request);
    curl_close($request);

    $htmlDom = Dom\HTMLDocument::createFromString($result, LIBXML_NOERROR);
    $mainContent = $htmlDom->getElementsByClassName("content")->item(0);
    $article = $mainContent->getElementsByClassName("editor")->item(0);
    $sidebar = $htmlDom->getElementsByClassName("sidebar")->item(0); // needed for Issue number
    if (!$sidebar) {
        // some articles are exclussively online
        // error_log("Sidebar not found for article: $url");
        $issueNumber = -1;
    }else {
        // get issue number
        $issueNumberString = $sidebar->getElementsByTagName("h3")->item(0)->textContent;
        preg_match("/Erschienen\sin\sder\sAusgabe\s#(\d+)/", $issueNumberString, $matches);
        $issueNumber = $matches[1];
    }
    $imgList = array();
    $imageid = 0;
    $ArticleStr = "";
    foreach ($article->childNodes as $node) {
        // just for intelliphense to not complain
        /** @var \Dom\Node|object{outerHTML: string} $node */
        if ($node->nodeName == "P") {
            // paragraphs and subheadings left as they are
            $ArticleStr .= $node->outerHTML . "\n";
        } elseif ($node->nodeName == "H2") {
            $ArticleStr .= $node->outerHTML . "\n";
        } elseif ($node->nodeName == "FIGURE") {
            // imag urls are extracted and replaced with placeholders
            $imgElement = $node->getElementsByTagName("img")->item(0);
            if (!$imgElement) {
                continue; // other figures(videos etc.) are skipped
            }
            $imgurl = getLargestSrcsetFromImgElement($imgElement);
            if (!$node->getElementsByTagName("figcaption")->item(0)) {
                $caption = "";
            } else {
                $caption = $node->getElementsByTagName("figcaption")->item(0)->textContent;
            }
            $img = array(
                "url" => $imgurl,
                "caption" => $caption,
                "id" => $imageid
            );
            $imageid++;
            $imgList[] = $img;
            $ArticleStr .= "<figure><img src=\"image-$imageid-src\" id=\"image-$imageid\"><figcaption>$caption</figcaption></figure><br>\n";
        } elseif ($node->nodeName == "BLOCKQUOTE") {

            $ArticleStr .= "<blockquote><p>" . $node->textContent . "</p></blockquote><br>\n";
        } elseif ($node->nodeName == "DIV") {
            // fetch divs mostly highlight boxes wiith a limited color set
            $nodeClasses = $node->getAttribute("class");
            $nodeClasses = explode(" ", $nodeClasses);
            foreach ($nodeClasses as $class) {
                if (str_starts_with($class, "is-style-highlight-box-")) {
                    $text = $node->getElementsByTagName("div")->item(0)->innerHTML;
                    $highlightColor = str_replace("is-style-highlight-box-", "", $class);
                    $ArticleStr .= "<div style=\"background-color: {$highlightColors[$highlightColor]}; padding: 10px; margin: 10px 0;\">$text</div>\n";
                }
            }
        }
    }
    $parsedArticle = array(
        "content" => $ArticleStr,
        "images" => $imgList,
        "issueNumber" => $issueNumber
    );

    return $parsedArticle;
}

function fetchAuthor($url, $name)
{
    $headers = array(
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0",
    );

    $request = curl_init();
    curl_setopt($request, CURLOPT_URL, $url);
    curl_setopt($request, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($request);
    curl_close($request);
    $htmlDom = Dom\HTMLDocument::createFromString($result, LIBXML_NOERROR);
    $authorInfo = $htmlDom->getElementsByClassName("author-cat")->item(0);
    if (!$authorInfo) { // some author pages are bugged or missing
        return array(
            "name" => $name,
            "bio" => "",
            "image" => null
        );
    }
    $authorName = $authorInfo->getElementsByClassName("info")->item(0)->getElementsByTagName("h1")->item(0)->textContent;
    $authorBio = $authorInfo->getElementsByClassName("info")->item(0)->getElementsByTagName("p")->item(0)->textContent;
    if (!$authorInfo->getElementsByTagName("figure")->item(0) || !$authorInfo->getElementsByTagName("figure")->item(0)->getElementsByTagName("img")->item(0)) {
        $imageData = null;
    } else {
        $image = getLargestSrcsetFromImgElement($authorInfo->getElementsByTagName("figure")->item(0)->getElementsByTagName("img")->item(0));
        $imageData = file_get_contents($image);
    }
    $author = array(
        "name" => trim($name, " \t\n\r\0\x0B\xC2\xA0"),
        "bio" => trim($authorBio, " \t\n\r\0\x0B\xC2\xA0"),
        "image" => $imageData
    );
    return $author;
}
function saveArticle(PDO $db, array $article)
{
    $articleStmt = $db->prepare("INSERT INTO articles (Headline, Outline, Content, Author, IssueNo, Tags, Date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $authorStmt = $db->prepare("INSERT INTO authors (Name, Bio, Image) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Name = Name");
    $articleContent = fetchArticle($article["url"]);
    $titlepath = $article["date"]->format("Y-m-d") . "_" . str_replace(" ", "_", $article["headline"]);

    //get title image
    $imgData = file_get_contents($article["image"]);
    $img_type = pathinfo($article["image"], PATHINFO_EXTENSION);
    $imgPath = "{$titlepath}/title-image.{$img_type}";
    if (!file_exists($titlepath)) {
        mkdir($titlepath);
    }
    if (!file_exists($imgPath)) {
        file_put_contents($imgPath, $imgData);
    }
    //get other images
    foreach ($articleContent["images"] as $img) {
        $imgData = file_get_contents($img["url"]);
        $img_type = pathinfo($img["url"], PATHINFO_EXTENSION);
        $imgPath = "{$titlepath}/image-{$img['id']}.{$img_type}";
        if (!file_exists($imgPath)) {
            file_put_contents($imgPath, $imgData);
        }
    }

    // fetch author info and save to db
    $localSearch = $db->query("SELECT Name, Bio FROM authors WHERE Name = " . $db->quote($article["author"]["name"]))->fetch(PDO::FETCH_ASSOC);
    if (!$localSearch){ // only fetch new authors
        $authorInfo = fetchAuthor($article["author"]["url"], $article["author"]["name"]);
        $authorStmt->execute([
            $authorInfo["name"],
            $authorInfo["bio"],
            $authorInfo["image"]
        ]);
    } else {
        $authorInfo = array(
            "name" => trim($localSearch["Name"], " \t\n\r\0\x0B\xC2\xA0"),
            "bio" => trim($localSearch["Bio"], " \t\n\r\0\x0B\xC2\xA0"),
            "image" => null
        );
    }
    

    $tags = implode(", ", $article["tags"]); // convert tags for storage
    $articleStmt->execute([
        $article["headline"],
        $article["outline"],
        $articleContent["content"],
        $authorInfo["name"],
        $articleContent["issueNumber"],
        $tags,
        $article["date"]->format("Y-m-d")
    ]);
}

function getLargestSrcsetFromImgElement(Dom\HTMLElement $img): ?string
{
    if (!$img) {
        return null;
    }

    $srcset = $img->getAttribute('srcset');
    $src = $img->getAttribute('src');

    if (empty($srcset)) {
        return $src ?: null;
    }

    // search for all url-width pairs
    preg_match_all('/(?:[^\s,]+)\s*(?:\d+[w])?/', $srcset, $matches);

    $largestUrl = $src;
    $maxWidth = 0;

    foreach ($matches[0] as $candidate) {
        $parts = preg_split('/\s+/', trim($candidate));
        $url = $parts[0];
        $descriptor = $parts[1] ?? '';

        // Extract and compare width markers
        if (str_ends_with($descriptor, 'w')) {
            $width = (int)$descriptor;
        } else {
            $width = 0;
        }

        if ($width > $maxWidth) {
            $maxWidth = $width;
            $largestUrl = $url;
        }
    }

    return $largestUrl;
}

function parseDate($dateString)
{
    // Date format "DD. MMM.(M) YYYY" months in German
    $months = array(
        "Jan" => 1,
        "Feb" => 2,
        "März" => 3,
        "Apr" => 4,
        "Mai" => 5,
        "Juni" => 6,
        "Juli" => 7,
        "Aug" => 8,
        "Sep" => 9,
        "Okt" => 10,
        "Nov" => 11,
        "Dez" => 12
    );
    $dateString = str_replace(".", "", $dateString);
    $dateParts = explode(" ", $dateString);

    $day = (int)$dateParts[0];
    $month = $months[$dateParts[1]];
    $year = (int)$dateParts[2];
    $parsedDate = DateTime::createFromFormat("Y-m-d", "{$year}-{$month}-{$day}");
    return $parsedDate;
}

function updateDB(PDO $db)
{
    if ($db->query("SELECT DATABASE()")->fetchColumn() != "bergundsteigen") {
        $db->exec("USE bergundsteigen");
    }
    $totalLocal = $db->query("SELECT COUNT(Headline) FROM articles")->fetchColumn();
    $totalOnline = fetchArchive()["total"];
    $offset = $totalLocal;
    if ($totalLocal < $totalOnline) {
        $newestLocalArticle = $db->query("SELECT Headline, Date FROM articles ORDER BY Date DESC LIMIT 1")->fetch();
        if (!$newestLocalArticle) {
            $newestLocalArticle = array(
                "Headline" => "",
                "date" => DateTime::createFromFormat("Y-m-d", "1970-01-01")
            );
        } else {
            $newestLocalArticle["date"] = DateTime::createFromFormat("Y-m-d", $newestLocalArticle["Date"]);
        }
        $onlineArticleSet = fetchArchive($offset, "artikel", "", "", "asc");
        $onlineArticle = $onlineArticleSet["articles"][0];
        while ($onlineArticle["date"] >= $newestLocalArticle["date"]) {
            foreach ($onlineArticleSet["articles"] as $article) {

                if ($article["date"] >= $newestLocalArticle["date"]) {
                    if ($article["headline"] == $newestLocalArticle["Headline"]) {
                        continue;
                    }
                    $onlineArticle = $article;
                    saveArticle($db, $onlineArticle);
                    sleep(5); // prevent DoS flagging
                } else {
                    continue;
                }
            }
            $totalLocal = $db->query("SELECT COUNT(Headline) FROM articles")->fetchColumn();
            $offset = $totalLocal;
            $onlineArticleSet = fetchArchive($offset, "artikel", "", "", "asc");
            sleep(5); // prevent DoS flagging
        }
    }
}

function createDB(PDO $conn)
{
    $conn->exec("CREATE DATABASE IF NOT EXISTS bergundsteigen");
    $conn->exec("USE bergundsteigen");

    $conn->exec("CREATE TABLE IF NOT EXISTS authors (
        Name VARCHAR(255) PRIMARY KEY,
        Bio TEXT(16384),
        Image MEDIUMBLOB
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS articles (
        id INT AUTO_INCREMENT PRIMARY KEY ,
        Headline VARCHAR(512) NOT NULL,
        Outline TEXT(16384),
        Content MEDIUMTEXT,
        Author VARCHAR(255),
        IssueNo SMALLINT,
        Tags VARCHAR(512),
        Date DATE,
        CONSTRAINT FOREIGN KEY (Author) REFERENCES authors(Name)
    )");
}

<?php
function fetchArchive($offset = 0, $type = "artikel", $year = "", $search = "", $order = "desc") {
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
                "title" => "",
                "link" => "",
                "description" => "",
                "image" => "",
                "tags" => array(),
                "date" => "",
                "read_time" => "",
                "author" => ""
        );
        // fetch title and link of the article
        $title = $articleHTML->getElementsByClassName("clamp clamp-2")->item(0)->getElementsByTagName("a")->item(0);
        $link = $title->getAttribute("href");
        $titleString = $title->textContent;
        $article["title"] = $titleString;
        $article["link"] = $link;

        // fetch description of the article
        $description = $articleHTML->getElementsByTagName("p")->item(0)->textContent;
        $article["description"] = $description;

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
        $author = $articleHTML->getElementsByClassName("info-item author")->item(0)->getElementsByTagName("a")->item(0)->textContent;
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

function fetchArticle($url) {
    
    $headers = array(
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0",
    );

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
    
    $imgList = array();
    $imageid = 0;
    $ArticleStr = "";
    foreach ($article->childNodes as $node) {
        // just for intelliphense to not complain
        /** @var \Dom\Node|object{outerHTML: string} $node */
        if ($node->nodeName == "P") {

            $ArticleStr .= $node->outerHTML."\n";
        } elseif ($node->nodeName == "H2") {

            $ArticleStr .= $node->outerHTML."\n";
        } elseif ($node->nodeName == "FIGURE") {

            $imgurl = getLargestSrcsetFromImgElement($node->getElementsByTagName("img")->item(0));
            $caption = $node->getElementsByTagName("figcaption")->item(0)->textContent;
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
    
            $nodeClasses = $node->getAttribute("class");
            $nodeClasses = explode(" ", $nodeClasses);
            $text = $node->getElementsByTagName("div")->item(0)->innerHTML;
            foreach ($nodeClasses as $class) {
                if (str_starts_with($class, "is-style-highlight-box-")) {
                    $highlightColor = str_replace("is-style-highlight-box-", "", $class);
                    $ArticleStr .= "<div style=\"background-color: {$highlightColors[$highlightColor]}; padding: 10px; margin: 10px 0;\">$text</div>\n";
                }
            }
        }
    }
    $parsedArticle = array(
        "content" => $ArticleStr,
        "images" => $imgList
    );

    return $parsedArticle;
}

function getLargestSrcsetFromImgElement(Dom\HTMLElement $img): ?string {
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

function parseDate($dateString) {
    // Date Format "DD. MMM.(M) YYYY"
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
    return "$year-$month-$day";
}

fetchArticle("https://www.bergundsteigen.com/artikel/bergfuehrerserie-kameradenrettung-vorsteigersturz/");

?>
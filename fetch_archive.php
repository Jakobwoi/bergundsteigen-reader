<?php
$url = "https://www.bergundsteigen.com/wp-admin/admin-ajax.php";
$headers = array(
    "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0",
);
$data = array(
    "action" => "filterArchiv",
    "offset" => "0",
    "type" => "",
    //"ausgabe[]" => "",
    "year" => "",
    "search" => "",
    "order" => "desc"
);

$request = curl_init();
curl_setopt($request, CURLOPT_URL, $url);
curl_setopt($request, CURLOPT_POST, true);
curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($request, CURLOPT_HTTPHEADER, $headers);
curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($request, CURLOPT_COOKIE, http_build_query($cookies, '', '; '));
$result = curl_exec($request);
curl_close($request);
$json = json_decode($result);
$htmlDom = Dom\HTMLDocument::createFromString($json->data, LIBXML_NOERROR);
//echo $json->total;

foreach ($htmlDom->getElementsByTagName("article") as $article) {
    // fetch title and link of the article
    $title = $article->getElementsByClassName("clamp clamp-2")->item(0)->getElementsByTagName("a")->item(0);
    $link = $title->getAttribute("href");
    $titleString = $title->textContent;
    echo "<strong>";
    echo ($title->outerHTML);
    echo "</strong>";
    echo "<br>\n";
    // fetch description of the article
    $description = $article->getElementsByTagName("p")->item(0)->textContent;
    echo "<i>".$description."</i><br>\n";
    // fetch image of the article
    $img = $article->getElementsByTagName("img")->item(0);
    $largestSrc = getLargestSrcsetFromImgElement($img);
    echo "<img style='max-width: 25%; height: auto;' src='".$largestSrc."' alt='Image'>";
    echo "<br>\n";
    // fetch tags of the article
    foreach ($article->getElementsByClassName("cat") as $cat) {
        echo ($cat->getElementsByTagName("span")->item(0)->textContent);
        echo ", ";
    }
    echo "<br>\n";
    // fetch date and read time of the article
    $date_readtime = $article->getElementsByClassName("info list-info")->item(0)->getElementsByTagName("span")->item(0)->textContent;
    $date_readtime = explode("-", $date_readtime);
    echo $date_readtime[0]."<br>\n";
    if (isset($date_readtime[1])) {
    echo $date_readtime[1]."<br>\n";
    }
    // fetch author of the article
    $author = $article->getElementsByClassName("info-item author")->item(0)->getElementsByTagName("a")->item(0)->textContent;
    echo $author;
    echo "<br><br>\n";
}
// var_dump($htmlDom->getElementsByTagName("article")->item(0)->getElementsByClassName("clamp clamp-2")->item(0)->getElementsByTagName("a")->item(0)->textContent);

function getLargestSrcsetFromImgElement(Dom\HTMLElement $img): ?string 
{
    if (!$img) {
        return null;
    }

    // Access attributes standardly via getAttribute()
    $srcset = $img->getAttribute('srcset');
    $src = $img->getAttribute('src');

    if (empty($srcset)) {
        return $src ?: null;
    }

    // Match image candidate strings
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

?>
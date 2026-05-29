<?php
function fetchArticle($url)
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
    $mainContent = $htmlDom->getElementsByClassName("content")->item(0);
    $article = $mainContent->getElementsByClassName("editor")->item(0);
    echo $article->childNodes->length;
    echo "<br><br>\n";
    foreach ($article->childNodes as $node) {
        /** @var \Dom\Node|object{outerHTML: string} $node */ // just for intelliphense to not complain
        if ($node->nodeName == "P") {
            echo $node->outerHTML."\n";
        } elseif ($node->nodeName == "H2") {
            echo $node->outerHTML."\n";
        } elseif ($node->nodeName == "FIGURE") {
            echo "figure<br>\n";
        } elseif ($node->nodeName == "BLOCKQUOTE")
        {
            echo "blockquote<br>\n";
        } elseif ($node->nodeName == "DIV") {
            echo "div<br>\n";
        } elseif ($node->nodeName == "#text"){
            if (trim($node->textContent) != "") {
                echo "text: " . $node->textContent . "<br>\n";
            }
        } else {
            echo "other: " . $node->nodeName . "<br>\n";
            echo $node->textContent . "<br>\n";
        }
    }
}
fetchArticle("https://www.bergundsteigen.com/artikel/bergfuehrerserie-kameradenrettung-vorsteigersturz/");
?>
<?php
$url = "https://www.bergundsteigen.com/wp-admin/admin-ajax.php";
$headers = array(
    "User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0",
    "Accept: application/json, text/javascript, */*; q=0.01",
    "Accept-Language: en-US,en;q=0.9",
    "Referer: https://www.bergundsteigen.com/archiv/",
    "X-Requested-With: XMLHTTPRequest",
);
$cookies = array(
    "OptanonConsent" => "isGpcEnabled=0&datestamp=Thu+May+07+2026+08:45:08+GMT+0200+(Mitteleuropäische+Sommerzeit)&version=6.25.0&isIABGlobal=false&hosts=&landingPath=NotLandingPage&groups=C0001:1,C0002:0,C0004:0&AwaitingReconsent=false&geolocation=AT;7",
    "OptanonAlertBoxClosed" => "2026-05-05T21:17:48.840Z",
    "wp-wpml_current_language" => "de"
);
$data = array(
    "action" => "filterArchiv",
    "offset" => "0",
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
$clean_result = $result;
curl_close($request);
$json = json_decode($clean_result);
echo $json->data;
?>
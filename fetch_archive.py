import requests, json

url = "https://www.bergundsteigen.com/wp-admin/admin-ajax.php"

headers = {
    "User-Agent": "Mozilla/5.0 (X11; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0",
    "Accept": "application/json, text/javascript, */*; q=0.01",
    "Accept-Language": "en-US,en;q=0.9",
    "Referer": "https://www.bergundsteigen.com/archiv/",
    "X-Requested-With": "XMLHttpRequest",
}

cookies = {
    "OptanonConsent": "isGpcEnabled=0&datestamp=Thu+May+07+2026+08:45:08+GMT+0200+(Mitteleuropäische+Sommerzeit)&version=6.25.0&isIABGlobal=false&hosts=&landingPath=NotLandingPage&groups=C0001:1,C0002:0,C0004:0&AwaitingReconsent=false&geolocation=AT;7",
    "OptanonAlertBoxClosed": "2026-05-05T21:17:48.840Z",
    "wp-wpml_current_language": "de"
}

data = {
    "action": "filterArchiv",
    "offset": "0",
    "year": "",
    "search": "",
    "order": "desc"
}

def fetch_data():
    print(f"Sending POST request to {url}...")
    response = requests.post(url, headers=headers, cookies=cookies, data=data)
    
    print(f"Status Code: {response.status_code}")
    try:
        json_data = response.json()
        print("Response received successfully!")
        with open("response", "w") as f:
            f.write(json_data['data'])
        print(json_data['data'][:200])
        print('"total":' + str(json_data['total']))
        print('"count":' + str(json_data['count']))
    except requests.exceptions.JSONDecodeError:
        print("Response (Text):")
        print(response.text )

if __name__ == "__main__":
    fetch_data()

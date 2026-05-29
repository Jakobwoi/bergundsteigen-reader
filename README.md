# Bergundsteigen Reader
This tool scrapes all articles from the website of the mountaineering magazine Bergundsteigen. It also implements fast search and filters. I created this tool, because they have a very weird scrolling on their website.
## DB-Scheme
### article table
| Headline | outline | Content | author | tags     | issue | date  |
|----------|---------|---------|--------|----------|-------|-------|
| string   | string  | string  | string | string[] | int   | date  |

### author table
| author     | description | image | 
|------------|-------------|-------|
| key string | string      | data  |
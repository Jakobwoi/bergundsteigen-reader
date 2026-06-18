# Bergundsteigen Reader
This tool scrapes all articles from the website of the mountaineering magazine Bergundsteigen. It also implements fast search and filters. I created this tool, because they have a very weird scrolling on their website, which becomes very odd when searching for something using the browsers search tool.
## Setup
Copy all files to the directory where you want the reader and change the admin and user pw as well as the DB credentials in ```config.php```. Create a daily cronjob for ```update.php```.
## AI Declaration
AI inline suggestions were used in this Project, also AI was used for researching and understanding concepts.
## Technical stuff
### Basic Structure
```text
repo/
├── README.md               # readme
├── admin.php               # admin panel
├── article.php             # endpoint for retrieving articles
├── articles.php            # tabular view of Articles in DB
├── config.php              # config options
├── fetcher.php             # contains Functions for fetching and parsing upstream articles from bergundsteigen
├── main.js                 # js function defs
├── reader.php              # contains reader for articles
├── style.css               # css
├── update.php              # php script for cron job
├── viewer.php              # contains PHP functions for fetching articles from local DB
```
### image locations
The images src fields are referenced in the saved HTML with img-n-src e.g img-1-src. They're saved in Folders named after the articles headline, but with Spaces replaced with underscores and illegal(unix) Characters(\0 and /) removed.
### DB-Scheme
#### article table
| id                              | Hash  | Headline     | Outline | Content      | Author       | Tags         | IssueNo          | Date |
|---------------------------------|-------|--------------|---------|--------------|--------------|--------------|------------------|------|
| INT                             |BIN(64)| VARCHAR(512) | TEXT    | MEDIUMTEXT   | VARCHAR(255) | VARCHAR(512) | SMALLINT         | DATE |
| AUTO_INCREMENT <br> PRIMARY KEY |       |              |         |              | FOREIGN KEY  |              |                  |      |
|                                 |       |              |         | saved as HTML|              | saved as csv | -1 if online only|      |
#### authors table
| author       | bio  | image           |
|--------------|------|-----------------|
| VARCHAR(255) | TEXT | MEDIUMBLOB      |
| PRIMARY KEY  |      |                 |
|              |      | just image data |
### session ids table
just contains active sessions
| sessionId   | admin |
|-------------|-------|
| VARCHAR(32) | BOOL  |
| PRIMARY KEY |       |
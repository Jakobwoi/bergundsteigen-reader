# Bergundsteigen Reader
This tool scrapes all articles from the website of the mountaineering magazine Bergundsteigen. It also implements fast search and filters. I created this tool, because they have a very weird scrolling on their website.
## DB-Scheme
### article table
| id                              | Headline    | Outline | Content    | Author       | Tags         | IssueNo  | Date |
|---------------------------------|-------------|---------|------------|--------------|--------------|----------|------|
| INT                             | VARCHAR(512)| TEXT    | MEDIUMTEXT | VARCHAR(255) | VARCHAR(512) | SMALLINT | DATE |
| AUTO_INCREMENT <br> PRIMARY KEY |             |         |            | FOREIGN KEY  |              |          |      |

### authors table
| author       | description | image      | 
|--------------|-------------|------------|
| VARCHAR(255) | TEXT        | MEDIUMBLOB |
| PRIMARY KEY  |             |            |
# Top Albums API

This API allows you to view [iTunes Top 100 albums data](https://itunes.apple.com/us/rss/topalbums/limit=100/json "iTunes Top 100 albums data") and store this data in a database. You are also able to add your own albums to the list, update indvidual album records, delete individual album records, and delete all records simultaneously. Album records can also be populated to the database and data refreshed using the `/refresh` endpoint. 

### The following ***album parameters*** are included:

**name** - The name of the album

**artist** - The album artist name

**image** - A link to the album cover art image

**price** - The price of the album in USD

**rights** - Copyright attribution 

**link** - A link to the album on Apple music

**category** - The album music category

**releaseDate** - The original album release date

**album_id** - a unique identified associated with the album

## ## The following endpoints are available publicly:

#### Search and return top album data
**GET** `/fetch`

URL slugs after `/fetch/` will be interpreted as columns to be selected. 

URL parameter keys and values will be interpreted as sql `WHERE` clause.

### **Examples:**

**Show all records:**

**GET**`/fetch`

**Select `name`, `artist`, and `image` for all records:**

**GET**`/fetch/name/artist/image`

**Select `name`, `artist`, `image`, & `releaseDate` where artist = "Nirvana":**

 **GET**`/fetch/name/artist/image?artist=nirvana`

## ## The following endpoints require an API key:

An API key can be generated here: [https://weecare-top-albums.herokuapp.com/](https://weecare-top-albums.herokuapp.com/ "https://weecare-top-albums.herokuapp.com/")

API key must be included in your request as a URL parameter`api_key`

ex. `/foo?api_key=[your_api_key]`

## Add a new album record
**POST** `/add`

Content-Type: `application/x-www-form-urlencoded`

**Required parameters:** `name`, `artist`

Parameter other than valid  ***album parameters*** will be ignored.


## Update an album record
**POST** `/[album_id]`

*album_id* - a valid album identifier

Content-Type: `application/x-www-form-urlencoded`

Parameter other than valid  ***album parameters*** will be ignored.


## Delete a specific album record
**DELETE** `/[album_id]`

*album_id* - a valid album identifier

Ex: `/[album_id]?api_key=[your_api_key]`


## Delete all album records
**DELETE** `/`

Ex: `/?api_key=[your_api_key]`


## Refresh album data from iTunes Top 100 endpoint

This endpoint will populate the database using data from [iTunes Top 100 albums](https://itunes.apple.com/us/rss/topalbums/limit=100/json "iTunes Top 100 albums"). If a particular record already exists, the  ***album parameters*** for that record will be updated to the latest version.

**GET** `/refresh`

Ex: `/refresh?api_key=[your_api_key]`
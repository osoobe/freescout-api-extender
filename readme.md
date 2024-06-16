# API Extender module for FreeScout
This module adds the following API access for the [FreeScout](https://freescout.net).
- knowledge base (module)
- report module

## Requirements
- [FreeScout](https://freescout.net) installed 
- FreeScout [Knowledge base module](https://freescout.net/module/knowledge-base/)
- FreeScout [Reports module](https://freescout.net/module/reports)

## Installation
### Method 1: Clone the repo
1. ssh into your server, and navigate to the freescout modules folder, e.g.
```bash
cd /freescout/Modules/
```
2. Clone the repo
     ```bash
     git clone https://github.com/osoobe/freescout-api-extender ApiExtender
     ```
     If you cloned the repo locally, and need it on the server, then zip it first and follow the step 2 of Method 2.
2. Activate the module via the Modules page in FreeScout.


### Moethod 2: Download Zip file
1. Download the latest module zip file via the releases card on the right.
2. Transfer the zip file to the server in the Modules folder of FreeScout.
3. Unpack the zip file.
4. Remove the zip file.
5. Activate the module via the Modules page in FreeScout.

## Update instructions

### Method 1: Pull changes via git
If you had originally downloaded the zip file, please follow Method 2, otherwise, follow the steps below.
1. ssh into your server, and navigate to the freescout modules folder, e.g.
```bash
cd /freescout/Modules/ApiExtender
```
2. Pull the updates
```bash
git pull origin main
```

### Moethod 2: Download Zip file
1. Download the latest module zip file via the releases card on the right.
2. Transfer the zip file to the server in the Modules folder of FreeScout.
3. Remove the folder ApiExtender
4. Unpack the zip file.
5. Remove the zip file.

## Contributing

Feel free to add your own features by sending a pull request.

## Get knowledge base categories in a mailbox

```
curl "https://example.com/api/knowledgebase/1/categories?locale=en" \
-H 'Accept: application/json' \
-H 'Content-Type: application/json; charset=utf-8' \
-d $'{}'
```

## Get articles in a category

```
curl "https://example.com/api/knowledgebase/1/categories/1?locale=en" \
     -H 'Accept: application/json' \
     -H 'Content-Type: application/json; charset=utf-8' \
     -d $'{}'
```


## Get article

```
curl "https://example.com/api/knowledgebase/138/1/how-do-i-reset-my-password" \
     -H 'Accept: application/json' \
     -H 'Content-Type: application/json; charset=utf-8' \
     -d $'{}'
```



## Get report

```
curl --location 'https://example.com/api/report/productivity' \
--header 'X-FreeScout-API-Key: <API-KEY>' \
--header 'Content-Type: application/json' \
--data '{
  "action": "report",
  "report_name": "productivity",
  "filters": {
    "type": "",
    "mailbox": "",
    "tag": "",
    "from": "2024-06-09",
    "to": "2024-06-16"
  },
  "chart": {
    "group_by": "d",
    "type": "customers_helped"
  }
}'
```


## LICENSE

MIT
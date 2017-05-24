# xero-php-cli
A command line tool for Xero with PHP

## Setup
Use config/_config.sample.php as a template to create a _config.php
The keys you added while creating the Xero application, should be added in /config/certs/

## Use
From the root of the folder, run `php xero.php --help` to see use instructions
```
 Help for HNG Xero API script 
 
-f path to csv file to upload. Required. 
 
-t upload type: bill or invoice. Required 
 
-s status to set for the uploaded invoices: draft, submitted or authorised.```

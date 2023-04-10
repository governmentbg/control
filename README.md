# ELECTIONS VIDEO CONTROL

A system designed to assist with the process of setting up, testing and streaming devices for the 2023 parliament elections in Bulgaria.

This software includes modules for devices, sections and infrastructure management, as well as monitoring capabilites for both the infrastructure and streams, devices and sections.

## Installation

1. Clone this repository: ```git clone ...```
1. Run ```composer install``` in the root of the project - this will install server side dependencies
1. Run ```composer post-install``` in the root of the project - this will install client side dependencies, as well as create a config file and prompt you for a database config and populate the database

## Verify installation

1. Open ```public/statuschecker.php``` in your browser to check that the system is configured properly and that all server requirements are met
1. Open ```public/``` in any modern browser

## Development

1. Run ```composer tools``` to install all needed development tools
1. Run ```composer phpstan``` to perform a static analysis on the code before pushing
1. Run ```composer psalm``` to perform additional static analysis on the code before pushing
1. Run ```composer phpcs``` to perform a coding style check before pushing (PSR-12)
1. Run ```composer test``` to run all configured tests on your installation

## Deploy

Use ```scripts\deploy.php``` or complete the following steps manually:

1. Make sure DEBUG is set to FALSE in the .env file
1. Set STATUS_CHECKER_USER and STATUS_CHECKER_PASS in the .env file
1. Change SIGNATUREKEY and ENCRYPTIONKEY to new values (32 symbols)
1. Encrypt the passwords in the database using ```scripts/passwords_encrypt.php```
1. Make sure ENVCACHE is set to TRUE in the .env file and run ```scripts/cache_env.php``` to skip parsing the .env file on each request

## Conventions

Use utf-8 without BOM for all files. Also make sure to use LF (instead of CRLF), use 4 spaces for indentation (not tabs). Adhere to the Development section of this file - run ```composer test```, ```composer phpstan```, ```composer phpcs``` and ```composer test``` and correct any errors before pushing any changes.

## Licence
European Union Public Licence

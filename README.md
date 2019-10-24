# Elatus Cabal API
API to facilitate running of SQL queries through client web server rather than them having to share credentials to 
Elatus Cabal Admin tool.

## Requirements

* Web server with PHP version 5.6 or above configured to run.
* PHP Sqlsrv PDO drivers installed and activated.

## Features
* Full API is in the single file and hence easy to move to different location when needed.
* URL key based security system so that only an application with the right key can access it's features.
* SQL query type based permission system. 
Hence if you wish to give permission to only view data all queries except ``SELECT`` can be denied from being executed.
* Logging of each and every SQL query execute request into date-wise log files for future reference.

## Running the API
* Download the latest code from [here](https://github.com/Elatus-Gaming/elatus-cabal-api/archive/master.zip).
* Unzip the archive and copy ``api.php`` file to your web server root directory or a sub-directory under root directory.
* Update the database configuration and key. 
Updating the constant ``API_KEY`` at line number 8 of ``api.php`` is very important for the security of your API.
* Test by visiting either ``http://{server name or IP}/api.php?key={key}`` or ``http://{server name or IP}/{sub-folder}/api.php?key={key}`` 
depending upon ``api.php`` file location. If installation is successful then you should something like ``{"success":true,"msg":"Running Elatus Cabal API","version":1.1}`` in your browser.
* Visit [Elatus Cabal Admin Configure](https://cabal.elat.us/configure) page and enter API URL i.e. 
``http://{server name or IP}/api.php`` or ``http://{server name or IP}/{sub-folder}/api.php`` 
as well as API key to save your API configuration at Elatus Cabal Admin tool.

## Installing required software under Windows and running the API

* Download and install [XAMPP with PHP 7.2](https://www.apachefriends.org/xampp-files/7.2.18/xampp-windows-x64-7.2.18-1-VC15-installer.exe).
* Download and install [ODBC Drivers](https://www.microsoft.com/en-us/download/details.aspx?id=36434).
* Download [PHP 7.2 MSSQL Drivers](https://github.com/microsoft/msphpsql/releases/download/v5.6.1/Windows-7.2.zip). 
  and copy `php_pdo_sqlsrv_72_ts.dll` as well as `php_sqlsrv_72_ts.dll` found inside `x64` folder of the downloaded
  zip file to `XamppInstallationDrive:\xampp\php\ext` folder.
* Open the file `XamppInstallationDrive:\xampp\php\php.ini` and append the following config into it

````
extension=php_sqlsrv_72_ts.dll
extension=php_pdo_sqlsrv_72_ts.dll
````
* Restart Apache web server using XAMPP control panel.
* Copy ``api.php`` to ``XamppInstallationDrive:\xampp\htdocs`` which is the root directory of your web server.
* Follow the remaining steps mentioned in the ``Running the API`` section.

## Installing required software under Linux and running the API

* Install Apache + PHP by referring [this guide](https://tecadmin.net/install-apache-php-on-centos-fedora/).
* Install PHP Sqlsrv PDO drivers by referring [this guide](https://www.danhendricks.com/2017/11/installing-microsoft-sql-server-php-extensions-plesk-onyx/).
* Copy ``api.php`` to ``/var/www/html`` which is the root directory of your web server.
* Follow the remaining steps mentioned in the ``Running the API`` section.

For further help on installation, configuration and running please contact ``cyberinferno#8771`` in Discord! 

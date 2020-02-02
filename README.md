# RDMonitor
RDMonitor is a simple Monitoring tool to track important infos from RDM - also supports Lorgnette (mysql &amp; postgres)

## Getting Started

1) Pull the repository to a folder that will be accessable on a page you specify in your webserver settings
```
cd /path/to/your/folder
git clone https://github.com/xxleevo/RDMonitor
cd RDMonitor
```

2) Create a config from the example:
```
cp config.example.php config.php
```

3) Adjust at least the following configs:

* The RDM Database Informations:
```
  "db" => [ //The rdm database
	  "type" => "mysql",
    "host" => "127.0.0.1",
    "port" => "3306",
    "user" => "YOURUSER",
    "pass" => "YOURPASSWORD",
    "dbname" => "YOURDBNAME",
    "charset" => "utf8mb4"
  ],
```

* The Lorgnette Database Information (if used. can be ignored if lorgnette isnt used
```
  "db_lorgnette" => [ //The Lorgnette Database (if lorgnette isnt used, just keep it as it is)
	"type" => "psql", //psql or mysql
	"host" => "127.0.0.1", //DB Host
    "port" => "5432", //DB Port. (mysql default: 3306, psql default: 5432)
    "user" => "YOURUSER", //DB User
    "pass" => "YOURPASSWORD", //DB Pass
    "dbname" => "YOURDBNAME", //DB Name
    "charset" => "utf8mb4" //DB character set. (mysql default: utf8mb4, psql: just keep this)
  ],
```

### Prerequisites

This guide is based on Ubuntu systems and the installation depends on your php version:

Make sure you have the correct php modules installed.
* For MySQL connection (needed):
```
sudo apt-get install php-common php-mysql php-cli
```
Make sure to uncomment the `extension=php_pdo_mysql.dll` in your php config. If its not there, you can add it at the extension section.

* For PostgreSQL (if used for Lorgnette):

```
sudo apt-get install php-pgsql
```
or
```
sudo apt-get install php7.0-pgsql
```
or
```
sudo apt-get install php5-pgsql
```

Make sure to uncomment the `extension=php_pgsql.dll` in your php config. If its not there, you can add it at the extension section.


Tip: The php file is usually found in `/etc/php/$VERSION%/php.ini`


## Thanks and Relations

* [RDM-o-Pole](https://github.com/versx/RealDeviceMap-opole) for the base Layout

<?php

//Configuration options
$config = [
  "core" => [
    "timeZone" => "Europe/Berlin", //Time zone for PHP pages and MySql data
    "fromTimeZoneOffset" => "+01:00", //Time zone offset to convert database time from e.g. UTC, -03:00, +01:00
    "dateTimeFormat" => "m-d-Y h:i:s A", //Date & Time format http://php.net/manual/en/function.date.php
    "showErrors" => true, //Show PHP errors
    "showDebug" => false, //Shows debug output
  ],
  "db" => [ //The rdm database
	"type" => "mysql", //Only Mysql here.
    "host" => "127.0.0.1", //DB Host
    "port" => "3306", //DB Port. (default: 3306)
    "user" => "YOURUSER", //DB User
    "pass" => "YOURPASSWORD", //DB Pass
    "dbname" => "YOURDBNAME", //DB Name
    "charset" => "utf8mb4" //DB character set. (default: utf8mb4)
  ],
  /*
	For mysql(rdm (and if used, for lorgnette)):
		1) Make sure your PHP has the correct module installed (php5.3 and above should not need it)
		2) If mysql connection still fails, make a phpinfo file to see if your pdo_mysql is enabled.
	For Psql(if used, for lorgnette):
		1) Make sure your PHP has the module installed (for Php7.0 for example: apt-get install php7.0-pgsql)
		2) Ensure that the module gets enabled in your php config (extension=php_pgsql.dll)
		3) Restart your webserver to apply the installation of the module.
  */
  "db_lorgnette" => [ //The Lorgnette Database (if lorgnette isnt used, just keep it as it is)
	"type" => "psql", //psql or mysql
	"host" => "127.0.0.1", //DB Host
    "port" => "5432", //DB Port. (mysql default: 3306, psql default: 5432)
    "user" => "YOURUSER", //DB User
    "pass" => "YOURPASSWORD", //DB Pass
    "dbname" => "YOURDBNAME", //DB Name
    "charset" => "utf8mb4" //DB character set. (mysql default: utf8mb4, psql: just keep this)
  ],
  "ui" => [
    "locale" => "en", //Set the language (supported: en, de)
    "pages" => [
      "quests" => [
        "enabled" => true, //Shows/hides quests-stats page
      ],
      "lorgnette" => [
        "enabled" => true, //Shows/hides lorgnette page
      ]
    ],
    "navBarIconSize" => [24, 24] //NavBar image icon size e.g. [Width, Height]
  ],
  "urls" => [
    "map" => "", //RealDeviceMap/PMSF/Other url
    "images" => [
      /* Pokemon images url 
        Needs pmsf formatted icons (default is already working)
        e.g. http://map.example.com/static/img/pokemon/pokemon_icon_%03d.png
      */
	  "pokemon" => "https://raw.githubusercontent.com/xxleevo/monicons/master/classic/pokemon_icon_%03d_00.png",
    ]
  ]
];

//Error reporting
ini_set("error_reporting", $config['core']['showErrors']);
if ($config['core']['showErrors']) {
  error_reporting(E_ALL|E_STRCT);
}
?>
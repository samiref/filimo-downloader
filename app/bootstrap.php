<?php

define("DS"    , DIRECTORY_SEPARATOR);
define("APP"   , __DIR__);
define("PUB"   , __DIR__ . DS . ".." . DS . 'public');
define("LIB"   , APP . DS . 'lib');
define("TEMP"  , APP . DS . 'temp');
define("THEME" , APP . DS . 'theme');

require_once LIB . DS . "Filimo.php";
require_once LIB . DS . "ThemeEngine.php";
require_once LIB . DS . "DB.php";
require_once LIB . DS . "Auth.php";
require_once LIB . DS . "helper.php";
require_once LIB . DS . "Route" . DS . "Route.php";

ThemeEngine::$theme_dir = THEME . DS;
VideoDownloader::$temp_dir = TEMP . DS;
VideoDownloader::$download_dir = PUB . DS . "Movies" . DS;

DB::$db_file = TEMP . DS . "db.ser";
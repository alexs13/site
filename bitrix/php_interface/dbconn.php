<?
define("DBPersistent", false);
$DBType = "mysql";
$DBHost = "localhost";
$DBLogin = "alexs13_1";
$DBPassword = "13031994";
$DBName = "alexs13_1";
$DBDebug = false;
$DBDebugToFile = false;

@set_time_limit(60);

define("DELAY_DB_CONNECT", true);
define("CACHED_b_file", 3600);
define("CACHED_b_file_bucket_size", 10);
define("CACHED_b_lang", 3600);
define("CACHED_b_option", 3600);
define("CACHED_b_lang_domain", 3600);
define("CACHED_b_site_template", 3600);
define("CACHED_b_event", 3600);
define("CACHED_b_agent", 3660);
define("CACHED_menu", 3600);

define("BX_FILE_PERMISSIONS", 0600);
define("BX_DIR_PERMISSIONS", 0700);
@umask(~BX_DIR_PERMISSIONS);
define("BX_DISABLE_INDEX_PAGE", true);
?>
<?php

if(defined('IN_COPPERMINE')) {
	die('Cannot be externally included');
}

define('IN_COPPERMINE', true);

require 'include/init.inc.php';
require_once 'include/upload_functs.php';

ini_set('error_reporting', 22527);
ini_set('log_errors', 'On');

$pid = $argv[1];
$r = cpg_db_query("SELECT filename,filepath FROM {$CONFIG['TABLE_PICTURES']} WHERE pid='$pid'");
$row = mysql_fetch_assoc($r);
mysql_free_result($r);
$path = $CONFIG['fullpath'] . $row['filepath'] . $row['filename'];
dkrn_make_video_thumb($path);

?>

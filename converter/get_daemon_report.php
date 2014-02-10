<?php
if (defined('IN_COPPERMINE')) {
	die();
}
define('IN_COPPERMINE', true);

include 'include/init.inc.php';
include 'include/video_convert.php';

$result = cpg_db_query("SELECT cid,error_message FROM {$CONFIG['TABLE_CONVERSIONS']} WHERE status='".CONVERT_STATUS_FAILURE."'");
while ($row = mysql_fetch_assoc($result)) {
	echo $row['cid'] . ":\n";
	echo $row['error_message'] ."\n\n\n";
}
mysql_free_result($result);
?>

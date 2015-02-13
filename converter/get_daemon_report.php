<?php
if (defined('IN_COPPERMINE')) {
	die();
}
define('IN_COPPERMINE', true);

include 'include/init.inc.php';
include 'include/video_convert.php';

$result = cpg_db_query("SELECT cid,error_message FROM {$CONFIG['TABLE_CONVERSIONS']}");
while ($row = mysql_fetch_assoc($result)) {
	echo $row['cid'] . ":\n";
	switch ($row['status']) {
		case CONVERT_STATUS_MOVING:
			echo 'Moving';
			break;
		case CONVERT_STATUS_READY:
			echo 'Ready';
			break;
		case CONVERT_STATUS_SUCCESS:
			echo 'Successful';
			break;
		case CONVERT_STATUS_FAILURE:
			echo 'Failed - ' . $row['error_message'];
			break;
	}
	echo "\n\n\n";
}
mysql_free_result($result);
?>

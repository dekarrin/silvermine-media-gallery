<?php
if (defined('IN_COPPERMINE')) {
	die();
}
define('IN_COPPERMINE', true);

require 'include/init.inc.php';
error_reporting(E_ALL & E_STRICT);

require_once 'include/video_convert.inc.php';

$result = cpg_db_query("SELECT cid,data,status,error_message,already_exists,pid FROM {$CONFIG['TABLE_CONVERSIONS']}");
while ($row = mysql_fetch_assoc($result)) {
	echo $row['cid'] . ': ';
	if ($row['already_exists'] == 1) {
		echo 'Conversion of PID ' . $row['pid'] . "\n";
	} else {
		echo "New conversion\n";
	}
	switch ($row['status']) {
		case CONVERT_STATUS_MOVING:
			echo "Moving\n";
			break;
		case CONVERT_STATUS_READY:
			echo "Ready\n";
			break;
		case CONVERT_STATUS_SUCCESS:
			echo "Successful\n";
			break;
		case CONVERT_STATUS_FAILURE:
			echo 'Failed - ' . $row['error_message'] . "\n";
			break;
	}
	echo "\n\n---------------------------------------------\n\n";
}
mysql_free_result($result);
?>

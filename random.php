<?php
// this entire file is one big DEKKY MOD
define('IN_COPPERMINE', true);

require 'include/init.inc.php';

function get_field_list($table, $column, $check, $check_value) {
	$sql = "SELECT `$column` FROM `$table` WHERE `$check` = '$check_value';";
	$result = cpg_db_query($sql);
	$data = array();
	while (($row = mysql_fetch_row($result)) !== false) {
		$data[] = $row[0];
	}
	mysql_free_result($result);
	return $data;
}

function get_albums_in_cat($cat_id) {
	global $CONFIG;
	return get_field_list($CONFIG['TABLE_ALBUMS'], 'aid', 'category', $cat_id);
}

function get_images_in_album($album_id) {
	global $CONFIG;
	return get_field_list($CONFIG['TABLE_PICTURES'], 'pid', 'aid', $album_id);
}

if (!$superCage->get->keyExists('id') || !$superCage->get->keyExists('mode')) {
	cpg_die(ERROR, 'There was a problem with the form parameters. Go back and try again.', __FILE__, __LINE__);
}

$mode = $superCage->get->getRaw('mode');
$id = $superCage->get->getInt('id');
$path = '';
switch ($mode) {
	case 'cat':
		$albums = get_albums_in_cat($id);
		shuffle($albums);
		$key = array_rand($albums);
		$aid = $albums[$key];
		$path = "thumbnails.php?album=$aid";
		break;

	case 'image':
		$pics = get_images_in_album($id);
		shuffle($pics);
		$key = array_rand($pics);
		$pid = $pics[$key];
		$path = "displayimage.php?album=$id&pid=$pid#top_display_media";
		break;

	default:
		cpg_die(ERROR, 'There was a problem with the form parameters. Go back and try again.', __FILE__, __LINE__);
		break;
}

header("Location: $path");
?>

<?php

if(defined('IN_COPPERMINE')) {
	die('Cannot be externally included');
}

define('LOCK_FILE', 'converter/dekky_mp4_daemon.lock');
define('IN_COPPERMINE', true);

include 'include/init.inc.php';
include_once 'include/upload_functs.php';
include_once 'include/picmgmt.inc.php';

ini_set('error_reporting', 22527);
ini_set('log_errors', 'On');

if (count($argv) > 0) {
	$extra_options = $argv[1];
} else {
	$extra_options = '';
}

function dkrn_clear_completed() {
	global $CONFIG;
	cpg_db_query("DELETE FROM {$CONFIG['TABLE_CONVERSIONS']} WHERE status='".CONVERT_STATUS_SUCCESS."'");
}

function dkrn_get_conversion_count() {
	global $CONFIG;
	$r = cpg_db_query("SELECT COUNT(*) FROM {$CONFIG['TABLE_CONVERSIONS']} WHERE status='".CONVERT_STATUS_READY."'");
	$row = mysql_fetch_row($r);
	mysql_free_result($r);
	return $row[0];
}

function converter_is_running() {
	if (file_exists(LOCK_FILE)) {
		// check if stale
		$lock_pid = trim(file_get_contents(LOCK_FILE));
		$pids = explode("\n", trim(`ps -e | awk '{print $1}'`));
		if (in_array($lock_pid, $pids)) {
			return true;
		} else {
			unlink(LOCK_FILE);
			return false;
		}
	}
	return false;
}

if (converter_is_running()) {
	die();
}

// Set the locking file
file_put_contents(LOCK_FILE, getmypid() . "\n");

do {
	$r = cpg_db_query("SELECT * FROM {$CONFIG['TABLE_CONVERSIONS']}
WHERE status='".CONVERT_STATUS_READY."' LIMIT 1");
	$row = cpg_db_fetch_row($r);
	mysql_free_result($r);
	if ($row === FALSE) {
		break;
	}
	$already_exists = ($row['already_exists'] == 1);
	$cid = $row['cid'];
	$data = json_decode($row['data'], true);
	$ext = pathinfo($data['filename'], PATHINFO_EXTENSION);
	$file = $CONFIG['fullpath'] . 'conversions/' . $cid . '.' . $ext;
	$new_path = $CONFIG['fullpath'] . 'conversions/' . $cid . '.mp4';
	list($convert_cmd, $convert_output) = dkrn_convert_video_file($file, $new_path, $extra_options);
	if (file_exists($new_path) && filesize($new_path) > 0) {
		// conversion succeeded, now get location to move converted movie to
		$dest_dir = $CONFIG['fullpath'] . $data['filepath'];
		$old_file_base = mb_substr($data['filename'], 0, mb_strrpos($data['filename'], '.'));
		$pic_parts = array('', $old_file_base, 'mp4');
		list($album_path, $album_name) = dkrn_create_all_pic_names($pic_parts, $dest_dir);
		if(rename($new_path, $album_path)) {
			if ($already_exists) {
				$pid = $row['pid'];
				$album_file = mb_substr($album_name, 0, mb_strrpos($album_name, '.'));
				$thumb = dkrn_rename_thumbnail($album_file, $old_file_base, $data['filepath']);
				if ($thumb === false) {
					$thumb = dkrn_make_video_thumb($album_path);
				}
				$size = filesize($album_path);
				$total_size = $size + filesize($thumb);
				cpg_db_query("UPDATE {$CONFIG['TABLE_PICTURES']}
SET filename='$album_name', filesize='$size', total_filesize='$total_size'
WHERE pid='$pid'");
			} else {
				add_picture($data['aid'], $data['filepath'], $album_name, $data['position'], $data['title'], $data['caption'], $data['keywords'], $data['user1'], $data['user2'], $data['user3'], $data['user4'], $data['category'], $data['raw_ip'], $data['hdr_ip'], $data['iwidth'], $data['iheight'], NULL, $data['SYS_STATE']);
			// create auto-generated thumbnail
				dkrn_make_video_thumb($album_path);
			}
			unlink($file);
			dkrn_set_convert_status($cid, CONVERT_STATUS_SUCCESS);
		} else {
			dkrn_set_convert_status($cid, CONVERT_STATUS_FAILURE, 'Could not rename converted file!');
		}
	} else {
		dkrn_set_convert_status($cid, CONVERT_STATUS_FAILURE, $convert_cmd . ":\n" . $convert_output);
	}
} while (dkrn_get_conversion_count() > 0);
dkrn_clear_completed();
unlink(LOCK_FILE);
?>

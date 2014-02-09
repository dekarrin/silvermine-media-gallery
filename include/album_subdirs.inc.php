<?php

if (!defined('IN_COPPERMINE')) die('Not in Coppermine...');

require_once('include/upload_functs.php');

function dkrn_move_image_to_subdir($source, $dest) {
	global $lang_util_php, $icon_array;
	if (file_exists($source)) {
		if (rename($source, $dest)) {
			return '<li>' . $icon_array['ok'] . '<tt>' . $source . "</tt> " . $lang_util_php['updated_successfully'] . '!</li>';
		} else {
			return '<li>' . $icon_array['stop'] .sprintf($lang_util_php['error_rename'], "<tt>$source</tt>", "<tt>$dest</tt>") . '</li>';
		}
	} else {
		return '';
	}
}

function dkrn_move_pid_to_album($pid, $dest_aid) {
	global $CONFIG;
	$result = cpg_db_query("SELECT P.aid, P.filepath, P.filename, A.category FROM {$CONFIG['TABLE_PICTURES']} AS P INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS A ON P.aid = A.aid WHERE P.pid = '$pid'");
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$result = cpg_db_query("SELECT category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = '$dest_aid'");
	$cat_row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$cat = $cat_row['category'];
	return dkrn_move_picture_to_album($pid, $row['filepath'], $row['filename'], $dest_aid, $cat);
}

// Does pathinfo($path, PATHINFO_FILENAME), but compatible with all PHP versions.
function dkrn_get_filename($path) {
	$basename = pathinfo($path, PATHINFO_BASENAME);
	if (strpos($basename, '.') !== FALSE) {
		return substr($basename, 0, strrpos($basename, '.'));
	} else {
		return $basename;
	}
}

// returns array containing (filename, basename)
function dkrn_get_thumbnail_basename($pic_basename) {
	$pic_ext = pathinfo($pic_basename, PATHINFO_EXTENSION);
	$filename = dkrn_get_filename($pic_basename);
	$ext = '.' . ((is_image($pic_basename)) ? $pic_ext : 'jpg');
	// above is hard-coded to work with the custom thumbnails mod by eenemeenemuu
	return array($filename, $ext);
}

// $src_dir is relative to album root dir
// $src_file is relative to src_dir, but should be a basename of the file.
// $dest_cid is id of category of album to move file to.
// $dest_aid is id of album to move file to.
// $dest_name is the filename (without extension) of the destination file. if it is not given,
// it is calculated by using dkrn_create_all_pic_names().
// $file_type is one of 'normal', 'thumb', 'orig', or NULL.
// returns the value of $dest_filename used.
function dkrn_move_image_file_to_album($src_dir, $src_file, $dest_cid, $dest_aid, $dest_name = NULL, $file_type = NULL) {
	global $CONFIG;
	list($album_path, $cpg_path) = dkrn_get_upload_paths($dest_cid, $dest_aid);
	$ext = pathinfo($src_file, PATHINFO_EXTENSION);
	if (is_null($dest_name)) {
		$filename = dkrn_get_filename($src_file);
		list($ul_path, $dest_name) = dkrn_create_all_pic_names(array('', $filename, $ext), $cpg_path);
		$dest_name = dkrn_get_filename($dest_name);
	}
	$prefix = (!is_null($file_type)) ? $CONFIG[$file_type . '_pfx'] : '';
	$src_path = $CONFIG['fullpath'] . $src_dir . $prefix . $src_file;
	$dest_path = $cpg_path . $prefix . $dest_name . '.' . $ext;
	$output = dkrn_move_image_to_subdir($src_path, $dest_path);
	return array($dest_name, $output, $album_path);
}

function dkrn_move_picture_to_album($p_id, $p_filepath, $p_filename, $dest_album_id, $dest_album_cat) {
	global $CONFIG;
	list($t_filename, $t_ext) = dkrn_get_thumbnail_basename($p_filename);
	$t_file = $t_filename . $t_ext;
	$out = '<tr><td class="'.$tablestyle.'"><ul>';
	list($name, $o, $a) = dkrn_move_image_file_to_album($p_filepath, $p_filename, $dest_album_cat, $dest_album_id);
	$out .= $o;
	list($n, $o, $a) = dkrn_move_image_file_to_album($p_filepath, $p_filename, $dest_album_cat, $dest_album_id, $name, 'normal');
	$out .= $o;
	list($n, $o, $a) = dkrn_move_image_file_to_album($p_filepath, $t_file, $dest_album_cat, $dest_album_id, $name, 'thumb');
	$out .= $o;
	list($n, $o, $a) = dkrn_move_image_file_to_album($p_filepath, $p_filename, $dest_album_cat, $dest_album_id, $name, 'orig');
	$out .= $o . '</ul></td></tr>';
	$pic_name = $name . '.' . pathinfo($p_filename, PATHINFO_EXTENSION);
	// now update the database
	$q = "UPDATE {$CONFIG['TABLE_PICTURES']} SET filename='$pic_name', filepath='$a' WHERE pid='{$p_id}'";
	cpg_db_query($q);
	return $out;
}

?>
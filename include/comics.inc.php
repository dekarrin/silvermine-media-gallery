<?php
if(!defined('IN_COPPERMINE')) die('Not in Coppermine...');

require_once 'include/init.inc.php';

require_once 'include/upload_functs.php';
require_once 'include/picmgmt.inc.php';

function upload_comic($file, $title, $author, $keywords) {
	$pages = get_comic_page_list($file);
	$pc = count($pages);
	$aid = create_manga($title);
	$kws = '';
	if (!empty($author)) {
		$kws = 'author:' . $author;
	}
	if (!empty($keywords)) {
		if (!empty($kws)) {
			$kws .= ';';
		}
		$kws .= $keywords;
	}
	foreach ($pages as $p) {
		$f = extract_comic_page($file, $p);
		add_single_file($f, $aid, $kws);
		unlink($f);
	}
	return $pc;
}

function extract_comic_page($file, $p) {
	$ext = pathinfo($file, PATHINFO_EXTENSION);
	$dir = pathinfo($file, PATHINFO_DIRNAME);
	switch ($ext) {
		case 'cbz':
			return extract_zip_file($file, $p, $dir);
	}
}

function extract_zip_file($archive, $file, $dir) {
	shell_exec('unzip ' . $archive . ' ' . $file . ' -d ' . $dir);
	return "$dir/$file";
}

function get_comic_page_list($file) {
	$ext = pathinfo($file, PATHINFO_EXTENSION);
	switch ($ext) {
		case 'cbz':
			return get_zip_page_list($file);
	}
}

function get_zip_page_list($file) {
	$files = shell_exec('zipinfo -1 ' . $file);
	$files = substr($files, 0, strlen($files)-1);
	return explode("\n", $files);
}

function create_manga($title) {
	global $CONFIG;
	$q = "SELECT cid FROM {$CONFIG['TABLE_CATEGORIES']} WHERE name = 'Manga'";
	$r = cpg_db_query($q);
	$cat_r = mysql_fetch_row($r);
	$cat = $cat_r[0];
	mysql_free_result($r);
	$query = "INSERT INTO {$CONFIG['TABLE_ALBUMS']} (category, title, uploads, pos, description, owner) VALUES ('$cat', '$title', '0', '100', '', '1')";
	cpg_db_query($query);
	$aid = mysql_insert_id($CONFIG['LINK_ID']);
	return $aid;
}

function add_single_file($filename, $aid, $keywords) {
	$picture_name = pathinfo($filename, PATHINFO_BASENAME);
	$cat = dkrn_ensure_valid_aid($aid);
	list($filepath, $dest_dir) = dkrn_get_upload_paths($cat, $aid);
	$matches = dkrn_validate_extension($picture_name);
	list($uploaded_pic, $picture_name) = dkrn_create_pic_names($matches, $dest_dir);
	if(!rename($filename, $uploaded_pic)) {
		cpg_die(ERROR, "Could not rename file $filename to $uploaded_pic!", __FILE__, __LINE__);
	}
	dkrn_set_pic_perms($uploaded_pic);
	dkrn_validate_pic_info($uploaded_pic, $picture_name);
	add_picture($aid, $filepath, $picture_name, 0, '', '', $keywords, '', '', '', '', $cat, '', '', '', '');
}

?>

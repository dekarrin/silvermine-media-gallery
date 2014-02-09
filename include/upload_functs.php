<?php
// abstracted-out coppermine upload image functions
// mod by dekarrin/dekky

// returns the category... for some reason. Ask the cpg dev team.
// NOTE: is actually used by other dekky mods
function dkrn_ensure_valid_aid($aid) {
	global $CONFIG, $lang_db_input_php;
	if (!(GALLERY_ADMIN_MODE || user_is_allowed())) {

        $result = cpg_db_query("SELECT category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = $aid AND (uploads = '1' OR owner = " . USER_ID . " OR category = " . (USER_ID + FIRST_USER_CAT) . ")");

        if (mysql_num_rows($result) == 0) {
            cpg_die(ERROR, $lang_db_input_php['unknown_album'], __FILE__, __LINE__);
        }

        $row = mysql_fetch_assoc($result);
        mysql_free_result($result);

        $category = $row['category'];

    } else {

        $result = cpg_db_query("SELECT category FROM {$CONFIG['TABLE_ALBUMS']} WHERE aid = $aid");

        if (mysql_num_rows($result) == 0) {
            cpg_die(ERROR, $lang_db_input_php['unknown_album'], __FILE__, __LINE__);
        }

        $row = mysql_fetch_assoc($result);
        mysql_free_result($result);

        $category = $row['category'];
    }
	return $category;
}

function dkrn_create_picdir($dir) {
	global $CONFIG, $lang_db_input_php;
	if (!is_dir($dir)) {
		mkdir($dir, octdec($CONFIG['default_dir_mode']));
		if (!is_dir($dir)) {
			cpg_die(CRITICAL_ERROR, sprintf($lang_db_input_php['err_mkdir'], $dir), __FILE__, __LINE__, true);
		}
		chmod($dir, octdec($CONFIG['default_dir_mode']));
		$fp = fopen($dir . '/index.php', 'w');
		fwrite($fp, ' ');
		fclose($fp);
	}
}

// Gets the paths to move uploaded files to
// Returns as array -
// [0] = $album_path, the path to the directory relative to
// the root album folder
// [1] = $cpg_path, the path to the directory relative to
// the root of Coppermine.
function dkrn_get_upload_paths($category, $album) {
	global $CONFIG, $lang_db_input_php;
	if (USER_ID && $CONFIG['silly_safe_mode'] != 1) {

        $album_path = $CONFIG['userpics'] . (USER_ID + FIRST_USER_CAT);
		$cpg_path = $CONFIG['fullpath'] . $album_path;

        dkrn_create_picdir($cpg_path);

        // Upload pictures in a sub-directory named according to the category and album ID
        if ($CONFIG['upload_create_album_directory']) {
            $album_path .= '/'.$category;
            $cpg_path .= '/'.$category;

            dkrn_create_picdir($cpg_path);
			
			$album_path .= '/'.$album;
			$cpg_path .= '/'.$album;
			
			dkrn_create_picdir($cpg_path);
        }

        $album_path .= '/';
        $cpg_path .= '/';

    } else {
        $album_path = $CONFIG['userpics'];
        $cpg_path = $CONFIG['fullpath'] . $filepath;
    }
	// Check that target dir is writable
    if (!is_writable($cpg_path)) {
        cpg_die(CRITICAL_ERROR, sprintf($lang_db_input_php['dest_dir_ro'], $cpg_path), __FILE__, __LINE__, true);
    }
	return array($album_path, $cpg_path);
}

function dkrn_get_uploaded_name() {
	global $superCage;
	if (get_magic_quotes_gpc()) {
        //Using getRaw() as we have custom sanitization code below
        $picture_name = stripslashes($superCage->files->getRaw("/userpicture/name"));
    } else {
        $picture_name = $superCage->files->getRaw("/userpicture/name");
    }
	
	$picture_name = CPGPluginAPI::filter('upload_file_name', $picture_name);

    // Replace forbidden chars (including white spaces and special chars) with underscores
    $picture_name = replace_forbidden($picture_name);
	
	return $picture_name;
}

function dkrn_validate_extension($picture_name) {
	global $CONFIG, $lang_db_input_php;
	if (!preg_match("/(.+)\.(.*?)\Z/", $picture_name, $matches)) {
        $matches[1] = 'invalid_fname';
        $matches[2] = 'xxx';
    }

    if ($matches[2] == '' || !is_known_filetype($matches)) {
        cpg_die(ERROR, $lang_db_input_php['err_invalid_fext'] . ' ' . $CONFIG['allowed_file_extensions'], __FILE__, __LINE__);
    }
	
	return $matches;
}

function dkrn_create_pic_names($pic_parts, $dest_dir) {
	$nr = 0;
    $picture_name = $pic_parts[1] . '.' . $pic_parts[2];

    while (file_exists($dest_dir . $picture_name)) {
        $picture_name = $pic_parts[1] . '~' . $nr++ . '.' . $pic_parts[2];
    }

    $uploaded_pic = $dest_dir . $picture_name;
	return array($uploaded_pic, $picture_name);
}

// like create_pic_names, but checks all possible types of images
// (thumbs, intermediates, originals)
function dkrn_create_all_pic_names($pic_parts, $dest_dir) {
	global $CONFIG;
	$nr = 0;
    $picture_name = $pic_parts[1] . '.' . $pic_parts[2];

    while (file_exists($dest_dir . $picture_name) ||
	  file_exists($dest_dir . $CONFIG['normal_pfx'] . $picture_name) ||
	  file_exists($dest_dir . $CONFIG['thumb_pfx'] . $picture_name) ||
	  file_exists($dest_dir . $CONFIG['orig_pfx'] . $picture_name)) {
        $picture_name = $pic_parts[1] . '~' . $nr++ . '.' . $pic_parts[2];
    }

    $uploaded_pic = $dest_dir . $picture_name;
	return array($uploaded_pic, $picture_name);
}

function dkrn_set_pic_perms($pic) {
	global $CONFIG;
	chmod($pic, octdec($CONFIG['default_file_mode']));
}

function dkrn_validate_pic_info($uploaded_pic, $original_name) {
	global $CONFIG, $lang_db_input_php, $lang_errors;
	if (filesize($uploaded_pic) > ($CONFIG['max_upl_size'] * 1024)) {

        @unlink($uploaded_pic);
        cpg_die(ERROR, sprintf($lang_db_input_php['err_imgsize_too_large'], $CONFIG['max_upl_size']), __FILE__, __LINE__);

    } elseif (is_image($original_name)) {

        $imginfo = cpg_getimagesize($uploaded_pic);

        if ($imginfo == null) {

            // getimagesize does not recognize the file as a picture
            @unlink($uploaded_pic);
            cpg_die(ERROR, $lang_db_input_php['err_invalid_img'], __FILE__, __LINE__, true);

        } elseif ($imginfo[2] != GIS_JPG && $imginfo[2] != GIS_PNG && $CONFIG['GIF_support'] == 0) {

            // JPEG and PNG only are allowed with GD
            @unlink($uploaded_pic);
            cpg_die(ERROR, $lang_errors['gd_file_type_err'], __FILE__, __LINE__, true);

            // Check that picture size (in pixels) is lower than the maximum allowed
        } // Image is ok
    }
	return $imginfo;
}
?>

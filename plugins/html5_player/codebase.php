<?php
/**************************************************
  Coppermine 1.5.x Plugin - HTML5 Player
  *************************************************
  Copyright (c) 2014 Rebecca Nelson
  ********************************************/

// note - due to laziness, this relies on the flash media player
// plugin to be installed

if (!defined('IN_COPPERMINE')) die('Not in Coppermine...');

$thisplugin->add_action('plugin_install', 'h5p_install');
$thisplugin->add_action('plugin_uninstall', 'h5p_uninstall');
$thisplugin->add_action('plugin_cleanup', 'h5p_cleanup');

$thisplugin->add_filter('html_other_media', 'h5p_other_media');

if (defined('DISPLAYIMAGE_PHP')) {
	$thisplugin->add_filter('template_html', 'h5p_template');
}

if ($CONFIG['thumbnail_to_fullsize'] == 1) {
	$thisplugin->add_filter('fullsize_html', 'h5p_fullsize');
	$thisplugin->add_filter('theme_display_thumbnails_params', 'h5p_thumb_params');
}

function h5p_install() {
	return true;
}

function h5p_cleanup() {}

function h5p_uninstall() {
	return true;
}

function h5p_other_media($pic_html) {
	global $CURRENT_PIC_DATA;
	if (h5p_is_supported_ext($CURRENT_PIC_DATA['extension'])) {
		$pic_html = h5p_get_html($CURRENT_PIC_DATA);
	}
	return $pic_html;
}

function h5p_template($html) {
	global $CURRENT_PIC_DATA;
	if (h5p_is_supported_ext($CURRENT_PIC_DATA['extension'])) {
		$html = preg_replace('/^(?:<!DOCTYPE[^>]+>)?/', '<!DOCTYPE html>', $html);
	}
	return $html;
}

function h5p_fullsize($html) {
    global $CONFIG, $FORBIDDEN_SET, $pid;

    $sql = "SELECT * FROM {$CONFIG['TABLE_PICTURES']} AS p WHERE pid='$pid' $FORBIDDEN_SET";
    $result = cpg_db_query($sql);
    if (!mysql_num_rows($result)) {
        cpg_die(ERROR, $lang_errors['non_exist_ap'], __FILE__, __LINE__);
    }
    $CURRENT_PIC_DATA = mysql_fetch_assoc($result);

    $mime_content = cpg_get_type($CURRENT_PIC_DATA['filename']);
    $CURRENT_PIC_DATA['extension'] = $mime_content['extension'];

    if ($CURRENT_PIC_DATA['pwidth']==0 || $CURRENT_PIC_DATA['pheight']==0) {
        $resize_method = $CONFIG['picture_use'] == "thumb" ? ($CONFIG['thumb_use'] == "ex" ? "any" : $CONFIG['thumb_use']) : $CONFIG['picture_use'];
        if ($resize_method == 'ht') {
            $pwidth = $CONFIG['picture_width']*4/3;
            $pheight = $CONFIG['picture_width'];
        } else { 
            $pwidth = $CONFIG['picture_width'];
            $pheight = $CONFIG['picture_width']*3/4;
        }

        $CURRENT_PIC_DATA['pwidth']  = $pwidth; // Default width

        // Set default height; if file is a movie
        if ($mime_content['content']=='movie') {
            $CURRENT_PIC_DATA['pheight'] = $pheight; // Default height
        }
    }

    if (h5p_is_supported_ext($CURRENT_PIC_DATA['extension'])) {
        $html = preg_replace('/<body style="margin:0px; padding:0px; background-color: gray;">(.*)<\/body>/Usi', '<body style="margin:0px; padding:0px; background-color: gray;">'.hp5_get_html($CURRENT_PIC_DATA).'</body>', $html);
    }

    return $html;
}

function h5p_thumb_params($data) {
    preg_match('/alt="(.*)"/Ui', $data['{THUMB}'], $match_alt);
    $mime_content = cpg_get_type($match_alt[1]);

    if (h5p_is_supported_ext($mime_content)) {
        global $CONFIG;

        preg_match('/pid=([0-9]+)/i', $data['{LINK_TGT}'], $match_pid);
        $pid = $match_pid[1];

        $sql = "SELECT pwidth, pheight FROM {$CONFIG['TABLE_PICTURES']} WHERE pid='$pid'";
        $result = cpg_db_query($sql);
        $CURRENT_PIC_DATA = mysql_fetch_assoc($result);

        if ($CURRENT_PIC_DATA['pwidth']==0 || $CURRENT_PIC_DATA['pheight']==0) {
            $resize_method = $CONFIG['picture_use'] == "thumb" ? ($CONFIG['thumb_use'] == "ex" ? "any" : $CONFIG['thumb_use']) : $CONFIG['picture_use'];
            if ($resize_method == 'ht') {
                $pwidth = $CONFIG['picture_width']*4/3;
                $pheight = $CONFIG['picture_width'];
            } else { 
                $pwidth = $CONFIG['picture_width'];
                $pheight = $CONFIG['picture_width']*3/4;
            }

            $CURRENT_PIC_DATA['pwidth']  = $pwidth; // Default width

            // Set default height; if file is a movie
            if ($mime_content['content']=='movie') {
                $CURRENT_PIC_DATA['pheight'] = $pheight; // Default height
            }
        }

        $new_height = ceil($CURRENT_PIC_DATA['pheight'] + $CONFIG['fullsize_padding_y']);
        $new_width = ceil($CURRENT_PIC_DATA['pwidth'] + $CONFIG['fullsize_padding_x']);

        $data['{LINK_TGT}'] = preg_replace('/width=([0-9]+),height=([0-9]+)/i', 'width='.$new_width.',height='.$new_height, $data['{LINK_TGT}']);
    }

    return $data;
}

function h5p_get_html($PIC_DATA) {
	global $CONFIG, $USER, $superCage;
	$media_file = get_pic_url($PIC_DATA, 'fullsize');
	$thumb = get_pic_url($PIC_DATA, 'thumb');
	if (file_exists($normal = str_replace($CONFIG['thumb_pfx'], $CONFIG['normal_pfx'], $thumb))) {
		$thumb = $normal;
	}
	$autostart = $CONFIG['media_autostart'] == 1 ? 'true' : 'false';
	$media = (is_movie($PIC_DATA['filename'])) ? 'video' : 'audio';
	$fmt = $PIC_DATA['extension'];
	$mimetype = "$media/$fmt";
	$size = dkrn_get_display_size($PIC_DATA['pwidth'], $PIC_DATA['pheight']);
	$size_str = (is_movie($PIC_DATA['filename'])) ? 'width="'.$size['w'].'" height="'.$size['h'].'"' : '';
	$pic_html = <<<EOT
		<{$media} {$size_str} controls>
			<source src="{$media_file}" type="{$mimetype}">
			Unable to load HTML5 {$media} tag.
		</{$media}>
EOT;
	return $pic_html;
}

function h5p_is_supported_ext($ext) {
	return in_array($ext, array(
		'mp4',
		'webm',
		'ogg',
		'mp3',
		'wav',
	));
}

?>

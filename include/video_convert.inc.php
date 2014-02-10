<?php
# To drop this file into the shared dir and keep it accessible,
# copy the following lines to 'video_convert.php' at the site root
# after moving this file to '/mnt/shared/video_convert.php',
# remove the comment characters, and add a PHP end tag:
#<?php
#ini_set('open_basedir', ini_get('open_basedir') . ':/mnt/shared/');
#require '/mnt/shared/video_convert.php';

define('CONVERT_STATUS_MOVING', 0);
define('CONVERT_STATUS_READY', 1);
define('CONVERT_STATUS_SUCCESS', 2);
define('CONVERT_STATUS_FAILURE', 3);

function dkrn_set_convert_status($cid, $status, $msg = '') {
	global $CONFIG;
	$msg = mysql_real_escape_string($msg);
	$errstr = ($status === CONVERT_STATUS_FAILURE) ? ", error_message='$msg'" : '';
	cpg_db_query("UPDATE {$CONFIG['TABLE_CONVERSIONS']} SET
status='$status'$errstr WHERE cid='$cid'");
}

function dkrn_get_video_info($file) {
	exec('ffprobe '. $file . ' 2>&1 | grep \'Stream #\\|Duration:\'', $output);
	if (empty($output)) {
		return false;
	}
	$mainline = $output[0];
	$matches = array();
	preg_match('/Duration: ([0-9]+):([0-9]+):([0-9]+\\.[0-9]+), start: ([0-9.]+), bitrate: ([0-9]+)/', $mainline, $matches);
	$info = array(
		'path' => $file,
		'duration' => array(
			'hours' => $matches[1] += 0,
			'minutes' => $matches[2] += 0,
			'seconds' => $matches[3] += 0.0,
		),
		'start' => $matches[4] += 0.0,
		'bitrate' => $matches[5] += 0,
		'streams' => array('video' => array(), 'audio' => array(), 'data' => array(), 'other' => array()),
	);
	for ($i = 1; $i < count($output); $i++) {
		$line = $output[$i];
		$matches = array();
		preg_match('/Stream #(\d+):(\d+)(?:\((...)\))?: ([A-Za-z]+):/', $line, $matches);
		$major = $matches[1] += 0;
		$minor = $matches[2] += 0;
		$stream_language = $matches[3];
		$stream_type = $matches[4];
		if ($stream_type === 'Video') {
			preg_match('/Video: ([^,]+), ([^,]+), (\d+)x(\d+)(?:[^,]+)?, (?:(\d+) kb\\/s, )?(?:[0-9.]+ fps, )?([0-9.]+) tbr/', $line, $matches);
			$codec = explode(' ', $matches[1], 2);
			$stream = array(
				'codec'		=>	array(
					'name'		=>	$codec[0],
					'info'		=>	$codec[1],),

				'color_profile'	=>	$matches[2],
				'size'		=>	array(
					'width' 	=>	$matches[3] + 0,
					'height'	=>	$matches[4] + 0,),

				'fps'		=>	$matches[6],
			);
			if (!empty($matches[5])) {
				$stream['bitrate'] = $matches[5] + 0;
			}
			$stream_index = 'video';
		} else if ($stream_type === 'Audio') {
			preg_match('/Audio: ([^,]+), (\d+) Hz, ([^,]+), ([^,]+), (\d+) kb\\/s/', $line, $matches);
			$codec = explode(' ', $matches[1], 2);
			$stream = array(
				'codec'		=>	array(
					'name'		=>	$codec[0],
					'info'		=>	$codec[1],),

				'sampling_rate'	=>	$matches[2] + 0,
				'channels'	=>	$matches[3],
				'sample_format'	=>	$matches[4],
				'bitrate'	=>	$matches[5] + 0,
			);
			$stream_index = 'audio';
		} else if ($stream_type === 'Data') {
			preg_match('/Data: ([^,]+), (\d+) kb\\/s/', $line, $matches);
			$stream = array();
			if (!empty($matches[1])) {
				$stream['codec'] = $matches[1];
			}
			if (!empty($matches[2])) {
				$stream['bitrate'] = $matches[2] + 0;
			}
			$stream_index = 'data';
		} else {
			preg_match('/' . $stream_type . ': (.*)/', $line, $matches);
			$stream = array(
				'type'		=>	$stream_type,
				'info'		=>	$matches[1],
			);
			$stream_index = 'other';
		}
		if (!empty($stream_language)) {
			$stream['language'] = $stream_language;
		}
		$stream['id'] = $major . ':' . $minor;
		$info['streams'][$stream_index][] = $stream;
	}
	return $info;
}

// $file - full path to file to be converted
// $target_dir - full path to output file
function dkrn_convert_video_file($file, $target_file, $extra_options = '', $video_index = 0, $audio_index = 0) {
	$info = dkrn_get_video_info($file);
	$video_id = $info['streams']['video'][$video_index]['id'];
	$audio_id = $info['streams']['audio'][$audio_index]['id'];
	$prgm_options = '-y'; // auto-overwrite existing files
	$options = "-map $video_id -map $audio_id";
	if ($video['codec']['name'] == 'h264') {
		$options .= ' -c:v copy';
	} else {
		$options .= ' -c:v h264';
	}
	if ($audio['codec']['name'] == 'aac') {
		$options .= ' -c:a copy';
	} else {
		$options .= ' -c:a aac -strict -2';
	}
	$cmd = "ffmpeg $prgm_options -i $file $options $extra_options $target_file 2>&1";
	exec($cmd, $output);
	return array($cmd, implode("\n", $output));
}

function dkrn_get_all_info($in_dir) {
	$files = scandir($in_dir);
	foreach ($files as $f) {
		$full_path = $in_dir . '/' . $f;
		$ext = pathinfo($f, PATHINFO_EXTENSION);
		if (!is_dir($full_path) && $ext != 'jpg' && $ext != 'php') {
			echo '<h3>' . $f . '</h3>';
			$info = dkrn_get_video_info($in_dir . '/' . $f);
			echo '<pre>';
			print_r($info);
			echo '</pre>';
		}
	}
	
}

function dkrn_make_video_thumb($video_path) {
	/* we don't use the config setting for video thumb size because video
	thumbs are used as the preview image for player, and thus must be much
	larger.
	VGA is sufficient for now, but... */
	// TODO: Add the video thumbnail size as an option in the config panel
	//$VIDEO_THUMB_SIZE = 640; // max size of any dimension of vid thumbnail
	// FURTHER TODO Would be to make the filmstrip respect the original size;
	// it makes thumbs super big.
	global $CONFIG;

	$VIDEO_THUMB_SIZE = $CONFIG['thumb_width'];

	$info = dkrn_get_video_info($video_path);
	$hrs = $info['duration']['hours'];
	$mns = $info['duration']['minutes'];
	$scs = $info['duration']['seconds'];
	$w = $info['streams']['video'][0]['size']['width'];
	$h = $info['streams']['video'][0]['size']['height'];
	$total_secs = ($hrs * 3600) + ($mns * 60) + floor($scs);
	
	// take thumbnail from halfway through movie
	$shot_time = floor($total_secs / 2);
	// get the formatted timestring
	$hrs = str_pad(floor($shot_time / 3600), 2, '0', STR_PAD_LEFT);
	$mns = str_pad(floor(($shot_time % 3600) / 60), 2, '0', STR_PAD_LEFT);
	$scs = str_pad(floor((($shot_time % 3600) % 60)), 2, '0', STR_PAD_LEFT);
	$time = "$hrs:$mns:$scs";
	
	// calc formatted size
	if (max($w, $h) > $VIDEO_THUMB_SIZE) {
		$factor = $VIDEO_THUMB_SIZE / max($w, $h);
		$w = round($w * $factor);
		$h = round($h * $factor);
	}
	$size = $w.'x'.$h;

	// get thumb's name
	$basename = basename($video_path);
	$filename = mb_substr($basename, 0, mb_strrpos($basename, '.'));
	$thumb_path = dirname($video_path) . '/' . $CONFIG['thumb_pfx'] . $filename . '.jpg';
	
	$cmd = "ffmpeg -ss $time -an -i $video_path -an -r 1 -vframes 1 -s $size -y $thumb_path 2>&1";
	exec($cmd, $output);
	return $thumb_path;
	//return array($cmd, implode("\n", $output));
}

function dkrn_rename_thumbnail($new_file, $old_file, $dir) {
	global $CONFIG;
	$exts = array('gif', 'jpg', 'png');
	$thumb_base = $CONFIG['fullpath'] . $dir . $CONFIG['thumb_pfx'] . $old_file . '.';
	$thumb = '';
	foreach ($exts as $ext) {
		if (file_exists($thumb_base . $ext)) {
			$thumb = $thumb_base . $ext;
		}
	}
	if (empty($thumb)) {
		return false;
	}
	$thumb_ext = pathinfo($thumb, PATHINFO_EXTENSION);
	$old_thumb = pathinfo($thumb, PATHINFO_BASENAME);
	$new_thumb = $CONFIG['thumb_pfx'] . $new_file . '.' . $thumb_ext;
	$old_thumb_path = $CONFIG['fullpath'] . $dir . $old_thumb;
	$new_thumb_path = $CONFIG['fullpath'] . $dir . $new_thumb;
	if(rename($old_thumb_path, $new_thumb_path)) {
		return $new_thumb_path;
	} else {
		return false;
	}
}

?>

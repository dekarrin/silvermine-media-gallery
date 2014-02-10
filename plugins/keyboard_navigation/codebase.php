<?php
/**************************************************
  Coppermine 1.5.x Plugin - keyboard_navigation
  *************************************************
  Copyright (c) 2009-2012 eenemeenemuu
  *************************************************
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 3 of the License, or
  (at your option) any later version.
  ********************************************
  $HeadURL: https://coppermine.svn.sourceforge.net/svnroot/coppermine/branches/cpg1.5.x/plugins/keyboard_navigation/codebase.php $
  $Revision: 8493 $
  $LastChangedBy: eenemeenemuu $
  $Date: 2012-09-05 17:00:46 +0200 (Mi, 05 Sep 2012) $
  **************************************************/

if (!defined('IN_COPPERMINE')) die('Not in Coppermine...');

if (defined('DISPLAYIMAGE_PHP')) {
    $thisplugin->add_action('page_start', 'keyboard_navigation_displayimage');
}

if (defined('THUMBNAILS_PHP')) {
    $thisplugin->add_action('page_start', 'keyboard_navigation_thumbnails');
}

function keyboard_navigation_displayimage() {
	global $superCage, $CONFIG;
	$pic = $superCage->get->getInt('pid');
	$result = cpg_db_query("SELECT f.content FROM {$CONFIG['TABLE_PICTURES']} AS p
			INNER JOIN {$CONFIG['TABLE_FILETYPES']} AS f
			ON LOWER(SUBSTRING_INDEX(p.filename, '.', -1)) = LOWER(f.extension)
			WHERE p.pid = '$pic'");
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
	$type = $row['content'];
	if ($type != 'movie') {
		js_include('plugins/keyboard_navigation/keydown_displayimage.js');
	}
}

function keyboard_navigation_thumbnails() {
    js_include('plugins/keyboard_navigation/keydown_thumbnails.js');
}

?>
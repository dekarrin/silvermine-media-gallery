<?php
/*****************
 File added to the Coppermine Photo Gallery as a
 DEKKY MOD START - media capture.
******************/

define('IN_COPPERMINE', true);
define('MEDIACAPTURE_PHP', true);

require('include/init.inc.php');

if (!GALLERY_ADMIN_MODE) {
	cpg_die(ERROR, $lang_errors['access_denied'], __FILE__, __LINE__);
}


//------------------------
// Page-specific functions
//------------------------

function dkrn_table_header() {
	global $lang_media_capture_php;
	echo "<tr><th>{$lang_media_capture_php['table_title_title']}</th><th>{$lang_media_capture_php['table_author_title']}</th><th>{$lang_media_capture_php['table_url_title']}</th><th>&nbsp;</th></tr>";
}


/**
 * Main code
 */
pageheader($lang_media_capture_php['page_title']);

if ($superCage->post->keyExists('insert')) {
	$titles_matches = $superCage->post->getMatched('title', '/^[\w\s -]+/');
	$authors_matches = $superCage->post->getMatched('author', '/^[\w\s -]+/');
	$url = $superCage->post->getEscaped('url');
	if ($titles_matches !== false && $authors_matches !== false) {
		$title = $titles_matches[0];
		$author = $authors_matches[0];
	} else {
		cpg_die(ERROR, $lang_errors['param_missing'], __FILE__, __LINE__);
	}
	$query = "INSERT INTO {$CONFIG['TABLE_CAPTURES']} (title, author, url) VALUES ('$title', '$author', '$url');";
	$result = cpg_db_query($query);
	mysql_free_result($result);
	msg_box($lang_common['infomation'], sprintf($lang_media_capture_php['msg_item_added'], $title));

} else if ($superCage->post->keyExists('modify')) {
	$type = $superCage->post->getAlpha('operation');

	if ($type === $lang_media_capture_php['form_edit_btn']) {
			$titles_matches = $superCage->post->getMatched('title', '/^[\w\s -]+/');
		$authors_matches = $superCage->post->getMatched('author', '/^[\w\s -]+/');
		$url = $superCage->post->getEscaped('url');
		if ($titles_matches !== false && $authors_matches !== false) {
			$title = $titles_matches[0];
			$author = $authors_matches[0];
		} else {
			cpg_die(ERROR, $lang_errors['param_missing'], __FILE__, __LINE__);
		}
		$cid = $superCage->post->getInt('cid');
		$query = "UPDATE {$CONFIG['TABLE_CAPTURES']} SET title='$title', author='$author', url='$url' WHERE cid='$cid';";
		$r = cpg_db_query($query);
		mysql_free_result($r);
		msg_box($lang_common['information'], sprintf($lang_media_capture_php['msg_item_edited'], $title));

	} else if ($type === $lang_media_capture_php['form_del_btn']) {
		$cid = $superCage->post->getInt('cid');
		$titles_matches = $superCage->post->getMatched('title', '/^[\w\s -]+/');
		if ($title_matches !== false) {
			$title = $titles_matches[0];
		} else {
			cpg_die(ERROR, $lang_errors['param_missing'], __FILE__, __LINE__);
		}
		$query = "DELETE FROM {$CONFIG['TABLE_CAPTURES']} WHERE cid='$cid'";
		$r = cpg_db_query($query);
		mysql_free_result($r);
		msg_box($lang_common['information'], sprintf($lang_media_capture_php['msg_item_deleted'], $title));
	}
}

$captures = cpg_db_fetch_rowset(cpg_db_query("SELECT * FROM {$CONFIG['TABLE_CAPTURES']}"));

if ($superCage->post->keyExists('export')) {
	$dl_cfg = '';
	foreach ($captures as $cap) {
		$author = strtolower(preg_replace('/[^\w]/', '_', $cap['author']));
		$title = strtolower(preg_replace('/[^\w]/', '_', $cap['title']));
		$url = $cap['url'];
		$dl_cfg .= "$author-$title $url\n";
	}
	file_put_contents('manga_downloader/manga.cfg', $dl_cfg);
	`manga_downloader/download_manga.sh > logs/media_capture.log 2>&1 & echo $!`;
	cpg_db_query('DELETE FROM '.$CONFIG['TABLE_CAPTURES'].' WHERE 1');
	msg_box($lang_common['information'], $lang_media_capture_php['capture_started']);
	$captures = cpg_db_fetch_rowset(cpg_db_query("SELECT * FROM {$CONFIG['TABLE_CAPTURES']}"));
}

echo '<form action="'.$CPG_PHP_SELF.'" method="post"><input type="hidden" name="insert" value="1">';
starttable('100%');
dkrn_table_header();
echo '<tr><td width="25%"><input type="text" name="title" /></td><td width="25%"><input type="text" name="author" /></td><td width="25%"><input type="text" name="url" /></td><td width="25%"><input type="submit" value="'.$lang_media_capture_php['form_add_btn'].'" /></td></tr>';
endtable();
echo '</form><hr />';

exec('tac manga_downloader/status.txt', $status);

echo '<textarea style="height:100px;width:600px" id="status">';
foreach ($status as $line) {
	echo $line . "\n";
}
echo '</textarea><hr /><script type="text/javascript">
function on_status_data(data, status, jqXHR) {
	$(\'#status\').val(data);
}

function get_status_data() {
	$.get(\'dl_status.php\', null, on_status_data);
}

setInterval(get_status_data, 500);
</script>';

if (count($captures) > 0) {
	echo '<form action="'.$CPG_PHP_SELF.'" method="post"><input type="hidden" name="export" value="1" /><input type="submit" value="'.$lang_media_capture_php['capture_btn'].'" /></form><hr />';

	foreach ($captures as $cap) {
		echo '<form action="'.$CPG_PHP_SELF.'" method="post"><input type="hidden" name="modify" value="1">';
		starttable('100%');
		echo '<tr><td width="25%"><input type="text" name="title" value="'.$cap['title'].'" /></td><td width="25%"><input type="text" name="author" value="'.$cap['author'].'" /></td><td width="25%"><input type="text" name="url" value="'.$cap['url'].'" /><input type="hidden" name="cid" value="'.$cap['cid'].'" /></td><td width="25%"><input type="submit" name="operation" value="'.$lang_media_capture_php['form_edit_btn'].'" /><input type="submit" name="operation" value="'.$lang_media_capture_php['form_del_btn'].'" /></td></tr>';
		endtable();
		echo '</form>';
	}
}

pagefooter();
?>

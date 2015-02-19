<?php
define('IN_COPPERMINE', true);
define('MISSSING_PHP', true);

require('include/init.inc.php');

if (!GALLERY_ADMIN_MODE) {
	cpg_die(ERROR, $lang_errors['access_denied'], __FILE__, __LINE__);
}

//------------------------
// Page-specific functions
//------------------------

/**
 * Main code
 */
pageheader($lang_missing_php['page_title']);

pagefooter();
?>

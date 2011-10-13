<?php
	global $CONFIG;
	//error_log("DOKUWIKI_OPEN:".$CONFIG->pluginspath.'dokuwiki/lib/dokuwiki/'.$vars['page']);
	$destfile = $CONFIG->pluginspath.'dokuwiki/lib/dokuwiki/'.$vars['page'];
	if (file_exists($destfile))
 	       include($destfile);
?>

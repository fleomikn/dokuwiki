<?php

$group = elgg_get_page_owner_entity();
if ($group->dokuwiki_enable  == 'yes' && $group->dokuwiki_frontsidebar_enable  == 'yes') {
	
	$all_link = elgg_get_site_url().'dokuwiki/'.$group->guid;
	$all_text = elgg_echo('viewall');
	$all_link = "<a href=\"$all_link\">$all_text</a>";
	
	echo elgg_view('groups/profile/module', array(
		'title' => elgg_echo('dokuwiki:group'),
		'content' => $none,
		//'all_link' => $all_link,
	));
	
	set_input("inline_sidebar", true);
	set_input("inline_page", false);
	echo dokuwiki_page_handler(array($group->guid));
 }

?>

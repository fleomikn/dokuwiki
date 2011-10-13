<?php

$group = elgg_get_page_owner_entity();
$offset = (int)get_input("offset",0);
if ($group->dokuwiki_enable  == 'yes' && $group->dokuwiki_frontpage_enable  == 'yes') {
	
	set_input("inline_page", true);
	
	$all_link = elgg_get_site_url().'dokuwiki/'.$group->guid;
	$all_text = elgg_echo('viewall');
	$all_link = "<a href=\"$all_link\">$all_text</a>";	
	
	echo elgg_view('groups/profile/module', array(
		'title' => elgg_echo('dokuwiki:group'),
		'content' => $none,
		'all_link' => $all_link,
	));
	dokuwiki_page_handler(array($group->guid));
}

?>

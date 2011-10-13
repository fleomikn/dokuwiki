<?php
	$offset = (int)get_input('offset', 0);
	$title = elgg_echo("dokuwiki:title");
	$body = elgg_view_title($title);
        $objects = elgg_list_entities(array('subtype'=>'dokuwiki', 'offset'=>$offset, 'types'=>'object','full_view'=>false));
        $body .= $objects;
        $body = elgg_view_layout('two_column_left_sidebar', '', $body, $area3);

        // Finally draw the page
        echo page_draw($title, $body);


?>

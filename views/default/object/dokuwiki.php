<?php
	$entity = $vars['entity'];
	$owner_guid = $entity->container_guid;
	$owner = get_entity($owner_guid);
	$icon = elgg_view(
                        "graphics/icon", array(
                        'entity' => $owner,
                        'size' => 'small',
                  )
                );

	if ($vars['full_view']) {
		//
	}
	elseif ($owner) {
		$directory = $CONFIG->dataroot."wikis/".$owner_guid."/pages";
		$filecount = count(glob("" . $directory . "/*"));
		$body = "<a href='".$vars['url']."pg/dokuwiki/".$owner_guid."'>".sprintf(elgg_echo('dokuwiki:wikifrom'),$owner->name)." (".sprintf(elgg_echo("dokuwiki:pages"), $filecount).")</a>";
		echo elgg_view_listing($icon, $body);
	}
	else {
		// shouldn't show wikis from groups you can't see :-P
		echo elgg_view_listing("", "");
	}
?>

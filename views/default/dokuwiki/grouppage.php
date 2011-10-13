<?php

$group = page_owner_entity();
$offset = (int)get_input("offset",0);
if ($group->dokuwiki_enable  == 'yes' && $group->dokuwiki_frontpage_enable  == 'yes') {
  echo '<div class="contentWrapper">';
  set_input("inline_page", true);
  echo elggdokuwiki_page_handler(array($group->guid));
  echo '</div>';
 $count = elgg_get_entities(array('offset'=>$offset,'full_view'=>FALSE,'types'=>'group','container_guid'=>$group->getGUID(),'count'=>TRUE));
}

?>

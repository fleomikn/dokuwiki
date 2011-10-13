<?php

$group = page_owner_entity();
if ($group->dokuwiki_enable  == 'yes' && $group->dokuwiki_frontsidebar_enable  == 'yes') {
  set_input("inline_sidebar", true);
  set_input("inline_page", false);
  echo "<div id='group_pages_widget'>";
  echo elggdokuwiki_page_handler(array($group->guid));
  echo "</div>";
 }

?>

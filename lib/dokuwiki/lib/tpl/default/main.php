<?php
/**
 * DokuWiki Default Template
 *
 * This is the template you need to change for the overall look
 * of DokuWiki.
 *
 * You should leave the doctype at the very top - It should
 * always be the very first line of a document.
 *
 * @link   http://dokuwiki.org/templates
 * @author Andreas Gohr <andi@splitbrain.org>
 */

// must be run from within DokuWiki
if (!defined('DOKU_INC')) die();


$sidebar_inline = get_input("inline_sidebar");
$page_inline = get_input("inline_page");

if (empty($sidebar_inline)) {
ob_start();
include(dirname(__FILE__).'/main_index.php');
$content = ob_get_clean();
}

if (empty($page_inline)) {
// include functions that provide sidebar functionality
@require_once(dirname(__FILE__).'/tplfn_sidebar.php');
ob_start();
include(dirname(__FILE__).'/sidebar.php');
$sidebar = ob_get_clean();
}

if (empty($sidebar_inline) && empty($page_inline)) {
	$body = elgg_view_layout('two_column_left_sidebar', '', $content, $sidebar);
	echo page_draw("dokuwiki",$body);
}
else {
	$all_link = elgg_get_site_url().'dokuwiki/'.elgg_get_page_owner_guid();
	$all_text = elgg_echo('link:view:all');
	$all_link = "<a href=\"$all_link\">$all_text</a>";

	echo elgg_view('groups/profile/module', array(
		'title' => elgg_echo('dokuwiki:group'),
		'content' => $content . $sidebar,
		'all_link' => $all_link,
	));
}


//echo $content;

?>

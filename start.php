<?php
/**
         * Elgg dokuwiki plugin
         * 
         * @package
         * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
         * @author lorea
         * @copyright lorea
         * @link http://lorea.cc
         */

	function current_dokuwiki_entity($create = true) {
		$page_owner = page_owner();
		$user = get_loggedin_user();
		//error_log($page_owner->guid);
		//error_log($user->guid);
		if (!$page_owner)
			$page_owner = 0;
		$entities = elgg_get_entities(array('types' => 'object', 'subtypes' => 'dokuwiki', 'limit' => 1, 'owner_guid' => $page_owner));
		if ($entities) {
			$doku = $entities[0];
			return $doku;
		}
		elseif ($user && $create) {
			elgg_set_ignore_access(true);
			$newdoku = new ElggObject();
			$newdoku->access_id = ACCESS_PUBLIC;
			$newdoku->owner_guid = $page_owner;
			$newdoku->subtype = 'dokuwiki';
			$newdoku->container_guid = $page_owner;
			$newdoku->save();
			$acl = array();
		        $acl[] = "# acl.auth.php";
		        $acl[] = '# <?php exit()?\>';
 		        $acl[] = "*               @ALL        0";
		        $acl[] = "*               @user        1";
		        $acl[] = "*               @member        8";
		        $acl[] = "*               @admin        16";
		        $acl[] = "*               @root        255";
			$newdoku->wiki_acl = implode("\n", $acl)."\n";
			elgg_set_ignore_access(false);
			return $newdoku;
		}
	}

	function elggdoku_recurse_copy($src,$dst) {
	    $dir = opendir($src);
	    @mkdir($dst);
	    while(false !== ( $file = readdir($dir)) ) {
		if (( $file != '.' ) && ( $file != '..' )) {
		    if ( is_dir($src . '/' . $file) ) {
			elggdoku_recurse_copy($src . '/' . $file,$dst . '/' . $file);
		    }
		    else {
			copy($src . '/' . $file,$dst . '/' . $file);
		    }
		}
	    }
	    closedir($dir);
	} 

	function elggdokuwiki_create_datafolder($path) {
		global $CONFIG;
		if (is_dir($path)) // if it exists must be already created
			return;
		mkdir($path, 0700, true);
		$orig = $CONFIG->pluginspath.'dokuwiki/lib/dokuwiki/data';
		elggdoku_recurse_copy($orig, $path);
		
	}

	function elggdokuwiki_page_handler($page) {
		global $CONFIG;
		if ($page[0] === "index") {
			set_context("search");
			include($CONFIG->pluginspath.'dokuwiki/index.php');
			return;
		}
		set_context("dokuwiki");
		$dokuwiki_path = $CONFIG->pluginspath.'dokuwiki/lib/dokuwiki/';
		$doku = current_dokuwiki_entity();
		if (!$doku) // can fail if there is no user and wiki doesnt exist
			forward();
		$parsed_url = parse_url($CONFIG->wwwroot.'pg/dokuwiki/');
		$url_base = $parsed_url['path'];
		if (is_numeric($page[0])) {
			$entity_guid = $page[0];
			$ent = get_entity($entity_guid);
			if (($ent && $ent instanceof ElggGroup) && $ent->dokuwiki_enable !== 'yes') {
				// wiki not activated for this group. bail out.
				forward();
			}
			if ($ent && (/*$ent instanceof ElggUser ||*/ $ent instanceof ElggGroup)) {
				set_page_owner($entity_guid);
				$data_path = $CONFIG->dataroot.'wikis/'.$entity_guid;
			} else {
				// can't see the group
				forward();
			}
			$page = array_slice($page, 1); // pop first element
			define('DOKU_REL', $url_base.$entity_guid."/");
			define('DOKU_BASE', $url_base.$entity_guid."/");
			define('DOKU_URL', $CONFIG->wwwroot.'pg/dokuwiki/'.$entity_guid."/");

		}
		else {
			$data_path = $CONFIG->dataroot.'wiki';
			define('DOKU_REL', $url_base);
			define('DOKU_BASE', $url_base);
			define('DOKU_URL', $CONFIG->wwwroot.'pg/dokuwiki/');
		}
		define('DOKU_INC', $dokuwiki_path);
		define('DOKU_MEDIA', $CONFIG->wwwroot.'mod/dokuwiki/lib/dokuwiki/');
		define('DOKU_CONF', $dokuwiki_path."conf/");

		elggdokuwiki_create_datafolder($data_path);
		define('DOKU_ELGGDATA',$data_path);
		if (empty($page) || (count($page)==1 && $page[0] == 'acl')) {
			$page = array('doku.php');
		}
		else if ((count($page)==1 && $page[0] == 'usermanager')) {
			$page = array('doku.php');
		}
		else if ((count($page)==1 && $page[0] == 'plugin')) {
			$page = array('doku.php');
		}
		else if ((count($page)==1 && $page[0] == 'config')) {
			$page = array('doku.php');
		}
		else if ((count($page)==1 && $page[0] == 'revert')) {
			$page = array('doku.php');
		}
		else if ((count($page)==1 && $page[0] == 'popularity')) {
			$page = array('doku.php');
		}
		if (empty($page) || (count($page)==1 && !$page[0])) {
			$page = array('doku.php');
		}
		$_SERVER['PHP_AUTH_USER'] = get_loggedin_user()->username;
		$_SERVER['PHP_AUTH_PW'] = get_loggedin_user()->password;
		if (count($page) == 1) {
			$doku_body = elgg_view('dokuwiki/index',array('page'=>$page[0]));
			echo $doku_body;
		}
		else {
			// avoid inclusion over root
			$dest = realpath($dokuwiki_path.implode("/",$page));
			if (strpos($dest, $dokuwiki_path) == 0)
				$doku_body = elgg_view('dokuwiki/index',array('page'=>implode("/",$page)));
			echo $doku_body;
		}
		return;
	}

	function elggdokuwiki_pagesetup() {
		global $CONFIG;
		if (page_owner()) {
			$page_owner = page_owner_entity();
			if ($page_owner instanceof ElggGroup && $page_owner->dokuwiki_enable == 'yes')
				$title = elgg_echo("dokuwiki:groupwiki");
			if ($title && get_context() == "groups") {
				add_submenu_item($title, $CONFIG->wwwroot . "pg/dokuwiki/" . page_owner());
			}
		}

	}

	function elggdokuwiki_icon_hook($hook, $entity_type, $returnvalue, $params) {
		global $CONFIG;
		if ($hook == 'entity:icon:url' && $params['entity']->getSubtype() == 'dokuwiki') {
			$owner = get_entity($params['entity']->container_guid);
			if ($owner)
				return $owner->getIcon($params['size']);
		}
		return $returnvalue;
	}

	function elggdokuwiki_url($entity) {
		global $CONFIG;
		return $CONFIG->url . "pg/dokuwiki/".$entity->container_guid;
	}

 	function elggdokuwiki_init(){
			global $CONFIG;
			register_entity_type('object','dokuwiki');
			register_plugin_hook('entity:icon:url', 'object', 'elggdokuwiki_icon_hook');
			register_entity_url_handler('elggdokuwiki_url','object', 'dokuwiki');
		        register_elgg_event_handler('pagesetup','system','elggdokuwiki_pagesetup');

			register_page_handler('dokuwiki','elggdokuwiki_page_handler');
	                add_group_tool_option('dokuwiki',elgg_echo('groups:enabledokuwiki'),false);
	                add_group_tool_option('dokuwiki_frontsidebar',elgg_echo('groups:enabledokuwiki_frontsidebar'),false);
	                add_group_tool_option('dokuwiki_frontpage',elgg_echo('groups:enabledokuwiki_frontpage'),false);
			elgg_extend_view('groups/forum_latest','dokuwiki/grouppage');
			elgg_extend_view('groups/left_column','dokuwiki/sidebar');
			/*if (isloggedin()) {
				add_menu(elgg_echo('dokuwiki'), $CONFIG->wwwroot . "pg/dokuwiki/index");
			}*/
                        elgg_extend_view("metatags", "dokuwiki/metatags");
	}

register_elgg_event_handler('init','system','elggdokuwiki_init');

?>

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
		if (is_dir($path)) // if it exists must be already created
			return;
		mkdir($path, 0700, true);
		$orig = elgg_get_plugins_path().'dokuwiki/lib/dokuwiki/data';
		elggdoku_recurse_copy($orig, $path);
		
	}

	/**
	 * Dispatches dokuwiki pages.
	 * URLs take the form of
	 *  All wikis:       dokuwiki/all
	 *  Group wiki:      dokuwiki/<guid>
	 *
	 * @param array $page
	 * @return NULL
	 */
	function dokuwiki_page_handler($page) {
		if ($page[0] === "all") {
			set_context("search");
			include(elgg_get_plugins_path().'dokuwiki/index.php');
			return;
		}
		set_context("dokuwiki");
		$dokuwiki_path = elgg_get_plugins_path().'dokuwiki/lib/dokuwiki/';
		$doku = current_dokuwiki_entity();
		if (!$doku) // can fail if there is no user and wiki doesnt exist
			forward();
		$parsed_url = parse_url(elgg_get_site_url().'dokuwiki/');
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
				$data_path = elgg_get_data_path().'wikis/'.$entity_guid;
			} else {
				// can't see the group
				forward();
			}
			$page = array_slice($page, 1); // pop first element
			define('DOKU_REL', $url_base.$entity_guid."/");
			define('DOKU_BASE', $url_base.$entity_guid."/");
			define('DOKU_URL', elgg_get_site_url().'dokuwiki/'.$entity_guid."/");

		}
		else {
			$data_path = elgg_get_data_path().'wiki';
			define('DOKU_REL', $url_base);
			define('DOKU_BASE', $url_base);
			define('DOKU_URL', elgg_get_site_url().'dokuwiki/');
		}
		define('DOKU_INC', $dokuwiki_path);
		define('DOKU_MEDIA', elgg_get_site_url().'mod/dokuwiki/lib/dokuwiki/');
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

	/**
	 * Add a menu item to an ownerblock
	 */
	function dokuwiki_owner_block_menu($hook, $type, $return, $params) {
		if (elgg_instanceof($params['entity'], 'group') && $params['entity']->dokuwiki_enable != "no") {
			$url = "dokuwiki/{$params['entity']->guid}/";
			$item = new ElggMenuItem('dokuwiki', elgg_echo('dokuwiki:group'), $url);
			$return[] = $item;
		}
		return $return;
	}

	function elggdokuwiki_icon_hook($hook, $entity_type, $returnvalue, $params) {
		if ($hook == 'entity:icon:url' && $params['entity']->getSubtype() == 'dokuwiki') {
			$owner = get_entity($params['entity']->container_guid);
			if ($owner)
				return $owner->getIcon($params['size']);
		}
		return $returnvalue;
	}

	function elggdokuwiki_url($entity) {
		return elgg_get_url_site() . "dokuwiki/".$entity->container_guid;
	}

 	function elggdokuwiki_init(){
		
			register_entity_type('object','dokuwiki');
			register_plugin_hook('entity:icon:url', 'object', 'elggdokuwiki_icon_hook');
			register_entity_url_handler('elggdokuwiki_url','object', 'dokuwiki');

		// add blog link to
		elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'dokuwiki_owner_block_menu');

			register_page_handler('dokuwiki','dokuwiki_page_handler');
	                add_group_tool_option('dokuwiki',elgg_echo('groups:enabledokuwiki'),false);
	                add_group_tool_option('dokuwiki_frontsidebar',elgg_echo('groups:enabledokuwiki_frontsidebar'),false);
	                add_group_tool_option('dokuwiki_frontpage',elgg_echo('groups:enabledokuwiki_frontpage'),false);
			elgg_extend_view('groups/forum_latest','dokuwiki/grouppage');
			elgg_extend_view('groups/left_column','dokuwiki/sidebar');
		
		// Extending CSS
		elgg_extend_view('css/elgg', 'dokuwiki/css');
		
		// add a site navigation item
		$item = new ElggMenuItem('wiki', elgg_echo('dokuwiki:title'), 'dokuwiki/all');
		elgg_register_menu_item('site', $item);
		
                        elgg_extend_view("metatags", "dokuwiki/metatags");
	}

register_elgg_event_handler('init','system','elggdokuwiki_init');

?>

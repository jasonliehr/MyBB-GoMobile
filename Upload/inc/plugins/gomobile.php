<?php
/*
	MyBB GoMobile - Version: 1.0 Beta 4
	Based on UA Theme. Notices below.
	
	Copyright (c) 2010, Fawkes Software
	All rights reserved.

	Redistribution and use in source and binary forms, with or without modification,
	are permitted provided that the following conditions are met:

	* Redistributions of source code must retain the above copyright notice, this
	list of conditions and the following disclaimer.
	* Redistributions in binary form must reproduce the above copyright notice,
	this list of conditions and the following disclaimer in the documentation
	and/or other materials provided with the distribution.
	* Neither the name of Fawkes Software nor the names of its contributors may be
	used to endorse or promote products derived from this software without specific
	prior written permission.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
	EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
	OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
	SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
	INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
	TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
	BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
	ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Page hook, for overriding the theme as best as we can
$plugins->add_hook("global_start", "gomobile_forcetheme");

// New Reply & New Thread hooks, for determining whether or not the post is from a mobile
$plugins->add_hook("datahandler_post_insert_post", "gomobile_posts");
$plugins->add_hook("datahandler_post_insert_thread_post", "gomobile_threads");

// Showthread hooks
$plugins->add_hook("showthread_end", "gomobile_showthread");

// User CP Options
$plugins->add_hook("usercp_options_end", "gomobile_usercp_options");
$plugins->add_hook("usercp_do_options_end", "gomobile_usercp_options");

// Misc hooks
$plugins->add_hook("misc_start", "gomobile_switch_version");

// Admin hooks, for adding our control panel page, and only if we're in the ACP
if(defined("IN_ADMINCP"))
{
	$plugins->add_hook('admin_config_action_handler','gomobile_adminAction');
	$plugins->add_hook('admin_config_menu','gomobile_adminLink');
	$plugins->add_hook('admin_load','gomobile_admin');
}

// Load our custom language file
global $lang;
$lang->load("gomobile");

function gomobile_info()
{
	global $lang;

	// Plugin information
	return array(
		"name"			=> $lang->gomobile,
		"description"	=> $lang->gomobile_desc,
		"website"		=> "http://www.mybbgm.com",
		"author"		=> "MyBB GoMobile",
		"authorsite"	=> "http://www.mybbgm.com",
		"version"		=> "1.0 Beta 4",
		"compatibility" => "16*"
	);
}

function gomobile_install()
{
	global $db, $mybb, $lang;

	// Install the right database table for our database type
	switch($mybb->config['database']['type'])
	{
		case "pgsql":
			$db->query("CREATE TABLE ".TABLE_PREFIX."gomobile (
				gmtid serial,
				string varchar(120) NOT NULL default '',
				PRIMARY KEY (gmtid)
			);");
			break;
		case "sqlite":
			$db->query("CREATE TABLE ".TABLE_PREFIX."gomobile (
				gmtid INTEGER PRIMARY KEY,
				string varchar(120) NOT NULL default '')
			);");
			break;
		default:
			$db->query("CREATE TABLE ".TABLE_PREFIX."gomobile (
				gmtid int(10) unsigned NOT NULL auto_increment,
				string varchar(120) NOT NULL default '',
				PRIMARY KEY(gmtid)
			) TYPE=MyISAM;");
	}

	// Add a column to the posts & threads tables for tracking mobile posts
	$db->query("ALTER TABLE ".TABLE_PREFIX."posts ADD mobile int(1) NOT NULL default '0'");
	$db->query("ALTER TABLE ".TABLE_PREFIX."threads ADD mobile int(1) NOT NULL default '0'");

	// And another to the users table for options
	$db->query("ALTER TABLE ".TABLE_PREFIX."users ADD usemobileversion int(1) NOT NULL default '1'");

	// First, check that our theme doesn't already exist
	$query = $db->simple_select("themes", "tid", "LOWER(name) LIKE '%gomobile 1.0%'");
	if($db->num_rows($query))
	{
		// We already have the GoMobile theme installed
		$theme = $db->fetch_field($query, "tid");
	}
	else
	{
		// Import the theme for our users
		$theme = MYBB_ROOT."inc/plugins/gomobile_theme.xml";
		if(!file_exists($theme))
		{
			flash_message("Upload the GoMobile Theme XML to the plugin directory (./inc/plugins/) before continuing.", "error");
			admin_redirect("index.php?module=config/plugins");
		}

		$contents = @file_get_contents($theme);
		if($contents)
		{
			$options = array(
				'no_stylesheets' => 0,
				'no_templates' => 0,
				'version_compat' => 1,
				'parent' => 1,
				'force_name_check' => true,
			);

			require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
			$theme = import_theme_xml($contents, $options);
		}
	}

	// Get a list of default UA strings ready for insertion
	// You can also add more from your ACP
	$data_array = array(
		"iPhone",
		"iPod",
		"mobile",
		"Android",
		"Opera Mini",
		"BlackBerry",
		"IEMobile",
		"Windows Phone",
		"HTC",
		"Nokia",
		"Netfront",
		"SmartPhone",
		"Symbian",
		"SonyEricsson",
		"AvantGo",
		"DoCoMo",
		"Pre/",
		"UP.Browser"
	);

	// Insert the data listed above
	foreach($data_array as $data)
	{
		$gomobile = array(
			"gmtid" => -1,
			"string" => $db->escape_string($data)
		);

		$db->insert_query("gomobile", $gomobile);
	}
	
	// Insert the string list into the cache
	$list = $db->query("SELECT gmtid,string FROM " .TABLE_PREFIX. "gomobile");
	$stringlist = array();
	
	while($string = $db->fetch_array($list))
	{
		$stringlist[] = $db->escape_string($string['string']);
	}
	
	$db->insert_query("datacache", array("title" => "gomobile", "cache" => serialize($stringlist)));

	// Edit existing templates (shows when posts are from GoMobile)
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";

	find_replace_templatesets("postbit_posturl", '#'.preg_quote('<span').'#', '<img src="{\$mybb->settings[\'bburl\']}/images/mobile/posted_{\$post[\'mobile\']}.gif" alt="" width="{\$post[\'mobile\']}8" height="{\$post[\'mobile\']}8" title="Posted from GoMobile (when icon is displayed)" style="vertical-align: middle;" /> '.'<span');

	// Get our settings ready
	$setting_group = array
	(
		"gid" => "NULL",
		"name" => "gomobile",
		"title" => "GoMobile Settings",
		"description" => "Configures options for MyBB GoMobile.",
		"disporder" => "1",
		"isdefault" => "0",
	);

	$gid = $db->insert_query("settinggroups", $setting_group);
	$dispnum = 0;

	$settings = array(
		"gomobile_mobile_name" => array(
			"title"			=> $lang->gomobile_settings_mobile_name_title,
			"description"	=> $lang->gomobile_settings_mobile_name,
			"optionscode"	=> "text",
			"value"			=> $db->escape_string($mybb->settings['bbname']),
			"disporder"		=> ++$dispnum
		),
		"gomobile_theme_id" => array(
			"title"			=> $lang->gomobile_settings_theme_id_title,
			"description"	=> $lang->gomobile_settings_theme_id,
			"optionscode"	=> "text",
			"value"			=> $theme,
			"disporder"		=> ++$dispnum
		),
		"gomobile_permstoggle" => array(
			"title"			=> $lang->gomobile_settings_permstoggle_title,
			"description"	=> $lang->gomobile_settings_permstoggle,
			"optionscode"	=> "yesno",
			"value"			=> 0,
			"disporder"		=> ++$dispnum
		),
		"gomobile_homename" => array(
			"title"			=> $lang->gomobile_settings_homename_title,
			"description"	=> $lang->gomobile_settings_homename,
			"optionscode"	=> "text",
			"value"			=> $db->escape_string($mybb->settings['homename']),
			"disporder"		=> ++$dispnum
		),
		"gomobile_homelink" => array(
			"title"			=> $lang->gomobile_settings_homelink_title,
			"description"	=> $lang->gomobile_settings_homelink,
			"optionscode"	=> "text",
			"value"			=> $db->escape_string($mybb->settings['homeurl']),
			"disporder"		=> ++$dispnum
		)
	);

	// Insert the settings listed above
	foreach($settings as $name => $setting)
	{
		$setting['gid'] = $gid;
		$setting['name'] = $name;

		$db->insert_query("settings", $setting);
	}

	rebuild_settings();
}

function gomobile_is_installed()
{
    global mybb;

	// Checks if GoMobile has made it through all necessary installation steps
    if(isset($mybb->settings['gomobile_homelink']))
    {
        return true;
    }

    return false;
} 

function gomobile_uninstall()
{
	global $db;

	// Drop the GoMobile table
	$db->drop_table("gomobile");

	// Clean up the users, posts & threads tables
	$db->query("ALTER TABLE ".TABLE_PREFIX."posts DROP COLUMN mobile");
	$db->query("ALTER TABLE ".TABLE_PREFIX."threads DROP COLUMN mobile");
	$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP COLUMN usemobileversion");

	// Can the template edits we made earlier
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";

	find_replace_templatesets("postbit_posturl", '#'.preg_quote('<img src="{\$mybb->settings[\'bburl\']}/images/mobile/posted_{\$post[\'mobile\']}.gif" alt="" width="{\$post[\'mobile\']}8" height="{\$post[\'mobile\']}8" title="Posted from GoMobile (when icon is displayed)" style="vertical-align: middle;" /> '.'').'#', '', 0);

	// Remove the GoMobile cache
	$db->query("DELETE FROM ".TABLE_PREFIX."datacache WHERE title='gomobile'");
	
	// Lastly, remove the settings for GoMobile
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='gomobile'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_header_text'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_theme_id'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_permstoggle'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_homename'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_homelink'");
}

function gomobile_forcetheme()
{
	global $db, $mybb, $plugins, $cache;

	if($mybb->session->is_spider == false)
	{
		// Force some changes to our footer but only if we're not a bot
		$GLOBALS['gmb_orig_style'] = intval($mybb->user['style']);
		$GLOBALS['gmb_post_key'] = md5($mybb->post_code);

		$plugins->add_hook("global_end", "gomobile_forcefooter");
	}

	// Has the user chosen to disable GoMobile completely?
	if(isset($mybb->user['usemobileversion']) && $mybb->user['usemobileversion'] == 0 && $mybb->user['uid'] && !$mybb->cookies['use_dmv'])
	{
		return false;
	}

	// Has the user temporarily disabled GoMobile via cookies?
	if($mybb->cookies['no_use_dmv'] == "1")
	{
		return false;
	}
	
	// Is the admin using theme permission settings?
	// If so, check them
	if($mybb->settings['gomobile_permstoggle'] == 1) {
		// Fetch the theme permissions from the database
		$tquery = $db->simple_select("themes", "*", "tid like '{$mybb->settings['gomobile_theme_id']}'");
		$tperms = $db->fetch_field($tquery, "allowedgroups");
		if($tperms != "all") {
			$canuse = explode(",", $tperms);
		}
	
		// Also explode our user's additional groups
		if($mybb->user['additionalgroups']) {
			$userag = explode(",", $mybb->user['additionalgroups']);
		}
	
		// If the user doesn't have permission to use the theme...
		if($tperms != "all") {
			if(!in_array($mybb->user['usergroup'], $canuse) && !in_array($userag, $canuse)) {
				return false;
			}
		}
	}

	// Load the list of stringes from the cache
	$list = $cache->read('gomobile');

	$switch = false;
	foreach($list as $uastring)
	{
		// Run as long as there hasn't been a match yet
		if(!$switch)
		{
			// Switch to GoMobile if the UA matches our list
			if(stristr($_SERVER['HTTP_USER_AGENT'], $uastring) == TRUE)
			{
				$switch = true;
				$mybb->user['style'] = $mybb->settings['gomobile_theme_id'];
			}
		}
	}

	// Have we got this far without catching somewhere? Have we enabled mobile version?
	if($mybb->cookies['use_dmv'] == 1 && $switch == false)
	{
		$mybb->user['style'] = $mybb->settings['gomobile_theme_id'];
	}
}

function gomobile_forcefooter()
{
    global $lang, $footer, $mybb, $navbits;

	// Replace the footer, but only if the visitor isn't a bot
    $footer = str_replace("<a href=\"<archive_url>\">".$lang->bottomlinks_litemode."</a>", "<a href=\"misc.php?action=switch_version&amp;my_post_key=".$GLOBALS['gmb_post_key']."\">".$lang->gomobile_mobile_version."</a>", $footer);

    if($mybb->user['style'] == $mybb->settings['gomobile_theme_id'])
    {
        // Override default breadcrumb bbname (for mobile theme only)
        $navbits = array();
        $navbits[0]['name'] = $mybb->settings['gomobile_mobile_name'];
        $navbits[0]['url'] = $mybb->settings['bburl']."/index.php";    
    }
}

function gomobile_showthread()
{
	global $mybb, $lang, $postcount, $perpage, $thread, $pagejump, $pages, $page_location;
	
	// Display the total number of pages
	if($pages > 0) {
		$page_location = " {$lang->gomobile_of} {$pages}";
	}
	
	// If there's more than one page, display links to the first & last posts
	if($postcount > $perpage){
		$pj_template = "<div class=\"float_left\" style=\"padding-top: 12px;\">
			<a href=\"".get_thread_link($thread['tid'])."\" class=\"pagination_a\">{$lang->gomobile_jump_fpost}</a>
			<a href=\"".get_thread_link($thread['tid'], 0, 'lastpost')."\" class=\"pagination_a\">{$lang->gomobile_jump_lpost}</a>
			</div>";
		$pagejump = $pj_template;
	}
}

function gomobile_posts($p)
{
    global $mybb;

    $is_mobile = intval($mybb->input['mobile']);
	
	// Was the post sent from GoMobile?
	if($is_mobile != 1) {
		$is_mobile = 0;
	}
	else {
		$is_mobile = 1;
	}

	// If so, we're going to store it for future use
    $p->post_insert_data['mobile'] = $is_mobile;
    return $p;
} 

function gomobile_threads($p)
{
	// Exact same as above, only for threads
    global $mybb;

    $is_mobile = intval($mybb->input['mobile']);
	
	if($is_mobile != 1) {
		$is_mobile = 0;
	}
	else {
		$is_mobile = 1;
	}

    $p->post_insert_data['mobile'] = $is_mobile;
    return $p;
}

function gomobile_update_cache() {
	global $db;

	// Update the GoMobile cache
	$list = $db->query("SELECT gmtid,string FROM " .TABLE_PREFIX. "gomobile");
	$stringlist = array();
	
	while($uastring = $db->fetch_array($list))
	{
		$stringlist[] = $db->escape_string($uastring['string']);
	}
	
	$db->query("UPDATE " .TABLE_PREFIX. "datacache set cache='" .serialize($stringlist). "' WHERE title='gomobile'");
}

function gomobile_adminAction(&$action)
{
	// I'm honestly not sure what this is for...
	$action['gomobile'] = array('active' => 'gomobile');
}

function gomobile_adminLink(&$sub)
{
	global $lang;

	end($sub);

	$key = key($sub) + 10;

	$sub[$key] = array(
		'id' => 'gomobile',
		'title' => $lang->gomobile_sidemenu,
		'link' => 'index.php?module=config/gomobile'
	);
}

function gomobile_admin()
{
	global $mybb, $page, $db, $lang;
	
	if($page->active_action != 'gomobile')
	{
		return false;
	}

	$page->add_breadcrumb_item($lang->gomobile, 'index.php?module=config-gomobile');

	if($mybb->input['action'] == 'edit')
	{
		// Adding or creating a string...
		if(!isset($mybb->input['gmtid']) || intval($mybb->input['gmtid']) == 0)
		{
			flash_message($lang->gomobile_noexist, 'error');
			admin_redirect('index.php?module=config/gomobile');
		}
		else
		{
			$gmtid = intval($mybb->input['gmtid']);
		}

		if($mybb->input['save'])
		{
			// User wants to save. Grab the values for later
			$gomobile['string'] = $mybb->input['string'];

			// Did they forget to fill in the string?
			if($gomobile['string'] == '')
			{
				$error = $lang->gomobile_nostring;
			}
			else
			{
				// No? Let's save it then
				$gomobile['string'] = $db->escape_string($gomobile['string']);

				// Did they create a new one?
				if($gmtid == -1)
				{
					// Yes, so we need to add a new database row
					$db->insert_query("gomobile", $gomobile);
					
					// Update the cache
					gomobile_update_cache();
				}
				else
				{
					// No, so we just update the existing one.
					// To do: check to make sure the gmtid exists
					$db->update_query("gomobile", $gomobile, "gmtid='{$gmtid}'");
					
					// Update the cache
					gomobile_update_cache();
				}

				flash_message($lang->gomobile_saved, 'success');
				admin_redirect('index.php?module=config/gomobile');
			}
		}
		else if($mybb->input['delete'])
		{
			// Delete the string and return to the main menu
			$db->delete_query("gomobile", "gmtid='{$gmtid}'");
			
			// Update the cache
			gomobile_update_cache();

			admin_redirect('index.php?module=config/gomobile');
		}

		// If there was a problem saving earlier,
		// we've already got this stuff, and the
		// user just needs to fix it
		if(!isset($gomobile))
		{
			// If it doesn't exist yet, let's fill it out
			if($gmtid != -1)
			{
				// The user is editing an existing string, so load it
				$query = $db->simple_select("gomobile", "string", "gmtid='{$gmtid}'");
				$gomobile = $db->fetch_array($query);
			}
			else
			{
				// The user is creating a new one, so fill it with some defaults
				$gomobile['string'] = "";
			}
		}

		// If at this point $gomobile == null,
		// we tried to load a non-existant string.
		if($gomobile != null)
		{
			// At this point, though, it does exist so
			// do the edity thingy
			$page->add_breadcrumb_item($lang->gomobile_edit);
			$page->output_header($lang->gomobile);

			// Display any errors set earlier
			if(isset($error))
			{
				$page->output_inline_error($error);
			}

			// Create edit box
			$form = new Form('index.php?module=config/gomobile&amp;action=edit&amp;gmtid=' . $gmtid, 'post');
			$form_container = new FormContainer($lang->gomobile_edit);

			// Long and ugly.
			// basically ends up as title, description, form thing(name, value, extras)
			$form_container->output_row($lang->gomobile_string, $lang->gomobile_string_desc, $form->generate_text_box('string', htmlspecialchars($gomobile['string']), array('id' => 'string')));

			// Done with the box!
			$form_container->end();

			// Buttons! Buttons everywhere!
			$buttons[] = $form->generate_submit_button($lang->gomobile_save, array('name' => 'save', 'id' => 'save'));

			// If the user is creating a new one, there's no sense in
			// showing the delete button.
			if($gmtid != -1)
			{
				$buttons[] = $form->generate_submit_button($lang->gomobile_delete, array('name' => 'delete', 'id' => 'delete'));
			}

			// Show the button(s)
			$form->output_submit_wrapper($buttons);

			// And we're done!
			$form->end();
			$page->output_footer();
		}
		else
		{
			// This happens if the user tried to edit a non-existant string
			flash_message($lang->gomobile_noexist, 'error');
			admin_redirect('index.php?module=config/gomobile');
		}
	}
	else
	{
		// This is the main menu
		$page->output_header($lang->gomobile);

		// Make a box for the menu
		$table = new Table;
		$table->construct_header($lang->gomobile_string);
		$table->construct_header($lang->controls, array("class" => "align_center", "width" => 155));

		// list existing stringes
		$query = $db->simple_select("gomobile", "gmtid, string");
		while($list = $db->fetch_array($query))
		{
			// show the string
			$list['string'] = htmlspecialchars($list['string']);
			$table->construct_cell("<strong>{$list['string']}</strong>");

			// Show the edit and delete menu
			$popup = new PopupMenu("gomobile_{$list['gmtid']}", $lang->options);
			$popup->add_item($lang->gomobile_edit, "index.php?module=config/gomobile&amp;action=edit&amp;gmtid={$list['gmtid']}");
			$popup->add_item($lang->gomobile_delete, "index.php?module=config/gomobile&amp;action=edit&amp;delete=true&amp;gmtid={$list['gmtid']}");
			$table->construct_cell($popup->fetch(), array("class" => "align_center", "width" => 155));

			// Done!
			$table->construct_row();
		}

		// list 'add new string' link
		$table->construct_cell("<strong><a href=\"index.php?module=config/gomobile&amp;action=edit&amp;gmtid=-1\">{$lang->gomobile_addnew}</a></strong>");
		$table->construct_cell('');
		$table->construct_row();

		// Done!
		$table->output($lang->gomobile);
		$page->output_footer();
	}
}

function gomobile_usercp_options()
{
	global $db, $mybb, $templates, $user;

	if(isset($GLOBALS['gmb_orig_style']))
	{
		// Because we override this above, reset it to the original
		$mybb->user['style'] = $GLOBALS['gmb_orig_style'];
	}

	if($mybb->request_method == "post")
	{
		// We're saving our options here
		$update_array = array(
			"usemobileversion" => intval($mybb->input['usemobileversion'])
		);

		$db->update_query("users", $update_array, "uid = '".$user['uid']."'");
	}

	$usercp_option = '</tr><tr>
<td valign="top" width="1"><input type="checkbox" class="checkbox" name="usemobileversion" id="usemobileversion" value="1" {$GLOBALS[\'$usemobileversioncheck\']} /></td>
<td><span class="smalltext"><label for="usemobileversion">{$lang->gomobile_use_mobile_version}</label></span></td>';

	$find = '{$lang->show_codebuttons}</label></span></td>';
	$templates->cache['usercp_options'] = str_replace($find, $find.$usercp_option, $templates->cache['usercp_options']);

	// We're just viewing the page
	$GLOBALS['$usemobileversioncheck'] = '';
	if($user['usemobileversion'])
	{
		$GLOBALS['$usemobileversioncheck'] = "checked=\"checked\"";
	}
}

function gomobile_switch_version()
{
	global $db, $lang, $mybb;

	if($mybb->input['action'] != "switch_version")
	{
		return false;
	}

	$url = "index.php";
	if(isset($_SERVER['HTTP_REFERER']))
	{
		$url = htmlentities($_SERVER['HTTP_REFERER']);
	}

	if(md5($mybb->post_code) != $mybb->input['my_post_key'])
	{
		redirect($url, $lang->invalid_post_code);
	}

	if($mybb->input['do'] == "full")
	{
		my_unsetcookie("use_dmv");
		my_setcookie("no_use_dmv", 1, -1);
	}
	else
	{
		// Assume we're wanting to switch to the mobile version
		my_unsetcookie("no_use_dmv");
		my_setcookie("use_dmv", 1, -1);
	}

	$lang->load("gomobile");
	redirect($url, $lang->gomobile_switched_version);
}
?>
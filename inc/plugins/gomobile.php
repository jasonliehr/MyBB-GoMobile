<?php
/*
	MyBB GoMobile - Version: 1.0 Beta 3
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

// Get our languages loaded
global $lang;

// Run only if the user isn't updating or installing
if(!defined("INSTALL_ROOT"))
{
	// Page hook, for overriding the theme as best as we can
	$plugins->add_hook("global_start", "gomobile_forcetheme");

	// New Reply & New Thread hooks, for determining whether or not the post is from a mobile
	$plugins->add_hook("datahandler_post_insert_post", "gomobile_posts");
	$plugins->add_hook("datahandler_post_insert_thread_post", "gomobile_threads");

	// Portal hooks
	$plugins->add_hook("portal_start", "gomobile_portal_default");
	$plugins->add_hook("pro_portal_start", "gomobile_portal_pro");
	
	// Forumdisplay hooks
	$plugins->add_hook("forumdisplay_thread", "gomobile_forumdisplay");
	
	// Showthread hooks
	$plugins->add_hook("showthread_end", "gomobile_showthread");

	// User CP Options
	$plugins->add_hook("usercp_options_end", "gomobile_usercp_options");
	$plugins->add_hook("usercp_do_options_end", "gomobile_usercp_options");

	// Misc hooks
	$plugins->add_hook("misc_start", "gomobile_switch_version");

	// Admin hooks, for adding our control panel page
	$plugins->add_hook('admin_config_action_handler','gomobile_adminAction');
	$plugins->add_hook('admin_config_menu','gomobile_adminLink');
	$plugins->add_hook('admin_load','gomobile_admin');

	$lang->load("gomobile");
}

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
		"version"		=> "1.0 Beta 3",
		"compatibility" => "14*, 16*"
	);
}

function gomobile_install()
{
	global $db, $mybb;

	// Install the right database table for our database type
	switch($mybb->config['database']['type'])
	{
		case "pgsql":
			$db->query("CREATE TABLE ".TABLE_PREFIX."gomobile (
				gmtid serial,
				regex varchar(120) NOT NULL default '',
				PRIMARY KEY (gmtid)
			);");
			break;
		case "sqlite":
			$db->query("CREATE TABLE ".TABLE_PREFIX."gomobile (
				gmtid INTEGER PRIMARY KEY,
				regex varchar(120) NOT NULL default '')
			);");
			break;
		default:
			$db->query("CREATE TABLE ".TABLE_PREFIX."gomobile (
				gmtid int(10) unsigned NOT NULL auto_increment,
				regex varchar(120) NOT NULL default '',
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
			flash_message("Upload the GoMobile Theme to the plugin directory (./inc/plugins/) before continuing.", "error");
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

	// Get a list of default regexes ready for insertion
	// You can also add more from your ACP
	$data_array = array(
		"/ip[ho](.+?)mobile(.+?)safari/i",
		"/mobile/i",
		"/Android(.+?)/i",
		"/Opera Mini(.+?)/i",
		"/BlackBerry(.+?)/i",
		"/IEMobile(.+?)/i",
		"/Windows Phone(.+?)/i",
		"/HTC(.+?)/i",
		"/Nokia(.+?)/i",
		"/Netfront(.+?)/i",
		"/SmartPhone(.+?)/i",
		"/Symbian(.+?)/i",
		"/SonyEricsson(.+?)/i",
		"/AvantGo(.+?)/i",
		"/DoCoMo(.+?)/i",
		"/Pre\/(.+?)/i",
		"/UP.Browser(.+?)/i"
	);

	// Insert the data listed above
	foreach($data_array as $data)
	{
		$gomobile = array(
			"gmtid" => -1,
			"regex" => $db->escape_string($data)
		);

		$db->insert_query("gomobile", $gomobile);
	}

	// Edit existing templates (shows when posts are from GoMobile)
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";

	find_replace_templatesets("postbit_posturl", '#'.preg_quote('<span').'#', '<img src="{\$mybb->settings[\'bburl\']}/images/mobile/posted_{\$post[\'mobile\']}.png" alt="" width="{\$post[\'mobile\']}8" height="{\$post[\'mobile\']}8" title="Posted from GoMobile (when icon is displayed)" style="vertical-align: middle;" /> '.'<span');

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

	$settings = array(
		"gomobile_mobile_text" => array(
			"title"			=> "Mobile Board Name",
			"description"	=> $lang->gomobile_settings_mobile_text,
			"optionscode"	=> "text",
			"value"			=> $mybb->settings['bbname'],
			"disporder"		=> "1"
		),
		"gomobile_redirect_enabled" => array(
			"title"			=> "Enable Redirect?",
			"description"	=> $lang->gomobile_settings_redirect_enabled,
			"optionscode"	=> "yesno",
			"value"			=> "0",
			"disporder"		=> "2",
		),
		"gomobile_redirect_location" => array(
			"title"			=> "Redirect where?",
			"description"	=> $lang->gomobile_settings_redirect_location,
			"optionscode"	=> "text",
			"value"			=> "index.php",
			"disporder"		=> "3"
		),
		"gomobile_theme_id" => array(
			"title"			=> "Theme ID",
			"description"	=> $lang->gomobile_settings_theme_id,
			"optionscode"	=> "text",
			"value"			=> $theme,
			"disporder"		=> "4"
		),
		"gomobile_homename" => array(
			"title"			=> "Home Name",
			"description"	=> $lang->gomobile_settings_homename,
			"optionscode"	=> "text",
			"value"			=> $mybb->settings['homename'],
			"disporder"		=> "5"
		),
		"gomobile_homelink" => array(
			"title"			=> "Home Link",
			"description"	=> $lang->gomobile_settings_homelink,
			"optionscode"	=> "text",
			"value"			=> $mybb->settings['homeurl'],
			"disporder"		=> "6"
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
    global $db;

    if($db->table_exists("gomobile"))
    {
        // The gomobile database table exists, so it must be installed.
        return true;
    }
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

	find_replace_templatesets("postbit_posturl", '#'.preg_quote('<img src="{$mybb->settings[\'bburl\']}/images/mobile/posted_{$post[\'mobile\']}.png" alt="" width="{$post[\'mobile\']}8" height="{$post[\'mobile\']}8" title="Posted from GoMobile (when icon is displayed)" style="vertical-align: middle;" /> '.'').'#', '', 0);

	// Lastly, remove the settings for GoMobile
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='gomobile'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_header_text'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_redirect_enabled'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_redirect_location'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_theme_id'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_homename'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='gomobile_homelink'");
}

function gomobile_forcetheme()
{
	global $db, $mybb, $plugins;

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
	
	// Fetch the theme permissions from the database
	$tquery = $db->simple_select("themes", "*", "name like '%gomobile%'");
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

	// Fetch the list of User Agent strings
	$query = $db->simple_select("gomobile", "regex");

	$switch = false;
	while($test = $db->fetch_array($query))
	{
		// Switch to GoMobile if the UA matches our list
		if(preg_match($test['regex'], $_SERVER['HTTP_USER_AGENT']) != 0)
		{
			$switch = true;
			$mybb->user['style'] = $mybb->settings['gomobile_theme_id'];
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
        $navbits[0]['name'] = $mybb->settings['gomobile_header_text'];
        $navbits[0]['url'] = $mybb->settings['bburl']."/index.php";    
    }
} 

function gomobile_forumdisplay()
{
	global $mybb, $thread, $tstatus;
	
	// All we're doing here is showing the thread title in a red font if it's closed
	if($thread['closed'] == 1) {
		$tstatus = "threadlist_closed";
	}
	else {
		$tstatus = "";
	}
}

function gomobile_showthread()
{
	global $mybb, $lang, $postcount, $perpage, $thread, $pagejump;
	
	// The jumper code
	$pj_template = "<div class=\"float_left\" style=\"padding-top: 12px;\">
        <a href=\"".get_thread_link($thread['tid'], 1)."\" class=\"pagination_a\">{$lang->gomobile_jump_fpost}</a>
        <a href=\"".get_thread_link($thread['tid'], 0, 'lastpost')."\" class=\"pagination_a\">{$lang->gomobile_jump_lpost}</a>
    </div>"; 
	
	// Figure out if we're going to display the first/last page jump
	if($postcount > $perpage){
		$pagejump = $pj_template;
	}
}

function gomobile_portal_default()
{
	global $mybb, $lang;
	
	// Has the admin disabled viewing of the portal from GoMobile?
	if($mybb->user['style'] == $mybb->settings['gomobile_theme_id'] && $mybb->settings['gomobile_redirect_enabled'] == 1)
	{
		redirect($mybb->settings['gomobile_redirect_location'], $lang->gomobile_redirect_portal);
	}
}

function gomobile_portal_pro()
{
	global $mybb, $lang;
	
	// Same as above, only for ProPortal
	if($mybb->user['style'] == $mybb->settings['gomobile_theme_id'] && $mybb->settings['gomobile_redirect_enabled'] == 1)
	{
		redirect($mybb->settings['gomobile_redirect_location'], $lang->gomobile_redirect_portal);
	}
}

function gomobile_posts($p)
{
    global $mybb;

    $is_mobile = intval($mybb->input['mobile']);

	// Was the post sent from GoMobile?
	if($is_mobile != 1)
	{
		$is_mobile = 0;
	}
	else
	{
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

	if($is_mobile != 1)
	{
		$is_mobile = 0;
	}
	else
	{
		$is_mobile = 1;
	}

    $p->post_insert_data['mobile'] = $is_mobile;

    return $p;
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
		// Adding or creating a regex...
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
			$gomobile['regex'] = $mybb->input['regex'];

			// Did they forget to fill in the regex?
			if($gomobile['regex'] == '')
			{
				$error = $lang->gomobile_noregex;
			}
			else
			{
				// No? Let's save it then
				$gomobile['regex'] = $db->escape_string($gomobile['regex']);

				// Did they create a new one?
				if($gmtid == -1)
				{
					// Yes, so we need to add a new database row
					$db->insert_query("gomobile", $gomobile);
				}
				else
				{
					// No, so we just update the existing one.
					// To do: check to make sure the gmtid exists
					$db->update_query("gomobile", $gomobile, "gmtid='{$gmtid}'");
				}

				flash_message($lang->gomobile_saved, 'success');
				admin_redirect('index.php?module=config/gomobile');
			}
		}
		else if($mybb->input['delete'])
		{
			// Delete the regex and return to the main menu
			$db->delete_query("gomobile", "gmtid='{$gmtid}'");

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
				// The user is editing an existing regex, so load it
				$query = $db->simple_select("gomobile", "regex", "gmtid='{$gmtid}'");
				$gomobile = $db->fetch_array($query);
			}
			else
			{
				// The user is creating a new one, so fill it with some defaults
				$gomobile['regex'] = "";
			}
		}

		// If at this point $gomobile == null,
		// we tried to load a non-existant regex.
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
			$form_container->output_row($lang->gomobile_regex, $lang->gomobile_regex_desc, $form->generate_text_box('regex', htmlspecialchars($gomobile['regex']), array('id' => 'regex')));

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
			// This happens if the user tried to edit a non-existant regex
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
		$table->construct_header($lang->gomobile_regex);
		$table->construct_header($lang->controls, array("class" => "align_center", "width" => 155));

		// list existing regexes
		$query = $db->simple_select("gomobile", "gmtid, regex");
		while($list = $db->fetch_array($query))
		{
			// show the regex
			$list['regex'] = htmlspecialchars($list['regex']);
			$table->construct_cell("<strong>{$list['regex']}</strong>");

			// Show the edit and delete menu
			$popup = new PopupMenu("gomobile_{$list['gmtid']}", $lang->options);
			$popup->add_item($lang->gomobile_edit, "index.php?module=config/gomobile&amp;action=edit&amp;gmtid={$list['gmtid']}");
			$popup->add_item($lang->gomobile_delete, "index.php?module=config/gomobile&amp;action=edit&amp;delete=true&amp;gmtid={$list['gmtid']}");
			$table->construct_cell($popup->fetch(), array("class" => "align_center", "width" => 155));

			// Done!
			$table->construct_row();
		}

		// list 'add new regex' link
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
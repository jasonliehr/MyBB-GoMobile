<?php
/*
 MyBB GoMobile plugin - based on UA Theme. Notices below.

 UA Theme 1.0 - Forces user agents matching a regex to use a certain theme
 (Useful for mobile versions of a forum)

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
 
 inc/languages/xxx/gomobile.lang.php: The various strings shown to the user
*/

$l['gomobile'] = "MyBB GoMobile";
$l['gomobile_desc'] = "MyBB GoMobile's accompanying plugin, forces user agents matching a regex to use a certain theme.";
$l['gomobile_regex'] = "Regular Expression";
$l['gomobile_addnew'] = "Add New Regex";
$l['gomobile_edit'] = "Edit Regex";
$l['gomobile_update_tid'] = "Update TIDs";
$l['gomobile_update_tid_desc'] = "Use this tool to update all tids to the one set in the settings for GoMobile (update that setting first, then run this tool). Useful if you manually imported the GoMobile theme.";
$l['gomobile_run_tool'] = "Run Tool";
$l['gomobile_tool'] = "Tool";
$l['gomobile_tools'] = "GoMobile Tools";
$l['gomobile_save'] = "Save Regex";
$l['gomobile_delete'] = "Delete Regex";
$l['gomobile_noexist'] = "That regex doesn't exist!";
$l['gomobile_regex_desc'] = "UA Theme matches this to the user's agent string to determine which theme to use.";
$l['gomobile_theme'] = "Theme";
$l['gomobile_theme_desc'] = "Which theme to use when a match is made. <em>Note:</em> This will be overridden by a forum theme override.";
$l['gomobile_noregex'] = "No regex to match. Please input a regex so GoMobile knows what to do.";
$l['gomobile_saved'] = "Regex saved!";
$l['gomobile_mobile_version'] = "Mobile Version";

$l['gomobile_ucp'] = "UCP";
$l['gomobile_mcp'] = "MCP";
$l['gomobile_acp'] = "ACP";
$l['gomobile_pms'] = "PMs";
$l['gomobile_logout'] = "Logout";
$l['gomobile_last'] = "Last";
$l['gomobile_lastby'] = "Last by:";
$l['gomobile_post_edit'] = "Edit";
$l['gomobile_post_delete'] = "Delete";
$l['gomobile_post_quote'] = "Quote";
$l['gomobile_post_warn'] = "Warn";
$l['gomobile_post_pm'] = "PM";
$l['gomobile_page'] = "Page:";
$l['gomobile_copyrights'] = "Powered by <a href='http://mybb.com' target='_blank'>MyBB</a>, mobile version by <a href='http://mybbgm.com' target='_blank'>MyBB GoMobile</a>.";
$l['gomobile_showsig'] = "Display your Signature?";
$l['gomobile_disablesmilies'] = "Disable smilies in this message?";
$l['gomobile_savecopy'] = "Save a copy?";
$l['gomobile_receipt'] = "Track when the receiver reads this?";
$l['gomobile_modclose'] = "Close this thread?";
$l['gomobile_modstick'] = "Stick this thread?";
$l['gomobile_send'] = "Send";
$l['gomobile_unreadreports'] = "Moderator Notice: Unread reported post(s)";
$l['gomobile_bbclosed'] = "Your board is set to closed.";
$l['gomobile_nosubs'] = "No subscriptions.";
$l['gomobile_newsubs'] = "New Posts in Subscribed Threads";
$l['gomobile_redirect_portal'] = "Your Administrator has chosen to disable viewing the portal while browsing via GoMobile, redirecting to an alternative page...";
$l['gomobile_use_mobile_version'] = "Use a mobile version if I visit from a mobile device";
$l['gomobile_switched_version'] = "Switching versions.<br />Please wait while we transfer you back...";
$l['gomobile_lastpost'] = "Last post: ";
$l['gomobile_orderby'] = "Order by: ";
$l['gomobile_forum'] = "Forum: ";
$l['gomobile_by'] = "By: ";
$l['gomobile_jump_fpost'] = "First Post";
$l['gomobile_jump_lpost'] = "Last Post";
$l['gomobile_votes'] = "Votes";

$l['gomobile_ucp_username'] = "Username:";
$l['gomobile_ucp_avatar'] = "Avatar:";
$l['gomobile_ucp_inbox'] = "Inbox";

$l['gomobile_pm_reply'] = "Reply";
$l['gomobile_pm_delete'] = "Delete";
$l['gomobile_pm_forward'] = "Forward";
$l['gomobile_pm_replyall'] = "Reply to All";

$l['gomobile_mcp_until'] = " - Until: ";
$l['gomobile_mcp_reason'] = "Reason: ";
$l['gomobile_mcp_controls'] = "Controls";
$l['gomobile_mcp_pid'] = "Post #";
$l['gomobile_mcp_in'] = " in ";
?>
<?php
/**
 * eXtreme Message Board
 * XMB 1.9.12-alpha  Do not use this experimental software after 1 October 2020.
 *
 * Developed And Maintained By The XMB Group
 * Copyright (c) 2001-2020, The XMB Group
 * https://www.xmbforum2.com/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 **/

define( 'X_SCRIPT', 'quarantine.php' );
require 'header.php';

nav( "<a href='quarantine.php'>{$lang['moderation_meta_name']}</a>" );

if ( ! X_SMOD ) {
    header( 'HTTP/1.0 403 Forbidden' );
    error( $lang['notpermitted'] );
}

$quarantine = true;

loadtemplates(
'viewthread_poll',
'viewthread_poll_options',
'viewthread_poll_options_view',
'viewthread_poll_submitbutton',
'viewthread_post',
'viewthread_post_sig',
'viewthread_post_nosig',
'viewthread_post_attachmentthumb',
'viewthread_post_attachmentimage',
'viewthread_post_attachment'
);
eval('$header = "'.template('header').'";');

echo $header;

?>
<table cellspacing="0" cellpadding="0" border="0" width="<?php echo $tablewidth; ?>" align="center">
<tr>
<td bgcolor="<?php echo $bordercolor; ?>">
<table border="0" cellspacing="<?php echo $THEME['borderwidth']; ?>" cellpadding="<?php echo $tablespace; ?>" width="100%">
<tr>
<td class="category"><font color="<?php echo $cattext; ?>"><strong><?php echo $lang['moderation_meta_name']; ?></strong></font></td>
</tr>
<tr>
<td class="tablerow" bgcolor="<?php echo $altbg1; ?>">
<?php

$action = postedVar('action', '', FALSE, FALSE, FALSE, 'g');

switch( $action ) {
case 'viewuser':
    $dbuser = postedVar('u', '', TRUE, TRUE, FALSE, 'g');
    $result = $db->query("SELECT * FROM ".X_PREFIX."members WHERE username='$dbuser' AND moderation = 1");
    if ($db->num_rows($result) == 0) {
        error($lang['nomember'], FALSE, '', '</td></tr></table></td></tr></table>');
    }
    $member = $db->fetch_array($result);
    $db->free_result($result);

    echo "<h2>{$lang['moderation_new_member']}: {$member['username']}</h2>\n";

    smcwcache();

    $specialrank = array();
    $rankposts = array();
    $queryranks = $db->query("SELECT id, title, posts, stars, allowavatars, avatarrank FROM ".X_PREFIX."ranks");
    while($query = $db->fetch_row($queryranks)) {
        $title = $query[1];
        $rposts= $query[2];
        if ($title == 'Super Administrator' || $title == 'Administrator' || $title == 'Super Moderator' || $title == 'Moderator') {
            $specialrank[$title] = "$query[0],$query[1],$query[2],$query[3],$query[4],$query[5]";
        } else {
            $rankposts[$rposts]  = "$query[0],$query[1],$query[2],$query[3],$query[4],$query[5]";
        }
    }
    $db->free_result($queryranks);
    $thisbg = $altbg2;
    $tmoffset = ($timeoffset * 3600) + ($addtime * 3600);

    $result = $db->query("SELECT * FROM ".X_PREFIX."hold_threads WHERE author='$dbuser'");

    if ($db->num_rows($result) > 0) {
        echo "<h3>{$lang['moderation_new_threads']}</h3>\n";
        while($thread = $db->fetch_array($result)){
            $tid = $thread['tid'];
            $fid = $thread['fid'];
            $forum = getForum($fid);
            $thread['subject'] = shortenString(rawHTMLsubject(stripslashes($thread['subject'])), 125, X_SHORTEN_SOFT|X_SHORTEN_HARD, '...');

            $pollhtml = $poll = '';
            $vote_id = $voted = 0;

            if ($thread['pollopts'] == 1) {
                $query = $db->query("SELECT vote_id FROM ".X_PREFIX."hold_vote_desc WHERE topic_id='$tid'");
                if ($query) {
                    $vote_id = $db->fetch_array($query);
                    $vote_id = (int) $vote_id['vote_id'];
                }
                $db->free_result($query);
            }

            if ($vote_id > 0) {
                $resultold = $result;
                $results = '- [<a href=""><font color="'.$cattext.'">'.$lang['viewresults'].'</font></a>]';
                $query = $db->query("SELECT vote_option_id, vote_option_text FROM ".X_PREFIX."hold_vote_results WHERE vote_id='$vote_id'");
                while($result = $db->fetch_array($query)) {
                    $poll = [];
                    $poll['id'] = (int) $result['vote_option_id'];
                    $poll['name'] = $result['vote_option_text'];
                    eval('$pollhtml .= "'.template('viewthread_poll_options').'";');
                }
                $db->free_result($query);
                // eval('$buttoncode = "'.template('viewthread_poll_submitbutton').'";');
                $buttoncode = '';
                eval('$poll = "'.template('viewthread_poll').'";');
                $result = $resultold;
                echo $poll;
            }

            echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"$tablewidth\" align=\"center\">\n";
            echo "<tr><td bgcolor=\"$bordercolor\">\n";
            echo "<table border=\"0\" cellspacing=\"{$THEME['borderwidth']}\" cellpadding=\"$tablespace\" width=\"100%\">\n";
            echo "<tr class=\"header\"><td width=\"18%\">{$lang['textauthor']} </td><td>{$lang['textsubject']} {$thread['subject']}</td></tr>\n";

            $result2 = $db->query("SELECT * FROM ".X_PREFIX."hold_posts WHERE newtid=$tid");
            $post = array_merge($db->fetch_array($result2), $member);
            $db->free_result($result2);
            $post['avatar'] = str_replace("script:", "sc ript:", $post['avatar']);
            if ($onlinetime - (int)$post['lastvisit'] <= X_ONLINE_TIMER) {
                if ($post['invisible'] == 1) {
                    if (!X_ADMIN) {
                        $onlinenow = $lang['memberisoff'];
                    } else {
                        $onlinenow = $lang['memberison'].' ('.$lang['hidden'].')';
                    }
                } else {
                    $onlinenow = $lang['memberison'];
                }
            } else {
                $onlinenow = $lang['memberisoff'];
            }
            $date = gmdate($dateformat, $post['dateline'] + $tmoffset);
            $time = gmdate($timecode, $post['dateline'] + $tmoffset);
            $poston = $lang['textposton'].' '.$date.' '.$lang['textat'].' '.$time;
            if ($post['icon'] != '' && file_exists($smdir.'/'.$post['icon'])) {
                $post['icon'] = '<img src="'.$smdir.'/'.$post['icon'].'" alt="'.$post['icon'].'" border="0" />';
            } else {
                $post['icon'] = '<img src="'.$imgdir.'/default_icon.gif" alt="[*]" border="0" />';
            }
            $encodename = recodeOut($post['author']);
            $profilelink = "<a href=\"./member.php?action=viewpro&amp;member=$encodename\">{$post['author']}</a>";
            $showtitle = $post['status'];
            $rank = array();
            if ($post['status'] == 'Administrator' || $post['status'] == 'Super Administrator' || $post['status'] == 'Super Moderator' || $post['status'] == 'Moderator') {
                $sr = $post['status'];
                $rankinfo = explode(",", $specialrank[$sr]);
                $rank['allowavatars'] = $rankinfo[4];
                $rank['title'] = $lang[$status_translate[$status_enum[$sr]]];
                $rank['stars'] = $rankinfo[3];
                $rank['avatarrank'] = $rankinfo[5];
            } else if ($post['status'] == 'Banned') {
                $rank['allowavatars'] = 'no';
                $rank['title'] = $lang['textbanned'];
                $rank['stars'] = 0;
                $rank['avatarrank'] = '';
            } else {
                $last_max = -1;
                foreach($rankposts as $key => $rankstuff) {
                    if ($post['postnum'] >= $key && $key > $last_max) {
                        $last_max = $key;
                        $rankinfo = explode(",", $rankstuff);
                        $rank['allowavatars'] = $rankinfo[4];
                        $rank['title'] = $rankinfo[1];
                        $rank['stars'] = $rankinfo[3];
                        $rank['avatarrank'] = $rankinfo[5];
                    }
                }
            }
            $allowavatars = $rank['allowavatars'];
            $stars = str_repeat('<img src="'.$imgdir.'/star.gif" alt="*" border="0" />', $rank['stars']) . '<br />';
            $showtitle = ($post['customstatus'] != '') ? $post['customstatus'].'<br />' : $rank['title'].'<br />';
            if ($allowavatars == 'no') {
                $post['avatar'] = '';
            }
            if ($rank['avatarrank'] != '') {
                $rank['avatar'] = '<img src="'.$rank['avatarrank'].'" alt="'.$lang['altavatar'].'" border="0" /><br />';
            } else {
                $rank['avatar'] = '';
            }
            $tharegdate = gmdate($dateformat, $post['regdate'] + $tmoffset);
            $avatar = '';
            if ($SETTINGS['avastatus'] == 'on' || $SETTINGS['avastatus'] == 'list') {
                if ($post['avatar'] != '' && $allowavatars != "no") {
                    $avatar = '<img src="'.$post['avatar'].'" alt="'.$lang['altavatar'].'" border="0" />';
                }
            }
            if ($post['mood'] != '') {
                $mood = '<strong>'.$lang['mood'].'</strong> '.postify($post['mood'], 'no', 'no', 'yes', 'no', 'yes', 'no', true, 'yes');
            } else {
                $mood = '';
            }
            if ($post['location'] != '') {
                $post['location'] = rawHTMLsubject($post['location']);
                $location = '<br />'.$lang['textlocation'].' '.$post['location'];
            } else {
                $location = '';
            }
            $email = '';
            $site = '';
            $icq = '';
            $msn = '';
            $aim = '';
            $yahoo = '';
            $profile = '';
            $search = '';
            $u2u = '';
            $ip = '';
            $repquote = '';
            $reportlink = '';
            $edit = '';
            $bbcodeoff = $post['bbcodeoff'];
            $smileyoff = $post['smileyoff'];
            $post['message'] = postify(stripslashes($post['message']), $smileyoff, $bbcodeoff, $forum['allowsmilies'], 'no', $forum['allowbbcode'], $forum['allowimgcode']);
            if ($forum['attachstatus'] == 'on') {
                require_once ROOT.'include/attach.inc.php';
                $queryattach = $db->query("SELECT a.aid, a.pid, a.filename, a.filetype, a.filesize, a.downloads, a.img_size, thumbs.aid AS thumbid, thumbs.filename AS thumbname, thumbs.img_size AS thumbsize FROM ".X_PREFIX."hold_attachments AS a LEFT JOIN ".X_PREFIX."hold_attachments AS thumbs ON a.aid=thumbs.parentid WHERE a.pid = {$post['pid']} AND a.parentid=0");
            }
            if ($forum['attachstatus'] == 'on' && $db->num_rows($queryattach) > 0) {
                $files = array();
                $db->data_seek($queryattach, 0);
                while($attach = $db->fetch_array($queryattach)) {
                    if ($attach['pid'] == $post['pid']) {
                        $files[] = $attach;
                    }
                }
                if (count($files) > 0) {
                    bbcodeFileTags( $post['message'], $files, (int) $post['pid'], ($forum['allowbbcode'] == 'yes' && $bbcodeoff == 'no'), $quarantine );
                }
            }
            if ($post['usesig'] == 'yes') {
                $post['sig'] = postify($post['sig'], 'no', 'no', $forum['allowsmilies'], 'no', $SETTINGS['sigbbcode'], $forum['allowimgcode'], false);
                eval('$post["message"] .= "'.template('viewthread_post_sig').'";');
            } else {
                eval('$post["message"] .= "'.template('viewthread_post_nosig').'";');
            }
            if ($post['subject'] != '') {
                $linktitle = rawHTMLsubject(stripslashes($post['subject']));
                $post['subject'] = wordwrap( $linktitle, 150, '<br />', true ).'<br />';
            } else {
                $linktitle = $thread['subject'];
            }
            eval('$post = "'.template('viewthread_post').'";');
            echo $post;
            echo "</table></td></tr></table><br />\n";

        }
    }
    $db->free_result($result);
    $result = $db->query("SELECT * FROM ".X_PREFIX."hold_posts WHERE author='$dbuser' AND tid != 0");

    if ($db->num_rows($result) > 0) {
        echo "<h3>{$lang['moderation_new_replies']}</h3>\n";
        while($post = $db->fetch_array($result)){
            $tid = $post['tid'];
            $fid = $post['fid'];
            $forum = getForum($fid);
            $result2 = $db->query("SELECT * FROM ".X_PREFIX."threads WHERE tid=$tid");
            $thread = $db->fetch_array($result2);
            $db->free_result($result2);
            $thread['subject'] = shortenString(rawHTMLsubject(stripslashes($thread['subject'])), 125, X_SHORTEN_SOFT|X_SHORTEN_HARD, '...');
            echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"$tablewidth\" align=\"center\">\n";
            echo "<tr><td bgcolor=\"$bordercolor\">\n";
            echo "<table border=\"0\" cellspacing=\"{$THEME['borderwidth']}\" cellpadding=\"$tablespace\" width=\"100%\">\n";
            echo "<tr class=\"header\"><td width=\"18%\">{$lang['textauthor']} </td><td>{$lang['textsubject']} {$thread['subject']}</td></tr>\n";

            $post = array_merge($post, $member);
            $post['avatar'] = str_replace("script:", "sc ript:", $post['avatar']);
            if ($onlinetime - (int)$post['lastvisit'] <= X_ONLINE_TIMER) {
                if ($post['invisible'] == 1) {
                    if (!X_ADMIN) {
                        $onlinenow = $lang['memberisoff'];
                    } else {
                        $onlinenow = $lang['memberison'].' ('.$lang['hidden'].')';
                    }
                } else {
                    $onlinenow = $lang['memberison'];
                }
            } else {
                $onlinenow = $lang['memberisoff'];
            }
            $date = gmdate($dateformat, $post['dateline'] + $tmoffset);
            $time = gmdate($timecode, $post['dateline'] + $tmoffset);
            $poston = $lang['textposton'].' '.$date.' '.$lang['textat'].' '.$time;
            if ($post['icon'] != '' && file_exists($smdir.'/'.$post['icon'])) {
                $post['icon'] = '<img src="'.$smdir.'/'.$post['icon'].'" alt="'.$post['icon'].'" border="0" />';
            } else {
                $post['icon'] = '<img src="'.$imgdir.'/default_icon.gif" alt="[*]" border="0" />';
            }
            $encodename = recodeOut($post['author']);
            $profilelink = "<a href=\"./member.php?action=viewpro&amp;member=$encodename\">{$post['author']}</a>";
            $showtitle = $post['status'];
            $rank = array();
            if ($post['status'] == 'Administrator' || $post['status'] == 'Super Administrator' || $post['status'] == 'Super Moderator' || $post['status'] == 'Moderator') {
                $sr = $post['status'];
                $rankinfo = explode(",", $specialrank[$sr]);
                $rank['allowavatars'] = $rankinfo[4];
                $rank['title'] = $lang[$status_translate[$status_enum[$sr]]];
                $rank['stars'] = $rankinfo[3];
                $rank['avatarrank'] = $rankinfo[5];
            } else if ($post['status'] == 'Banned') {
                $rank['allowavatars'] = 'no';
                $rank['title'] = $lang['textbanned'];
                $rank['stars'] = 0;
                $rank['avatarrank'] = '';
            } else {
                $last_max = -1;
                foreach($rankposts as $key => $rankstuff) {
                    if ($post['postnum'] >= $key && $key > $last_max) {
                        $last_max = $key;
                        $rankinfo = explode(",", $rankstuff);
                        $rank['allowavatars'] = $rankinfo[4];
                        $rank['title'] = $rankinfo[1];
                        $rank['stars'] = $rankinfo[3];
                        $rank['avatarrank'] = $rankinfo[5];
                    }
                }
            }
            $allowavatars = $rank['allowavatars'];
            $stars = str_repeat('<img src="'.$imgdir.'/star.gif" alt="*" border="0" />', $rank['stars']) . '<br />';
            $showtitle = ($post['customstatus'] != '') ? $post['customstatus'].'<br />' : $rank['title'].'<br />';
            if ($allowavatars == 'no') {
                $post['avatar'] = '';
            }
            if ($rank['avatarrank'] != '') {
                $rank['avatar'] = '<img src="'.$rank['avatarrank'].'" alt="'.$lang['altavatar'].'" border="0" /><br />';
            } else {
                $rank['avatar'] = '';
            }
            $tharegdate = gmdate($dateformat, $post['regdate'] + $tmoffset);
            $avatar = '';
            if ($SETTINGS['avastatus'] == 'on' || $SETTINGS['avastatus'] == 'list') {
                if ($post['avatar'] != '' && $allowavatars != "no") {
                    $avatar = '<img src="'.$post['avatar'].'" alt="'.$lang['altavatar'].'" border="0" />';
                }
            }
            if ($post['mood'] != '') {
                $mood = '<strong>'.$lang['mood'].'</strong> '.postify($post['mood'], 'no', 'no', 'yes', 'no', 'yes', 'no', true, 'yes');
            } else {
                $mood = '';
            }
            if ($post['location'] != '') {
                $post['location'] = rawHTMLsubject($post['location']);
                $location = '<br />'.$lang['textlocation'].' '.$post['location'];
            } else {
                $location = '';
            }
            $email = '';
            $site = '';
            $icq = '';
            $msn = '';
            $aim = '';
            $yahoo = '';
            $profile = '';
            $search = '';
            $u2u = '';
            $ip = '';
            $repquote = '';
            $reportlink = '';
            $edit = '';
            $bbcodeoff = $post['bbcodeoff'];
            $smileyoff = $post['smileyoff'];
            $post['message'] = postify(stripslashes($post['message']), $smileyoff, $bbcodeoff, $forum['allowsmilies'], 'no', $forum['allowbbcode'], $forum['allowimgcode']);
            if ($forum['attachstatus'] == 'on') {
                require_once ROOT.'include/attach.inc.php';
                $queryattach = $db->query("SELECT a.aid, a.pid, a.filename, a.filetype, a.filesize, a.downloads, a.img_size, thumbs.aid AS thumbid, thumbs.filename AS thumbname, thumbs.img_size AS thumbsize FROM ".X_PREFIX."hold_attachments AS a LEFT JOIN ".X_PREFIX."hold_attachments AS thumbs ON a.aid=thumbs.parentid WHERE a.pid={$post['pid']} AND a.parentid=0");
            }
            if ($forum['attachstatus'] == 'on' && $db->num_rows($queryattach) > 0) {
                $files = array();
                $db->data_seek($queryattach, 0);
                while($attach = $db->fetch_array($queryattach)) {
                    if ($attach['pid'] == $post['pid']) {
                        $files[] = $attach;
                    }
                }
                if (count($files) > 0) {
                    bbcodeFileTags( $post['message'], $files, $post['pid'], ($forum['allowbbcode'] == 'yes' && $bbcodeoff == 'no'), $quarantine );
                }
            }
            if ($post['usesig'] == 'yes') {
                $post['sig'] = postify($post['sig'], 'no', 'no', $forum['allowsmilies'], 'no', $SETTINGS['sigbbcode'], $forum['allowimgcode'], false);
                eval('$post["message"] .= "'.template('viewthread_post_sig').'";');
            } else {
                eval('$post["message"] .= "'.template('viewthread_post_nosig').'";');
            }
            if ($post['subject'] != '') {
                $linktitle = rawHTMLsubject(stripslashes($post['subject']));
                $post['subject'] = $linktitle.'<br />';
            } else {
                $linktitle = $thread['subject'];
            }
            eval('$post = "'.template('viewthread_post').'";');
            echo $post;
            echo "</table></td></tr></table><br />\n";

        }
    }
    $db->free_result($result);

    echo "<h3>{$lang['moderation_actions']}</h3>\n";
    echo "<form action='quarantine.php?action=modays' method='post'>\n";
    echo "<input type='hidden' name='u' value=\"{$member['username']}\" />\n";
    echo "<input type='submit' name='sub' value='{$lang['moderation_approve_all']}' />\n";
    echo "<input type='submit' name='sub' value='{$lang['moderation_delete_all']}' />\n";
    if (X_ADMIN) {
        echo "<input type='submit' name='sub' value='{$lang['moderation_delete_ban']}' />\n";
    }
    echo "</form>\n";

    break;
case 'modays':
    $member = postedVar('u');
    $sub = postedVar('sub');
    if ($sub == $lang['moderation_approve_all']) {
        $act = 'approveall';
        $key = 'pmapp';
        $phrase = 'moderation_conf_appr';
    } elseif ($sub == $lang['moderation_delete_all']) {
        $act = 'deleteall';
        $key = 'pmdel';
        $phrase = 'moderation_conf_dele';
    } elseif ($sub == $lang['moderation_delete_ban']) {
        $act = 'deleteban';
        $key = 'pmdel';
        $phrase = 'moderation_conf_dele';
    } else {
        error($lang['textnoaction'], FALSE, '', '</td></tr></table></td></tr></table>');
    }
    $key = template_key($key, $member);
    $phrase = str_replace('*user*', $member, $lang[$phrase]);
    ?>
    <tr bgcolor="<?php echo $altbg2; ?>" class="ctrtablerow"><td><?php echo $phrase; ?><br />
    <form action="moderation.php?action=<?php echo $act; ?>" method="post">
      <input type="hidden" name="token" value="<?php echo nonce_create($key); ?>" />
      <input type="hidden" name="u" value="<?php echo $member; ?>" />
      <input type="submit" name="yessubmit" value="<?php echo $lang['textyes']; ?>" /> -
      <input type="submit" name="yessubmit" value="<?php echo $lang['textno']; ?>" />
    </form></td></tr>
    <?php
    break;
case 'approveall':
    $member = postedVar('u');
    $ays = postedVar('yessubmit');
    request_secure('pmapp', $member, X_NONCE_AYS_EXP, FALSE);

    if ($ays == $lang['textyes']) {
        require_once ROOT.'include/attach.inc.php';
        $db->query("UPDATE ".X_PREFIX."members SET waiting_for_mod = 'no' WHERE username='$member'");
        $count = $db->result($db->query("SELECT COUNT(*) FROM ".X_PREFIX."hold_posts WHERE author='$member'"), 0);
        $thatime = $onlinetime - $count;
        $result = $db->query("SELECT * FROM ".X_PREFIX."hold_threads WHERE author='$member' ORDER BY lastpost ASC");
        while($thread = $db->fetch_array($result)) {
            $thatime++;
            $forum = getForum($thread['fid']);
            $db->query(
                "INSERT INTO ".X_PREFIX."threads " .
                "      (fid, subject, icon,           lastpost, views, replies, author, closed, topped, pollopts) " .
                "SELECT fid, subject, icon, '$thatime|$member', views, replies, author, closed, topped, pollopts " .
                "FROM ".X_PREFIX."hold_threads WHERE tid = {$thread['tid']}"
            );
            $newtid = $db->insert_id();
            $oldpid = $db->result($db->query("SELECT pid FROM ".X_PREFIX."hold_posts WHERE newtid = {$thread['tid']}"), 0);
            $db->query(
                "INSERT INTO ".X_PREFIX."posts " .
                "      (fid,     tid, author, message, subject, dateline, icon, usesig, useip, bbcodeoff, smileyoff) " .
                "SELECT fid, $newtid, author, message, subject, $thatime, icon, usesig, useip, bbcodeoff, smileyoff " .
                "FROM ".X_PREFIX."hold_posts WHERE pid = $oldpid"
            );
            $newpid = $db->insert_id();
            $db->query("UPDATE ".X_PREFIX."threads SET lastpost=concat(lastpost, '|$newpid') WHERE tid = $newtid");
            $where = "WHERE fid={$thread['fid']}";
            if ($forum['type'] == 'sub') {
                $where .= " OR fid={$forum['fup']}";
            }
            $db->query("UPDATE ".X_PREFIX."forums SET lastpost='$thatime|$member|$newpid', threads=threads+1, posts=posts+1 $where");
            unset($where);
            $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum+1 WHERE username='$member'");
            \XMB\Attach\approve( $oldpid, $newpid );
            if (intval($thread['pollopts']) != 0) {
                $oldpoll = $db->result($db->query("SELECT vote_id FROM ".X_PREFIX."hold_vote_desc WHERE topic_id = {$thread['tid']}"), 0);
                $db->query(
                    "INSERT INTO ".X_PREFIX."vote_desc " .
                    "      (topic_id, vote_text, vote_start, vote_length) " .
                    "SELECT  $newtid, vote_text, vote_start, vote_length " .
                    "FROM ".X_PREFIX."hold_vote_desc WHERE topic_id = {$thread['tid']}"
                );
                $newpoll = $db->insert_id();
                $db->query(
                    "INSERT INTO ".X_PREFIX."vote_results " .
                    "      ( vote_id, vote_option_id, vote_option_text, vote_result) " .
                    "SELECT $newpoll, vote_option_id, vote_option_text, vote_result " .
                    "FROM ".X_PREFIX."hold_vote_results WHERE vote_id = $oldpoll"
                );
                $db->query("DELETE FROM ".X_PREFIX."hold_vote_results WHERE vote_id = $oldpoll");
                $db->query("DELETE FROM ".X_PREFIX."hold_vote_desc WHERE vote_id = $oldpoll");
            }
            $count2 = $db->result($db->query("SELECT COUNT(*) FROM ".X_PREFIX."hold_favorites WHERE tid={$thread['tid']} AND username='$member' AND type='subscription'"), 0);
            if ($count2 != 0) {
                $db->query("INSERT INTO ".X_PREFIX."favorites (tid, username, type) VALUES ($newtid, '$member', 'subscription')");
                $db->query("DELETE FROM ".X_PREFIX."hold_favorites WHERE tid={$thread['tid']}");
            }
            $db->query("DELETE FROM ".X_PREFIX."hold_posts WHERE pid = $oldpid");
            $db->query("DELETE FROM ".X_PREFIX."hold_threads WHERE tid = {$thread['tid']}");
        }
        $db->free_result($result);
        $result = $db->query("SELECT * FROM ".X_PREFIX."hold_posts WHERE author='$member' ORDER BY dateline ASC");
        while($post = $db->fetch_array($result)) {
            $thatime++;
            $forum = getForum($post['fid']);
            $db->query(
                "INSERT INTO ".X_PREFIX."posts " .
                "      (fid, tid, author, message, subject, dateline, icon, usesig, useip, bbcodeoff, smileyoff) " .
                "SELECT fid, tid, author, message, subject, $thatime, icon, usesig, useip, bbcodeoff, smileyoff " .
                "FROM ".X_PREFIX."hold_posts WHERE pid = {$post['pid']}"
            );
            $newpid = $db->insert_id();
            $db->query("UPDATE ".X_PREFIX."threads SET lastpost='$thatime|$member|$newpid', replies=replies+1 WHERE tid = {$post['tid']}");
            $where = "WHERE fid={$post['fid']}";
            if ($forum['type'] == 'sub') {
                $where .= " OR fid={$forum['fup']}";
            }
            $db->query("UPDATE ".X_PREFIX."forums SET lastpost='$thatime|$member|$newpid', threads=threads+1, posts=posts+1 $where");
            unset($where);
            $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum+1 WHERE username='$member'");
            \XMB\Attach\approve( (int) $post['pid'], $newpid );
            $db->query("DELETE FROM ".X_PREFIX."hold_posts WHERE pid = {$post['pid']}");

            $result2 = $db->query("SELECT subject FROM ".X_PREFIX."threads WHERE tid = {$post['tid']}");
            $thread = $db->fetch_array($result2);
            $db->free_result($result2);
            $threadname = rawHTMLsubject(stripslashes($thread['subject']));

            $query = $db->query("SELECT COUNT(*) FROM ".X_PREFIX."posts WHERE pid <= $newpid AND tid={$post['tid']}");
            $posts = $db->result($query,0);
            $db->free_result($query);

            $lang2 = loadPhrases(array('charset','textsubsubject','textsubbody'));
            $viewperm = getOneForumPerm($forum, X_PERMS_RAWVIEW);
            $date = $db->result($db->query("SELECT dateline FROM ".X_PREFIX."posts WHERE tid={$post['tid']} AND pid < $newpid ORDER BY dateline DESC LIMIT 1"), 0);
            $subquery = $db->query("SELECT m.email, m.lastvisit, m.ppp, m.status, m.langfile "
                                 . "FROM ".X_PREFIX."favorites f "
                                 . "INNER JOIN ".X_PREFIX."members m USING (username) "
                                 . "WHERE f.type = 'subscription' AND f.tid = {$post['tid']} AND m.username != '$member' AND m.lastvisit >= $date");
            while($subs = $db->fetch_array($subquery)) {
                if ($viewperm < $status_enum[$subs['status']]) {
                    continue;
                }

                if ($subs['ppp'] < 1) {
                    $subs['ppp'] = $posts;
                }

                $translate = $lang2[$subs['langfile']];
                $topicpages = quickpage($posts, $subs['ppp']);
                $topicpages = ($topicpages == 1) ? '' : '&page='.$topicpages;
                $threadurl = $full_url.'viewthread.php?tid='.$post['tid'].$topicpages.'#pid'.$newpid;
                $rawsubject = htmlspecialchars_decode($threadname, ENT_QUOTES);
                $rawusername = htmlspecialchars_decode($member, ENT_QUOTES);
                $rawemail = htmlspecialchars_decode($subs['email'], ENT_QUOTES);
                $title = "$rawsubject ({$translate['textsubsubject']})";
                $body = "$rawusername {$translate['textsubbody']} \n$threadurl";
                xmb_mail( $rawemail, $title, $body, $translate['charset'] );
            }
            $db->free_result($subquery);
        }
        $db->free_result($result);
        moderate_cleanup($member);
        echo $lang['moderation_approved'];
    } else {
        echo $lang['moderation_canceled'];
    }

    break;
case 'deleteall':
case 'deleteban':
    $member = postedVar('u');
    $ays = postedVar('yessubmit');
    request_secure('pmdel', $member, X_NONCE_AYS_EXP, FALSE);

    if ($ays == $lang['textyes']) {
        $result = $db->query("SELECT * FROM ".X_PREFIX."hold_threads WHERE author='$member' ORDER BY lastpost ASC");
        while($thread = $db->fetch_array($result)) {
            $oldpid = $db->result($db->query("SELECT pid FROM ".X_PREFIX."hold_posts WHERE newtid = {$thread['tid']}"), 0);
            $db->query("DELETE FROM ".X_PREFIX."hold_attachments WHERE pid = $oldpid");
            if (intval($thread['pollopts']) != 0) {
                $oldpoll = $db->result($db->query("SELECT vote_id FROM ".X_PREFIX."hold_vote_desc WHERE topic_id = {$thread['tid']}"), 0);
                $db->query("DELETE FROM ".X_PREFIX."hold_vote_results WHERE vote_id = $oldpoll");
                $db->query("DELETE FROM ".X_PREFIX."hold_vote_desc WHERE vote_id = $oldpoll");
            }
            $db->query("DELETE FROM ".X_PREFIX."hold_favorites WHERE tid={$thread['tid']}");
            $db->query("DELETE FROM ".X_PREFIX."hold_posts WHERE pid = $oldpid");
            $db->query("DELETE FROM ".X_PREFIX."hold_threads WHERE tid = {$thread['tid']}");
        }
        $db->free_result($result);
        $result = $db->query("SELECT * FROM ".X_PREFIX."hold_posts WHERE author='$member' ORDER BY dateline ASC");
        while($post = $db->fetch_array($result)) {
            $db->query("DELETE FROM ".X_PREFIX."hold_attachments WHERE pid = {$post['pid']}");
            $db->query("DELETE FROM ".X_PREFIX."hold_posts WHERE pid = {$post['pid']}");
        }
        $db->free_result($result);
        moderate_cleanup($member);
        if ('deleteban' == $action && X_ADMIN) {
            $db->query("UPDATE ".X_PREFIX."members SET status = 'Banned', customstatus = 'Spammer' WHERE username = '$member'");
        }
        echo $lang['moderation_deleted'];
    } else {
        echo $lang['moderation_canceled'];
    }
    break;
default:
    echo "<h2>{$lang['moderation_new_memq']}</h2>\n";
    $result = $db->query(
        "SELECT m.username, COUNT(*) AS postnum " .
        "FROM ".X_PREFIX."members AS m " .
        "INNER JOIN ".X_PREFIX."hold_posts AS p ON m.username = p.author " .
        "WHERE m.moderation = 1 " .
        "GROUP BY m.username " .
        "ORDER BY m.regdate ASC " .
        "LIMIT 10"
    );
    if ($db->num_rows($result) == 0) {
        echo "<p>{$lang['moderation_empty']}</p>\n";
    } else {
        echo "<table>\n<tr><th>{$lang['textusername']}</th><th>{$lang['memposts']}</th></tr>\n";
        while($row = $db->fetch_array($result)) {
            $user = $row['username'];
            $userurl = recodeOut($user);
            $count = $row['postnum'];
            echo "<tr><td><a href='?action=viewuser&amp;u=$userurl'>$user</a></td><td>$count</td></tr>\n";
        }
        echo "</table>\n";
    }
    $db->free_result($result);


    echo "<h2>{$lang['moderation_anonq']}</h2>\n";
    $result = $db->query(
        "SELECT fid, COUNT(*) AS postnum " .
        "FROM ".X_PREFIX."hold_posts WHERE author='Anonymous' " .
        "GROUP BY fid "
    );
    if ($db->num_rows($result) == 0) {
        echo "<p>{$lang['moderation_empty']}</p>\n";
    } else {
        echo "<table>\n<tr><th>{$lang['textforum']}</th><th>{$lang['memposts']}</th></tr>\n";
        while($row = $db->fetch_array($result)) {
            $fid = $row['fid'];
            $forum = getForum($fid);
            $fname = fnameOut($forum['name']);
            $count = $row['postnum'];
            echo "<tr><td><a href='?action=viewforum&amp;fid=$fid'>$fname</a></td><td>$count</td></tr>\n";
        }
        echo "</table>\n";
    }
    $db->free_result($result);

    break;
}

end_time();
eval('echo "</td></tr></table></td></tr></table>'.template('footer').'";');

/**
 * Get rid of potentially orphaned objects.
 *
 * @param string $xmbuser A DB-safe username.
 */
function moderate_cleanup($xmbuser) {
    global $db;

    if ('Anonymous' == $xmbuser) return;

    $result = $db->query("SELECT uid FROM ".X_PREFIX."members WHERE username='$xmbuser'");
    if ($db->num_rows($result) != 0) {
        $uid = $db->result($result, 0);
        $db->query("DELETE FROM ".X_PREFIX."hold_attachments WHERE uid=$uid AND pid=0");
    }
    $db->free_result($result);
}
?>

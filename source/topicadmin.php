<?php
/**
 * eXtreme Message Board
 * XMB 1.9.11 Beta 4 - This software should not be used for any purpose after 30 February 2009.
 *
 * Developed And Maintained By The XMB Group
 * Copyright (c) 2001-2009, The XMB Group
 * http://www.xmbforum.com
 *
 * Sponsored By iEntry, Inc.
 * http://www.ientry.com
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 **/

define('X_SCRIPT', 'topicadmin.php');

require 'header.php';
require ROOT.'include/topicadmin.inc.php';

if (X_GUEST) {
    redirect("{$full_url}misc.php?action=login", 0);
    exit;
}

smcwcache();

$_tid = isset($_POST['tid']) ? $_POST['tid'] : (isset($_GET['tid']) ? $_GET['tid'] : 0);
$fid = getInt('fid', 'p');
if ($fid == 0) {
    $fid = getInt('fid');
}
$pid = getInt('pid');
$othertid = formInt('othertid');
$action = postedVar('action');
if ($action == '') {
    $action = postedVar('action', '', TRUE, TRUE, FALSE, 'g');
}

if (is_array($_tid)) {
    $tids = array_unique(array_map('intval', $_tid));
    $tid = array();
    foreach($tids as $value) {
        $tid[] = $value;
    }
} else if (strstr($_tid, ',')) {
    $tids = array_unique(array_map('intval', explode(',', $_tid)));
    $tid = array();
    foreach($tids as $value) {
        $tid[] = $value;
    }
    $tid = implode(',', $tid);
} else {
    $tid = (int) $_tid;
}

loadtemplates(
'topicadmin_delete',
'topicadmin_openclose',
'topicadmin_move',
'topicadmin_topuntop',
'topicadmin_bump',
'topicadmin_split_row',
'topicadmin_split',
'topicadmin_merge',
'topicadmin_empty',
'topicadmin_threadprune_row',
'topicadmin_threadprune',
'topicadmin_copy'
);

eval('$css = "'.template('css').'";');

if ($tid && !is_array($tid) && false === strstr($tid, ',')) {
    $query = $db->query("SELECT * FROM ".X_PREFIX."threads WHERE tid='$tid'");
    $thread = $db->fetch_array($query);
    $db->free_result($query);
    $threadname = rawHTMLsubject(stripslashes($thread['subject']));
    $fid = (int)$thread['fid'];
} else {
    $threadname = '';
}

$forums = getForum($fid);

if (($forums['type'] != 'forum' && $forums['type'] != 'sub') || $forums['status'] != 'on') {
    header('HTTP/1.0 404 Not Found');
    error($lang['textnoforum']);
}

// Check for authorization to be here in the first place
$perms = checkForumPermissions($forums);
if (!$perms[X_PERMS_VIEW]) {
    error($lang['privforummsg']);
} else if (!$perms[X_PERMS_PASSWORD]) {
    handlePasswordDialog($fid);
}

$fup = array();
if ($forums['type'] == 'sub') {
    $fup = getForum($forums['fup']);
    // prevent access to subforum when upper forum can't be viewed.
    $fupPerms = checkForumPermissions($fup);
    if (!$fupPerms[X_PERMS_VIEW]) {
        error($lang['privforummsg']);
    } else if (!$fupPerms[X_PERMS_PASSWORD]) {
        handlePasswordDialog($fup['fid']);
    } else if ($fup['fup'] > 0) {
        $fupup = getForum($fup['fup']);
        nav('<a href="index.php?gid='.$fup['fup'].'">'.fnameOut($fupup['name']).'</a>');
        unset($fupup);
    }
    nav('<a href="forumdisplay.php?fid='.$fup['fid'].'">'.fnameOut($fup['name']).'</a>');
} else if ($forums['fup'] > 0) { // 'forum' in a 'group'
    $fup = getForum($forums['fup']);
    nav('<a href="index.php?gid='.$fup['fid'].'">'.fnameOut($fup['name']).'</a>');
}
nav('<a href="forumdisplay.php?fid='.$fid.'">'.fnameOut($forums['name']).'</a>');
if (isset($thread['subject'])) {
    nav('<a href="viewthread.php?tid='.$tid.'">'.$threadname.'</a>');
}

$kill = FALSE;

switch($action) {
    case 'delete':
        nav($lang['textdeletethread']);
        break;
    case 'top':
        nav($lang['texttopthread']);
        break;
    case 'close':
        nav($lang['textclosethread']);
        break;
    case 'copy':
        nav($lang['copythread']);
        break;
    case 'f_close':
        nav($lang['textclosethread']);
        break;
    case 'f_open':
        nav($lang['textopenthread']);
        break;
    case 'move':
        nav($lang['textmovemethod1']);
        break;
    case 'getip':
        $kill |= !X_ADMIN;
        nav($lang['textgetip']);
        break;
    case 'bump':
        nav($lang['textbumpthread']);
        break;
    case 'split':
        nav($lang['textsplitthread']);
        break;
    case 'merge':
        nav($lang['textmergethread']);
        break;
    case 'threadprune':
        nav($lang['textprunethread']);
        break;
    case 'empty':
        nav($lang['textemptythread']);
        break;
    default:
        $kill = TRUE;
        break;
}

$mod = new mod();
$kill |= !X_STAFF || !$mod->statuscheck($fid);

if ($kill) {
    error($lang['notpermitted']);
}

if ($SETTINGS['subject_in_title'] == 'on') {
    $threadSubject = '- '.$threadname;
}

// Search-link
$searchlink = makeSearchLink($forums['fid']);

eval('echo "'.template('header').'";');

switch($action) {
    case 'delete':
        if (noSubmit('deletesubmit')) {
            $tid = $mod->create_tid_string($tid);
            eval('echo "'.template('topicadmin_delete').'";');
        } else {
            require('include/attach.inc.php');
            $tids = $mod->create_tid_array($tid);
            foreach($tids AS $tid) {
                $query = $db->query("SELECT author, COUNT(pid) AS pidcount FROM ".X_PREFIX."posts WHERE tid='$tid' GROUP BY author");
                while($result = $db->fetch_array($query)) {
                    $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum-{$result['pidcount']} WHERE username='".$db->escape_var($result['author'])."'");
                }
                $db->free_result($query);

                deleteThreadAttachments($tid);  // Must delete attachments before posts!
                $db->query("DELETE FROM ".X_PREFIX."posts WHERE tid='$tid'");
                $db->query("DELETE FROM ".X_PREFIX."favorites WHERE tid='$tid'");
                
                $db->query("DELETE FROM d, r, v "
                         . "USING ".X_PREFIX."vote_desc AS d "
                         . "LEFT JOIN ".X_PREFIX."vote_results AS r ON r.vote_id = d.vote_id "
                         . "LEFT JOIN ".X_PREFIX."vote_voters AS v  ON v.vote_id = d.vote_id "
                         . "WHERE d.topic_id = $tid");

                $db->query("DELETE FROM ".X_PREFIX."threads WHERE tid='$tid' OR closed='moved|$tid'");

                if ($forums['type'] == 'sub') {
                    updateforumcount($fup['fid']);
                }
                updateforumcount($fid);

                $mod->log($xmbuser, $action, $fid, $tid);
            }
            message($lang['deletethreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'close':
        $query = $db->query("SELECT closed FROM ".X_PREFIX."threads WHERE fid=$fid AND tid='$tid'");
        if ($db->num_rows($query) == 0) {
            error($lang['textnothread'], FALSE);
        }
        $closed = $db->result($query, 0);
        $db->free_result($query);

        if (noSubmit('closesubmit')) {
            if ($closed == 'yes') {
                $lang['textclosethread'] = $lang['textopenthread'];
            } else if ($closed == '') {
                $lang['textclosethread'] = $lang['textclosethread'];
            }
            eval('echo "'.template('topicadmin_openclose').'";');
        } else {
            if ($closed == 'yes') {
                $db->query("UPDATE ".X_PREFIX."threads SET closed='' WHERE tid='$tid' AND fid='$fid'");
            } else {
                $db->query("UPDATE ".X_PREFIX."threads SET closed='yes' WHERE tid='$tid' AND fid='$fid'");
            }

            $act = ($closed != '') ? 'open' : 'close';
            $mod->log($xmbuser, $act, $fid, $tid);

            message($lang['closethreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'f_close':
        if (noSubmit('closesubmit')) {
            $tid = $mod->create_tid_string($tid);
            eval('echo "'.template('topicadmin_openclose').'";');
        } else {
            $tids = $mod->create_tid_array($tid);
            foreach($tids AS $tid) {
                $db->query("UPDATE ".X_PREFIX."threads SET closed='yes' WHERE tid='$tid' AND fid='$fid'");
                $mod->log($xmbuser, 'close', $fid, $tid);
            }

            message($lang['closethreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'f_open':
        if (noSubmit('closesubmit')) {
            $tid = $mod->create_tid_string($tid);
            $lang['textclosethread'] = $lang['textopenthread'];
            eval('echo "'.template('topicadmin_openclose').'";');
        } else {
            $tids = $mod->create_tid_array($tid);
            foreach($tids AS $tid) {
                $db->query("UPDATE ".X_PREFIX."threads SET closed='' WHERE tid='$tid' AND fid='$fid'");
                $mod->log($xmbuser, 'open', $fid, $tid);
            }

            message($lang['closethreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'move':
        if (noSubmit('movesubmit')) {
            $tid = $mod->create_tid_string($tid);
            $forumselect = forumList('moveto', false, false, $fid);
            eval('echo "'.template('topicadmin_move').'";');
        } else {
            $moveto = formInt('moveto');
            $movetorow = getForum($moveto);
            if ($movetorow === FALSE) {
                error($lang['textnoforum'], FALSE);
            }
            if ($movetorow['type'] == 'group') {
                error($lang['errormovingthreads'], FALSE);
            }

            $tids = $mod->create_tid_array($tid);
            foreach($tids AS $tid) {
                if ($type == "normal") {
                    $db->query("UPDATE ".X_PREFIX."threads SET fid=$moveto WHERE tid='$tid'");
                    $db->query("UPDATE ".X_PREFIX."posts SET fid=$moveto WHERE tid='$tid'");
                } else {
                    $query = $db->query("SELECT * FROM ".X_PREFIX."threads WHERE tid='$tid'");
                    $info = $db->fetch_array($query);
                    $db->free_result($query);

                    $db->query("INSERT INTO ".X_PREFIX."threads (fid, subject, icon, lastpost, views, replies, author, closed, topped) VALUES ({$info['fid']}, '".$db->escape_var($info['subject'])."', '', '".$db->escape_var($info['lastpost'])."', 0, 0, '".$db->escape_var($info['author'])."', 'moved|{$info['tid']}', '{$info['topped']}')");
                    $ntid = $db->insert_id();

                    $db->query("INSERT INTO ".X_PREFIX."posts (fid, tid, author, message, subject, dateline, icon, usesig, useip, bbcodeoff, smileyoff) VALUES ({$info['fid']}, '$ntid', '".$db->escape_var($info['author'])."', '{$info['tid']}', '".$db->escape_var($info['subject'])."', 0, '', '', '', '', '')");
                    $db->query("UPDATE ".X_PREFIX."threads SET fid=$moveto WHERE tid='$tid' AND fid='$fid'");
                    $db->query("UPDATE ".X_PREFIX."posts SET fid=$moveto WHERE tid='$tid' AND fid='$fid'");
                }
                updatethreadcount($tid);
                $f = "$fid -> $moveto";
                $mod->log($xmbuser, $action, $moveto, $tid);
            }

            if ($forums['type'] == 'sub') {
                updateforumcount($fup['fid']);
            }
            if ($movetorow['type'] == 'sub') {
                if ($movetorow['fup'] != $fup['fid']) {
                    updateforumcount($movetorow['fup']);
                }
            }
            updateforumcount($fid);
            updateforumcount($moveto);

            message($lang['movethreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'top':
        if (noSubmit('topsubmit')) {
            if (!is_array($tid)) {
                $query = $db->query("SELECT topped FROM ".X_PREFIX."threads WHERE fid=$fid AND tid='$tid'");
                if ($db->num_rows($query) == 0) {
                    $db->free_result($query);
                    error($lang['textnothread'], FALSE);
                }
                $topped = $db->result($query, 0);
                $db->free_result($query);
                if ($topped == 1) {
                    $lang['texttopthread'] = $lang['textuntopthread'];
                }
            } else {
                $lang['texttopthread'] = $lang['texttopthread'].' / '.$lang['textuntopthread'];
            }
            $tid = $mod->create_tid_string($tid);
            eval('echo "'.template('topicadmin_topuntop').'";');
        } else {
            $tids = $mod->create_tid_array($tid);
            foreach($tids AS $tid) {
                $query = $db->query("SELECT topped FROM ".X_PREFIX."threads WHERE fid=$fid AND tid='$tid'");
                if ($db->num_rows($query) == 0) {
                    $db->free_result($query);
                    error($lang['textnothread'], FALSE);
                }
                $topped = $db->result($query, 0);
                $db->free_result($query);

                if ($topped == 1) {
                    $db->query("UPDATE ".X_PREFIX."threads SET topped='0' WHERE tid='$tid' AND fid='$fid'");
                } else if ($topped == 0)    {
                    $db->query("UPDATE ".X_PREFIX."threads SET topped='1' WHERE tid='$tid' AND fid='$fid'");
                }

                $act = ($topped ? 'untop' : 'top');
                $mod->log($xmbuser, $act, $fid, $tid);
            }

            message($lang['topthreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'getip':
        if ($pid) {
            $query = $db->query("SELECT * FROM ".X_PREFIX."posts WHERE pid='$pid'");
        } else {
            $query = $db->query("SELECT * FROM ".X_PREFIX."threads WHERE tid='$tid'");
        }
        $ipinfo = $db->fetch_array($query);
        $db->free_result($query);
        ?>
        <form method="post" action="cp.php?action=ipban">
        <table cellspacing="0" cellpadding="0" border="0" width="60%" align="center">
        <tr><td bgcolor="<?php echo $bordercolor?>">
        <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $tablespace?>" width="100%">
        <tr>
        <td class="header" colspan="3"><?php echo $lang['textgetip']?></td>
        </tr>
        <tr bgcolor="<?php echo $altbg2?>">
        <td class="tablerow"><?php echo $lang['textyesip']?> <strong><?php echo $ipinfo['useip']?></strong> - <?php echo gethostbyaddr($ipinfo['useip'])?>
        <?php

        $ip = explode('.', $ipinfo['useip']);
        $query = $db->query("SELECT * FROM ".X_PREFIX."banned WHERE (ip1='$ip[0]' OR ip1='-1') AND (ip2='$ip[1]' OR ip2='-1') AND (ip3='$ip[2]' OR ip3='-1') AND (ip4='$ip[3]' OR ip4='-1')");
        $result = $db->fetch_array($query);
        $db->free_result($query);
        if ($result) {
            $buttontext = $lang['textunbanip'];
            for($i=1; $i<=4; ++$i) {
                $j = "ip$i";
                if ($result[$j] == -1) {
                    $result[$j] = "*";
                    $foundmask = 1;
                }
            }

            if ($foundmask) {
                $ipmask = "<strong>$result[ip1].$result[ip2].$result[ip3].$result[ip4]</strong>";
                eval($lang['evalipmask']);
                $lang['bannedipmask'] = stripslashes($lang['bannedipmask']);
                echo $lang['bannedipmask'];
            } else {
                $lang['textbannedip'] = stripslashes($lang['textbannedip']);
                echo $lang['textbannedip'];
            }
            echo "<input type=\"hidden\" name=\"delete$result[id]\" value=\"$result[id]\" />";
        } else {
            $buttontext = $lang['textbanip'];
            for($i=1; $i<=4; ++$i) {
                $j = $i - 1;
                echo "<input type=\"hidden\" name=\"newip$i\" value=\"$ip[$j]\" />";
            }
        }
        ?>
        </td>
        </tr>
        <tr bgcolor="<?php echo $altbg1?>"><td class="ctrtablerow"><input type="submit" name="ipbansubmit" value="<?php echo $buttontext?>" />
        <?php

        echo '</td></tr></table></td></tr></table></form>';
        break;

    case 'bump':
        if (noSubmit('bumpsubmit')) {
            $tid = $mod->create_tid_string($tid);
            eval('echo "'.template('topicadmin_bump').'";');
        } else {
            $tids = $mod->create_tid_array($tid);
            foreach($tids AS $tid) {
                $query = $db->query("SELECT pid FROM ".X_PREFIX."posts WHERE tid=$tid ORDER BY dateline DESC, pid DESC LIMIT 1");
                if ($db->num_rows($query) == 1) {
                    $pid = $db->result($query, 0);

                    $where = "WHERE fid=$fid";
                    if ($forums['type'] == 'sub') {
                        $where .= " OR fid={$forums['fup']}";
                    }

                    $db->query("UPDATE ".X_PREFIX."threads SET lastpost='$onlinetime|$xmbuser|$pid' WHERE tid=$tid AND fid=$fid");
                    $db->query("UPDATE ".X_PREFIX."forums SET lastpost='$onlinetime|$xmbuser|$pid' $where");

                    $mod->log($xmbuser, $action, $fid, $tid);
                }
                $db->free_result($query);
            }

            message($lang['bumpthreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'empty':
        if (noSubmit('emptysubmit')) {
            $tid = $mod->create_tid_string($tid);
            eval('echo "'.template('topicadmin_empty').'";');
        } else {
            require('include/attach.inc.php');
            $tids = $mod->create_tid_array($tid);
            foreach($tids AS $tid) {
                $query = $db->query("SELECT pid FROM ".X_PREFIX."posts WHERE tid=$tid ORDER BY dateline ASC LIMIT 1");
                if ($db->num_rows($query) == 1) {
                    $pid = $db->result($query, 0);
                    $query = $db->query("SELECT author, COUNT(pid) AS pidcount FROM ".X_PREFIX."posts WHERE tid=$tid AND pid!=$pid GROUP BY author");
                    while($result = $db->fetch_array($query)) {
                        $dbauthor = $db->escape_var($result['author']);
                        $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum-{$result['pidcount']} WHERE username='$dbauthor'");
                    }

                    emptyThreadAttachments($tid, $pid);  // Must delete attachments before posts!
                    $db->query("DELETE FROM ".X_PREFIX."posts WHERE tid=$tid AND pid!=$pid");

                    updatethreadcount($tid); //Also updates lastpost
                    $mod->log($xmbuser, $action, $fid, $tid);
                }
                $db->free_result($query);
            }
            if ($forums['type'] == 'sub') {
                updateforumcount($fup['fid']);
            }
            updateforumcount($fid);

            message($lang['emptythreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'split':
        if (noSubmit('splitsubmit')) {
            $query = $db->query("SELECT replies FROM ".X_PREFIX."threads WHERE tid='$tid'");
            if ($db->num_rows($query) == 0) {
                error($lang['textnothread'], FALSE);
            }
            $replies = $db->result($query, 0);
            $db->free_result($query);
            if ($replies == 0) {
                error($lang['cantsplit'], false);
            }

            $query = $db->query("SELECT * FROM ".X_PREFIX."posts WHERE tid='$tid' ORDER BY dateline");
            $posts = '';
            while($post = $db->fetch_array($query))    {
                $bbcodeoff = $post['bbcodeoff'];
                $smileyoff = $post['smileyoff'];
                $post['message'] = stripslashes($post['message']);
                $post['message'] = postify($post['message'], $smileyoff, $bbcodeoff, $fid, $bordercolor, 'no', 'no');
                eval('$posts .= "'.template('topicadmin_split_row').'";');
            }
            $db->free_result($query);
            eval('echo "'.template('topicadmin_split').'";');
        } else {
            $subject = addslashes(postedVar('subject', 'javascript', TRUE, TRUE, TRUE));  // Subjects are historically double-quoted
            if ($subject == '') {
                error($lang['textnosubject'], false);
            }

            $threadcreated = false;
            $firstmove = false;
            $query = $db->query("SELECT pid, author, dateline, subject FROM ".X_PREFIX."posts WHERE tid='$tid' ORDER BY dateline ASC");
            $movecount = 0;
            while($post = $db->fetch_array($query)) {
                $move = getInt('move'.$post['pid'], 'p');
                if ($move == $post['pid']) {
                    if (!$threadcreated) {
                        $thatime = $onlinetime;
                        $db->query("INSERT INTO ".X_PREFIX."threads (fid, subject, icon, lastpost, views, replies, author, closed, topped) VALUES ($fid, '$subject', '', '$thatime|$xmbuser', 0, 0, '".$db->escape_var($post['author'])."', '', 0)");
                        $newtid = $db->insert_id();
                        $threadcreated = true;
                    }

                    $newsub = '';
                    if (!$firstmove) {
                        $newsub = ", subject='$subject'";
                        $firstmove = true;
                    }
                    $db->query("UPDATE ".X_PREFIX."posts SET tid=$newtid $newsub WHERE pid=$move");
                    $lastpost = $post['dateline'].'|'.$db->escape_var($post['author']).'|'.$post['pid'];
                    $movecount++;
                } else {
                    $oldlastpost = $post['dateline'].'|'.$db->escape_var($post['author']).'|'.$post['pid'];
                }
            }
            $db->query("UPDATE ".X_PREFIX."threads SET replies=$movecount-1, lastpost='$lastpost' WHERE tid='$newtid'");
            $db->query("UPDATE ".X_PREFIX."threads SET replies=replies-$movecount, lastpost='$oldlastpost' WHERE tid='$tid'");
            $db->free_result($query);

            $mod->log($xmbuser, $action, $fid, $tid);

            if ($forums['type'] == 'sub') {
                updateforumcount($fup['fid']);
            }
            updateforumcount($fid);

            message($lang['splitthreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'merge':
        $tid = intval($tid);
        if (noSubmit('mergesubmit')) {
            eval('echo "'.template('topicadmin_merge').'";');
        } else {
            if ($othertid == 0) {
                error($lang['invalidtid'], false);
            } else if ($tid == $othertid) {
                error($lang['cannotmergesamethread'], false);
            }

            $queryadd1 = $db->query("SELECT t.replies, t.fid, f.type, f.fup FROM ".X_PREFIX."threads AS t LEFT JOIN ".X_PREFIX."forums AS f USING(fid) WHERE t.tid='$othertid'");

            if ($db->num_rows($queryadd1) == 0) {
                $db->free_result($queryadd1);
                error($lang['invalidtid'], false);
            }
            $otherthread = $db->fetch_array($queryadd1);
            $db->free_result($queryadd1);
            $replyadd = intval($otherthread['replies']) + 1;
            $otherfid = $otherthread['fid'];

            $db->query("UPDATE ".X_PREFIX."posts SET tid='$tid', fid='$fid' WHERE tid='$othertid'");

            $db->query("UPDATE ".X_PREFIX."threads SET closed='moved|$tid' WHERE closed='moved|$othertid'");

            $db->query("DELETE FROM ".X_PREFIX."threads WHERE tid='$othertid'");

            $db->query("DELETE FROM d, r, v "
                     . "USING ".X_PREFIX."vote_desc AS d "
                     . "LEFT JOIN ".X_PREFIX."vote_results AS r ON r.vote_id = d.vote_id "
                     . "LEFT JOIN ".X_PREFIX."vote_voters AS v  ON v.vote_id = d.vote_id "
                     . "WHERE d.topic_id = $othertid");

            $db->query("UPDATE ".X_PREFIX."favorites AS f "
                     . "INNER JOIN ".X_PREFIX."members AS m ON m.username = f.username "
                     . "INNER JOIN ( "
                     . " SELECT username, COUNT(*) AS fcount "
                     . " FROM ".X_PREFIX."favorites AS f2 "
                     . " WHERE tid=$tid "
                     . " GROUP BY username "
                     . ") AS query2 ON m.username = query2.username "
                     . "SET f.tid=$tid "
                     . "WHERE f.tid='$othertid' AND query2.fcount=0");
            $db->query("DELETE FROM ".X_PREFIX."favorites WHERE tid='$othertid'");

            $query = $db->query("SELECT subject, author, icon FROM ".X_PREFIX."posts WHERE tid='$tid' OR tid='$othertid' ORDER BY pid ASC LIMIT 1");
            $thread = $db->fetch_array($query);
            $db->free_result($query);
            $query = $db->query("SELECT author, dateline, pid FROM ".X_PREFIX."posts WHERE tid='$tid' ORDER BY dateline DESC LIMIT 0, 1");
            $lastpost = $db->fetch_array($query);
            $db->free_result($query);
            $db->query("UPDATE ".X_PREFIX."threads SET replies=replies+'$replyadd', subject='".$db->escape_var($thread['subject'])."', icon='{$thread['icon']}', author='".$db->escape_var($thread['author'])."', lastpost='{$lastpost['dateline']}|".$db->escape_var($lastpost['author'])."|{$lastpost['pid']}' WHERE tid='$tid'");

            $mod->log($xmbuser, $action, $fid, $tid);

            if ($forums['type'] == 'sub') {
                updateforumcount($fup['fid']);
            }
            if ($otherthread['type'] == 'sub') {
                if ($otherthread['fup'] != $fup['fid']) {
                    updateforumcount($otherthread['fup']);
                }
            }
            updateforumcount($fid);
            updateforumcount($otherfid);

            message($lang['mergethreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'threadprune':
        if (noSubmit('threadprunesubmit')) {
            $query = $db->query("SELECT replies FROM ".X_PREFIX."threads WHERE tid='$tid'");
            if ($db->num_rows($query) == 0) {
                error($lang['textnothread'], FALSE);
            }
            $replies = $db->result($query, 0);
            $db->free_result($query);

            if ($replies == 0) {
                error($lang['cantthreadprune'], false);
            }

            if (X_SADMIN || $SETTINGS['allowrankedit'] == 'off') {
                $disablePost = '';
                $posts = '';
                $query = $db->query("SELECT * FROM ".X_PREFIX."posts WHERE tid='$tid' ORDER BY dateline");
                while($post = $db->fetch_array($query)) {
                    $bbcodeoff = $post['bbcodeoff'];
                    $smileyoff = $post['smileyoff'];
                    $post['message'] = stripslashes($post['message']);
                    $post['message'] = postify($post['message'], $smileyoff, $bbcodeoff, $fid, $bordercolor, 'no', 'no');
                    eval('$posts .= "'.template('topicadmin_threadprune_row').'";');
                }
                $db->free_result($query);
            } else {
                $ranks = array('Super Administrator'=>5, 'Administrator'=>4, 'Super Moderator'=>3, 'Moderator'=>2, 'Member'=>1, ''=>0);
                $posts = '';
                $query = $db->query("SELECT p.*, m.status FROM ".X_PREFIX."posts p LEFT JOIN ".X_PREFIX."members m ON (m.username=p.author) WHERE tid='$tid' ORDER BY dateline");
                while($post = $db->fetch_array($query)) {
                    if ($ranks[$post['status']] > $ranks[$self['status']]) {
                        $disablePost = 'disabled="disabled"';
                    } else {
                        $disablePost = '';
                    }
                    $bbcodeoff = $post['bbcodeoff'];
                    $smileyoff = $post['smileyoff'];
                    $post['message'] = stripslashes($post['message']);
                    $post['message'] = postify($post['message'], $smileyoff, $bbcodeoff, $fid, $bordercolor, 'no', 'no');
                    eval('$posts .= "'.template('topicadmin_threadprune_row').'";');
                }
                $db->free_result($query);
            }
            eval('echo "'.template('topicadmin_threadprune').'";');
        } else {
            $postcount = $db->result($db->query("SELECT COUNT(pid) FROM ".X_PREFIX."posts WHERE tid=$tid"), 0);
            $delcount = 0;
            foreach($_POST as $key=>$val) {
                if (substr($key, 0, 4) == 'move') {
                    $delcount++;
                }
            }
            if ($delcount >= $postcount) {
                error($lang['cantthreadprune'], false);
            }
            require('include/attach.inc.php');
            if (X_SADMIN || $SETTINGS['allowrankedit'] == 'off') {
                $query = $db->query("SELECT author, pid, message FROM ".X_PREFIX."posts WHERE tid='$tid'");
                while($post = $db->fetch_array($query))    {
                    $move = "move".$post['pid'];
                    $move = getInt($move, 'p');
                    if (!empty($move)) {
                        $dbauthor = $db->escape_var($post['author']);
                        $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum-1 WHERE username='$dbauthor'");
                        $db->query("DELETE FROM ".X_PREFIX."posts WHERE pid=$move");
                        deleteAllAttachments($move);
                        $db->query("UPDATE ".X_PREFIX."threads SET replies=replies-1 WHERE tid='$tid'");
                    }
                }
                $db->free_result($query);
            } else {
                $ranks = array('Super Administrator'=>5, 'Administrator'=>4, 'Super Moderator'=>3, 'Moderator'=>2, 'Member'=>1, ''=>0);
                $query = $db->query("SELECT m.status, p.author, p.pid FROM ".X_PREFIX."posts p LEFT JOIN ".X_PREFIX."members m ON (m.username=p.author) WHERE p.tid='$tid'");
                while($post = $db->fetch_array($query))    {
                    if ($ranks[$post['status']] > $ranks[$self['status']]) {
                        continue;
                    }
                    $move = "move".$post['pid'];
                    $move = getInt($move, 'p');
                    if (!empty($move)) {
                        $dbauthor = $db->escape_var($post['author']);
                        $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum-1 WHERE username='$dbauthor'");
                        $db->query("DELETE FROM ".X_PREFIX."posts WHERE pid=$move");
                        deleteAllAttachments($move);
                        $db->query("UPDATE ".X_PREFIX."threads SET replies=replies-1 WHERE tid='$tid'");
                    }
                }
                $db->free_result($query);
            }

            $firstauthor = $db->result($db->query("SELECT author FROM ".X_PREFIX."posts WHERE tid='$tid' ORDER BY dateline ASC LIMIT 0,1"), 0);
            $firstauthor = $db->escape_var($firstauthor);

            $query = $db->query("SELECT pid, author, dateline FROM ".X_PREFIX."posts WHERE tid='$tid' ORDER BY dateline DESC LIMIT 0,1");
            $lastpost = $db->fetch_array($query);
            $db->free_result($query);

            $db->query("UPDATE ".X_PREFIX."threads SET author='$firstauthor', lastpost='$lastpost[dateline]|".$db->escape_var($lastpost['author'])."|$lastpost[pid]' WHERE tid='$tid'");

            if ($forums['type'] == 'sub') {
                updateforumcount($fup['fid']);
            }
            updateforumcount($fid);

            $mod->log($xmbuser, $action, $fid, $tid);

            message($lang['complete_threadprune'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;

    case 'copy':
        if (noSubmit('copysubmit')) {
            $tid = $mod->create_tid_string($tid);
            $forumselect = forumList('newfid', false, false);
            eval('echo "'.template('topicadmin_copy').'";');
        } else {
            require('include/attach.inc.php');
            if (!formInt('newfid')) {
                error($lang['privforummsg'], false);
            }

            $newfid = getRequestInt('newfid');
            
            $otherforum = getForum($newfid);
            if ($otherforum === FALSE) {
                error($lang['textnoforum'], FALSE);
            }

            if (!$mod->statuscheck($newfid)) {
                error($lang['notpermitted'], false);
            }

            $tids = $mod->create_tid_array($tid);
            foreach($tids AS $tid) {
                $thread = $db->fetch_array($db->query("SELECT * FROM ".X_PREFIX."threads WHERE tid='$tid'"));

                $thread['fid'] = $newfid;
                unset($thread['tid']);

                $cols = array();
                $vals = array();

                foreach($thread as $key=>$val) {
                    $cols[] = $key;
                    $vals[] = $db->escape_var($val);
                }
                $columns = implode(', ', $cols);
                $values  = "'".implode("', '", $vals)."'";

                $db->query("INSERT INTO ".X_PREFIX."threads ($columns) VALUES ($values)");

                $newtid = $db->insert_id();

                $query = $db->query("SELECT * FROM ".X_PREFIX."posts WHERE tid='$tid' ORDER BY pid ASC");
                while($post = $db->fetch_array($query)) {
                    $oldPid = $post['pid'];
                    $post['fid'] = $newfid;
                    $post['tid'] = $newtid;
                    unset($post['pid']);

                    $cols = array();
                    $vals = array();

                    foreach($post as $key=>$val) {
                        $cols[] = $key;
                        $vals[] = $db->escape_var($val);
                    }
                    $columns = implode(', ', $cols);
                    $values  = "'".implode("', '", $vals)."'";

                    $db->query("INSERT INTO ".X_PREFIX."posts ($columns) VALUES ($values)");
                    $newpid = $db->insert_id();

                    copyAllAttachments($oldPid, $newpid);
                }

                $query = $db->query("SELECT author, COUNT(pid) AS pidcount FROM ".X_PREFIX."posts WHERE tid='$tid' GROUP BY author");
                while($result = $db->fetch_array($query)) {
                    $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum+{$result['pidcount']} WHERE username='".$db->escape_var($result['author'])."'");
                }
                $db->free_result($query);

                $mod->log($xmbuser, $action, $fid, $tid);
                
                if ($otherforum['type'] == 'sub') {
                    updateforumcount($otherforum['fup']);
                }
                updateforumcount($newfid);
            }

            message($lang['copythreadmsg'], false, '', '', $full_url.'forumdisplay.php?fid='.$fid, true, false, true);
        }
        break;
}

end_time();
eval('echo "'.template('footer').'";');
?>

<?php
/**
 * eXtreme Message Board
 * XMB 1.9.10 Karl
 *
 * Developed And Maintained By The XMB Group
 * Copyright (c) 2001-2008, The XMB Group
 * http://www.xmbforum.com
 *
 * Sponsored By iEntry, Inc.
 * Copyright (c) 2007, iEntry, Inc.
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

define('X_SCRIPT', 'post.php');

require 'header.php';

loadtemplates(
'post_captcha',
'post_notloggedin',
'post_loggedin',
'post_preview',
'post_attachmentbox',
'post_newthread',
'post_reply_review_toolong',
'post_reply_review_post',
'post_reply',
'post_edit',
'functions_smilieinsert',
'functions_smilieinsert_smilie',
'functions_bbcodeinsert',
'forumdisplay_password',
'functions_bbcode',
'post_newpoll',
'post_edit_attachment'
);

eval('$css = "'.template('css').'";');

if (X_GUEST) {
    eval('$loggedin = "'.template('post_notloggedin').'";');
} else {
    eval('$loggedin = "'.template('post_loggedin').'";');
}

if ($self['ban'] == "posts" || $self['ban'] == "both") {
    error($lang['textbanfrompost']);
}

//Validate $pid, $tid, $fid, and $repquote
$fid = -1;
$tid = -1;
$pid = -1;
$repquote = -1;
if ($action == 'edit') {
    $pid = getRequestInt('pid');
    $query = $db->query("SELECT f.*, t.tid FROM ".X_PREFIX."posts AS p LEFT JOIN (".X_PREFIX."threads AS t LEFT JOIN ".X_PREFIX."forums AS f USING (fid)) USING (tid) WHERE p.pid=$pid");
    if ($db->num_rows($query) != 1) {
        error($lang['textnothread']);
    }
    $forum = $db->fetch_array($query);
    $db->free_result($query);
    $fid = $forum['fid'];
    $tid = $forum['tid'];
} elseif ($action == 'reply') {
    $tid = getRequestInt('tid');
    $repquote = getInt('repquote');
    $query = $db->query("SELECT f.* FROM ".X_PREFIX."threads AS t LEFT JOIN ".X_PREFIX."forums AS f USING (fid) WHERE t.tid=$tid");
    if ($db->num_rows($query) != 1) {
        error($lang['textnothread']);
    }
    $forum = $db->fetch_array($query);
    $db->free_result($query);
    $fid = $forum['fid'];
} elseif ($action == 'newthread') {
    $fid = getRequestInt('fid');
    $query = $db->query("SELECT * FROM ".X_PREFIX."forums WHERE fid=$fid");
    if ($db->num_rows($query) != 1) {
        error($lang['textnoforum']);
    }
    $forum = $db->fetch_array($query);
    $db->free_result($query);
} else {
    error($lang['textnoaction']);
}

if ($fid == 0 || ($forum['type'] != 'forum' && $forum['type'] != 'sub') || $forum['status'] != 'on') {
    error($lang['textnoforum']);
}

if ($tid > 0) {
    $query = $db->query("SELECT * FROM ".X_PREFIX."threads WHERE tid=$tid");
    if ($db->num_rows($query) != 1) {
        error($lang['textnothread']);
    }
    $thread = $db->fetch_array($query);
    $db->free_result($query);
    $threadname = stripslashes($thread['subject']);
} else {
    $thread = array();
    $threadname = '';
}

//Warning! These variables are used for template output.
$captchapostcheck = '';
$dissubject = '';
$message = '';
$message1 = '';
$preview = '';
$subject = '';
if (X_GUEST) {
    $username = 'Anonymous';
} else {
    $username = $xmbuser;
}

smcwcache();

validatePpp();

if (isset($forum['type']) && $forum['type'] == 'forum') {
    nav('<a href="forumdisplay.php?fid='.$fid.'">'.stripslashes($forum['name']).'</a>');
} else {
    if (!isset($forum['fup']) || !is_numeric($forum['fup'])) {
        error($lang['textnoforum']);
    } else {
        $query = $db->query("SELECT name, fid FROM ".X_PREFIX."forums WHERE fid={$forum['fup']}");
        $fup = $db->fetch_array($query);
        nav('<a href="forumdisplay.php?fid='.intval($fup['fid']).'">'.stripslashes($fup['name']).'</a>');
        nav('<a href="forumdisplay.php?fid='.$fid.'">'.stripslashes($forum['name']).'</a>');
    }
}

$attachfile = '';
if (isset($forum['attachstatus']) && $forum['attachstatus'] != 'off') {
    eval('$attachfile = "'.template("post_attachmentbox").'";');
}

$listed_icons = 0;
$icons = '<input type="radio" name="posticon" value="" /> <img src="'.$imgdir.'/default_icon.gif" alt="[*]" border="0" />';
if ($action != 'edit') {
    $captchapostcheck = '';
    if (X_GUEST && $SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_post_status'] == 'on' && !DEBUG) {
        require ROOT.'include/captcha.inc.php';
        $Captcha = new Captcha(250, 50);
    }

    $querysmilie = $db->query("SELECT url, code FROM ".X_PREFIX."smilies WHERE type='picon'");
    while($smilie = $db->fetch_array($querysmilie)) {
        $icons .= ' <input type="radio" name="posticon" value="'.$smilie['url'].'" /><img src="'.$smdir.'/'.$smilie['url'].'" alt="'.$smilie['code'].'" border="0" />';
        $listed_icons += 1;
        if ($listed_icons == 9) {
            $icons .= '<br />';
            $listed_icons = 0;
        }
    }
    $db->free_result($querysmilie);
}

eval('$bbcodescript = "'.template('functions_bbcode').'";');

if (!isset($usesig)) {
    $usesig = 'no';
}

if ($usesig != 'yes') {
    $usesig = 'no';
}

$allowimgcode = (isset($forum['allowimgcode']) && $forum['allowimgcode'] == 'yes') ? $lang['texton'] : $lang['textoff'];
$allowhtml = (isset($forum['allowhtml']) && $forum['allowhtml'] == 'yes') ? $lang['texton'] : $lang['textoff'];
$allowsmilies = (isset($forum['allowsmilies']) && $forum['allowsmilies'] == 'yes') ? $lang['texton'] : $lang['textoff'];
$allowbbcode = (isset($forum['allowbbcode']) && $forum['allowbbcode'] == 'yes') ? $lang['texton'] : $lang['textoff'];


// check permissions on this forum (and top forum if it's a sub?)
$perms = checkForumPermissions($forum);
if(!$perms[X_PERMS_VIEW] || !$perms[X_PERMS_USERLIST]) {
    error($lang['privforummsg']);
} elseif(!$perms[X_PERMS_PASSWORD]) {
    handlePasswordDialog($fid, basename(__FILE__), $_GET);
}

// check posting permissions specifically
if($action == 'newthread') {
    if (!$perms[X_PERMS_THREAD]) {
        error($lang['textnoaction']);
    } else if(isset($poll) && $poll == 'yes') {
        if(!$perms[X_PERMS_POLL]) {
            error($lang['textnoaction']);
        }
    }
} elseif($action == 'reply') {
    if(!$perms[X_PERMS_REPLY]) {
        error($lang['textnoaction']);
    }
} elseif($action == 'edit') {
    // let's allow edits for now, we'll check for permissions later on in the script (due to need for $orig['author'])
} else {
    error($lang['textnoaction']);
}

// check parent-forum permissions
if($forum['type'] == 'sub') {
    $fup = $db->fetch_array($db->query("SELECT postperm, userlist, password FROM ".X_PREFIX."forums WHERE fid=$forum[fup]"));
    $fupPerms = checkForumPermissions($fup);

    if(!$fupPerms[X_PERMS_VIEW] || !$fupPerms[X_PERMS_USERLIST] || !$fupPerms[X_PERMS_PASSWORD]) {
        error($lang['privforummsg']);
    }
    // do not show password-dialog here; it makes the situation too complicated
}

if (isset($smileyoff) && $smileyoff == 'yes') {
    $smileoffcheck = $cheHTML;
} else {
    $smileoffcheck = '';
    $smileyoff = 'no';
}

if (isset($bbcodeoff) && $bbcodeoff == 'yes') {
    $codeoffcheck = $cheHTML;
} else {
    $codeoffcheck = '';
    $bbcodeoff = 'no';
}

if (isset($emailnotify) && $emailnotify == 'yes') {
    $emailnotifycheck = $cheHTML;
} else {
    $emailnotifycheck = '';
    $emailnotify = 'no';
}

if (isset($subaction) && $subaction == 'spellcheck' && (isset($spellchecksubmit) || isset($spellcheckersubmit))) {
    $sc = true;
} else {
    $sc = false;
}

if ((isset($previewpost) || $sc) && isset($usesig) && $usesig == 'yes') {
    $usesigcheck = $cheHTML;
} else if (isset($previewpost) || $sc) {
    $usesigcheck = '';
} else if ($self['sig'] != '') {
    $usesigcheck = $cheHTML;
} else {
    $usesigcheck = '';
}

if (X_STAFF) {
    if (isset($toptopic) && $toptopic == 'yes') {
        $topcheck = $cheHTML;
    } else {
        $topcheck = '';
        $toptopic = 'no';
    }

    if (isset($closetopic) && $closetopic == 'yes') {
        $closecheck = $cheHTML;
    } else {
        $closecheck = '';
        $closetopic = 'no';
    }
} else {
    $topcheck = '';
    $closecheck = '';
}

if (isset($poll)) {
    if ($poll != 'yes') {
        $poll = '';
    } else {
        $poll = 'yes';
    }
} else {
    $poll = '';
}

if (!empty($posticon)) {
    $thread['icon'] = (file_exists($smdir.'/'.$posticon)) ? "<img src=\"$smdir/$posticon\" />" : '';
    $icons = str_replace('<input type="radio" name="posticon" value="'.$posticon.'" />', '<input type="radio" name="posticon" value="'.$posticon.'" checked="checked" />', $icons);
} else {
    $thread['icon'] = '';
    $icons = str_replace('<input type="radio" name="posticon" value="" />', '<input type="radio" name="posticon" value="" checked="checked" />', $icons);
}

$messageinput = postedVar('message', '', TRUE, FALSE);  //postify() is responsible for DECODING if html is allowed.

if ($SETTINGS['spellcheck'] == 'on') {
    $spelling_submit1 = '<input type="hidden" name="subaction" value="spellcheck" /><input type="submit" class="submit" name="spellchecksubmit" value="'.$lang['checkspelling'].'" />';
    $spelling_lang = '<select name="language"><option value="en" selected="selected">English</option></select>';
    if (isset($subaction) && $subaction == 'spellcheck' && (isset($spellchecksubmit) || isset($updates_submit))) {
        if (!isset($updates_submit)) {
            require ROOT.'include/spelling.inc.php';
            $spelling = new spelling($language);
            $problems = $spelling->check_text($messageinput);
            if (count($problems) > 0) {
                $suggest = array();
                foreach($problems as $orig=>$new) {
                    $mistake = array();
                    foreach($new as $suggestion) {
                        eval('$mistake[] = "'.template('spelling_suggestion_new').'";');
                    }
                    $mistake = implode("\n", $mistake);
                    eval('$suggest[] = "'.template('spelling_suggestion_row').'";');
                }
                $suggestions = implode("\n", $suggest);
                eval('$suggestions = "'.template('spelling_suggestion').'";');
                $spelling_submit2 = '<input type="submit" class="submit" name="updates_submit" value="'.$lang['replace'].'" />';
            } else {
                eval('$suggestions = "'.template('spelling_suggestion_no').'";');
                $spelling_submit2 = '';
            }
        } else {
            $old_words = postedArray('old_words');
            foreach($old_words as $word) {
                $messageinput = str_replace($word, ${'replace_'.$word}, $messageinput);
            }
            $spelling_submit2 = '';
        }
    } else {
        $suggestions = '';
        $spelling_submit2 = '';
    }
} else {
    $spelling_submit1 = '';
    $spelling_submit2 = '';
    $spelling_lang = '';
    $suggestions = '';
}

if (isset($topicsubmit)) {
    if (strlen(postedVar('subject')) == 0) {
        $preview = error($lang['textnosubject'], false, '', '<br /><br />', false, false, true, false);
        $error = true;
        unset($topicsubmit);
        if (isset($previewpost)) {
            unset($previewpost);
        }
    } else {
        $preview = '';
        $error = false;
    }
} else {
    $error = false;
}

$bbcodeinsert = bbcodeinsert();
$smilieinsert = smilieinsert();

//Allow sanitized message to pass-through to template in case of: #1 preview, #2 post error
$subject = censor(postedVar('subject', 'javascript', TRUE, FALSE, TRUE));  //per viewthread design of version 1.9.9, HTML is never allowed in subjects.
$message = censor($messageinput);

if (isset($previewpost)) {
    $currtime = $onlinetime;
    $date = gmdate($dateformat, $currtime + ($timeoffset * 3600) + ($addtime * 3600));
    $time = gmdate($timecode, $currtime + ($timeoffset * 3600) + ($addtime * 3600));
    $poston = $lang['textposton'].' '.$date.' '.$lang['textat'].' '.$time;

    $dissubject = $subject;
    $message1 = postify($messageinput, $smileyoff, $bbcodeoff, $forum['allowsmilies'], $forum['allowhtml'], $forum['allowbbcode'], $forum['allowimgcode']);

    if ($pid > 0) {
        eval('$preview = stripslashes("'.template('post_preview').'");');
    } else {
        eval('$preview = "'.template('post_preview').'";');
    }
}

switch($action) {
    case 'reply':
        nav('<a href="viewthread.php?tid='.$tid.'">'.$threadname.'</a>');
        nav($lang['textreply']);
        if ($SETTINGS['subject_in_title'] == 'on') {
            $threadSubject = '- '.$threadname;
        }

        //Check all replying permissions for this $tid.
        if (!X_SADMIN And $thread['closed'] != '') {
            error($lang['closedmsg']);
        }

        if (!isset($replysubmit) || !$replysubmit) {
            if (X_GUEST && $SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_post_status'] == 'on' && !DEBUG) {
                if ($Captcha->bCompatible !== false) {
                    $imghash = $Captcha->GenerateCode();
                    eval('$captchapostcheck = "'.template('post_captcha').'";');
                }
            }

            $posts = '';
            eval('echo "'.template('header').'";');

            if (X_STAFF) {
                $closeoption = '<br /><input type="checkbox" name="closetopic" value="yes" '.$closecheck.' /> '.$lang['closemsgques'].'<br />';
            } else {
                $closeoption = '';
            }

            if (isset($repquote) && ($repquote = (int) $repquote)) {
                $query = $db->query("SELECT p.message, p.fid, p.author, f.postperm, f.userlist, f.password FROM ".X_PREFIX."posts p, ".X_PREFIX."forums f WHERE p.pid=$repquote AND f.fid=p.fid");
                $thaquote = $db->fetch_array($query);
                $db->free_result($query);
                $quotefid = $thaquote['fid'];

                $quoteperms = checkForumPermissions($thaquote);
                if(!$quoteperms[X_PERMS_VIEW] || !$quoteperms[X_PERMS_USERLIST]) {
                    error($lang['privforummsg'], false);
                }
                $message = "[quote][i]{$lang['origpostedby']} {$thaquote['author']}[/i]\n".censor(stripslashes($thaquote['message']))." [/quote]"; //Messages are historically double-quoted.
            }

            $querytop = $db->query("SELECT COUNT(tid) FROM ".X_PREFIX."posts WHERE tid='$tid'");
            $replynum = $db->result($querytop, 0);
            if ($replynum >= $ppp) {
                $threadlink = 'viewthread.php?fid='.$fid.'&tid='.$tid;
                eval($lang['evaltrevlt']);
                eval('$posts .= "'.template('post_reply_review_toolong').'";');
            } else {
                $thisbg = $altbg1;
                $query = $db->query("SELECT * FROM ".X_PREFIX."posts WHERE tid='$tid' ORDER BY dateline DESC");
                while($post = $db->fetch_array($query)) {
                    $date = gmdate($dateformat, $post['dateline'] + ($timeoffset * 3600) + ($addtime * 3600));
                    $time = gmdate($timecode, $post['dateline'] + ($timeoffset * 3600) + ($addtime * 3600));
                    $poston = $lang['textposton'].' '.$date.' '.$lang['textat'].' '.$time;

                    if ($post['icon'] != '') {
                        $post['icon'] = '<img src="'.$smdir.'/'.$post['icon'].'" alt="'.$lang['altpostmood'].'" border="0" />';
                    } else {
                        $post['icon'] = '<img src="'.$imgdir.'/default_icon.gif" alt="[*]" border="0" />';
                    }

                    $post['message'] = postify($post['message'], $post['smileyoff'], $post['bbcodeoff'], $forum['allowsmilies'], $forum['allowhtml'], $forum['allowbbcode'], $forum['allowimgcode']);
                    eval('$posts .= "'.template('post_reply_review_post').'";');
                    if ($thisbg == $altbg2) {
                        $thisbg = $altbg1;
                    } else {
                        $thisbg = $altbg2;
                    }
                }
                $db->free_result($query);
            }
            $db->free_result($querytop);

            $attachfile = '';
            if (isset($forum['attachstatus']) && $forum['attachstatus'] == 'on') {
                eval('$attachfile = "'.template('post_attachmentbox').'";');
            }
            eval('echo stripslashes("'.template('post_reply').'");');
        } else {
            if (strlen(postedVar('subject')) == 0 And strlen($messageinput) == 0) {
                error($lang['postnothing']);
            }

            if (X_GUEST And strlen(postedVar('username')) > 0 And isset($_POST['password'])) {
                $password = '';
                if (loginUser(postedVar('username'), md5($_POST['password']))) {
                    $username = $xmbuser;
                } else {
                    error($lang['textpw1']);
                }
            }

            if ($self['status'] == "Banned") {
                error($lang['bannedmessage']);
            }

            if ($self['ban'] == 'posts' || $self['ban'] == 'both') {
                error($lang['textbanfrompost']);
            }

            if ($username == 'Anonymous' && $SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_post_status'] == 'on' && !DEBUG) {
                if ($Captcha->bCompatible !== false) {
                    $imghash = addslashes($imghash);
                    $imgcode = addslashes($imgcode);
                    if ($Captcha->ValidateCode($imgcode, $imghash) !== true) {
                        error($lang['captchaimageinvalid']);
                    }
                }
            }

            if (isset($posticon) && $posticon != "") {
                $query = $db->query("SELECT id FROM ".X_PREFIX."smilies WHERE type='picon' AND url='$posticon'");
                if (!$db->result($query, 0)) {
                    $db->free_result($query);
                    exit();
                }
            } else {
                $posticon = '';
            }

            $query = $db->query("SELECT lastpost, type, fup FROM ".X_PREFIX."forums WHERE fid='$fid'");
            $for = $db->fetch_array($query);
            $db->free_result($query);
            $last = $for['lastpost'];

            if ($last != '') {
                $lastpost = explode('|', $last);
                $rightnow = $onlinetime - $floodctrl;
                if ($rightnow <= $lastpost[0] && $username == $lastpost[1]) {
                    $floodlink = "<a href=\"viewthread.php?fid=$fid&tid=$tid\">Click here</a>";
                    error($lang['floodprotect'].' '.$floodlink.' '.$lang['tocont']);
                }
            }

            if ($usesig != "yes") {
                $usesig = "no";
            }

            $thatime = $onlinetime;
            $dbmessage = $db->escape(addslashes($messageinput)); //The message column is historically double-quoted.
            $dbsubject = addslashes(postedVar('subject', 'javascript', TRUE, TRUE, TRUE));
            $db->query("INSERT INTO ".X_PREFIX."posts (fid, tid, author, message, subject, dateline, icon, usesig, useip, bbcodeoff, smileyoff) VALUES ($fid, $tid, '$username', '$dbmessage', '$dbsubject', ".$db->time(time()).", '$posticon', '$usesig', '$onlineip', '$bbcodeoff', '$smileyoff')");
            $pid = $db->insert_id();

            if ((X_STAFF) && $closetopic == 'yes') {
                $db->query("UPDATE ".X_PREFIX."threads SET closed='yes' WHERE tid='$tid' AND fid='$fid'");
            }

            $db->query("UPDATE ".X_PREFIX."threads SET lastpost='$thatime|$username|$pid', replies=replies+1 WHERE (tid='$tid' AND fid='$fid') OR closed='moved|$tid'");

            if ($for['type'] == 'sub') {
                $db->query("UPDATE ".X_PREFIX."forums SET lastpost='$thatime|$username|$pid', posts=posts+1 WHERE fid='$for[fup]'");
            }

            $db->query("UPDATE ".X_PREFIX."forums SET lastpost='$thatime|$username|$pid', posts=posts+1 WHERE fid='$fid'");
            $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum+1 WHERE username='$username'");

            $query = $db->query("SELECT COUNT(pid) FROM ".X_PREFIX."posts WHERE pid <= $pid AND tid='$tid'");
            $posts = $db->result($query,0);
            $db->free_result($query);

            if ($posts > $ppp) {
                $topicpages = quickpage($posts, $ppp);
            } else {
                $topicpages = 1;
            }

            $date = $db->result($db->query("SELECT dateline FROM ".X_PREFIX."posts WHERE tid='$tid' AND pid < $pid ORDER BY pid ASC LIMIT 1"), 0);
            $subquery = $db->query("SELECT m.email, m.lastvisit, m.ppp, m.status FROM ".X_PREFIX."favorites f LEFT JOIN ".X_PREFIX."members m ON (m.username=f.username) WHERE f.type='subscription' AND f.tid='$tid' AND f.username!= '$username'");
            while($subs = $db->fetch_array($subquery)) {
                if ($subs['status'] == 'banned' || $subs['lastvisit'] < $date) {
                    continue;
                }

                if ($subs['ppp'] < 1) {
                    $subs['ppp'] = $posts;
                }

                $topicpages = quickpage($posts, $subs['ppp']);
                $threadurl = $SETTINGS['boardurl'] . 'viewthread.php?tid='.$tid.'&page='.$topicpages.'#pid'.$pid;
                $rawsubject = htmlspecialchars_decode($threadname, ENT_QUOTES);
                altMail($subs['email'], $lang['textsubsubject'].' '.$rawsubject, $username.' '.$lang['textsubbody']." \n".$threadurl, "From: $bbname <$adminemail>");
            }
            $db->free_result($subquery);

            if (isset($emailnotify) && $emailnotify == 'yes') {
                $query = $db->query("SELECT tid FROM ".X_PREFIX."favorites WHERE tid='$tid' AND username='$xmbuser' AND type='subscription'");
                if ($db->num_rows($query) < 1) {
                    $db->query("INSERT INTO ".X_PREFIX."favorites (tid, username, type) VALUES ($tid, '$username', 'subscription')");
                }
                $db->free_result($query);
            }

            eval('echo "'.template('header').'";');

            if (isset($_FILES['attach']) && ($attachedfile = get_attached_file($_FILES['attach'], $forum['attachstatus'], $SETTINGS['maxattachsize'])) !== false) {
                $db->query("INSERT INTO ".X_PREFIX."attachments (tid, pid, filename, filetype, filesize, attachment, downloads) VALUES ($tid, $pid, '$filename', '$filetype', '$filesize', '$attachedfile', 0)");
            }


            $topicpages = quickpage($posts, $ppp);
            message($lang['replymsg'], false, '', '', "viewthread.php?tid=${tid}&page=${topicpages}#pid${pid}", true, false, true);
        }
        break;

    case 'newthread':
        if (isset($poll) && $poll == 'yes') {
            nav($lang['textnewpoll']);
        } else {
            nav($lang['textpostnew']);
        }
        if ($SETTINGS['subject_in_title'] == 'on') {
            $threadSubject = '- '.$dissubject;
        }

        if (!isset($topicsubmit) || !$topicsubmit) {
            if (X_GUEST && $SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_post_status'] == 'on' && !DEBUG) {
                if ($Captcha->bCompatible !== false) {
                    $imghash = $Captcha->GenerateCode();
                    eval('$captchapostcheck = "'.template('post_captcha').'";');
                }
            }

            eval('echo "'.template('header').'";');

            if (X_STAFF) {
                $topoption = '<br /><input type="checkbox" name="toptopic" value="yes" '.$topcheck.' /> '.$lang['topmsgques'];
                $closeoption = '<br /><input type="checkbox" name="closetopic" value="yes" '.$closecheck.' /> '.$lang['closemsgques'].'<br />';
            } else {
                $topoption = '';
                $closeoption = '';
            }

            if (!isset($spelling_submit2)) {
                $spelling_submit2 = '';
            }

            if (isset($poll) && $poll == 'yes' && $perms[X_PERMS_POLL]) {
                if (!isset($pollanswers)){
                    $pollanswers = '';
                }
                eval('echo stripslashes("'.template('post_newpoll').'");');
            } else {
                eval('echo stripslashes("'.template('post_newthread').'");');
            }
        } else {
            if (strlen(postedVar('subject')) == 0 && strlen($messageinput == 0)) {
                error($lang['postnothing']);
            }

            if (X_GUEST And strlen(postedVar('username')) > 0 And isset($_POST['password'])) {
                $password = '';
                if (loginUser(postedVar('username'), md5($_POST['password']))) {
                    $username = $xmbuser;
                } else {
                    error($lang['textpw1']);
                }
            }

            if ($self['status'] == "Banned") {
                error($lang['bannedmessage']);
            }

            if ($self['ban'] == "posts" || $self['ban'] == "both") {
                error($lang['textbanfrompost']);
            }

            if ($username == 'Anonymous' && $SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_post_status'] == 'on' && !DEBUG) {
                if ($Captcha->bCompatible !== false) {
                    $imghash = addslashes($imghash);
                    $imgcode = addslashes($imgcode);
                    if ($Captcha->ValidateCode($imgcode, $imghash) !== true) {
                        error($lang['captchaimageinvalid']);
                    }
                }
            }

            $query = $db->query("SELECT lastpost, type, fup FROM ".X_PREFIX."forums WHERE fid='$fid'");
            $for = $db->fetch_array($query);
            $db->free_result($query);

            if ($for['lastpost'] != '') {
                $lastpost = explode('|', $for['lastpost']);
                $rightnow = $onlinetime - $floodctrl;
                if ($rightnow <= $lastpost[0] && $username == $lastpost[1]) {
                    error($lang['floodprotect'].' '.$floodlink.' '.$lang['tocont']);
                }
            }

            if (isset($posticon) && $posticon != '') {
                $query = $db->query("SELECT id FROM ".X_PREFIX."smilies WHERE type='picon' AND url='$posticon'");
                if (!$db->result($query, 0)) {
                    $db->free_result($query);
                    exit();
                }
            } else {
                $posticon = '';
            }

            $thatime = $onlinetime;

            $dbmessage = $db->escape(addslashes($messageinput)); //The message column is historically double-quoted.
            $dbsubject = addslashes(postedVar('subject', 'javascript', TRUE, TRUE, TRUE));
            $db->query("INSERT INTO ".X_PREFIX."threads (fid, subject, icon, lastpost, views, replies, author, closed, topped) VALUES ($fid, '$dbsubject', '$posticon', '$thatime|$username', 0, 0, '$username', '', 0)");
            $tid = $db->insert_id();

            $db->query("INSERT INTO ".X_PREFIX."posts (fid, tid, author, message, subject, dateline, icon, usesig, useip, bbcodeoff, smileyoff) VALUES ($fid, $tid, '$username', '$dbmessage', '$dbsubject', ".$db->time($thatime).", '$posticon', '$usesig', '$onlineip', '$bbcodeoff', '$smileyoff')");
            $pid = $db->insert_id();

            $db->query("UPDATE ".X_PREFIX."threads SET lastpost=concat(lastpost, '|".$pid."') WHERE tid='$tid'");

            if (isset($forum['type']) && $forum['type'] == 'sub') {
                $db->query("UPDATE ".X_PREFIX."forums SET lastpost='$thatime|$username|$pid', threads=threads+1, posts=posts+1 WHERE fid='$for[fup]'");
            }

            $db->query("UPDATE ".X_PREFIX."forums SET lastpost='$thatime|$username|$pid', threads=threads+1, posts=posts+1 WHERE fid='$fid'");

            if (X_MEMBER && isset($pollanswers) && $perms[X_PERMS_POLL]) {
                $pollanswers = checkInput($pollanswers);
                $pollopts = explode("\n", $pollanswers);
                $pnumnum = count($pollopts);

                if ($pnumnum < 2) {
                    error($lang['too_few_pollopts']);
                }

                $query = $db->query("SELECT vote_id, topic_id FROM ".X_PREFIX."vote_desc WHERE topic_id='$tid'");
                if ($query) {
                    $vote_id = $db->fetch_array($query);
                    $vote_id = $vote_id['vote_id'];
                    if ($vote_id > 0) {
                        $db->query("DELETE FROM ".X_PREFIX."vote_results WHERE vote_id='$vote_id'");
                        $db->query("DELETE FROM ".X_PREFIX."vote_voters WHERE vote_id='$vote_id'");
                        $db->query("DELETE FROM ".X_PREFIX."vote_desc WHERE vote_id='$vote_id'");
                    }
                }
                $db->free_result($query);

                $dbsubject = addslashes(postedVar('subject', 'javascript', TRUE, TRUE, TRUE));
                $db->query("INSERT INTO ".X_PREFIX."vote_desc (topic_id, vote_text) VALUES ($tid, '$dbsubject')");
                $vote_id =  $db->insert_id();
                $i = 1;
                foreach($pollopts as $p) {
                    $p = addslashes($p);
                    $db->query("INSERT INTO ".X_PREFIX."vote_results (vote_id, vote_option_id, vote_option_text, vote_result) VALUES ($vote_id, $i, '$p', 0)");
                    $i++;
                }
                $db->query("UPDATE ".X_PREFIX."threads SET pollopts=1 WHERE tid='$tid'");
            }

            if (isset($emailnotify) && $emailnotify == 'yes') {
                $query = $db->query("SELECT tid FROM ".X_PREFIX."favorites WHERE tid='$tid' AND username='$xmbuser' AND type='subscription'");
                $thread = $db->fetch_array($query);
                $db->free_result($query);
                if (!$thread) {
                    $db->query("INSERT INTO ".X_PREFIX."favorites (tid, username, type) VALUES ($tid, '$username', 'subscription')");
                }
            }

            $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum+1 WHERE username like '$username'");

            if ((X_STAFF) && $toptopic == 'yes') {
                $db->query("UPDATE ".X_PREFIX."threads SET topped='1' WHERE tid='$tid' AND fid='$fid'");
            }

            if ((X_STAFF) && $closetopic == 'yes') {
                $db->query("UPDATE ".X_PREFIX."threads SET closed='yes' WHERE tid='$tid' AND fid='$fid'");
            }

            eval('echo "'.template('header').'";');

            if (isset($_FILES['attach']) && ($attachedfile = get_attached_file($_FILES['attach'], $forum['attachstatus'], $SETTINGS['maxattachsize'])) !== false) {
                $db->query("INSERT INTO ".X_PREFIX."attachments (tid, pid, filename, filetype, filesize, attachment, downloads) VALUES ($tid, $pid, '$filename', '$filetype', '$filesize', '$attachedfile', 0)");
            }

            $query = $db->query("SELECT COUNT(tid) FROM ".X_PREFIX."posts WHERE tid='$tid'");
            $posts = $db->result($query, 0);
            $db->free_result($query);

            $topicpages = quickpage($posts, $ppp);
            message($lang['postmsg'], false, '', '', "viewthread.php?tid=${tid}&page=${topicpages}#pid${pid}", true, false, true);
        }
        break;

    case 'edit':
        nav('<a href="viewthread.php?tid='.$tid.'">'.$threadname.'</a>');
        nav($lang['texteditpost']);
        if ($SETTINGS['subject_in_title'] == 'on') {
            $threadSubject = '- '.$threadname;
        }

        //Check all editing permissions for this $pid.  Based on viewthread design, forum Moderators can always edit, $orig['author'] can edit open threads only.
        $query = $db->query("SELECT p.author as author, m.status as status, p.subject as subject FROM ".X_PREFIX."posts p LEFT JOIN ".X_PREFIX."members m ON p.author=m.username WHERE pid=$pid");
        $orig = $db->fetch_array($query);
        $db->free_result($query);

        $status1 = modcheckPost($self['username'], $forum['moderator'], $orig['status']);

        if ($status1 != 'Moderator' And ($self['username'] != $orig['author'] Or $thread['closed'] != '')) {
            error($lang['noedit']);
        }

        if (!isset($editsubmit)) {
            eval('echo "'.template('header').'";');

            $subjectinput = postedVar('subject', 'javascript', TRUE, FALSE, TRUE);
            if (isset($previewpost) || (isset($subaction) && $subaction == 'spellcheck' && (isset($spellchecksubmit) || isset($updates_submit)))) {
                $postinfo = array("usesig"=>$usesig, "bbcodeoff"=>$bbcodeoff, "smileyoff"=>$smileyoff, "message"=>$messageinput, "subject"=>$subjectinput, 'icon'=>$posticon);
                $query = $db->query("SELECT filename, filesize, downloads FROM ".X_PREFIX."attachments WHERE pid='$pid' AND tid='$tid'");
                if ($db->num_rows($query) > 0) {
                    $postinfo = array_merge($postinfo, $db->fetch_array($query));
                }
                $db->free_result($query);
            } else {
                $query = $db->query("SELECT a.filename, a.filesize, a.downloads, p.* FROM ".X_PREFIX."posts p LEFT JOIN ".X_PREFIX."attachments a  ON (a.pid=p.pid) WHERE p.pid='$pid' AND p.tid='$tid' AND p.fid=".$forum['fid']);
                $postinfo = $db->fetch_array($query);
                $db->free_result($query);
                $postinfo['message'] = stripslashes($postinfo['message']); //Messages are historically double-quoted.
                $postinfo['subject'] = stripslashes($postinfo['subject']);
            }
//update
            if (isset($postinfo['filesize'])) {
                $postinfo['filesize'] = number_format($postinfo['filesize'], 0, '.', ',');
            }
            if (isset($postinfo['filename'])) {
                $postinfo['filename'] = checkInput($postinfo['filename'], 'no', 'no', '', false);
            }

            if ($postinfo['bbcodeoff'] == 'yes') {
                $offcheck1 = $cheHTML;
            } else {
                $offcheck1 = '';
            }

            if ($postinfo['smileyoff'] == 'yes') {
                $offcheck2 = $cheHTML;
            } else {
                $offcheck2 = '';
            }

            if ($postinfo['usesig'] == 'yes') {
                $offcheck3 = $cheHTML;
            } else {
                $offcheck3 = '';
            }

            $querysmilie = $db->query("SELECT * FROM ".X_PREFIX."smilies WHERE type='picon'");
            while($smilie = $db->fetch_array($querysmilie)) {
                if ($postinfo['icon'] == $smilie['url']) {
                    $icons .= " <input type=\"radio\" name=\"posticon\" value=\"$smilie[url]\" checked=\"checked\"/><img src=\"$smdir/$smilie[url]\" alt=\"$smilie[code]\" />";
                } else {
                    $icons .= " <input type=\"radio\" name=\"posticon\" value=\"$smilie[url]\" /><img src=\"$smdir/$smilie[url]\" alt=\"$smilie[code]\" />";
                }
                $listed_icons += 1;
                if ($listed_icons == 9) {
                    $icons .= '<br />';
                    $listed_icons = 0;
                }
            }
            $db->free_result($querysmilie);

            if (!X_SADMIN) {
                $postinfo['subject'] = censor($postinfo['subject']);
            }

            $message = $postinfo['message'];
            $subject = $postinfo['subject'];

            if (isset($previewpost)) {
                $message = censor($message);
            }

            if (isset($postinfo['filename']) && $postinfo['filename'] != '') {
                eval('$attachment = "'.template('post_edit_attachment').'";');
            } else {
                $attachment = $attachfile;
            }
            eval('echo "'.template('post_edit').'";');
        } else {
            if ($self['ban'] == "posts" || $self['ban'] == "both") {
                error($lang['textbanfrompost']);
            }

            if (isset($posticon) && $posticon != "") {
                $query = $db->query("SELECT id FROM ".X_PREFIX."smilies WHERE type='picon' AND url='$posticon'");
                if (!$db->result($query, 0)) {
                    $db->free_result($query);
                    exit();
                }
            } else {
                $posticon = '';
            }

            $query = $db->query("SELECT pid FROM ".X_PREFIX."posts WHERE tid='$tid' ORDER BY dateline LIMIT 1");
            $isfirstpost = $db->fetch_array($query);
            $db->free_result($query);

            if ((strlen(postedVar('subject')) == 0 && $pid == $isfirstpost['pid']) && !(isset($delete) && $delete == 'yes')) {
                error($lang['textnosubject']);
            }

            $posticon = htmlspecialchars($posticon);

            $threaddelete = 'no';
            eval('echo "'.template('header').'";');

            $dbsubject = addslashes(postedVar('subject', 'javascript', TRUE, TRUE, TRUE));
            if ($isfirstpost['pid'] == $pid && !(isset($delete) && $delete == 'yes')) {
                $db->query("UPDATE ".X_PREFIX."threads SET icon='$posticon', subject='$dbsubject' WHERE tid='$tid'");
            }

            if ($SETTINGS['editedby'] == 'on') {
                $messageinput .= "\n\n[".$lang['textediton'].' '.gmdate($dateformat).' '.$lang['textby']." $username]";
            }
            $dbmessage = $db->escape(addslashes($messageinput)); //The subject and message columns are historically double-quoted.
            $db->query("UPDATE ".X_PREFIX."posts SET message='$dbmessage', usesig='$usesig', bbcodeoff='$bbcodeoff', smileyoff='$smileyoff', icon='$posticon', subject='$dbsubject' WHERE pid='$pid'");

            if (isset($_FILES['attach']) && ($file = get_attached_file($_FILES['attach'], $forum['attachstatus'], $SETTINGS['maxattachsize'])) !== false) {
                $db->query("INSERT INTO ".X_PREFIX."attachments (tid, pid, filename, filetype, filesize, attachment, downloads) VALUES ($tid, $pid, '$filename', '$attach[type]', '$filesize', '$file', 0)");
            }

            if (isset($attachment) && is_array($attachment)) {
                switch($attachment['action']) {
                    case 'replace':
                        if (isset($_FILES['attachment_replace']) && ($file = get_attached_file($_FILES['attachment_replace'], $forum['attachstatus'], $SETTINGS['maxattachsize'])) !== false) {
                            $db->query("DELETE FROM ".X_PREFIX."attachments WHERE pid='$pid'");
                            $db->query("INSERT INTO ".X_PREFIX."attachments (tid, pid, filename, filetype, filesize, attachment, downloads) VALUES ($tid, $pid, '$filename', '$attachment_replace[type]', '$filesize', '$file', 0)");
                        }
                        break;
                    case 'rename':
                        if (isValidFilename($_POST['attach_name'])) {
                            $name = addslashes(trim($_POST['attach_name']));
                            $db->query("UPDATE ".X_PREFIX."attachments SET filename='$name' WHERE pid=$pid");
                        }
                        break;
                    case 'delete':
                        $db->query("DELETE FROM ".X_PREFIX."attachments WHERE pid='$pid'");
                        break;
                    default:
                        break;
                }
            }

            if (isset($delete) && $delete == 'yes' && !($isfirstpost['pid'] == $pid)) {
                $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum-1 WHERE username='$orig[author]'");
                $db->query("DELETE FROM ".X_PREFIX."attachments WHERE pid='$pid'");
                $db->query("DELETE FROM ".X_PREFIX."posts WHERE pid='$pid'");
                updatethreadcount($tid);
                updateforumcount($fid);
            } else if (isset($delete) && $delete == 'yes' && $isfirstpost['pid'] == $pid) {
                $query = $db->query("SELECT pid FROM ".X_PREFIX."posts WHERE tid='$tid'");
                $numrows = $db->num_rows($query);
                $db->free_result($query);

                if ($numrows == 1) {
                    $query = $db->query("SELECT author FROM ".X_PREFIX."posts WHERE tid='$tid'");
                    while($result = $db->fetch_array($query)) {
                        $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum-1 WHERE username='$result[author]'");
                    }
                    $db->free_result($query);
                    $db->query("DELETE FROM ".X_PREFIX."threads WHERE tid='$tid'");
                    $db->query("DELETE FROM ".X_PREFIX."attachments WHERE tid='$tid'");
                    $db->query("DELETE FROM ".X_PREFIX."posts WHERE tid='$tid'");
                    $threaddelete = 'yes';
                }

                if ($numrows > 1) {
                    $db->query("UPDATE ".X_PREFIX."members SET postnum=postnum-1 WHERE username='$orig[author]'");
                    $db->query("DELETE FROM ".X_PREFIX."attachments WHERE pid='$pid'");
                    $db->query("DELETE FROM ".X_PREFIX."posts WHERE pid='$pid'");
                    $db->query("UPDATE ".X_PREFIX."posts SET subject='$orig[subject]' WHERE tid='$tid' ORDER BY dateline ASC LIMIT 1");
                    $threaddelete = 'no';
                }
                updatethreadcount($tid);
                updateforumcount($fid);
            }

            if ($threaddelete != 'yes') {
                $query = $db->query("SELECT COUNT(pid) FROM ".X_PREFIX."posts WHERE pid <= '$pid' AND tid='$tid' AND fid='$fid'");
                $posts = $db->result($query,0);
                $db->free_result($query);
                $topicpages = quickpage($posts, $ppp);
                message($lang['editpostmsg'], false, '', '', "viewthread.php?tid=${tid}&page=${topicpages}#pid${pid}", true, false, true);
            } else {
                message($lang['editpostmsg'], false, '', '', 'forumdisplay.php?fid='.$fid, true, false, true);
            }
        }
        break;

    default:
        error($lang['textnoaction']);
        break;
}

end_time();
eval('echo "'.template('footer').'";');

function bbcodeinsert() {
    global $imgdir, $bbinsert, $altbg1, $altbg2, $lang, $SETTINGS, $spelling_lang;

    $bbcode = '';
    if ($SETTINGS['bbinsert'] == 'on') {
        eval('$bbcode = "'.template('functions_bbcodeinsert').'";');
    }
    return $bbcode;
}

?>
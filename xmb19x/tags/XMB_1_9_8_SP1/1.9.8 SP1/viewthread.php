<?php
/**
 * eXtreme Message Board
 * XMB 1.9.8 Engage Final SP1
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

require 'header.php';

validatePpp();

$pid = getInt('pid');
$tid = getInt('tid');
$page = getInt('page');
$goto = getVar('goto');
$action = getVar('action');

if ($goto == 'lastpost') {
    if ($pid > 0) {
        if ($tid == 0) {
            $tid = $db->result($db->query("SELECT tid FROM ".X_PREFIX."posts WHERE pid='$pid'"), 0);
        }

        $query = $db->query("SELECT COUNT(pid) as num FROM ".X_PREFIX."posts WHERE tid='$tid' AND pid <= $pid");
        $posts = $db->result($query, 0);
        $db->free_result($query);

        if ($posts == 0) {
            eval('$css = "'.template('css').'";');
            error($lang['textnothread']);
        }
    } else if ($tid > 0) {
        $query = $db->query("SELECT COUNT(pid) FROM ".X_PREFIX."posts WHERE tid='$tid'");
        $posts = $db->result($query, 0);
        $db->free_result($query);

        if ($posts == 0) {
            eval('$css = "'.template('css').'";');
            error($lang['textnothread']);
        }

        $query = $db->query("SELECT pid FROM ".X_PREFIX."posts WHERE tid='$tid' ORDER BY pid DESC LIMIT 0, 1");
        $pid = $db->result($query, 0);
        $db->free_result($query);
    } else if ($fid > 0) {
        $query = $db->query("SELECT pid, tid FROM ".X_PREFIX."posts WHERE fid='$fid' ORDER BY pid DESC LIMIT 0, 1");
        $posts = $db->fetch_array($query);
        $db->free_result($query);

        $pid = $posts['pid'];
        $tid = $posts['tid'];

        $query = $db->query("SELECT p.pid, p.tid FROM ".X_PREFIX."posts p, ".X_PREFIX."forums f WHERE p.fid=f.fid and (f.fup=$fid) ORDER BY p.pid DESC LIMIT 0, 1");
        $fupPosts = $db->fetch_array($query);
        $db->free_result($query);

        if ($fupPosts['pid'] > $pid) {
            $pid = $fupPosts['pid'];
            $tid = $fupPosts['tid'];
        }

        $query = $db->query("SELECT COUNT(pid) FROM ".X_PREFIX."posts WHERE tid='$tid'");
        $posts = $db->result($query, 0);
        $db->free_result($query);
    }
    $page = quickpage($posts, $ppp);
    redirect("viewthread.php?tid=$tid&page=$page#pid$pid", 0);
}

loadtemplates(
'functions_bbcode',
'functions_smilieinsert_smilie',
'viewthread_reply',
'viewthread_quickreply',
'viewthread_quickreply_captcha',
'viewthread',
'viewthread_invalid',
'viewthread_modoptions',
'viewthread_newpoll',
'viewthread_newtopic',
'viewthread_poll_options_view',
'viewthread_poll_options',
'viewthread_poll_submitbutton',
'viewthread_poll',
'viewthread_post',
'viewthread_post_email',
'viewthread_post_site',
'viewthread_post_icq',
'viewthread_post_aim',
'viewthread_post_msn',
'viewthread_post_yahoo',
'viewthread_post_search',
'viewthread_post_profile',
'viewthread_post_u2u',
'viewthread_post_ip',
'viewthread_post_repquote',
'viewthread_post_report',
'viewthread_post_edit',
'viewthread_post_attachmentimage',
'viewthread_post_attachment',
'viewthread_post_sig',
'viewthread_post_nosig',
'viewthread_printable',
'viewthread_printable_row',
'viewthread_multipage'
);

smcwcache();

eval('$css = "'.template('css').'";');

$notexist = false;
$notexist_txt = $posts = '';

$query = $db->query("SELECT fid, subject, replies, closed, topped, lastpost FROM ".X_PREFIX."threads WHERE tid='$tid'");
if ($tid == 0 || $db->num_rows($query) != 1) {
    $db->free_result($query);
    error($lang['textnothread']);
}

$thread = $db->fetch_array($query);
$db->free_result($query);

if (strpos($thread['closed'], '|') !== false) {
    $moved = explode('|', $thread['closed']);
    if ($moved[0] == 'moved') {
        redirect('forumdisplay.php?tid='.$moved[1], 0);
    }
}

$thread['subject'] = shortenString($thread['subject'], 125, X_SHORTEN_SOFT|X_SHORTEN_HARD, '...');
$thread['subject'] = checkOutput($thread['subject'], 'no', '', true);

$thislast = explode('|', $thread['lastpost']);
$lastPid = isset($thislast[2]) ? $thislast[2] : 0;
if (!isset($oldtopics)) {
    put_cookie('oldtopics', '|'.$lastPid.'|', $onlinetime+600, $cookiepath, $cookiedomain, null, X_SET_HEADER);
} else if (false === strpos($oldtopics, '|'.$lastPid.'|')) {
    $expire = $onlinetime + 600;
    $oldtopics .= $lastPid.'|';
    put_cookie('oldtopics', $oldtopics, $expire, $cookiepath, $cookiedomain, null, X_SET_HEADER);
}

$thread['subject'] = censor($thread['subject']);
$fid = (int) $thread['fid'];

$query = $db->query("SELECT * FROM ".X_PREFIX."forums WHERE fid='$fid'");
$forum = $db->fetch_array($query);

if ((!isset($forum['type']) && $forum['type'] != 'forum' && $forum['type'] != 'sub') || $db->num_rows($query) != 1) {
    $db->free_result($query);
    error($lang['textnoforum']);
}

$db->free_result($query);

$authorization = true;
if (isset($forum['type']) && $forum['type'] == 'sub') {
    $query = $db->query("SELECT name, fid, private, userlist FROM ".X_PREFIX."forums WHERE fid='$forum[fup]'");
    $fup = $db->fetch_array($query);
    $db->free_result($query);
    $authorization = privfcheck($fup['private'], $fup['userlist']);
}

if (!$authorization || !privfcheck($forum['private'], $forum['userlist'])) {
    $threadSubject = '';
    error($lang['privforummsg']);
}

$ssForumName = html_entity_decode(stripslashes($forum['name']));
if (isset($forum['type']) && $forum['type'] == 'forum') {
    nav('<a href="forumdisplay.php?fid='.$fid.'"> '.$ssForumName.'</a>');
    nav(checkOutput(stripslashes($thread['subject']), 'no', '', true));
} else {
    nav('<a href="forumdisplay.php?fid='.$fup['fid'].'">'.html_entity_decode(stripslashes($fup['name'])).'</a>');
    nav('<a href="forumdisplay.php?fid='.$fid.'">'.$ssForumName.'</a>');
    nav(checkOutput(stripslashes($thread['subject']), 'no', '', true));
}

$allowimgcode = ($forum['allowimgcode'] == 'yes') ? $lang['texton']:$lang['textoff'];
$allowhtml = ($forum['allowhtml'] == 'yes') ? $lang['texton']:$lang['textoff'];
$allowsmilies = ($forum['allowsmilies'] == 'yes') ? $lang['texton']:$lang['textoff'];
$allowbbcode = ($forum['allowbbcode'] == 'yes') ? $lang['texton']:$lang['textoff'];

eval('$bbcodescript = "'.template('functions_bbcode').'";');

if ($smileyinsert == 'on' && $smiliesnum > 0) {
    $max = ($smiliesnum > 16) ? 16 : $smiliesnum;
    srand((double)microtime() * 1000000);
    $keys = array_rand($smiliecache, $max);
    $smilies = array();
    $smilies[] = '<table border="0"><tr>';
    $i = 0;
    $total = 0;
    $pre = 'opener.';
    foreach($keys as $key) {
        if ($total == 16) {
            break;
        }
        $smilie['code'] = $key;
        $smilie['url'] = $smiliecache[$key];

        if ($i >= 4) {
            $smilies[] = '</tr><tr>';
            $i = 0;
        }
        eval('$smilies[] = "'.template('functions_smilieinsert_smilie').'";');
        $i++;
        $total++;
    }
    $smilies[] = '</tr></table>';
    $smilies = implode("\n", $smilies);
}

$usesig = false;
$replylink = $quickreply = '';

$status1 = modcheck($self['status'], $xmbuser, $forum['moderator']);

if (!$action) {
    if (X_MEMBER && $self['sig'] != '') {
        $usesig = true;
    }

    eval('echo "'.template('header').'";');

    pwverify($forum['password'], 'viewthread.php?tid='.$tid, $fid);

    $ppthread = postperm($forum, 'thread');
    $ppreply  = postperm($forum, 'reply');

    $usesigcheck = $usesig ? 'checked="checked"' : '';

    $captchapostcheck = '';
    if (X_GUEST && $SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_post_status'] == 'on' && !DEBUG) {
        require ROOT.'include/captcha.inc.php';
        $Captcha = new Captcha(250, 50);
        if ($Captcha->bCompatible !== false) {
            $imghash = $Captcha->GenerateCode();
            eval('$captchapostcheck = "'.template('viewthread_quickreply_captcha').'";');
        }
    }

    if ($thread['closed'] == 'yes') {
        if (X_SADMIN) {
            eval('$replylink = "'.template('viewthread_reply').'";');
            $quickreply = '';
            if ($SETTINGS['quickreply_status'] == 'on') {
                eval('$quickreply = "'.template('viewthread_quickreply').'";');
            }
        }
        $closeopen = $lang['textopenthread'];
    } else {
        if (X_MEMBER || X_GUEST && isset($forum['guestposting']) && $forum['guestposting'] == 'on') {
            $closeopen = $lang['textclosethread'];
            eval('$replylink = "'.template('viewthread_reply').'";');
            $quickreply = '';
            if ($SETTINGS['quickreply_status'] == 'on') {
                eval('$quickreply = "'.template('viewthread_quickreply').'";');
            }
        }
    }

    if (!$ppthread) {
        $newtopiclink = $newpolllink = '';
        if (!$ppreply || (X_GUEST && isset($forum['guestposting']) && $forum['guestposting'] != 'on')) {
            $replylink = $quickreply = '';
        }
    } else {
        if (X_GUEST && isset($forum['guestposting']) && $forum['guestposting'] != 'on') {
            $newtopiclink = $newpolllink = '';
        } else {
            eval('$newtopiclink = "'.template('viewthread_newtopic').'";');
            if (isset($forum['pollstatus']) && $forum['pollstatus'] != 'off') {
                eval('$newpolllink = "'.template('viewthread_newpoll').'";');
            }
        }

        if (!$ppreply || (X_GUEST && isset($forum['guestposting']) && $forum['guestposting'] != 'on')) {
            $replylink = $quickreply = '';
        }
    }

    $topuntop = ($thread['topped'] == 1) ? $lang['textuntopthread'] : $lang['texttopthread'];

    $max_page = (int) ($thread['replies'] / $ppp) + 1;
    if ($page && $page >= 1 && $page <= $max_page) {
        if ($page < 1) {
            $page = 1;
        }
        $start_limit = ($page-1) * $ppp;
    } else {
        $start_limit = 0;
        $page = 1;
    }

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

    $db->query("UPDATE ".X_PREFIX."threads SET views=views+1 WHERE tid='$tid'");
    $query = $db->query("SELECT COUNT(pid) FROM ".X_PREFIX."posts WHERE tid='$tid'");
    $num = $db->result($query, 0);
    $db->free_result($query);

    $mpurl = 'viewthread.php?tid='.$tid;
    $multipage = '';
    if (($multipage = multi($num, $ppp, $page, $mpurl)) !== false) {
        eval('$multipage = "'.template('viewthread_multipage').'";');
    }

    $pollhtml = $poll = '';
    $vote_id = $voted = 0;

    $query = $db->query("SELECT vote_id FROM ".X_PREFIX."vote_desc WHERE topic_id='$tid'");
    if ($query) {
        $vote_id = $db->fetch_array($query);
        $vote_id = (int) $vote_id['vote_id'];
    }
    $db->free_result($query);

    if ($vote_id > 0 && isset($forum['pollstatus']) && $forum['pollstatus'] != 'off') {
        if (X_MEMBER) {
            $query = $db->query("SELECT COUNT(vote_id) AS cVotes FROM ".X_PREFIX."vote_voters WHERE vote_id='$vote_id' AND vote_user_id=".intval($self['uid']));
            if ($query) {
                $voted = $db->fetch_array($query);
                $voted = (int) $voted['cVotes'];
            }
        }

        $viewresults = (isset($viewresults) && $viewresults == 'yes') ? 'yes' : '';
        if ($voted === 1 || $thread['closed'] == 'yes' || X_GUEST || $viewresults) {
            if ($viewresults) {
                $results = '- [<a href="viewthread.php?tid='.$tid.'"><font color="'.$cattext.'">'.$lang['backtovote'].'</font></a>]';
            } else {
                $results = '';
            }

            $num_votes = 0;
            $query = $db->query("SELECT vote_result, vote_option_text FROM ".X_PREFIX."vote_results WHERE vote_id='$vote_id'");
            while($result = $db->fetch_array($query)) {
                $num_votes += $result['vote_result'];
                $pollentry = array();
                $pollentry['name'] = postify($result['vote_option_text'], 'no', 'no', 'yes', 'no', 'yes', 'yes');
                $pollentry['votes'] = $result['vote_result'];
                $poll[] = $pollentry;
            }
            $db->free_result($query);

            reset($poll);
            foreach($poll as $num=>$array) {
                $pollimgnum = 0;
                $pollbar = '';
                if ($array['votes'] > 0) {
                    $orig = round($array['votes']/$num_votes*100, 2);
                    $percentage = round($orig, 2);
                    $percentage .= '%';
                    $poll_length = (int) $orig;
                    if ($poll_length > 97) {
                        $poll_length = 97;
                    }
                    $pollbar = '<img src="'.$imgdir.'/pollbar.gif" height="10" width="'.$poll_length.'%" alt="'.$lang['altpollpercentage'].'" title="'.$lang['altpollpercentage'].'" border="0" />';
                } else {
                    $percentage = '0%';
                }
                eval('$pollhtml .= "'.template('viewthread_poll_options_view').'";');
                $buttoncode = '';
            }
        } else {
            $results = '- [<a href="viewthread.php?tid='.$tid.'&amp;viewresults=yes"><font color="'.$cattext.'">'.$lang['viewresults'].'</font></a>]';
            $query = $db->query("SELECT vote_option_id, vote_option_text FROM ".X_PREFIX."vote_results WHERE vote_id='$vote_id'");
            while($result = $db->fetch_array($query)) {
                $poll['id'] = (int) $result['vote_option_id'];
                $poll['name'] = $result['vote_option_text'];
                eval('$pollhtml .= "'.template('viewthread_poll_options').'";');
            }
            $db->free_result($query);
            eval('$buttoncode = "'.template('viewthread_poll_submitbutton').'";');
        }
        eval('$poll = "'.template('viewthread_poll').'";');
    }

    $thisbg = $altbg2;
    $querypost = $db->query("SELECT a.aid, a.filename, a.filetype, a.filesize, a.downloads, p.*, m.*,w.time FROM ".X_PREFIX."posts p LEFT JOIN ".X_PREFIX."members m ON m.username=p.author LEFT JOIN ".X_PREFIX."attachments a ON a.pid=p.pid LEFT JOIN ".X_PREFIX."whosonline w ON w.username=p.author WHERE p.fid='$fid' AND p.tid='$tid' GROUP BY p.pid ORDER BY p.pid ASC LIMIT $start_limit, $ppp");
    $tmoffset = ($timeoffset * 3600) + ($addtime * 3600);
    while($post = $db->fetch_array($querypost)) {
        $post['avatar'] = str_replace("script:", "sc ript:", $post['avatar']);

        $onlinenow = $lang['memberisoff'];
        if ($post['time'] != '' && $post['author'] != "xguest123") {
            if ($post['invisible'] == 1) {
                $onlinenow = X_ADMIN ? $lang['memberison'] . ' ('.$lang['hidden'].')' : $lang['memberisoff'];
            } else {
                $onlinenow = $lang['memberison'];
            }
        }

        $date = gmdate($dateformat, $post['dateline'] + $tmoffset);
        $time = gmdate($timecode, $post['dateline'] + $tmoffset);

        $poston = $lang['textposton'].' '.$date.' '.$lang['textat'].' '.$time;

        if ($post['icon'] != '' && file_exists($smdir.'/'.$post['icon'])) {
            $post['icon'] = '<img src="'.$smdir.'/'.$post['icon'].'" alt="'.$post['icon'].'" border="0" />';
        } else {
            $post['icon'] = '<img src="'.$imgdir.'/default_icon.gif" alt="[*]" border="0" />';
        }

        if ($post['author'] != 'Anonymous' && $post['username']) {
            if (X_MEMBER && $post['showemail'] == 'yes') {
                eval('$email = "'.template('viewthread_post_email').'";');
            } else {
                $email = '';
            }

            if ($post['site'] == '') {
                $site = '';
            } else {
                $post['site'] = str_replace("http://", "", $post['site']);
                $post['site'] = "http://$post[site]";
                eval('$site = "'.template('viewthread_post_site').'";');
            }

            $encodename = rawurlencode($post['author']);

            $icq = '';
            if ($post['icq'] != '' && $post['icq'] > 0) {
                eval('$icq = "'.template('viewthread_post_icq').'";');
            }

            $aim = '';
            if ($post['aim'] != '') {
                eval('$aim = "'.template('viewthread_post_aim').'";');
            }

            $msn = '';
            if ($post['msn'] != '') {
                eval('$msn = "'.template('viewthread_post_msn').'";');
            }

            $yahoo = '';
            if ($post['yahoo'] != '') {
                eval('$yahoo = "'.template('viewthread_post_yahoo').'";');
            }

            eval('$search = "'.template('viewthread_post_search').'";');
            eval('$profile = "'.template('viewthread_post_profile').'";');
            eval('$u2u = "'.template('viewthread_post_u2u').'";');

            $showtitle = $post['status'];
            $rank = array();
            if ($post['status'] == 'Administrator' || $post['status'] == 'Super Administrator' || $post['status'] == 'Super Moderator' || $post['status'] == 'Moderator') {
                $sr = $post['status'];
                $rankinfo = explode(",", $specialrank[$sr]);
                $rank['allowavatars'] = $rankinfo[4];
                $rank['title'] = $rankinfo[1];
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
            }

            $tharegdate = gmdate($dateformat, $post['regdate'] + $tmoffset);
            $avatar = '';

            if ($SETTINGS['avastatus'] == 'on' || $SETTINGS['avastatus'] == 'list') {
                if ($post['avatar'] != '' && $allowavatars != "no") {
                    if (false !== ($pos = strpos($post['avatar'], ',')) && substr($post['avatar'], $pos-4, 4) == '.swf') {
                        $flashavatar = explode(',',$post['avatar']);
                        $avatar = '<object type="application/x-shockwave-flash" data="'.$flashavatar[0].'" width="'.$flashavatar[1].'" height="'.$flashavatar[2].'"><param name="movie" value="'.$flashavatar[0].'" /><param name="AllowScriptAccess" value="never" /></object>';
                    } else {
                        $avatar = '<img src="'.$post['avatar'].'" alt="'.$lang['altavatar'].'" border="0" />';
                    }
                }
            }

            if ($post['mood'] != '') {
                $post['mood'] = censor($post['mood']);
                $mood = '<strong>'.$lang['mood'].'</strong> '.postify($post['mood'], 'no', 'no', 'yes', 'no', 'yes', 'no', true, 'yes');
            } else {
                $mood = '';
            }

            if ($post['location'] != '') {
                $post['location'] = censor($post['location']);
                $location = '<br />'.$lang['textlocation'].' '.$post['location'];
            } else {
                $location = '';
            }
        } else {
            $post['author'] = ($post['author'] == 'Anonymous') ? $lang['textanonymous'] : $post['author'];
            $showtitle = $lang['textunregistered'].'<br />';
            $stars = '';
            $avatar = '';
            $rank['avatar'] = '';
            $post['postnum'] = 'N/A';
            $tharegdate = 'N/A';
            $email = '';
            $site = '';
            $icq = '';
            $msn = '';
            $aim = '';
            $yahoo = '';
            $profile = '';
            $search = '';
            $u2u = '';
            $location = '';
            $mood = '';
        }

        $ip = '';
        if (X_ADMIN) {
            eval('$ip = "'.template('viewthread_post_ip').'";');
        }

        $repquote = '';
        if (X_ADMIN || $status1 == 'Moderator' || ($thread['closed'] != 'yes')) {
            if (X_MEMBER || (X_GUEST && isset($forum['guestposting']) && $forum['guestposting'] == 'on')) {
                eval('$repquote = "'.template('viewthread_post_repquote').'";');
            }
        }

        $reportlink = '';
        if (X_MEMBER && $post['author'] != $xmbuser && $SETTINGS['reportpost'] == 'on') {
            eval('$reportlink = "'.template('viewthread_post_report').'";');
        }

        if ($post['subject'] != '') {
            $post['subject'] = censor($post['subject']).'<br />';
            $post['subject'] = checkOutput($post['subject'], 'no', '', true);
        }

        $edit = '';
        if (X_ADMIN || $status1 == 'Moderator' || ($thread['closed'] != 'yes' && $post['author'] == $xmbuser)) {
            eval('$edit = "'.template('viewthread_post_edit').'";');
        }

        $bbcodeoff = $post['bbcodeoff'];
        $smileyoff = $post['smileyoff'];
        $post['message'] = postify($post['message'], $smileyoff, $bbcodeoff, $forum['allowsmilies'], $forum['allowhtml'], $forum['allowbbcode'], $forum['allowimgcode']);

        if ($post['filename'] != '' && $forum['attachstatus'] != 'off') {
            $attachsize = $post['filesize'];
            if ($attachsize >= 1073741824) {
                $attachsize = round($attachsize / 1073741824 * 100) / 100 . "gb";
            } else if ($attachsize >= 1048576) {
                $attachsize = round($attachsize / 1048576 * 100) / 100 . "mb";
            } else if ($attachsize >= 1024) {
                $attachsize = round($attachsize / 1024 * 100) / 100 . "kb";
            } else {
                $attachsize = $attachsize . "b";
            }

            $extention = strtolower(substr(strrchr($post['filename'],"."),1));
            if ($attachimgpost == 'on' && ($extention == 'jpg' || $extention == 'jpeg' || $extention == 'jpe' || $extention == 'gif' || $extention == 'png' || $extention == 'bmp')) {
                eval("\$post['message'] .= \"".template('viewthread_post_attachmentimage')."\";");
            } else {
                $downloadcount = $post['downloads'];
                if ($downloadcount == '') {
                    $downloadcount = 0;
                }
                eval("\$post['message'] .= \"".template('viewthread_post_attachment')."\";");
            }
        }

        if ($post['usesig'] == 'yes') {
            $post['sig'] = postify($post['sig'], 'no', 'no', $forum['allowsmilies'], $SETTINGS['sightml'], $SETTINGS['sigbbcode'], $forum['allowimgcode'], false);
            eval("\$post['message'] .= \"".template('viewthread_post_sig')."\";");
        } else {
            eval("\$post['message'] .= \"".template('viewthread_post_nosig')."\";");
        }

        if (!isset($rank['avatar'])) {
            $rank['avatar'] = '';
        }

        if (!$notexist) {
            eval('$posts .= "'.template('viewthread_post').'";');
        } else {
            eval('$posts .= "'.template('viewthread_invalid').'";');
        }

        if ($thisbg == $altbg2) {
            $thisbg = $altbg1;
        } else {
            $thisbg = $altbg2;
        }
    }
    $db->free_result($querypost);

    $modoptions = '';
    if ('Moderator' == $status1) {
        eval('$modoptions = "'.template('viewthread_modoptions').'";');
    }
    eval('echo stripslashes("'.template('viewthread').'");');
    end_time();
    eval('echo "'.template('footer').'";');
    exit();
} else if ($action == 'attachment' && $forum['attachstatus'] != 'off' && $pid > 0 && $tid > 0) {
    pwverify($forum['password'], 'viewthread.php?tid='.$tid, $fid, true);
    $query = $db->query("SELECT * FROM ".X_PREFIX."attachments WHERE pid='$pid' and tid='$tid'");
    $file = $db->fetch_array($query);
    $db->free_result($query);

    $db->query("UPDATE ".X_PREFIX."attachments SET downloads=downloads+1 WHERE pid='$pid'");

    if ($file['filesize'] != strlen($file['attachment'])) {
        error($lang['filecorrupt']);
    }

    $type = strtolower($file['filetype']);
    $name = $file['filename'];
    $size = (int) $file['filesize'];
    $type = ($type == 'text/html') ? 'text/plain' : $type;

    header("Content-type: $type");
    header("Content-length: $size");
    header("Content-Disposition: attachment; filename=$name");
    header("Content-Description: XMB Attachment");
    header("Cache-Control: public; max-age=604800");
    header("Expires: 604800");

    echo $file['attachment'];
    exit();
} else if ($action == 'printable') {
    pwverify($forum['password'], 'viewthread.php?tid='.$tid, $fid, true);
    $querypost = $db->query("SELECT * FROM ".X_PREFIX."posts WHERE fid='$fid' AND tid='$tid' ORDER BY pid");
    $posts = '';
    $tmoffset = ($timeoffset * 3600) + ($addtime * 3600);
    while($post = $db->fetch_array($querypost)) {
        $date = gmdate($dateformat, $post['dateline'] + $tmoffset);
        $time = gmdate($timecode, $post['dateline'] + $tmoffset);
        $poston = "$date $lang[textat] $time";
        $post['message'] = stripslashes($post['message']);
        $bbcodeoff = $post['bbcodeoff'];
        $smileyoff = $post['smileyoff'];
        $post['message'] = postify($post['message'], $smileyoff, $bbcodeoff, $forum['allowsmilies'], $forum['allowhtml'], $forum['allowbbcode'], $forum['allowimgcode']);
        eval('$posts .= "'.template('viewthread_printable_row').'";');
    }
    $db->free_result($querypost);
    eval('echo stripslashes("'.template('viewthread_printable').'");');
}
?>
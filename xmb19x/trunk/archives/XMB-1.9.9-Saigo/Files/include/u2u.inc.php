<?php
/**
 * XMB 1.9.9 Saigo
 *
 * Developed by the XMB Group Copyright (c) 2001-2008
 * Sponsored by iEntry Inc. Copyright (c) 2007
 *
 * http://xmbgroup.com , http://ientry.com
 *
 * This software is released under the GPL License, you should
 * have received a copy of this license with the download of this
 * software. If not, you can obtain a copy by visiting the GNU
 * General Public License website <http://www.gnu.org/licenses/>.
 *
 **/

if (!defined('IN_CODE')) {
    exit("Not allowed to run this file directly.");
}

function u2u_msg($msg, $redirect) {
    global $u2uheader, $u2ufooter, $tablewidth, $bordercolor, $tablespace, $borderwidth, $altbg1;

    if (!empty($redirect)) {
        redirect($redirect);
    }
    eval('echo stripslashes("'.template('u2u_msg').'");');
    exit;
}

function db_u2u_insert($to, $from, $type, $owner, $folder, $subject, $message, $isRead, $isSent) {
    global $db, $onlinetime, $oToken;

    $subject = checkInput(censor(addslashes($subject)));
    $message = checkInput(censor(addslashes($message)));
    $db->query("INSERT INTO ".X_PREFIX."u2u (msgto, msgfrom, type, owner, folder, subject, message, dateline, readstatus, sentstatus) VALUES ('".addslashes($to)."', '".addslashes($from)."', '$type', '".addslashes($owner)."', '$folder', '$subject', '$message', '$onlinetime', '$isRead', '$isSent')");
}

function u2u_send_multi_recp($msgto, $subject, $message, $u2uid=0) {
    $errors = '';
    $recipients = array_unique(array_map('trim', explode(',', $msgto)));

    foreach($recipients as $recp) {
        $errors .= u2u_send_recp($recp, $subject, $message, $u2uid);
    }
    return $errors;
}

function u2u_send_recp($msgto, $subject, $message, $u2uid=0) {
    global $db, $self, $SETTINGS, $lang, $onlinetime, $bbname, $adminemail, $del, $oToken;

    $del = ('yes' === $del) ? 'yes' : 'no';
    $errors = '';

    $query = $db->query("SELECT username, email, lastvisit, ignoreu2u, emailonu2u, status FROM ".X_PREFIX."members WHERE username='" . trim($msgto) . "'");
    if ($rcpt = $db->fetch_array($query)) {
        $ilist = array_map('trim', explode(',', $rcpt['ignoreu2u']));
        if (!in_array($self['username'], $ilist) || X_ADMIN) {
            $username = $rcpt['username'];
            db_u2u_insert($username, $self['username'], 'incoming', $username, 'Inbox', $subject, $message, 'no', 'yes');
            if ($self['saveogu2u'] == 'yes') {
                db_u2u_insert($username, $self['username'], 'outgoing', $self['username'], 'Outbox', $subject, $message, 'no', 'yes');
            }

            $u2uid = (int) $u2uid;
            if ($del == 'yes' && $u2uid > 0){
                   $db->query("UPDATE ".X_PREFIX."u2u SET folder='Trash' WHERE u2uid='$u2uid' AND owner='$self[username]'");
            }

            if ($rcpt['emailonu2u'] == 'yes' && $rcpt['status'] != 'Banned') {
                $lastvisitcheck = $onlinetime - 600;
                if ($lastvisitcheck > $rcpt['lastvisit']) {
                    $u2uurl = $SETTINGS['boardurl'] . 'u2u.php';
                    altMail($rcpt['email'], "$lang[textnewu2uemail]", "$self[username] $lang[textnewu2ubody] \n$u2uurl", "From: $bbname <$adminemail>");
                }
            }
        } else {
            $errors = '<br />'.$lang['u2ublocked'];
        }
    } else {
        $errors = '<br />'.$lang['badrcpt'];
    }
    $db->free_result($query);
    return $errors;
}

function u2u_send($u2uid, $msgto, $subject, $message, $u2upreview) {
    global $db, $self, $lang, $username, $SETTINGS, $del;
    global $u2uheader, $u2ufooter, $u2ucount, $u2uquota, $oToken;
    global $altbg1, $altbg2, $bordercolor, $borderwidth, $tablespace, $cattext, $thewidth;
    global $forward, $reply, $sendsubmit, $savesubmit, $previewsubmit;

    $leftpane = '';
    $del = ($del == 'yes') ? 'yes' : 'no';
    $msgto = checkInput($msgto, '', '', 'script', false);
    $username = checkInput($username, '', '', 'script', false);

    if ($self['ban'] == 'u2u' || $self['ban'] == 'both') {
        error($lang['textbanfromu2u'], false, $u2uheader, $u2ufooter, false, true, false, false);
    }

    if (!X_STAFF && $u2ucount >= $u2uquota && $u2uquota > 0) {
        error($lang['u2ureachedquota'], false, $u2uheader, $u2ufooter, false, true, false, false);
    }

    if (onSubmit('savesubmit')) {
        // fixed by John Briggs
        $subject = (empty($subject) ? $lang['textnosub'] : $subject);

        if (empty($message)) {
            error($lang['u2uempty'], false, $u2uheader, $u2ufooter, false, true, false, false);
        }
        db_u2u_insert('', '', 'draft', $self['username'], 'Drafts', $subject, $message, 'yes', 'no');
        u2u_msg($lang['imsavedmsg'], 'u2u.php?folder=Drafts');
    }

    if (onSubmit('sendsubmit')) {
        $errors = '';
        // fixed by John Briggs
        $subject = (empty($subject) ? $lang['textnosub'] : $subject);

        // fixed lang variable use by John Briggs
        if (empty($message)) {
            error($lang['u2umsgempty'], false, $u2uheader, $u2ufooter, false, true, false, false);
        }

        if ($db->result($db->query("SELECT count(u2uid) FROM ".X_PREFIX."u2u WHERE msgfrom='$self[username]' AND dateline > ".(time()-$SETTINGS['floodctrl'])), 0) > 0) {
            error($lang['floodprotect_u2u'], false, $u2uheader, $u2ufooter, false, true, false, false);
        }

        $u2uid = (int) $_POST['u2uid'];

        if (strstr($msgto, ',') && X_STAFF) {
            $errors = u2u_send_multi_recp($msgto, $subject, $message, $u2uid);
        } else {
            $errors = u2u_send_recp($msgto, $subject, $message, $u2uid);
        }

        if (empty($errors)) {
            u2u_msg($lang['imsentmsg'], 'u2u.php');
        } else {
            u2u_msg(substr($errors, 6) , 'u2u.php');
        }
    }

    if ($u2uid > 0) {
        $query = $db->query("SELECT subject, msgfrom, message FROM ".X_PREFIX."u2u WHERE u2uid='$u2uid' AND owner='$self[username]'");
        $quote = $db->fetch_array($query);
        if ($quote) {
            if (!isset($previewsubmit)) {
                $prefixes = array($lang['textre'], $lang['textfwd']);
                $subject = trim(stripslashes(str_replace($prefixes, '', $quote['subject'])));
                $message = trim(stripslashes($quote['message']));
                if ($forward == 'yes') {
                    $subject = $lang['textfwd'].' '.$subject;
                    $message = '[quote][i]'.$lang['origpostedby'].' '.$quote['msgfrom']."[/i]\n".$message.'[/quote]';
                } else if ($reply == 'yes') {
                    $subject = $lang['textre'].' '.$subject;
                    $message = '[quote]'.$message.'[/quote]';
                    $username = $quote['msgfrom'];
                }
            }
        }
        $db->free_result($query);
    }

    if (isset($previewsubmit)) {
        $u2usubject = censor(stripslashes($subject));
        $u2umessage = censor(stripslashes($message));
        $u2umessage = postify($u2umessage, 'no', '', 'yes', 'no');
        $username = htmlspecialchars($msgto);
        eval('$u2upreview = "'.template('u2u_send_preview').'";');
    }

    eval('$leftpane = "'.template('u2u_send').'";');
    return $leftpane;
}

function u2u_view($u2uid, $folders) {
    global $db, $dateformat, $timecode, $timeoffset, $addtime, $lang, $self, $oToken;
    global $altbg1, $altbg2, $bordercolor, $borderwidth, $tablespace, $cattext, $thewidth;
    global $sendoptions, $u2uheader, $u2ufooter;

    $delchecked = '';
    $leftpane = '';

    $u2uid = (int) $u2uid;

    if (!($u2uid > 0)) {
        error($lang['textnonechosen'], false, $u2uheader, $u2ufooter, "u2u.php", true, false, false);
        return;
    }

    $query = $db->query("SELECT * FROM ".X_PREFIX."u2u WHERE u2uid='$u2uid' AND owner='$self[username]'");
    $u2u = $db->fetch_array($query);
    if ($u2u) {
        if ($u2u['type'] == 'incoming') {
            $db->query("UPDATE ".X_PREFIX."u2u SET readstatus='yes' WHERE u2uid=$u2u[u2uid] OR (u2uid=$u2u[u2uid]+1 AND type='outgoing' AND msgto='$self[username]')");
        } else if ($u2u['type'] == 'draft') {
            $db->query("UPDATE ".X_PREFIX."u2u SET readstatus='yes' WHERE u2uid=$u2u[u2uid]");
        }

        $adjTime = ($timeoffset * 3600) + ($addtime * 3600);
        $u2udate = gmdate($dateformat, $u2u['dateline'] + $adjTime);
        $u2utime = gmdate($timecode, $u2u['dateline'] + $adjTime);
        $u2udateline = $u2udate.' '.$lang['textat'].' '.$u2utime;
        $u2usubject = html_entity_decode(checkOutput(censor($u2u['subject'])));
        $u2umessage = html_entity_decode(checkOutput(postify($u2u['message'], 'no', '', 'yes', 'no')));
        $u2ufolder = $u2u['folder'];
        $u2ufrom = '<a href="member.php?action=viewpro&amp;member='.rawurlencode($u2u['msgfrom']).'" target="mainwindow">'.$u2u['msgfrom'].'</a>';
        $u2uto = ($u2u['type'] == 'draft') ? $lang['textu2unotsent'] : '<a href="member.php?action=viewpro&amp;member='.rawurlencode($u2u['msgto']).'" target="mainwindow">'.$u2u['msgto'].'</a>';
        if ($u2u['type'] == 'draft') {
            $sendoptions = '<input type="radio" name="mod" value="send" /> '.$lang['textu2u'].'<br />';
            $delchecked = ' checked="checked"';
        } else if ($u2u['msgfrom'] != $self['username']) {
            $sendoptions = '<input type="radio" name="mod" value="reply" checked="checked" /> '.$lang['textreply'].'<br /><input type="radio" name="mod" value="replydel" /> '.$lang['textreplytrash'].'<br /><input type="radio" name="mod" value="forward" /> '.$lang['textforward'].'<br />';
        } else {
            $delchecked = ' checked="checked"';
        }
        $mtofolder = array();
        $mtofolder[] = '<select name="tofolder">';
        $mtofolder[] = '<option value="">'.$lang['textpickfolder'].'</option>';
        foreach($folders as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
            }
            $mtofolder[] = '<option value="'.$key.'">'.$value.'</option>';
        }
        $mtofolder[] = '</select>';
        $mtofolder = implode("\n", $mtofolder);
    } else {
        error($lang['u2uadmin_noperm'], false, $u2uheader, $u2ufooter, false, true, false, false);
    }
    $db->free_result($query);
    eval('$leftpane = "'.template('u2u_view').'";');
    return $leftpane;
}

function u2u_print($u2uid, $eMail = false) {
    global $SETTINGS, $css, $db, $self, $timeoffset, $lang, $u2uheader, $u2ufooter, $dateformat, $timecode, $addtime, $charset, $bbname, $logo, $oToken;
    $mailHeader = $mailFooter = '';

    $u2uid = (int) $u2uid;

    if (!($u2uid > 0)) {
        error($lang['textnonechosen'], false, $u2uheader, $u2ufooter, "u2u.php", true, false, false);
        return;
    }

    $query = $db->query("SELECT * FROM ".X_PREFIX."u2u WHERE u2uid='$u2uid' AND owner='$self[username]'");
    $u2u = $db->fetch_array($query);
    $db->free_result($query);
    if ($u2u) {
        $adjTime = ($timeoffset * 3600) + ($addtime * 3600);
        $u2udate = gmdate($dateformat, $u2u['dateline'] +  $adjTime);
        $u2utime = gmdate($timecode, $u2u['dateline'] + $adjTime);
        $u2udateline = $u2udate.' '.$lang['textat'].' '.$u2utime;
        $u2usubject = html_entity_decode(stripslashes(checkOutput(censor($u2u['subject']))));
        $u2umessage = postify(html_entity_decode(stripslashes($u2u['message'])), 'no', 'no', 'yes', 'no', 'yes', 'yes', false, "no", "yes");;
        $u2ufolder = $u2u['folder'];
        $u2ufrom = $u2u['msgfrom'];
        $u2uto = ($u2u['type'] == 'draft') ? $lang['textu2unotsent'] : $u2u['msgto'];

        if ($eMail) {
            eval('$mailHeader = "'.template('email_html_header').'";');
            eval('$mailFooter = "'.template('email_html_footer').'";');
            $email = $mailHeader.$lang['textsubject']." ".$u2usubject."<br />\n".$lang['textfrom']." ".$u2ufrom."<br />\n".$lang['textto']." ".$u2uto."<br />\n".$lang['textu2ufolder']." ".$u2ufolder."<br />\n".$lang['textsent']." ".$u2udateline."<br />\n<br />\n".stripslashes($u2umessage).$mailFooter;
            altMail($self['email'], $lang['textu2utoemail']." ".$u2usubject, $email, 'From: '.$bbname.' <'.$self['email'].">\r\n".'Content-type: text/html');
            u2u_msg($lang['textu2utoemailsent'], 'u2u.php?action=view&u2uid='.$u2uid);
        } else {
            eval('echo stripslashes("'.template('u2u_printable').'");');
            exit;
        }
    } else {
        error($lang['u2uadmin_noperm'], false, $u2uheader, $u2ufooter, false, true, false, false);
    }
}

function u2u_delete($u2uid, $folder) {
    global $db, $self, $lang;
    global $u2uheader, $u2ufooter, $oToken;

    $u2uid = (int) $u2uid;

    if (!($u2uid > 0)) {
        error($lang['textnonechosen'], false, $u2uheader, $u2ufooter, 'u2u.php', true, false, false);
        return;
    }

    if ($folder == "Trash") {
        $db->query("DELETE FROM ".X_PREFIX."u2u WHERE u2uid='$u2uid' AND owner='$self[username]'");
    } else {
        $db->query("UPDATE ".X_PREFIX."u2u SET folder='Trash' WHERE u2uid='$u2uid' AND owner='$self[username]'");
    }
    u2u_msg($lang['imdeletedmsg'], 'u2u.php?folder='.$folder);
}

function u2u_mod_delete($folder, $u2u_select) {
    global $db, $self, $lang, $oToken;

    $in = '';
    foreach($u2u_select as $value) {
        $value = (int) $value;
        $in .= ($value > 0 ? (empty($in) ? "$value" : ", $value") : '');
    }

    if ($folder == "Trash") {
        $db->query("DELETE FROM ".X_PREFIX."u2u WHERE u2uid IN($in) AND owner='$self[username]'");
    } else {
        $db->query("UPDATE ".X_PREFIX."u2u SET folder='Trash' WHERE u2uid IN($in) AND owner='$self[username]'");
    }
    u2u_msg($lang['imdeletedmsg'], "u2u.php?folder=$folder");
}

function u2u_move($u2uid, $tofolder) {
    global $db, $self, $lang, $u2uheader, $u2ufooter, $folders, $type, $folder, $oToken;

    $u2uid = (int) $u2uid;

    if (!($u2uid > 0)) {
        error($lang['textnonechosen'], false, $u2uheader, $u2ufooter, 'u2u.php', true, false, false);
        return;
    }

    if (empty($tofolder)) {
        error($lang['textnofolder'], false, $u2uheader, $u2ufooter, "u2u.php?action=view&amp;u2uid=$u2uid", true, false, false);
    } else {
        if (!(in_array($tofolder, $folders) || $tofolder == 'Inbox' || $tofolder == 'Outbox' || $tofolder == 'Drafts') || ($tofolder == 'Inbox' && ($type == 'draft' || $type == 'outgoing')) || ($tofolder == 'Outbox' && ($type == 'incoming' || $type == 'draft')) || ($tofolder == 'Drafts' && ($type == 'incoming' || $type == 'outgoing'))) {
            error($lang['textcantmove'], false, $u2uheader, $u2ufooter, "u2u.php?action=view&amp;u2uid=$u2uid", true, false, false);
        }
        $db->query("UPDATE ".X_PREFIX."u2u SET folder='$tofolder' WHERE u2uid='$u2uid' AND owner='$self[username]'");
        u2u_msg($lang['textmovesucc'], "u2u.php?folder=$folder");
    }
}

function u2u_mod_move($tofolder, $u2u_select) {
    global $db, $self, $lang, $u2uheader, $u2ufooter, $folders, $oToken, $folder;

    $in = '';
    foreach($u2u_select as $value) {
        $value = (int) $value;
        if ($value > 0) {
            $type = $GLOBALS['type'.$value];
            if ((in_array($tofolder, $folders) || $tofolder == 'Inbox' || $tofolder == 'Outbox' || $tofolder == 'Drafts') && !($tofolder == 'Inbox' && ($type == 'draft' || $type == 'outgoing')) && !($tofolder == 'Outbox' && ($type == 'incoming' || $type == 'draft')) && !($tofolder == 'Drafts' && ($type == 'incoming' || $type == 'outgoing'))) {
                $in .= (empty($in)) ? "$value" : ",$value";
            }
        }
    }

    if (empty($in)) {
        error($lang['textcantmove'], false, $u2uheader, $u2ufooter, "u2u.php?folder=$folder", true, false, false);
        return;
    }
    $db->query("UPDATE ".X_PREFIX."u2u SET folder='$tofolder' WHERE u2uid IN($in) AND owner='$self[username]'");
    u2u_msg($lang['textmovesucc'], "u2u.php?folder=$folder");
}

function u2u_markUnread($u2uid, $folder, $type) {
    global $db, $self, $lang, $u2uheader, $u2ufooter, $oToken;

    $u2uid = (int) $u2uid;

    if (!($u2uid > 0)) {
        error($lang['textnonechosen'], false, $u2uheader, $u2ufooter, "u2u.php", true, false, false);
        return;
    }

    if (empty($folder)) {
        error($lang['textnofolder'], false, $u2uheader, $u2ufooter, "u2u.php?action=view&amp;u2uid=$u2uid", true, false, false);
        return;
    }

    if ($type == 'outgoing') {
        error($lang['textnomur'], false, $u2uheader, $u2ufooter, "u2u.php?folder=$folder", true, false, false);
    }
    $db->query("UPDATE ".X_PREFIX."u2u SET readstatus='no' WHERE u2uid=$u2uid AND owner='$self[username]'");
    u2u_msg($lang['textmarkedunread'], "u2u.php?folder=$folder");
}

function u2u_mod_markUnread($folder, $u2u_select) {
    global $db, $lang, $u2uheader, $u2ufooter, $self, $oToken;

    if (empty($folder)) {
        error($lang['textnofolder'], false, $u2uheader, $u2ufooter, "u2u.php?action=view&amp;u2uid=$u2uid", true, false, false);
        return;
    }

    if (empty($u2u_select)) {
        error($lang['textnonechosen'], false, $u2uheader, $u2ufooter, "u2u.php?folder=$folder", true, false, false);
        return;
    }

    $in = '';
    foreach($u2u_select as $value) {
        $value = (int) $value;
        if ($value > 0) {
            if ($GLOBALS['type'.$value] != 'outgoing') {
                $value = intval($value);
                $in .= (empty($in)) ? "$value" : ",$value";
            }
        }
    }

    if (empty($in)) {
        error($lang['textnonechosen'], false, $u2uheader, $u2ufooter, "u2u.php?folder=$folder", true, false, false);
    }
    $db->query("UPDATE ".X_PREFIX."u2u SET readstatus='no' WHERE u2uid IN($in) AND owner='$self[username]'");
    u2u_msg($lang['textmarkedunread'], "u2u.php?folder=$folder");
}

function u2u_folderSubmit($u2ufolders, $folders) {
    global $db, $lang, $self, $farray, $oToken;

    $error = '';

    $newfolders = explode(',', $u2ufolders);
    foreach($newfolders as $key => $value) {
        $newfolders[$key] = trim($value);
        if (empty($newfolders[$key])) {
            unset($newfolders[$key]);
        }
    }

    foreach($folders as $value) {
        if (isset($farray[$value]) && $farray[$value] != 0 && !in_array($value, $newfolders) && !in_array($value, array('Inbox', 'Outbox', 'Drafts', 'Trash'))) {
            $newfolders[] = $value;
            $error .= (empty($error)) ? '<br />'.$lang['foldersupdateerror'].' '.$value : ', '.$value;
        }
    }
    $u2ufolders = checkInput(implode(', ', $newfolders));
    $db->query("UPDATE ".X_PREFIX."members SET u2ufolders='$u2ufolders' WHERE username='$self[username]'");
    u2u_msg($lang['foldersupdate'].$error, "u2u.php?folder=Inbox");
}

function u2u_ignore() {
    global $ignorelist, $ignoresubmit, $self, $lang, $db, $oToken;
    global $altbg1, $altbg2, $bordercolor, $borderwidth, $tablespace, $tablewidth, $cattext, $thewidth;

    $leftpane = '';
    if (isset($ignoresubmit) && isset($ignorelist)) {
        $self['ignoreu2u'] = htmlspecialchars(checkInput($ignorelist));
        $db->query("UPDATE ".X_PREFIX."members SET ignoreu2u='" . $self['ignoreu2u'] . "' WHERE username='$self[username]'");
        u2u_msg($lang['ignoreupdate'], "u2u.php?action=ignore");
    } else {
        $self['ignoreu2u'] = checkOutput($self['ignoreu2u']);
        eval('$leftpane = "'.template('u2u_ignore').'";');
    }
    return $leftpane;
}

function u2u_display($folder, $folders) {
    global $db, $self, $lang;
    global $altbg1, $altbg2, $bordercolor, $borderwidth, $tablespace, $tablewidth, $cattext, $thewidth;
    global $addtime, $timeoffset, $dateformat, $timecode, $oToken;

    $u2usin = '';
    $u2usout = '';
    $u2usdraft = '';
    $leftpane = '';

    if (empty($folder)) {
        $folder = "Inbox";
    }

    $query = $db->query("SELECT u.*, w.username, w.invisible FROM ".X_PREFIX."u2u u LEFT JOIN ".X_PREFIX."whosonline w ON (u.msgto=w.username OR u.msgfrom=w.username) AND w.username!='$self[username]' WHERE u.folder='$folder' AND u.owner='$self[username]' ORDER BY dateline DESC");
    while($u2u = $db->fetch_array($query)) {
        if ($u2u['readstatus'] == 'yes') {
            $u2ureadstatus = $lang['textread'];
        } else {
            $u2ureadstatus = '<strong>'.$lang['textunread'].'</strong>';
        }

        if (empty($u2u['subject'])) {
            $u2u['subject'] = '&laquo;'.$lang['textnosub'].'&raquo;';
        } else {
            $u2u['subject'] = html_entity_decode($u2u['subject']);
        }

        $u2usubject = checkOutput(censor($u2u['subject']));
        if ($u2u['type'] == 'incoming') {
            if ($u2u['msgfrom'] == $u2u['username'] || $u2u['msgfrom'] == $self['username']) {
                if ($u2u['invisible'] == 1) {
                    if (X_ADMIN) {
                        $online = $lang['hidden'];
                    } else {
                        $online = $lang['textoffline'];
                    }
                } else {
                    $online = $lang['textonline'];
                }
            } else {
                $online = $lang['textoffline'];
            }
            $u2usent = '<a href="member.php?action=viewpro&amp;member='.rawurlencode($u2u['msgfrom']).'" target="_blank">'.$u2u['msgfrom'].'</a> ('.$online.')';
        } else if ($u2u['type'] == 'outgoing') {
            if ($u2u['msgto'] == $u2u['username'] || $u2u['msgto'] == $self['username']) {
                if ($u2u['invisible'] == 1) {
                    if (X_ADMIN) {
                        $online = $lang['hidden'];
                    } else {
                        $online = $lang['textoffline'];
                    }
                } else {
                    $online = $lang['textonline'];
                }
            } else {
                $online = $lang['textoffline'];
            }
            $u2usent = '<a href="member.php?action=viewpro&amp;member='.rawurlencode($u2u['msgto']).'" target="_blank">'.$u2u['msgto'].'</a> ('.$online.')';
        } else if ($u2u['type'] == 'draft') {
            $u2usent = $lang['textu2unotsent'];
        }

        $adjTime = ($timeoffset * 3600) + ($addtime * 3600);
        $u2udate = gmdate("$dateformat", $u2u['dateline'] + $adjTime);
        $u2utime = gmdate("$timecode", $u2u['dateline'] + $adjTime);
        $u2udateline = "$u2udate $lang[textat] $u2utime";
        switch($u2u['type']) {
            case 'outgoing':
                $u2us = 'u2usout';
                break;
            case 'draft':
                $u2us = 'u2usdraft';
                break;
            case 'incoming':
            default:
                $u2us = 'u2usin';
                break;
        }
        eval('$$u2us .= "'.template('u2u_row').'";');
    }
    $db->free_result($query);

    if (empty($u2usin)) {
        eval('$u2usin = "'.template('u2u_row_none').'";');
    }

    if (empty($u2usout)) {
        eval('$u2usout = "'.template('u2u_row_none').'";');
    }

    if (empty($u2usdraft)) {
        eval('$u2usdraft = "'.template('u2u_row_none').'";');
    }

    switch($folder) {
        case 'Outbox':
            eval('$u2ulist = "'.template('u2u_outbox').'";');
            break;
        case 'Drafts':
            eval('$u2ulist = "'.template('u2u_drafts').'";');
            break;
        case 'Inbox':
            eval('$u2ulist = "'.template('u2u_inbox').'";');
            break;
        default:
            eval('$u2ulist = "'.template('u2u_inbox').'<br />'.template('u2u_outbox').'<br />'.template('u2u_drafts').'";');
            break;
    }

    $mtofolder = array();
    $mtofolder[] = '<select name="tofolder">';
    $mtofolder[] = '<option value="">'.$lang['textpickfolder'].'</option>';
    foreach($folders as $key => $value) {
        if (is_numeric($key)) {
            $key = $value;
        }
        $mtofolder[] = '<option value="'.$key.'">'.$value.'</option>';
    }
    $mtofolder[] = '</select>';
    $mtofolder = implode("\n", $mtofolder);
    eval('$leftpane = "'.template('u2u_main').'";');
    return $leftpane;
}

function u2u_folderList() {
    global $db, $self, $lang, $altbg1, $oToken;
    global $folder, $folderlist, $folders, $farray; // <--- these are modified in here

    $u2ucount = 0;
    $folders = (empty($self['u2ufolders'])) ? array() : explode(",", $self['u2ufolders']);
    foreach($folders as $key => $value) {
        $folders[$key] = trim($value);
    }
    sort($folders);
    $folders = array_merge(array('Inbox' => $lang['textu2uinbox'], 'Outbox' => $lang['textu2uoutbox']), $folders, array('Drafts' => $lang['textu2udrafts'], 'Trash' => $lang['textu2utrash']));

    $query = $db->query("SELECT folder, count(u2uid) as count FROM ".X_PREFIX."u2u WHERE owner='$self[username]' GROUP BY folder ORDER BY folder ASC");
    $flist = array();
    while($flist = $db->fetch_array($query)) {
        $farray[$flist['folder']] = $flist['count'];
        $u2ucount += $flist['count'];
    }
    $db->free_result($query);

    $emptytrash = $folderlist = '';
    foreach($folders as $link => $value) {
        echo ("<!-- $link = $value -->");
        if (is_numeric($link)) {
            $link = $value;
        }

        if ($link == $folder) {
            $value = '<strong>'.$value.'</strong>';
        }

        $count = (empty($farray[$link])) ? 0 : $farray[$link];
        if ($link == 'Trash') {
            if ($count != 0) {
                $emptytrash = ' (<a href="u2u.php?action=emptytrash">'.$lang['textemptytrash'].'</a>)';
            }
        }
        eval('$folderlist .= "'.template('u2u_folderlink').'";');
    }
    return $u2ucount;
}
?>
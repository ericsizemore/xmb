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

define('X_SCRIPT', 'member.php');

require 'header.php';

loadtemplates(
'member_coppa',
'member_reg_rules',
'member_reg_password',
'member_reg_avatarurl',
'member_reg_avatarlist',
'member_reg',
'member_reg_optional',
'member_reg_captcha',
'member_profile_email',
'member_profile',
'misc_feature_not_while_loggedin',
'misc_feature_notavailable',
'timezone_control'
);

smcwcache();

eval('$css = "'.template('css').'";');

$action = postedVar('action', '', FALSE, FALSE, FALSE, 'g');
switch($action) {
    case 'reg':
        nav($lang['textregister']);
        break;
    case 'viewpro':
        nav($lang['textviewpro']);
        break;
    case 'coppa':
        nav($lang['textcoppa']);
        break;
    default:
        header('HTTP/1.0 404 Not Found');
        error($lang['textnoaction']);
        break;
}

switch($action) {
    case 'coppa':
        eval('$header = "'.template('header').'";');
        if ($SETTINGS['regstatus'] == 'off') {
            header('HTTP/1.0 403 Forbidden');
            eval('$memberpage = "'.template('misc_feature_notavailable').'";');
        } elseif (X_MEMBER) {
            eval('$memberpage = "'.template('misc_feature_not_while_loggedin').'";');
        } else {
            if ($SETTINGS['coppa'] != 'on') {
                redirect($full_url.'member.php?action=reg', 0);
            }
            if (onSubmit('coppasubmit')) {
                redirect($full_url.'member.php?action=reg', 0);
            } else {
                eval('$memberpage = "'.template('member_coppa').'";');
            }
        }
        break;

    case 'reg':
        if ($SETTINGS['pruneusers'] > 0) {
            $prunebefore = $onlinetime - (60 * 60 * 24 * $SETTINGS['pruneusers']);
            $db->query("DELETE FROM ".X_PREFIX."members WHERE lastvisit=0 AND regdate < $prunebefore AND status='Member'");
        }

        if ($SETTINGS['maxdayreg'] > 0) {
            $time = $onlinetime - 86400; // subtract 24 hours
            $query = $db->query("SELECT COUNT(uid) FROM ".X_PREFIX."members WHERE regdate > $time");
            if ($db->result($query, 0) > $SETTINGS['maxdayreg']) {
                error($lang['max_regs']);
            }
            $db->free_result($query);
        }

        eval('$header = "'.template('header').'";');

        if ($SETTINGS['regstatus'] == 'off') {
            header('HTTP/1.0 403 Forbidden');
            eval('$memberpage = "'.template('misc_feature_notavailable').'";');
        } elseif (X_MEMBER) {
            eval('$memberpage = "'.template('misc_feature_not_while_loggedin').'";');
        } elseif (noSubmit('regsubmit')) {
            if ($SETTINGS['bbrules'] == 'on' && noSubmit('rulesubmit')) {
                $SETTINGS['bbrulestxt'] = nl2br($SETTINGS['bbrulestxt']);
                eval('$memberpage = "'.template('member_reg_rules').'";');
            } else {
                $currdate = gmdate($timecode, $onlinetime+ ($addtime * 3600));
                eval($lang['evaloffset']);

                $themelist = array();
                $themelist[] = '<select name="thememem">';
                $themelist[] = '<option value="0">'.$lang['textusedefault'].'</option>';
                $query = $db->query("SELECT themeid, name FROM ".X_PREFIX."themes ORDER BY name ASC");
                while($themeinfo = $db->fetch_array($query)) {
                    $themelist[] = '<option value="'.intval($themeinfo['themeid']).'">'.$themeinfo['name'].'</option>';
                }
                $themelist[] = '</select>';
                $themelist = implode("\n", $themelist);
                $db->free_result($query);

                $langfileselect = createLangFileSelect($langfile);

                $dayselect = array();
                $dayselect[] = '<select name="day">';
                $dayselect[] = '<option value="">&nbsp;</option>';
                for($num = 1; $num <= 31; $num++) {
                    $dayselect[] = '<option value="'.$num.'">'.$num.'</option>';
                }
                $dayselect[] = '</select>';
                $dayselect = implode("\n", $dayselect);

                if ($SETTINGS['sigbbcode'] == 'on') {
                    $bbcodeis = $lang['texton'];
                } else {
                    $bbcodeis = $lang['textoff'];
                }

                if ($SETTINGS['sightml'] == 'on') {
                    $htmlis = $lang['texton'];
                } else {
                    $htmlis = $lang['textoff'];
                }

                $pwtd = '';
                if ($SETTINGS['emailcheck'] == 'off') {
                    eval('$pwtd = "'.template('member_reg_password').'";');
                }

                if ($SETTINGS['timeformat'] == 24) {
                    $timeFormat12Checked = '';
                    $timeFormat24Checked = $cheHTML;
                } else {
                    $timeFormat12Checked = $cheHTML;
                    $timeFormat24Checked = '';
                }

                $timezones = timezone_control( $SETTINGS['def_tz'] );

                $avatd = '';
                if ($SETTINGS['avastatus'] == 'on') {
                    eval('$avatd = "'.template('member_reg_avatarurl').'";');
                } else if ($SETTINGS['avastatus'] == 'list') {
                    $avatars = array();
                    $avatars[] = '<option value=""/>'.$lang['textnone'].'</option>';
                    $dirHandle = opendir(ROOT.'images/avatars');
                    while($avFile = readdir($dirHandle)) {
                        if (is_file(ROOT.'images/avatars/'.$avFile) && $avFile != '.' && $avFile != '..' && $avFile != 'index.html') {
                            $avatars[] = '<option value="./images/avatars/'.$avFile.'" />'.$avFile.'</option>';
                        }
                    }
                    closedir($dirHandle);
                    $avatars = implode("\n", str_replace('value="'.$member['avatar'].'"', 'value="'.$member['avatar'].'" selected="selected"', $avatars));
                    eval('$avatd = "'.template('member_reg_avatarlist').'";');
                }

                if (empty($dformatorig)) {
                    $dformatorig = $SETTINGS['dateformat'];
                }

                $regoptional = '';
                if ($SETTINGS['regoptional'] == 'on') {
                    eval('$regoptional = "'.template('member_reg_optional').'";');
                }

                $captcharegcheck = '';
                if ($SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_reg_status'] == 'on') {
                    require ROOT.'include/captcha.inc.php';
                    $Captcha = new Captcha();
                    if ($Captcha->bCompatible !== false) {
                        $imghash = $Captcha->GenerateCode();
                        if ($SETTINGS['captcha_code_casesensitive'] == 'off') {
                            $lang['captchacaseon'] = '';
                        }
                        eval('$captcharegcheck = "'.template('member_reg_captcha').'";');
                    }
                }
                eval('$memberpage = "'.template('member_reg').'";');
            }
        } else {
            $username = trim(postedVar('username', '', TRUE, FALSE));

            if (strlen($username) < 3 || strlen($username) > 32) {
                error($lang['username_length_invalid']);
            }

            $nonprinting = '\\x00-\\x1F\\x7F';  //Universal chars that are invalid.
            $specials = '\\]\'<>\\\\|"[,@';  //Other universal chars disallowed by XMB: []'"<>\|,@
            $sequences = '|  ';  //Phrases disallowed, each separated by '|'
            $icharset = strtoupper($charset);
            if (substr($icharset, 0, 8) == 'ISO-8859') {
                if ($icharset == 'ISO-8859-11') {
                    $nonprinting .= '-\\x9F\\xDB-\\xDE\\xFC-\\xFF';  //More chars invalid for the Thai set.
                } else {
                    $nonprinting .= '-\\x9F\\xAD';  //More chars invalid for all ISO 8859 sets except Part 11 (Thai).
                }
            } elseif (substr($icharset, 0, 11) == 'WINDOWS-125') {
                $nonprinting .= '\\xAD';  //More chars invalid for all Windows code pages.
            }

            if ($_POST['username'] != preg_replace("#[{$nonprinting}{$specials}]{$sequences}#", '', $_POST['username'])) {
                error($lang['restricted']);
            }

            $username = trim(postedVar('username'));

            if ($SETTINGS['ipreg'] != 'off') {
                $time = $onlinetime-86400;
                $query = $db->query("SELECT uid FROM ".X_PREFIX."members WHERE regip='$onlineip' AND regdate >= $time");
                if ($db->num_rows($query) >= 1) {
                    error($lang['reg_today']);
                }
                $db->free_result($query);
            }

            $email = postedVar('email', 'javascript', TRUE, TRUE, TRUE);
            if ($SETTINGS['doublee'] == 'off' && false !== strpos($email, "@")) {
                $email1 = ", email";
                $email2 = "OR email='$email'";
            } else {
                $email1 = '';
                $email2 = '';
            }

            $query = $db->query("SELECT username$email1 FROM ".X_PREFIX."members WHERE username='$username' $email2");
            if ($member = $db->fetch_array($query)) {
                $db->free_result($query);
                error($lang['alreadyreg']);
            }

            $postcount = $db->result($db->query("SELECT COUNT(*) FROM ".X_PREFIX."posts WHERE author='$username'"), 0);
            if (intval($postcount) > 0) {
                error($lang['alreadyreg']);
            }

            if ($SETTINGS['emailcheck'] == 'on') {
                $password = '';
                $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
                $get = strlen($chars) - 1;
                for($i = 0; $i < 8; $i++) {
                    $password .= $chars[random_int(0, $get)];
                }
                $password2 = $password;
            } elseif (!isset($_POST['password']) || !isset($_POST['password2'])) {
                error($lang['textpw1']);
            } else {
                $password = $_POST['password'];
                $password2 = $_POST['password2'];
            }

            if ($password != $password2) {
                error($lang['pwnomatch']);
            }

            $fail = false;
            $efail = false;
            $query = $db->query("SELECT * FROM ".X_PREFIX."restricted");
            while($restriction = $db->fetch_array($query)) {
                $t_username = $username;
                $t_email = $email;
                if ($restriction['case_sensitivity'] == 0) {
                    $t_username = strtolower($t_username);
                    $t_email = strtolower($t_email);
                    $restriction['name'] = strtolower($restriction['name']);
                }

                if ($restriction['partial'] == 1) {
                    if (strpos($t_username, $restriction['name']) !== false) {
                        $fail = true;
                    }

                    if (strpos($t_email, $restriction['name']) !== false) {
                        $efail = true;
                    }
                } else {
                    if ($t_username == $restriction['name']) {
                        $fail = true;
                    }

                    if ($t_email == $restriction['name']) {
                        $efail = true;
                    }
                }
            }
            $db->free_result($query);

            if ($fail) {
                error($lang['restricted']);
            }

            if ($efail) {
                error($lang['emailrestricted']);
            }

            require ROOT.'include/validate-email.inc.php';
            $test = new EmailAddressValidator();
            $rawemail = postedVar('email', '', FALSE, FALSE);
            if (false === $test->check_email_address($rawemail)) {
                error($lang['bademail']);
            }

            if ($password == '' || strpos($password, '"') != false || strpos($password, "'") != false) {
                error($lang['textpw1']);
            }

            if ($username == '') {
                error($lang['textnousername']);
            }

            if ($SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_reg_status'] == 'on') {
                require ROOT.'include/captcha.inc.php';
                $Captcha = new Captcha();
                if ($Captcha->bCompatible !== false) {
                    $imghash = postedVar('imghash', '', FALSE, TRUE);
                    $imgcode = postedVar('imgcode', '', FALSE, FALSE);
                    if ($Captcha->ValidateCode($imgcode, $imghash) !== true) {
                        error($lang['captchaimageinvalid']);
                    }
                }
            }

            $langfilenew = postedVar('langfilenew');
            $result = $db->query("SELECT devname FROM ".X_PREFIX."lang_base WHERE devname='$langfilenew'");
            if ($db->num_rows($result) == 0) {
                $langfilenew = $SETTINGS['langfile'];
            }

            $query = $db->query("SELECT COUNT(uid) FROM ".X_PREFIX."members");
            $count1 = $db->result($query,0);
            $db->free_result($query);

            $self['status'] = ($count1 != 0) ? 'Member' : 'Super Administrator';

            $timeoffset1 = isset($_POST['timeoffset1']) && is_numeric($_POST['timeoffset1']) ? $_POST['timeoffset1'] : 0;
            $thememem = formInt('thememem');
            $tpp = formInt('tpp');
            $ppp = formInt('ppp');
            $showemail = formYesNo('showemail');
            $newsletter = formYesNo('newsletter');
            $saveogu2u = formYesNo('saveogu2u');
            $emailonu2u = formYesNo('emailonu2u');
            $useoldu2u = formYesNo('useoldu2u');
            $u2ualert = formInt('u2ualert');
            $year = formInt('year');
            $month = formInt('month');
            $day = formInt('day');
            // For year of birth, reject all integers from 100 through 1899.
            if ($year >= 100 && $year <= 1899) $year = 0;
            $bday = iso8601_date($year, $month, $day);

            $dateformatnew = postedVar('dateformatnew', '', FALSE, TRUE);
            $dateformattest = attrOut($dateformatnew, 'javascript');  // NEVER allow attribute-special data in the date format because it can be unescaped using the date() parser.
            if (strlen($dateformatnew) == 0 || $dateformatnew != $dateformattest) {
                $dateformatnew = $SETTINGS['dateformat'];
            }
            unset($dateformattest);

            $timeformatnew = formInt('timeformatnew');
            if ($timeformatnew != 12 && $timeformatnew != 24) {
                $timeformatnew = $SETTINGS['timeformat'];
            }

            $password = md5($password);

            if ($SETTINGS['regoptional'] == 'off') {
                $db->query("INSERT INTO ".X_PREFIX."members (username, password, regdate, postnum, email, site, aim, status, location, bio, sig, showemail, timeoffset, icq, avatar, yahoo, customstatus, theme, bday, langfile, tpp, ppp, newsletter, regip, timeformat, msn, ban, dateformat, ignoreu2u, lastvisit, mood, pwdate, invisible, u2ufolders, saveogu2u, emailonu2u, useoldu2u, u2ualert) VALUES ('$username', '$password', ".$db->time($onlinetime).", 0, '$email', '', '', '$self[status]', '', '', '', '$showemail', '$timeoffset1', '', '', '', '', $thememem, '$bday', '$langfilenew', $tpp, $ppp, '$newsletter', '$onlineip', $timeformatnew, '', '', '$dateformatnew', '', 0, '', 0, '0', '', '$saveogu2u', '$emailonu2u', '$useoldu2u', $u2ualert)");
            } else {
                $location = postedVar('location', 'javascript', TRUE, TRUE, TRUE);
                $icq = postedVar('icq', '', FALSE, FALSE);
                $icq = ($icq && is_numeric($icq) && $icq > 0) ? $icq : 0;
                $yahoo = postedVar('yahoo', 'javascript', TRUE, TRUE, TRUE);
                $aim = postedVar('aim', 'javascript', TRUE, TRUE, TRUE);
                $msn = postedVar('msn', 'javascript', TRUE, TRUE, TRUE);
                $site = postedVar('site', 'javascript', TRUE, TRUE, TRUE);
                $bio = postedVar('bio', 'javascript', TRUE, TRUE, TRUE);
                $mood = postedVar('mood', 'javascript', TRUE, TRUE, TRUE);
                $sig = postedVar('sig', 'javascript', ($SETTINGS['sightml']=='off'), TRUE, TRUE);

                if ($SETTINGS['avastatus'] == 'on') {
                    $avatar = postedVar('newavatar', 'javascript', TRUE, TRUE, TRUE);
                    $rawavatar = postedVar('newavatar', '', FALSE, FALSE);

                    $newavatarcheck = postedVar('newavatarcheck');

                    $max_size = explode('x', $SETTINGS['max_avatar_size']);

                    if (preg_match('/^' . get_img_regexp() . '$/i', $rawavatar) == 0) {
                        $avatar = '';
                    } elseif (ini_get('allow_url_fopen')) {
                        if ($max_size[0] > 0 && $max_size[1] > 0 && strlen($rawavatar) > 0) {
                            $size = @getimagesize($rawavatar);
                            if ($size === FALSE) {
                                $avatar = '';
                            } elseif (($size[0] > $max_size[0] && $max_size[0] > 0) || ($size[1] > $max_size[1] && $max_size[1] > 0)) {
                                error($lang['avatar_too_big'] . $SETTINGS['max_avatar_size'] . 'px');
                            }
                        }
                    } elseif ($newavatarcheck == "no") {
                        $avatar = '';
                    }
                    unset($rawavatar);
                } elseif ($SETTINGS['avastatus'] == 'list') {
                    $rawavatar = postedVar('newavatar', '', FALSE, FALSE);
                    $dirHandle = opendir(ROOT.'images/avatars');
                    $filefound = FALSE;
                    while($avFile = readdir($dirHandle)) {
                        if ($rawavatar == './images/avatars/'.$avFile) {
                            if (is_file(ROOT.'images/avatars/'.$avFile) && $avFile != '.' && $avFile != '..' && $avFile != 'index.html') {
                                $filefound = TRUE;
                            }
                        }
                    }
                    closedir($dirHandle);
                    unset($rawavatar);
                    if ($filefound) {
                        $avatar = postedVar('newavatar', 'javascript', TRUE, TRUE, TRUE);
                    } else {
                        $avatar = '';
                    }
                } else {
                    $avatar = '';
                }

                $db->query("INSERT INTO ".X_PREFIX."members (username, password, regdate, postnum, email, site, aim, status, location, bio, sig, showemail, timeoffset, icq, avatar, yahoo, customstatus, theme, bday, langfile, tpp, ppp, newsletter, regip, timeformat, msn, ban, dateformat, ignoreu2u, lastvisit, mood, pwdate, invisible, u2ufolders, saveogu2u, emailonu2u, useoldu2u, u2ualert) VALUES ('$username', '$password', ".$db->time($onlinetime).", 0, '$email', '$site', '$aim', '$self[status]', '$location', '$bio', '$sig', '$showemail', '$timeoffset1', '$icq', '$avatar', '$yahoo', '', $thememem, '$bday', '$langfilenew', $tpp, $ppp, '$newsletter', '$onlineip', $timeformatnew, '$msn', '', '$dateformatnew', '', 0, '$mood', 0, '0', '', '$saveogu2u', '$emailonu2u', '$useoldu2u', $u2ualert)");
            }

            $lang2 = loadPhrases(array('charset','textnewmember','textnewmember2','textyourpw','textyourpwis','textusername','textpassword'));

            if ($SETTINGS['notifyonreg'] != 'off') {
                $mailquery = $db->query("SELECT username, email, langfile FROM ".X_PREFIX."members WHERE status = 'Super Administrator'");
                while($admin = $db->fetch_array($mailquery)) {
                    $translate = $lang2[$admin['langfile']];
                    if ($SETTINGS['notifyonreg'] == 'u2u') {
                        $db->query("INSERT INTO ".X_PREFIX."u2u (u2uid, msgto, msgfrom, type, owner, folder, subject, message, dateline, readstatus, sentstatus) VALUES ('', '$admin[username]', '".$db->escape($bbname)."', 'incoming', '$admin[username]', 'Inbox', '$translate[textnewmember]', '$translate[textnewmember2]', '".$onlinetime."', 'no', 'yes')");
                    } else {
                        $adminemail = htmlspecialchars_decode($admin['email'], ENT_QUOTES);
                        $body = "{$translate['textnewmember2']}\n\n$full_url";
                        xmb_mail( $adminemail, $translate['textnewmember'], $body, $translate['charset'] );
                    }
                }
                $db->free_result($mailquery);
            }

            if ($SETTINGS['emailcheck'] == 'on') {
                $translate = $lang2[$langfilenew];
                $username = trim(postedVar('username', '', FALSE, FALSE));
                $rawbbname = htmlspecialchars_decode($bbname, ENT_NOQUOTES);
                $subject = "[$rawbbname] {$translate['textyourpw']}";
                $body = "{$translate['textyourpwis']} \n\n{$translate['textusername']} $username\n{$translate['textpassword']} $password2\n\n$full_url";
                xmb_mail( $rawemail, $subject, $body, $translate['charset'] );
            } else {
                $username = trim(postedVar('username', '', TRUE, FALSE));
                $currtime = $onlinetime + (86400*30);
                put_cookie("xmbuser", $username, $currtime);
                put_cookie("xmbpw", $password, $currtime);
            }
            $memberpage = ($SETTINGS['emailcheck'] == 'on') ? "<center><span class=\"mediumtxt \">$lang[emailpw]</span></center>" : "<center><span class=\"mediumtxt \">$lang[regged]</span></center>";

            redirect($full_url);
        }
        break;

    case 'viewpro':
        $member = postedVar('member', '', TRUE, FALSE, FALSE, 'g');
        if (strlen($member) < 3 || strlen($member) > 32) {
            header('HTTP/1.0 404 Not Found');
            error($lang['nomember']);
        }

        $memberinfo = \XMB\SQL\getMemberByName( $member );

        if ( empty( $memberinfo ) ) {
            header('HTTP/1.0 404 Not Found');
            error($lang['nomember']);
        }

        $memberinfo['password'] = '';

        $member = $db->escape( $member );

        if ($memberinfo['status'] == 'Banned') {
            $memberinfo['avatar'] = '';
            $rank = array(
            'title' => 'Banned',
            'posts' => 0,
            'id' => 0,
            'stars' => 0,
            'allowavatars' => 'no',
            'avatarrank' => ''
            );
        } else {
            if ($memberinfo['status'] == 'Administrator' || $memberinfo['status'] == 'Super Administrator' || $memberinfo['status'] == 'Super Moderator' || $memberinfo['status'] == 'Moderator') {
                $limit = "title = '$memberinfo[status]'";
            } else {
                $limit = "posts <= '$memberinfo[postnum]' AND title != 'Super Administrator' AND title != 'Administrator' AND title != 'Super Moderator' AND title != 'Moderator'";
            }

            $rank = $db->fetch_array($db->query("SELECT * FROM ".X_PREFIX."ranks WHERE $limit ORDER BY posts DESC LIMIT 1"));
        }

        eval('$header = "'.template('header').'";');

        $encodeuser = recodeOut($memberinfo['username']);
        if (X_GUEST) {
            $memberlinks = '';
        } else {
            $memberlinks = " <small>(<a href=\"u2u.php?action=send&amp;username=$encodeuser\" onclick=\"Popup(this.href, 'Window', 700, 450); return false;\">{$lang['textu2u']}</a>)&nbsp;&nbsp;(<a href=\"buddy.php?action=add&amp;buddys=$encodeuser\" onclick=\"Popup(this.href, 'Window', 450, 400); return false;\">{$lang['addtobuddies']}</a>)</small>";
        }

        $daysreg = ($onlinetime - $memberinfo['regdate']) / (24*3600);
        if ($daysreg > 1) {
            $ppd = $memberinfo['postnum'] / $daysreg;
            $ppd = round($ppd, 2);
        } else {
            $ppd = $memberinfo['postnum'];
        }

        $memberinfo['regdate'] = gmdate($dateformat , $memberinfo['regdate'] + ($addtime * 3600) + ($timeoffset * 3600));

        $memberinfo['site'] = format_member_site( $memberinfo['site'] );
        $site = $memberinfo['site'];

        if (X_MEMBER && $memberinfo['email'] != '' && $memberinfo['showemail'] == 'yes') {
            $email = $memberinfo['email'];
        } else {
            $email = '';
        }

        $rank['avatarrank'] = trim($rank['avatarrank']);
        $memberinfo['avatar'] = trim($memberinfo['avatar']);

        if ($rank['avatarrank'] != '') {
            $rank['avatarrank'] = '<img src="'.$rank['avatarrank'].'" alt="'.$lang['altavatar'].'" border="0" />';
        }

        if ($memberinfo['avatar'] != '') {
            $memberinfo['avatar'] = '<img src="'.$memberinfo['avatar'].'" alt="'.$lang['altavatar'].'" border="0" />';
        }

        if ( ($rank['avatarrank'] || $memberinfo['avatar']) && $site != '' ) {
            $sitelink = $site;
            if ($memberinfo['avatar'] != '') {
                $newsitelink = "<a href=\"$sitelink\" onclick=\"window.open(this.href); return false;\">{$memberinfo['avatar']}</a></td>";
            } else {
                $newsitelink = '';
            }
        } else {
            $sitelink = "about:blank";
            $newsitelink = $memberinfo['avatar'];
        }

        $showtitle = $rank['title'];
        $stars = str_repeat('<img src="'.$imgdir.'/star.gif" alt="*" border="0" />', $rank['stars']);

        if ($memberinfo['customstatus'] != '') {
            $showtitle = $rank['title'];
            $customstatus = '<br />'.censor($memberinfo['customstatus']);
        } else {
            $showtitle = $rank['title'];
            $customstatus = '';
        }

        if (!($memberinfo['lastvisit'] > 0)) {
            $lastmembervisittext = $lang['textpendinglogin'];
        } else {
            $lastvisitdate = gmdate($dateformat, $memberinfo['lastvisit'] + ($timeoffset * 3600) + ($addtime * 3600));
            $lastvisittime = gmdate($timecode, $memberinfo['lastvisit'] + ($timeoffset * 3600) + ($addtime * 3600));
            $lastmembervisittext = $lastvisitdate.' '.$lang['textat'].' '.$lastvisittime;
        }

        $query = $db->query("SELECT COUNT(*) FROM ".X_PREFIX."posts");
        $posts = $db->result($query, 0);
        $db->free_result($query);

        $posttot = $posts;
        if ($posttot == 0) {
            $percent = '0';
        } else {
            $percent = $memberinfo['postnum']*100/$posttot;
            $percent = round($percent, 2);
        }

        $memberinfo['bio'] = nl2br(rawHTMLsubject($memberinfo['bio']));

        $emailblock = '';
        if ($memberinfo['showemail'] == 'yes') {
            eval('$emailblock = "'.template('member_profile_email').'";');
        }

        if (X_SADMIN) {
            $admin_edit = "<br />$lang[adminoption] <a href=\"./editprofile.php?user=$encodeuser\">$lang[admin_edituseraccount]</a>";
        } else {
            $admin_edit = NULL;
        }

        if ($memberinfo['mood'] != '') {
            $memberinfo['mood'] = postify($memberinfo['mood'], 'no', 'no', 'yes', 'no', 'yes', 'no', true, 'yes');
        } else {
            $memberinfo['mood'] = '';
        }

        $memberinfo['location'] = rawHTMLsubject($memberinfo['location']);
        $memberinfo['aim'] = censor($memberinfo['aim']);
        $memberinfo['aimrecode'] = recodeOut($memberinfo['aim']);
        $memberinfo['icq'] = ($memberinfo['icq'] > 0) ? $memberinfo['icq'] : '';
        $memberinfo['yahoo'] = censor($memberinfo['yahoo']);
        $memberinfo['yahoorecode'] = recodeOut($memberinfo['yahoo']);
        $memberinfo['msn'] = censor($memberinfo['msn']);
        $memberinfo['msnrecode'] = recodeOut($memberinfo['msn']);

        if ($memberinfo['bday'] === iso8601_date(0,0,0)) {
            $memberinfo['bday'] = $lang['textnone'];
        } else {
            $memberinfo['bday'] = printGmDate(MakeTime(12,0,0,substr($memberinfo['bday'],5,2),substr($memberinfo['bday'],8,2),substr($memberinfo['bday'],0,4)), $dateformat, -$timeoffset);
        }

        // Forum most active in
        $fids = permittedForums(forumCache(), 'thread', 'csv');
        if (strlen($fids) > 0) {
            $query = $db->query(
                "SELECT fid, COUNT(*) AS posts
                 FROM ".X_PREFIX."posts
                 WHERE author='$member' AND fid IN ($fids)
                 GROUP BY fid
                 ORDER BY posts DESC
                 LIMIT 1"
            );
            $found = ($db->num_rows($query) == 1);
        } else {
            $found = FALSE;
        }

        if ($found) {
            $row = $db->fetch_array($query);
            $posts = $row['posts'];
            $forum = getForum($row['fid']);
            $topforum = "<a href='./forumdisplay.php?fid={$forum['fid']}'>".fnameOut($forum['name'])."</a> ($posts {$lang['memposts']}) [".round(($posts/$memberinfo['postnum'])*100, 1)."% {$lang['textoftotposts']}]";
        } else {
            $topforum = $lang['textnopostsyet'];
        }

        // Last post
        if (strlen($fids) > 0) {
            $pq = $db->query(
                "SELECT p.tid, t.subject, p.dateline, p.pid
                 FROM ".X_PREFIX."posts AS p
                 INNER JOIN ".X_PREFIX."threads AS t USING (tid)
                 WHERE p.author='$member' AND p.fid IN ($fids)
                 ORDER BY p.dateline DESC
                 LIMIT 1"
            );
            $lpfound = ($db->num_rows($pq) == 1);
        } else {
            $lpfound = FALSE;
        }
        if ($lpfound) {
            $post = $db->fetch_array($pq);

            $lastpostdate = gmdate($dateformat, $post['dateline'] + ($timeoffset * 3600) + ($SETTINGS['addtime'] * 3600));
            $lastposttime = gmdate($timecode, $post['dateline'] + ($timeoffset * 3600) + ($SETTINGS['addtime'] * 3600));
            $lastposttext = $lastpostdate.' '.$lang['textat'].' '.$lastposttime;
            $lpsubject = rawHTMLsubject(stripslashes($post['subject']));
            $lastpost = "<a href=\"./viewthread.php?tid={$post['tid']}&amp;goto=search&amp;pid={$post['pid']}\">$lpsubject</a> ($lastposttext)";
        } else {
            $lastpost = $lang['textnopostsyet'];
        }

        if (X_GUEST && $SETTINGS['captcha_status'] == 'on' && $SETTINGS['captcha_search_status'] == 'on') {
            $lang['searchusermsg'] = '';
        } else {
            $lang['searchusermsg'] = str_replace('*USER*', recodeOut($memberinfo['username']), $lang['searchusermsg']);
        }
        eval('$memberpage = "'.template('member_profile').'";');
        break;

    default:
        error($lang['textnoaction']);
        break;
}

end_time();
eval('$footer = "'.template('footer').'";');
echo $header, $memberpage, $footer;
?>

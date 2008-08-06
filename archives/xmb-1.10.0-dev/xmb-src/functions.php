<?php
/* $Id: functions.php,v 1.28 2006/03/09 17:11:26 Tularis Exp $ */
/*
    XMB 1.10
    � 2001 - 2006 Aventure Media & The XMB Development Team
    http://www.aventure-media.co.uk
    http://www.xmbforum.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// constants for return value selection
define('X_RET_BOOL',		1);
define('X_RET_FULL',		2);

// constants for the template cache
define('X_CACHE_GET',		1);
define('X_CACHE_PUT',		2);

// constants for setting cookies
define('X_SET_HEADER',		1);
define('X_SET_JS',			2);

// constants for setting redirects
define('X_REDIRECT_HEADER',	1);
define('X_REDIRECT_JS',		2);


/**
* nav() - Create a navigation link and add it to the $navigation global
*
* Create a navigation link using $navigation global with a possible optional addition
*
* @param    $add        (optional, false) additional navigation element if string or clear navigation if false boolean
* @param    $raquo      (optional, true) prepends &raquo; to the string if true, doesn't if false. Defaults to true.
* @return   no return value
*/
function nav($add=false, $raquo=true) {
    global $navigation;

    if (!$add) {
        $navigation = '';
    } else {
        $navigation .= ($raquo ? ' &raquo; ' : ''). $add;
    }
}

/**
* template() - load a named template and return it
*
* template() checks the cache first, but will always return the template as long as it exists.
*
* @param    $name   string, template name
* @return   the template
*/
function template($name) {
    global $table_templates, $db, $comment_output;

    if (($template = templatecache(X_CACHE_GET, $name)) === false) {
        $query = $db->query("SELECT template FROM $table_templates WHERE name='$name'");
        if ( $db->num_rows($query) == 1) {
            if ( X_SADMIN && DEBUG ) {
                // show an alert that the template $name is not cached!
                trigger_error('Efficiency Notice: The template `'.$name.'` was not preloaded.', E_USER_NOTICE);
            }
            $gettemplate = $db->fetch_array($query);
            templatecache(X_CACHE_PUT, $name, $gettemplate['template']);
            $template = $gettemplate['template'];
        } else {
            if ( X_SADMIN && DEBUG ) {
                // show an alert that the template $name is not cached!
                trigger_error('Efficiency Warning: The template `'.$name.'` could not be found.', E_USER_WARNING);
            }
        }
        $db->free_result($query);
    }

    $template = str_replace("\\'","'", $template);

    if ( $name != 'phpinclude' && $comment_output === true) {
        return "<!--Begin Template: $name -->\n$template\n<!-- End Template: $name -->";
    } else {
        return $template;
    }
}


/**
 * templatecache()
 *
 * @param defined $type get or put
 * @param string $name the template to retrieve
 * @param string $data if put, the data to insert
 * @return mixed true if PUT. If GET, false if not in cache, otherwise a string containing the cache entry
 **/
function templatecache($type=X_CACHE_GET, $name, $data='') {
    static $cache;

    switch($type) {
        case X_CACHE_GET:
            if (!isset($cache[$name])) {
                return false;
            } else {
                return $cache[$name];
            }
            break;

        case X_CACHE_PUT:
            $cache[$name] = $data;
            return true;
            break;
    }
}

/**
* loadtemplates()
*
* Preloads templates, storing them in a template cache
*
* @param    $any  takes a variable amount of strings, and tries to preload each template specified by one of these
* @return   void, returns nothing
*/
function loadtemplates() {
    global $db, $table_templates;

    $num = func_num_args();
    if ( $num < 1) {
        echo 'Not enough arguments given to loadtemplates() on line: '.__LINE__;
        return false;
    }else{
        $namesarray = array_unique(array_merge(func_get_args(), array('header', 'css', 'error', 'footer', 'footer_querynum', 'footer_phpsql', 'footer_totaltime', 'footer_load')));
        $sql = "'".implode("', '", $namesarray)."'";

        $query = $db->query("SELECT name, template FROM $table_templates WHERE name IN ($sql)");
        while($template = $db->fetch_array($query)) {
            templatecache(X_CACHE_PUT, $template['name'], $template['template']);
        }
        $db->free_result($query);
    }
}

/**
* censor() - censors text
*
* Takes text and uses predefined censors on them. Includes option to ignore whitespaces
*
* @param    $txt    string, the text to apply the censors to
* @param    $ignorespaces    bool, tells the function to replace any occurence of the sequence of characters to censor (on true), or to make sure it is used as a word before censoring it. This parameter defaults to false
* @return   string, the censored version of the input string
*/
function censor($txt, $ignorespaces=false) {
    global $censorcache;

    if (is_array($censorcache)) {
        if (count($censorcache) > 0) {
            foreach($censorcache as $find=>$replace) {
                if ($ignorespaces === true) {
                    $txt = str_replace($find, $replace, $txt);
                } else {
                    $txt = preg_replace("#(^|[^a-z])(".preg_quote($find).")($|[^a-z])#si", '\1'.$replace.'\3', $txt);
                }
            }
        }
    }
    return $txt;
}

/**
* smile() - replaces text-based smilies with their (HTML) image counterparts
*
* @param    $txt    string, text to convert smilies in
* @return   string, the input containing the converted smilies
*/
function smile($txt) {
    global $smiliesnum, $smiliecache, $smdir;

    if ( $smiliesnum > 0) {
        reset($smiliecache);
        foreach ($smiliecache as $code=>$url) {
            $txt = str_replace($code, '<img src="./'.$smdir.'/'.$url.'" style="border:none" alt="'.$code.'" />', $txt);
        }
    }
    return $txt;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function createAbsFSizeFromRel($rel) {
    global $fontsize;
    static $cachedFs;

    if (!is_array($cachedFs) || count($cachedFs) != 2) {
        preg_match('#([0-9]+)([a-z]+)?#i', $fontsize, $res);
        $cachedFs[0] = $res[1];
        $cachedFs[1] = $res[2];

        if (empty($cachedFs[1])) {
            $cachedFs[1] = 'px';
        }
    }

    $o = ($rel+$cachedFs[0]).$cachedFs[1];
    //echo $o;
    return $o;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function postify($message, $smileyoff='no', $bbcodeoff='no', $allowsmilies='yes', $allowhtml='yes', $allowbbcode='yes', $allowimgcode='yes', $ignorespaces=false, $ismood="no", $wrap="yes") {
    global $imgdir, $bordercolor, $table_words, $table_forums, $table_smilies, $db, $smdir, $smiliecache, $censorcache, $smiliesnum, $wordsnum, $versionbuild, $lang, $fontsize;

    $message = checkOutput($message, $allowhtml, '', true);

    $message = censor($message, $ignorespaces);

    $bballow = ($allowbbcode == 'yes' || $allowbbcode == 'on') ? (($bbcodeoff != 'off' && $bbcodeoff != 'yes') ? true : false) : false;
    $smiliesallow = ($allowsmilies == 'yes' || $allowsmilies == 'on') ? (($smileyoff != 'off' && $smileyoff != 'yes') ? true : false) : false;

    if ( $bballow) {
        $message = stripslashes($message);

        if ( $ismood == "yes") {
            $message = str_replace(array('[poem]', '[/poem]', '[quote]', '[/quote]', '[code]', '[/code]', '[list]', '[/list]', '[list=1]', '[list=a]', '[list=A]', '[/list=1]', '[/list=a]', '[/list=A]'), '', $message);
        }

        // cut out [code]*[/code] parts here and replace with a unique identifier
        // so we can reinsert it later again
        $codes = array();
        $numCs = substr_count($message, '[code]');
        if($numCs > 0) {
        	while(preg_match('#\[code\](.*)\[/code\]#U', $message, $ret, PREG_OFFSET_CAPTURE)) {
        		$rand = mt_rand();
        		if(false !== strpos($message, 'CODE-IDENTIFIER-'.$rand)) {
        			continue;
        		} else {
        			$codes[$rand] = $ret[1][0];
        			$message = substr($message, 0, $ret[0][1]).'CODE-IDENTIFIER-'.$rand.substr($message, $ret[0][1]+strlen($ret[0][0]));
        		}
        	}
        }

        $begin = array(
                0    => '[b]',
                1    => '[i]',
                2    => '[u]',
                3    => '[poem]',
                4    => '[marquee]',
                5    => '[blink]',
                6    => '[strike]',
                7    => '[quote]',
                9    => '[list]',
                10    => '[list=1]',
                11    => '[list=a]',
                12    => '[list=A]',
                );

        $end = array(
                0    => '[/b]',
                1    => '[/i]',
                2    => '[/u]',
                3    => '[/poem]',
                4    => '[/marquee]',
                5    => '[/blink]',
                6    => '[/strike]',
                7    => '[/quote]',
                9    => '[/list]',
                10    => '[/list=1]',
                11    => '[/list=a]',
                12    => '[/list=A]',
                );

        foreach ($begin as $key=>$value) {
            $check = substr_count($message, $value) - substr_count($message, $end[$key]);
            if ( $check > 0) {
                $message = $message.str_repeat($end[$key], $check);
            }elseif ( $check < 0) {
                $message = str_repeat($value, abs($check)).$message;
            }
        }

        $find = array(
                0   => '[b]',
                1   => '[/b]',
                2   => '[i]',
                3   => '[/i]',
                6   => '[u]',
                7   => '[/u]',
                8   => '[marquee]',
                9   => '[/marquee]',
                10  => '[blink]',
                11  => '[/blink]',
                12  => '[strike]',
                13  => '[/strike]',
                14  => '[quote]',
                15  => '[/quote]',
                18  => '[list]',
                19  => '[/list]',
                20  => '[list=1]',
                21  => '[list=a]',
                22  => '[list=A]',
                23  => '[/list=1]',
                24  => '[/list=a]',
                25  => '[/list=A]',
                26  => '[*]',
                27  => '<br />'
                );

        $replace = array(
                0   => '<strong>',
                1   => '</strong>',
                2   => '<em>',
                3   => '</em>',
                6   => '<u>',
                7   => '</u>',
                8   => '<marquee>',
                9   => '</marquee>',
                10  => '<blink>',
                11  => '</blink>',
                12  => '<strike>',
                13  => '</strike>',
                14  => '</font><table align="center" class="quote" cellspacing="0" cellpadding="0"><tr><td class="quote">'.$lang['textquote'].'</td></tr><tr><td class="quotemessage">',
                15  => '</td></tr></table><font class="mediumtxt">',
                18  => '<ul type="square">',
                19  => '</ul>',
                20  => '<ol type="1">',
                21  => '<ol type="A">',
                22  => '<ol type="A">',
                23  => '</ol>',
                24  => '</ol>',
                25  => '</ol>',
                26  => '<li />',
                27  => ' <br />'
                );

        $message = str_replace($find, $replace, $message);

        if ( $smiliesallow) {
            $message = smile($message);
        }

        $patterns = array();
        $replacements = array();

        $patterns[] = "#\[color=([^\"'<>]*?)\](.*?)\[/color\]#Ssi";
        $replacements[] = '<font color="\1">\2</font>';

        $patterns[] = "#\[size=([+-]?[0-9]{1,2})\](.*?)\[/size\]#Ssie";
        $replacements[] = '"<font style=\"font-size: ".createAbsFSizeFromRel(\'$1\').";\">".stripslashes(\'$2\')."</font>"';

        $patterns[] = "#\[font=([a-z\r\n\t 0-9]+)\](.*?)\[/font\]#Ssi";
        $replacements[] = '<font face="\1">\2</font>';

        $patterns[] = "#\[align=([a-z]+)\](.*?)\[/align\]#Ssi";
        $replacements[] = '<p align="\1">\2</p>';

        if ($allowimgcode != 'no' && $allowimgcode != 'off') {

        if (!stristr($message, 'javascript:') && (stristr($message, 'jpg[/img]') || stristr($message, 'jpeg[/img]') || stristr($message, 'gif[/img]') || stristr($message, 'png[/img]') || stristr($message, 'bmp[/img]') || stristr($message, 'php[/img]'))) {
            $patterns[] = '#\[img\](http[s]?|ftp[s]?){1}://([:a-z\\./_\-0-9%~]+){1}(\?[a-z=_\-0-9&;~]*)?\[/img\]#Smi';
            $replacements[] = '<img src="\1://\2\3" border="0" alt="\1://\2\3"/>';

            $patterns[] = "#\[img=([0-9]*?){1}x([0-9]*?)\](http[s]?|ftp[s]?){1}://([:~a-z\\./0-9_\-%]+){1}(\?[a-z=0-9&_\-;~]*)?\[/img\]#Smi";
            $replacements[] = '<img width="\1" height="\2" src="\3://\4\5" alt="\3://\4\5" border="0" />';        }

            $patterns[] = "#\[flash=([0-9]*?){1}x([0-9]*?)\]([^\"'<>]*?)\[/flash\]#Ssi";
            $replacements[] = '<object type="application/x-shockwave-flash" data="$3" width="$1" height="$2"><param name="movie" value="$3" /></object>';
        }

		$patterns[] = '#([^\'"=\]]|^)(http[s]?|ftp[s]?|gopher|irc){1}://([a-z0-9.\-]+[a-z0-9])(:[0-9]+)?(/[a-z0-9./~\-_\\%]*)?(\?[a-z=0-9.\-_&;]*)?(\#[a-z0-9._]+)?#Smi';
		$replacements[] = '\1<a href="\2://\3\4\5\6\7" target="_blank">\2://\3\4\5\6\7</a>';

        $patterns[] = "#\[url\]([a-z]+?://){1}([^\"'<>]*?)\[/url\]#Smi";
        $replacements[] = '<a href="\1\2" target="_blank">\1\2</a>';

        $patterns[] = "#\[url\]([^\"'<>]*?)\[/url\]#Smi";
        $replacements[] = '<a href="http://\1" target="_blank">\1</a>';

        // Do we need this? yes!
        $patterns[] = "#\[url=([a-z]+?://){1}([^\"'<>]*?)\](.*?)\[/url\]#Smi";
        $replacements[] = '<a href="\1\2" target="_blank">\3</a>';

        $patterns[] = "#\[url=([^\"'<>]*?)\](.*?)\[/url\]#Smi";
        $replacements[] = '<a href="http://\1" target="_blank">\2</a>';

        $patterns[] = "#\[email\]([^\"'<>]*?)\[/email\]#Smi";
        $replacements[] = '<a href="mailto:\1">\1</a>';

        $patterns[] = "#\[email=([^\"'<>]*?){1}([^\"]*?)\](.*?)\[/email\]#Smi";
        $replacements[] = '<a href="mailto:\1\2">\3</a>';

        $message = preg_replace($patterns, $replacements, $message);

        // add [code] and such back in, parsed obviously
        if(count($codes) > 0) {
        	foreach($codes as $code=>$replace) {
        		$message = str_replace('CODE-IDENTIFIER-'.$code, '</font><table align="center" class="code" cellspacing="0" cellpadding="0"><tr><td class="code">'.$lang['textcode'].'</td></tr><tr><td class="codemessage">'.$replace.'</td></tr></table><font class="mediumtxt">', $message);
        	}
        }

        $message = safeAddslashes($message);
    }else{
        if ( $smiliesallow) {
            $message = smile($message);
        }
    }

    $message = nl2br($message);
    if ( $wrap == "yes") {
        $message = wordwrap($message, 150, "\n", 1);
        $message = preg_replace('#(\[/?.*)\n(.*\])#mi', '\\1\\2', $message);
    }

    return $message;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function modcheck($status, $username, $mods) {

    if ( X_ADMIN || in_array($status, array('Super Moderator'))) {
        return 'Moderator';
    }

    $retval = '';

    if ($status == 'Moderator') {
        $username = strtoupper($username);
        $mods = explode(',', $mods);
        foreach ($mods as $key=>$moderator) {
            if (strtoupper(trim($moderator)) == $username) {
                $retval = 'Moderator';
                break;
            }
        }
    }
    return $retval;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function privfcheck($private, $userlist) {
    global $self, $xmbuser;

    // Super admins can see all forums
    if ( X_SADMIN ) {
        return true;
    }

    switch($private) {
        case 4: // 4 - No Viewing
            return false;
            break;

        case 3: // 3 - Mods/Admins
            return X_STAFF;
            break;

        case 2: // 2 - Admins
            return X_ADMIN;
            break;

        case 1: // 1 - All users... minus guests.
            if (trim($userlist) == '') {
                return true;
            }

            $user = explode(',', $userlist);
            $xuser = strtolower($xmbuser);

            foreach ($user as $usr) {
                $usr = strtolower(trim($usr));
                if ( $usr != '' && $xuser == $usr ) {
                   return true;
                }
            }
            return false;
            break;

        default:
            return false;
            break;
    }
    return false;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function forum($forum, $template) {
    global $timecode, $dateformat, $lang, $xmbuser, $self, $lastvisit2, $timeoffset, $hideprivate, $addtime, $oldtopics, $lastvisit, $THEME, $SETTINGS;

    $altbg1 = $GLOBALS['altbg1'];
    $altbg2 = $GLOBALS['altbg2'];
    $imgdir = $GLOBALS['imgdir'];

    if ( $forum['lastpost'] != '') {
        $lastpost = explode('|', $forum['lastpost']);
        $dalast = $lastpost[0];
        if ( $lastpost[1] != 'Anonymous' && $lastpost[1] != '') {
            $lastpost[1] = '<a href="member.php?action=viewpro&amp;member='.rawurlencode($lastpost[1]).'">'.$lastpost[1].'</a>';
        } else {
            $lastpost[1] = $lang['textanonymous'];
        }

        $lastPid = isset($lastpost[2]) ? $lastpost[2] : 0;
        $lastpostdate = printGmDate($lastpost[0]);
        $lastposttime = printGmTime($lastpost[0]);
        $lastpost = $lastpostdate.' '.$lang['textat'].' '.$lastposttime.'<br />'.$lang['textby'].' '.$lastpost[1];
        eval("\$lastpostrow = \"".template($template."_lastpost")."\";");
    } else {
        $dalast     = 0;
        $lastpost   = $lang['textnever'];
        eval('$lastpostrow = "'.template($template.'_nolastpost').'";');
    }

    if ($lastvisit < $dalast && (strpos($oldtopics, '|'.$lastPid.'|') === false)) {
        $folder = '<img src="'.$THEME['imgdir'].'/red_folder.gif" alt="'.$lang['altredfolder'].'" />';
    } else {
        $folder = '<img src="'.$THEME['imgdir'].'/folder.gif" alt="'.$lang['altfolder'].'" />';
    }

    if ( $dalast == "") {
        $folder = '<img src="'.$THEME['imgdir'].'/folder.gif" alt="'.$lang['altfolder'].'" />';
    }

    $foruminfo = '';

    if (X_SADMIN || $hideprivate == 'off' || privfcheck($forum['private'], $forum['userlist'])) {
        if ( $forum['moderator'] != '') {
            $moderators = explode(', ', $forum['moderator']);
            $forum['moderator'] = array();
            for($num = 0; $num < count($moderators); $num++) {
                $forum['moderator'][] = '<a href="member.php?action=viewpro&amp;member='.rawurlencode($moderators[$num]).'">'.$moderators[$num].'</a>';
            }
            $forum['moderator'] = implode(', ', $forum['moderator']);

            $forum['moderator'] = '('.$lang['textmodby'].' '.$forum['moderator'].')';
        }
        eval('$foruminfo = stripslashes("'.template($template).'");');
    }

    $dalast = '';
    $fmods = '';

    return $foruminfo;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function multi($num, $perpage, $page, $mpurl, $strict=false) {

    $multipage = $GLOBALS['lang']['textpages'];

    $pages = quickpage($num, $perpage);

    if ( $pages > 1 ) {
        if ( $page == 0) {
            if ( $pages < 4) {
                $to = $pages;
            } else {
                $to = 3;
            }
        } elseif ($page == $pages) {
            $to = $pages;
        } elseif ( $page == $pages-1) {
            $to = $page+1;
        } elseif ( $page == $pages-2) {
            $to = $page+2;
        } else {
            $to = $page+3;
        }

        if ($page >= 0 && $page <= 3) {
            $from = 1;
        } else {
            $from = $page - 3;
        }

        $to--;
        $from++;

        $string = (strpos($mpurl, '?') !== false) ? ($strict === false ? '&amp;' : '&') : '?';
        if (1 != $page) {
            $multipage .= '&nbsp;&nbsp;<a href="'.$mpurl.$string.'page=1">1</a>';
            if (2 < $from) {
                $multipage .= '&nbsp;&nbsp;..';
            }
        } else {
            $multipage .= '&nbsp;&nbsp;<u><strong>1</strong></u>';
        }

        for($i = $from; $i <= $to; $i++) {
            if ($i != $page) {
                $multipage .= '&nbsp;&nbsp;<a href="'.$mpurl.$string.'page='.$i.'">'.$i.'</a>';
            } else {
                $multipage .= '&nbsp;&nbsp;<u><strong>'.$i.'</strong></u>';
            }
        }

        if ($pages != $page) {
            if (($pages - 1) > $to) {
                $multipage .= '&nbsp;&nbsp;..';
            }
            $multipage .= '&nbsp;&nbsp;<a href="'.$mpurl.$string.'page='.$pages.'">'.$pages.'</a>';
        } else {
            $multipage .= '&nbsp;&nbsp;<u><strong>'.$pages.'</strong></u>';
        }
    }
    elseif ( $strict !== true) {
        //$multipage .= '&nbsp;&nbsp;<u><strong>1</strong></u>';
        return false;
    }
    return $multipage;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function quickpage($things, $thingsperpage) {
    return ((($things > 0) && ($thingsperpage > 0) && ($things > $thingsperpage)) ? ceil($things / $thingsperpage) : 1);
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function smilieinsert() {
	global $imgdir, $smdir, $table_smilies, $db, $smileyinsert, $smcols, $smtotal;

	$smilienum		= 0;
	$smilies		= '';
	$smilieinsert	= '';
	if($smileyinsert == 'on' && $smcols != '') {
		if($smtotal == 0) {
			$querysmilie = $db->query("SELECT * FROM $table_smilies WHERE type='smiley' ORDER BY code DESC");
		} else {
			$querysmilie = $db->query("SELECT * FROM $table_smilies WHERE type='smiley' ORDER BY code DESC LIMIT 0, $smtotal");
		}

		if(($smilienum = $db->num_rows($querysmilie)) > 0){
			while($smilie = $db->fetch_array($querysmilie)) {
				eval('$sms[] = "'.template('functions_smilieinsert_smilie').'";');
			}

			$smilies = '<tr>';
			for($i=0;$i<count($sms);$i++) {
				$smilies .= $sms[$i];
				if(($i+1)%$smcols == 0) {
					// we have reached the Xth smiley, end row
					$smilies .= '</tr>';
					if(($i+1) < $smtotal) {
						// check if we have reached the end already
						$smilies .= '<tr>';
					}
				}
			}
			if($smilienum%$smcols > 0) {
				// not all cols will be filled entirely.
				$left = $smcols-($smilienum%$smcols);
				for($i=0;$i<$left;$i++) {
					$smilies .= '<td>&nbsp;</td>';
				}
				$smilies .= '</tr>';
			}
			eval("\$smilieinsert = \"".template("functions_smilieinsert")."\";");
		} else {
			$smilieinsert = '';
		}
	}
	return $smilieinsert;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function updateforumcount($fid) {
    global $db, $table_posts, $table_forums, $table_threads;

    $postcount = 0;
    $threadcount = 0;

    $query = $db->query("SELECT count(pid) FROM $table_posts WHERE fid='$fid'");
    $postcount = $db->result($query, 0);
    $db->free_result($query);

    $query = $db->query("SELECT count(tid) FROM $table_threads WHERE (fid='$fid' AND closed != 'moved')");
    $threadcount = $db->result($query, 0);
    $db->free_result($query);

    // Count posts in subforums.
    $query = $db->query("SELECT fid FROM $table_forums WHERE fup='$fid'");
    while($children = $db->fetch_array($query)) {
        $chquery1 = $db->query("SELECT count(pid) FROM $table_posts WHERE fid='$children[fid]'");
        $postcount += $db->result($chquery1, 0);
        $db->free_result($chquery1);

        $chquery2 = $db->query("SELECT count(tid) FROM $table_threads WHERE fid='$children[fid]' AND closed != 'moved'");
        $threadcount += $db->result($chquery2, 0);
        $db->free_result($chquery2);
    }
    $db->free_result($query);

    $query = $db->query("SELECT t.lastpost FROM $table_threads t, $table_forums f WHERE (t.fid=f.fid AND f.fid='$fid') OR (t.fid=f.fid AND f.fup='$fid') ORDER BY t.lastpost DESC LIMIT 0,1");
    $lp = $db->fetch_array($query);
    $db->query("UPDATE $table_forums SET posts='$postcount', threads='$threadcount', lastpost='$lp[lastpost]' WHERE fid='$fid'");
    $db->free_result($query);
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function updatethreadcount($tid) {
    global $db, $table_threads, $table_posts;

    $query = $db->query("SELECT tid FROM $table_posts WHERE tid='$tid'");
    $replycount = $db->num_rows($query);
    $db->free_result($query);
    $replycount--;
    $query = $db->query("SELECT dateline, author, pid FROM $table_posts WHERE tid='$tid' ORDER BY dateline DESC LIMIT 1");
    $lp = $db->fetch_array($query);
    $db->free_result($query);
    $lastpost = $lp['dateline'].'|'.$lp['author'].'|'.$lp['pid'];
    $db->query("UPDATE $table_threads SET replies='$replycount', lastpost='$lastpost' WHERE tid='$tid'");
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function smcwcache() {
    global $db, $table_smilies, $table_words, $smiliecache, $censorcache, $smiliesnum, $wordsnum;
    static $cached;

    if (!$cached) {
        $smiliecache = array();
        $censorcache = array();

        $query = $db->query("SELECT code, url FROM $table_smilies WHERE type='smiley'");
        $smiliesnum = $db->num_rows($query);

        if ( $smiliesnum > 0) {
            while($smilie = $db->fetch_array($query)) {
                $code = $smilie['code'];
                $smiliecache[$code] = $smilie['url'];
            }
        }
        $db->free_result($query);

        $query = $db->query("SELECT find, replace1 FROM $table_words");
        $wordsnum = $db->num_rows($query);
        if ( $wordsnum > 0) {
            while($word = $db->fetch_array($query)) {
                $find = $word['find'];
                $censorcache[$find] = $word['replace1'];
            }
        }
        $db->free_result($query);

        $cached = true;
        return true;
    }
    return false;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function checkInput($input, $striptags='no', $allowhtml='no', $word='', $no_quotes=true) {
    // Function generously donated by FiXato

    $input = trim($input);
    if ( $striptags == 'yes' ) {
        $input = strip_tags($input);
    }

    if ( $allowhtml != 'yes' && $allowhtml != 'on' ) {
        if ( $no_quotes ) {
            $input = htmlspecialchars($input, ENT_NOQUOTES);
        } else {
            $input = htmlspecialchars($input, ENT_QUOTES);
        }
    }
    if ( $word != '' ) {
        $input = str_replace($word, "_".$word, $input);
    }

    return $input;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function checkOutput($output, $allowhtml='no', $word='', $allowEntities=false) {
    $output = trim($output);
    if ( $allowhtml == 'yes' || $allowhtml == 'on') {
        $output = htmlspecialchars_decode($output);
    }
    if ( $word != '') {
        $output = str_replace($word, "_".$word, $output);
    }
    if($allowEntities) {
    	$output = preg_replace('/(&amp;(#[0-9]+);)/Ui', '&$2;', $output);
    }

    return $output;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
if(!function_exists('htmlspecialchars_decode')) {
	function htmlspecialchars_decode($string, $type=ENT_QUOTES) {

		$array = array_flip(get_html_translation_table(HTML_SPECIALCHARS, $type));
		return strtr($string, $array);
	}
}
/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
if(!function_exists('htmlentities_decode')) {
	function htmlentities_decode($string, $type=ENT_QUOTES) {
	    $array = array_flip(get_html_translation_table(HTML_ENTITIES, $type));
	    return strtr($string, $array);
	}
}
/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function end_time() {
    global $footerstuff, $footerads;

    if(isset($GLOBALS)) {
    	extract($GLOBALS);
    }

	if(!isset($footerads) || empty($footerads)) {
		$footerads = '';
	}

	if(!isset($footerads) || empty($footerads)) {
		$footerads = '';
	}

    $mtime2 = explode(" ", microtime());
    $endtime = $mtime2[1] + $mtime2[0];

    $totaltime = ($endtime - $starttime);

    $footer_options = explode('-', $SETTINGS['footer_options']);

    if ( X_ADMIN && in_array('serverload', $footer_options) ) {
        $load = ServerLoad();
        if ( ! empty($load) ) {
            eval("\$footerstuff['load'] = \"".template('footer_load')."\";");
        } else {
            $footerstuff['load'] = '';
        }
    }else{
        $footerstuff['load'] = '';
    }
    if (in_array('queries', $footer_options)) {
        $querynum = $db->querynum;
        eval("\$footerstuff['querynum'] = \"".template('footer_querynum')."\";");
    }else{
        $footerstuff['querynum'] = '';
    }
    if (in_array('phpsql', $footer_options)) {
        $db_duration = number_format(($db->duration/$totaltime)*100, 1);
        $php_duration = number_format((1-($db->duration/$totaltime))*100, 1);
        eval("\$footerstuff['phpsql'] = \"".template('footer_phpsql')."\";");
    }else{
        $footerstuff['phpsql'] = '';
    }

    if (in_array('loadtimes', $footer_options) && X_ADMIN) {
        $totaltime = number_format($totaltime, 7);
        eval("\$footerstuff['totaltime'] = \"".template('footer_totaltime')."\";");
    }else{
        $footerstuff['totaltime'] = '';
    }

    if ( X_SADMIN && DEBUG ) {
        $stuff = array();

        $stuff[] = '<table cols="2" style="width: 97%;"><tr><td style="width: 2em;">#</td><td style="width: 8em;">Duration:</td><td>Query:</td></tr>';
        foreach ($db->querylist as $key=>$val) {
            $val = mysql_syn_highlight($val);
            $stuff[] = '<tr><td><strong>'.++$key.'.</strong></td><td>'.number_format($db->querytimes[$key-1], 8).'</td><td>'.$val.'</td></tr>';
        }
        $stuff[] = '</table>';
        $footerstuff['querydump'] = implode("\n", $stuff);
    }else{
        $footerstuff['querydump'] = '';
    }

    return $footerstuff;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function pwverify($pass='', $url, $fid, $showHeader=false) {
    global $self, $cookiepath, $cookiedomain;

    if ( X_SADMIN ) {
        return true;
    }

    if (trim($pass) != '' && $_COOKIE['fidpw'.$fid] != $pass ) {
        if ( $_POST['pw'] != $pass) {
            extract($GLOBALS);
            eval('$pwform = "'.template('forumdisplay_password').'";');
            error(((isset($_POST['pw'])) ? $lang['invalidforumpw'] : $lang['forumpwinfo']), $showHeader, '', $pwform, false, true, false, true);
        } else {
            put_cookie("fidpw$fid", $pass, (time() + (86400*30)), $cookiepath, $cookiedomain);
            redirect($url, 0);
        }
        exit();
    }

    return true;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function redirect($path, $timeout=2, $type=X_REDIRECT_HEADER) {

    // split session attack code fix from phpBB. Thanks guys :)
    if (strpos(urldecode($path), "\n") !== false || strpos(urldecode($path), "\r") !== false)
    {
        error('Tried to redirect to potentially insecure url.');
    }

    // force session to be written before redirecting
    session_write_close();

    $type = (headers_sent() || $type == X_REDIRECT_JS ) ? X_REDIRECT_JS : X_REDIRECT_HEADER;
    if ($type == X_REDIRECT_JS) {
        ?>
        <script language="javascript" type="text/javascript">
        function redirect() {
            window.location.replace("<?php echo $path?>");
        }

        setTimeout("redirect();", <?php echo ($timeout*1000)?>);
        </script>

        <?
    } else {
        if ( $timeout == 0) {
            header("Location: $path");
        } else {
            header("Refresh: $timeout; URL=./$path");
        }
    }
    return true;
}

/**
* postperm() - Authorization check for forums
*
* Authorization check for forums.
* DO NOT DELETE THE &
*  - it's a pass by reference and saves doing a memcpy() on each perm check
*
* @param    array &$forums    forum result array, passed by reference.
* @return   boolean           true = can read forum, false = can't read forum
*/
function postperm(& $forums, $type) {
    global $lang, $self, $whopost1, $whopost2;
    static $cache;

    $pperm = explode('|', $forums['postperm']);

    if (!isset($cache[$forums['fid']])) {
        switch($pperm[0]) {
            case 1:
                $whopost1 = $lang['whocanpost11'];
                break;

            case 2:
                $whopost1 = $lang['whocanpost12'];
                break;

            case 3:
                $whopost1 = $lang['whocanpost13'];
                break;

            case 4:
                $whopost1 = $lang['whocanpost14'];
                break;
        }

        switch($pperm[1]) {
            case 1:
                $whopost2 = $lang['whocanpost21'];
                break;

            case 2:
                $whopost2 = $lang['whocanpost22'];
                break;

            case 3:
                $whopost2 = $lang['whocanpost23'];
                break;

            case 4:
                $whopost2 = $lang['whocanpost24'];
                break;
        }

        $cache[$forums['fid']] = true;
    }

    if (X_SADMIN) {
        return true;
    }

    // $private = VIEW permission
    // $pperm[0] = THREAD permission
    // $pperm[1] = REPLY permission

    $perm = ($type == 'thread') ? $pperm[0] : $pperm[1];

    switch ($forums['private']) {
        case 1: // all can view
            // first, we always check for the password and userlist here.
            $fplen = isset($forums['password']) ? strlen($forums['password']) : 0;
            $fulen = isset($forums['userlist']) ? strlen($forums['userlist']) : 0;

            if ( ($fplen > 1 || $fulen > 1 ) && privfcheck($forums['private'], $forums['userlist']) ) {
                return true;
            }

            if (!X_STAFF) {
                // only permissions for 1 allowed
                if ( $perm == 1) {
                    return true;
                }
                break;
            }

        case 3:
            if (!X_ADMIN) {
                // mods/supermods
                if ($perm == 1 || $perm == 3) {
                    return true;
                }
                break;
            }

        case 2:
            // admin only
            if ( $perm < 4) {
                return true;
            }
            break;

        case 4:
            // none can see
            return false;
            break;

        default:
            // none can see
            return false;
            break;
    }
    return false;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function get_extension($filename) {
    $a = explode('.', $filename);
    $count = count($a);
    if ( $count == 1) {
        return '';
    }else{
        return $a[$count-1];
    }
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function get_attached_file($file, $attachstatus, $max_size=1000000) {
    global $lang, $filename, $filetype, $filesize;

    $filename = '';
    $filetype = '';
    $filesize = 0;

    if ( $file['name'] != 'none' && !empty($file['name']) && $attachstatus != 'off' && is_uploaded_file($file['tmp_name'])) {
        $attachment = safeAddslashes(fread( fopen($file['tmp_name'], 'rb'), filesize($file['tmp_name'])));
        $file['size'] = filesize($file['tmp_name']);    // fix bad filesizes

        if ( $file['size'] > $max_size) {
            error($lang['attachtoobig'], false, '', '', false, false, false, false);
            return false;
        }else{
            $filename = $file['name'];
            $filetype = $file['type'];
            $filesize = $file['size'];

            if ( $filesize == 0) {
                return false;
            } else {
                return $attachment;
            }
        }
    }else{
        return false;
    }
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function ServerLoad() {
    if ( $stats = @exec('uptime')) {
        $parts = explode(',', $stats);
        $count = count($parts);

        $first = explode(' ', $parts[$count-3]);
        $c = count($first);
        $first = $first[$c-1];

        return array($first, $parts[$count-2], $parts[$count-1]);
    }else{
        return array();
    }
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function error($msg, $showheader=true, $prepend='', $append='', $redirect=false, $die=true, $return_as_string=false, $showfooter=true) {
    global $footerstuff;
    if(isset($GLOBALS)) {
    	extract($GLOBALS);
    }
    $args = func_get_args();

    $message    = (isset($args[0]) ? $args[0] : '');
    $showheader = (isset($args[1]) ? $args[1] : true);
    $prepend    = (isset($args[2]) ? $args[2] : '');
    $append     = (isset($args[3]) ? $args[3] : '');
    $redirect   = (isset($args[4]) ? $args[4] : false);
    $die        = (isset($args[5]) ? $args[5] : true);
    $return_str = (isset($args[6]) ? $args[6] : false);
    $showfooter = (isset($args[7]) ? $args[7] : true);

    // Initialize navigation
    $navigation = '';
    nav();
    nav($lang['error']);

    end_time();

    if ( $redirect !== false) {
        redirect($redirect, 1);
    }

    if ( $showheader === false) {
        $header = '';
    }else{
    	if(!isset($css) || strlen($css) ==0) {
    		eval('$css = "'.template('css').'";');
    	}
        eval('$header = "'.template('header').'";');
    }

    eval("\$error = \"".template('error')."\";");
    if ( $showfooter === true) {
        eval('$footer = "'.template('footer').'";');
    }else{
        $footer = '';
    }

    if ( $return_str !== false) {
        $return = $prepend . $error . $footer . $append;
    }else{
        echo $prepend . $error . $append . $footer;
        $return = '';
    }

    if ( $die) {
        exit();
    }
    return $return;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function array_keys2keys($array, $translator) {  // changes array(0=>'two', 1=>'six') & array(0=>2,1=>6) to array(2=>'two', 6=>'six');
    $new_array = array();

    foreach ($array as $key=>$val) {
        if (isset($translator[$key])) {
            $new_key = $translator[$key];
        }else{
            $new_key = $key;
        }
        $new_array[$new_key] = $val;
    }

    return $new_array;
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function mysql_syn_highlight($query) {
    global $tables, $tablepre;

    $find       = array();
    $replace    = array();

    foreach ($tables as $name) {
        $find[] = $tablepre.$name;
    }

    $find[] = 'SELECT';
    $find[] = 'UPDATE';
    $find[] = 'DELETE';
    $find[] = 'INSERT INTO ';

    $find[] = ' WHERE ';
    $find[] = ' ON ';
    $find[] = ' FROM ';

    $find[] = ' GROUP BY ';
    $find[] = 'ORDER BY ';
    $find[] = ' LEFT JOIN ';

    $find[] = ' IN ';
    $find[] = ' SET ';
    $find[] = ' AS ';

    $find[] = '(';
    $find[] = ')';

    $find[] = ' ASC';
    $find[] = ' DESC';

    $find[] = ' AND ';
    $find[] = ' OR ';
    $find[] = ' NOT';

    // temporary

    foreach ($find as $key=>$val) {
        $replace[$key] = '</em><strong>'.$val.'</strong><em>';
    }

    //$query = preg_replace('#([A-Za-Z0-9]?)[\d]?=\'(.*?)\'#i', '<strong>\1</strong> = </em>\2<em>', $query);
    return '<em>'.str_replace($find, $replace, $query).'</em>';
    //return str_replace('</em><strong></em><strong>IN</strong><em>SERT </em><strong>IN</strong><em>TO</strong><em>', '</em><strong>INSERT INTO</strong><em>', $return);
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function dump_query($resource, $header=true) {
    global $altbg2, $altbg1, $db, $cattext;
    if (!$db->error()) {
        $count = $db->num_fields($resource);
        if ( $header) {
            ?><tr class="category" bgcolor="<?php echo $altbg2?>" align="center"><?php
            for($i=0;$i<$count;$i++) {
                echo '<td align="left">';
                echo '<strong><font color='.$cattext.'>'.$db->field_name($resource, $i).'</font></strong>';
                echo '</td>';
            }
            echo '</tr>';
        }

        while($a = $db->fetch_array($resource, SQL_NUM)) {
            ?><tr bgcolor="<?php echo $altbg1?>" class="ctrtablerow"><?php
            for($i=0;$i<$count;$i++) {
                echo '<td align="left">';

                if (trim($a[$i]) == '') {
                    echo '&nbsp;';
                }else{
                    echo nl2br($a[$i]);
                }
                echo '</td>';
            }
            echo '</tr>';
        }
    }else{
        error($db->error());
    }
}

/**
* function() - short description of function
*
* Long description of function
*
* @param    $varname    type, what it does
* @return   type, what the return does
*/
function put_cookie($name, $value=null, $expire=null, $path=null, $domain=null, $secure=null, $setVia=X_SET_HEADER) {

    if (!headers_sent() && $setVia != X_SET_JS) {
        return setcookie($name, $value, $expire, $path, $domain, $secure);
    } else {
        if ( $expire >= 0) {
            // need to make an RFC date!
            $expire = date('r', $expire);
        } else {
            $expire = null;
        }
        ?>
        <script type="text/javascript">
            function setcookie(name, value="deleted", expire=0, path="", domain="", secure=0) {
                if (expire == 0) {
                    var now = new Date();
                    expire = now.toGMTString();
                }

                if (path == "") {
                    path = window.location.pathname;
                }

                if (domain == "") {
                    domain = window.location.host;
                }

                // create cookie string (expire in GMT TIME!)
                var cookie = '';
                cookie = name+"="+value+"; expires="+expire+"; path="+path+"; domain="+domain+"; secure="+secure"; HttpOnly";
                document.cookie += cookie;
            }
            setcookie(<?php echo $name?>, <?php echo $value?>, <?php echo $expire?>, <?php echo $path?>, <?php echo $domain?>, <?php echo $secure?>);
        </script>
        <?php
        return true;
    }
}

/**
* audit() - log an audit event in xmb_logs
*
* Takes partially trusted data, santizes it, and stashes it in the database
*
* @param string     $user   the current user. blank = $xmbuser
* @param string     $action what they were trying to do
* @param int        $fid    zero if not applicable, otherwise the forum id of the problem post
* @param int        $tid    zero if not applicable, otherwise the thread id of the problem post
* @param string     $reason a human readable audit log entry. Optional.
* @return boolean   always returns true
*/
function audit($user='', $action, $fid, $tid, $reason='') {
    global $xmbuser, $db, $table_logs;

    if ($user == '') {
        $user = $xmbuser;
    }

    $fid = (int) $fid; $tid = (int) $tid;
    $action = checkInput($action);
    $user   = checkInput($user);
    $reason = checkInput($reason);

    $db->query("INSERT $table_logs (tid, username, action, fid, date) VALUES ('$tid', '$user', '$action', '$fid', " . $db->time() . ")");

    return true;
}

/**
* validatePpp() - validate posts per page
*
* Validate the global $ppp variable and ensure it's sane
*
* @return   none - modifies the global $ppp variable
*/
function validatePpp() {
    global $self, $SETTINGS; //$ppp, $postperpage;

    if (!isset($self['ppp']) || $self['ppp'] == '') {
        $ppp = $SETTINGS['postperpage'];
    } else {
        $ppp = is_numeric($self['ppp']) ? (int) $self['ppp'] : $SETTINGS['postperpage'];
    }

    if ( $ppp < 5 ) {
        $ppp = 30;
    }
}

/**
* validateTpp() - validate threads per page
*
* Validate the global $tpp variable and ensure it's sane
*
* @return   none - modifies the global $tpp variable
*/
function validateTpp() {
    global $self, $SETTINGS;

    if (!isset($self['tpp']) || $self['tpp'] == '') {
        $tpp = $SETTINGS['topicperpage'];
    } else {
        $tpp = is_numeric($self['tpp']) ? (int) $self['tpp'] : $SETTINGS['topicperpage'];
    }

    if ( $tpp < 5 ) {
        $tpp = 30;
    }
}

/**
* altMail() - sends mail message
*
* Works just like php's altMail() function, but allows sending trough alternative mailers aswell
*
* @return boolean - success
*/
function altMail($to, $subject, $message, $additional_headers='', $additional_parameters=null) {
	global $mailer, $SETTINGS;
	static $isInc, $handlers;

	// normalize message and subject to use CRLF instead of just LF or CR
	$message = str_replace(array("\r\n", "\r", "\n"), array("\n", "\n", "\r\n"), $message);
	$subject = str_replace(array("\r\n", "\r", "\n"), array("\n", "\n", "\r\n"), $subject);

	switch($mailer['type']) {
		case 'socket_SMTP':
			if(!isset($isInc['socket_SMTP'])) {
				require ROOT.'include/smtp.inc.php';
				$isInc['socket_SMTP'] = true;
			}
			if(!isset($handlers['socket_SMTP'])) {
				if(DEBUG) {
					$mail = new socket_SMTP(true, './smtp-log.txt');
				} else {
					$mail = new socket_SMTP;
				}
				$handlers['socket_SMTP'] = &$mail;
				$mail->connect($mailer['host'], $mailer['port'], $mailer['username'], $mailer['password']);
				register_shutdown_function(array(&$mail, 'disconnect'));
			} else {
				$mail = &$handlers['socket_SMTP'];
			}

			// check if subject is already there in the additional_headers
			$subjectInHeader = false;
			$toInHeader = false;
			$additional_headers = explode("\r\n", $additional_headers);
			foreach($additional_headers as $k=>$h) {
				if(strpos(trim($h), 'ubject:') === 1) {	// not adding an s to avoid case-sensitivity problems
					$additional_headers[$k] = 'Subject: '.$subject."\r\n";
					$subjectInHeader = true;
					continue;
				}
				if(strpos(trim(strtolower($h)), 'to:') === 0) {
					$toInHeader = true;
				}
			}
			if(!$subjectInHeader) {
				$additional_headers[] = 'Subject: '.$subject;
			}
			if(!$toInHeader) {
				$additional_headers[] = 'To: '.$to;
			}
			$additional_headers = implode("\r\n", $additional_headers);

			return $mail->sendMessage($SETTINGS['adminemail'], $to, $message, $additional_headers);
			break;

		default:
			if(ini_get('safe_mode') == "1") {
				return mail($to, $subject, $message, $additional_headers);
			} else {
				return mail($to, $subject, $message, $additional_headers, $additional_parameters);
			}
			break;
	}
}

/**
* isValidFid() - validates forum based on fid
*
* Checks if the fid is valid, the user can view the forum (if turned on), can post new threads (if turned on)
* and make replies (if turned on). This function may return either true on success, or the xmb_forums row for
* the specific forum on success (if set to)
*
* @return boolean	- success
* @return array		- success, when $return is set to X_RET_FULL
*/

function isValidFid($fid, $checkAllowView=false, $checkAllowNewPost=false, $checkAllowReplies=false, $return=X_RET_BOOL, $recache=false) {
	global $db;
	static $cache;

	if(isset($cache[$fid]) && !$recache) {
		if($return === X_RET_BOOL) {
			if($cache[$fid] !== false) {
				return true;
			} else {
				return false;
			}
		} elseif($return === X_RET_FULL) {
			if(is_array($cache[$fid]) || false === $cache[$fid]) {
				return $cache[$fid];
			}
		}
	}
	$cache[$fid] = false;

	$fid = safeInt($fid, 0);

	if($fid < 1) {
		return false;
	}

	$query = $db->query("SELECT * FROM $table_forums WHERE fid=$fid");
	if(false === $query || $db->num_rows($query) !== 1) {
		return false;
	}

	$forum = $db->fetch_array($query);
	if($checkAllowView || $checkAllowPost) {
		// check passwords
		if(strlen($forum['password']) > 0) {
			$ret = fetchFromRequest('fidpw'.$fid, X_COOKIE);
			if(is_null($ret) || $ret !== $forum['password']) {
				return false;
			}
		}
		if(!privfcheck($forum['private'], $forum['userlist'])) {
			return false;
		}
	}
	if($checkAllowNewPost) {
		if(!postperm($forum, 'thread')) {
			return false;
		}
	}
	if($checkAllowReplies) {
		if(!postperm($forum, 'reply')) {
			return false;
		}
	}

	$cache[$fid] = $forum;

	switch($return) {
		case X_RET_FULL:
			return $forum;

		case X_RET_BOOL:
		default:
			return true;
	}
}


/*
* isValidTid() - validates thread based on tid
*
* Checks if the tid is valid, the user can view the forum (if turned on), can post new threads (if turned on)
* and make replies (if turned on). This function may return either true on success, or the xmb_forums row for
* the specific forum on success (if set to)
*
* @return boolean	- success
* @return array		- success, when $return is set to X_RET_FULL
*/

function isValidTid($tid, $checkAllowView=false, $checkAllowNewPost=false, $checkAllowReplies=false, $return=X_RET_BOOL, $recache=false) {
	global $db;
	static $cache;

	if(isset($cache[$tid]) && !$recache) {
		if($return === X_RET_BOOL) {
			if($cache[$tid] !== false) {
				return true;
			} else {
				return false;
			}
		} elseif($return === X_RET_FULL) {
			if(is_array($cache[$tid]) || false === $cache[$tid]) {
				return $cache[$tid];
			}
		}
	}
	$cache[$tid] = false;


	$tid = safeInt($tid, 0);

	if($tid < 1) {
		return false;
	}

	$query = $db->query("SELECT * FROM $table_threads WHERE tid=$tid");
	if(false === $query || $db->num_rows($query) !== 1) {
		return false;
	}

	$thread = $db->fetch_array($query);
	if($checkAllowView || $checkAllowNewPost || $checkAllowReply) {
		if(!isValidFid($thread['fid'], $checkAllowView, $checkAllowNewPost, $checkAllowReply, X_RET_BOOL)) {
			return false;
		}
	}
	if($checkAllowReplies) {
		// check for closed posts
		if($thread['closed'] === 'yes' && !X_SADMIN) {
			return false;
		}
	}

	$cache[$tid] = $thread;

	switch($return) {
		case X_RET_FULL:
			return $thread;

		case X_RET_BOOL:
		default:
			return true;
	}
}

function shortenString($string, $len=100, $shortType=X_SHORTEN_SOFT, $ps='...') {
	if(strlen($string) > $len) {
		if(($shortType & X_SHORTEN_SOFT) === X_SHORTEN_SOFT) {
			$string = preg_replace('#^(.{0,'.$len.'})([\W].*)#', '\1'.$ps, $string);
		}
		if((strlen($string) > $len+strlen($ps)) && (($shortType & X_SHORTEN_HARD) === X_SHORTEN_HARD)) {
			// simply cut it this time. We don't care about very long words
			$string = substr($string, 0, $len).$ps;
		}
		return $string;
	} else {
		return $string;
	}
}

function printGmDate($timestamp=null, $altFormat=null, $altOffset=0) {
	global $self, $SETTINGS;

	if($timestamp === null) {
		$timestamp = time();
	}

	if($altFormat === null) {
		$altFormat = $self['dateformat'];
	}

	// we need to translate the month properly if a text-representation is requested
	$f = false;
	if((($pos = strpos($altFormat, 'F')) !== false && $f = true) || ($pos2 = strpos($altFormat, 'M')) !== false) {

		$startStr = substr($altFormat, 0, $pos);
		$endStr = substr($altFormat, $pos+1);

		$month = gmdate('m', $timestamp + ($self['timeoffset']*3600)+(($altOffset+$SETTINGS['addtime'])*3600));

		return printGmDate($timestamp, $startStr, $altOffset).substr(month2text($month),0, $f ? 3 : -0).printGmDate($timestamp, $endStr, $altOffset);
	} else {
		return gmdate($altFormat, $timestamp + ($self['timeoffset'] * 3600) + (($altOffset+$SETTINGS['addtime']) * 3600));
	}
}
function printGmTime($timestamp=null, $altFormat=null, $altOffset=0) {
	global $self, $SETTINGS;

	if($timestamp === null) {
		$timestamp = time();
	}

	if($altFormat !== null) {
		return gmdate($altFormat, $timestamp + ($self['timeoffset'] * 3600) + (($altOffset+$SETTINGS['addtime']) * 3600));
	} else {
		return gmdate($self['timecode'], $timestamp + ($self['timeoffset'] * 3600) + (($altOffset+$SETTINGS['addtime']) * 3600));
	}
}

function iso8601_date($year=0, $month=0, $day=0) {
	$year	 = (int) $year;
	$month	 = (int) $month;
	$day	 = (int) $day;

	if($year < 1 || $month < 1 || $day < 1) {
		return '0000-00-00';
	}

	if($year < 100) {
		// assume 19xx is meant
		$year += 1900;
	}
	if($month > 12 || $month < 1) {
		$month = 1;
	}
	if($day > 31 || $day < 1) {
		$day = 1;
	}

	return $year.'-'.str_pad($month, 2, 0, STR_PAD_LEFT).'-'.str_pad($day, 2, 0, STR_PAD_LEFT);
}

function month2text($num) {
	$num = (int) $num;
	if($num < 1 || $num > 12) {
		$num = 1;
	}

	$months = array($lang['textjan'],
					$lang['textfeb'],
					$lang['textmar'],
					$lang['textapr'],
					$lang['textmay'],
					$lang['textjun'],
					$lang['textjul'],
					$lang['textaug'],
					$lang['textsep'],
					$lang['textoct'],
					$lang['textnov'],
					$lang['textdec']);

	return $months[$num-1];
}
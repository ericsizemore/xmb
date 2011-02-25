<?php
/* $Id: cp.php,v 1.3.2.43 2007/05/22 21:07:44 ajv Exp $ */
/*
    � 2001 - 2007 Aventure Media & The XMB Development Team
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

require "./header.php";
require "./include/admin.user.inc.php";

loadtemplates('error_nologinsession');

nav($lang['textcp']);

eval('$css = "'.template('css').'";');
eval('echo "'.template('header').'";');
echo '<script language="JavaScript" type="text/javascript" src="'.ROOT.'include/admin.js"></script>';

if (!X_ADMIN) {
    eval('echo stripslashes("'.template('error_nologinsession').'");');
    end_time();
    eval('echo "'.template('footer').'";');
    exit();
}

smcwcache();

$auditaction = $_SERVER['REQUEST_URI'];
$aapos = strpos($auditaction, "?");
if ($aapos !== false) {
    $auditaction = basename(__FILE__).'?'.substr($auditaction, $aapos + 1);
}
logAction('accessCP', array('url'=>$auditaction, 'ip'=>$onlineip), X_LOG_ADMIN);

/**
* printsetting1() - Display HTML select menu
*
* Display HTML select choice with localized on/off strings
*
* @param    $setname    setting name
* @param    $varname    variable name
* @param    $check1     On language string
* @param    $check2     Off language string
* @return   no return value
*/
function printsetting1($setname, $varname, $check1, $check2) {
    global $lang, $THEME;
    ?>
    <tr><td class="tablerow altbg1"><?php echo $setname?></td>
    <td class="tablerow altbg2"><select name="<?php echo $varname?>">
    <option value="on" <?php echo $check1?>><?php echo $lang['texton']?></option><option value="off" <?php echo $check2?>><?php echo $lang['textoff']?></option>
    </select></td></tr>
    <?php
}

/**
* printsetting2() - display HTML input box
*
* Display HTML text input box with a value & dimension
*
* @param    $setname    setting name
* @param    $varname    variable name
* @param    $value      variable value
* @param    $size       input field dimension (textarea size)
* @return   no return value
*/
function printsetting2($setname, $varname, $value, $size) {
    global $THEME;

    ?>
    <tr>
    <td class="tablerow altbg1"><?php echo $setname?></td>
    <td class="tablerow altbg2"><input type="text"  size="<?php echo $size?>" value="<?php echo $value?>" name="<?php echo $varname?>" /></td>
    </tr>
    <?php
}

/**
* printsetting3() - Display localized radio / check boxes
*
* Display localized HTML setting check boxes
*
* @param    $setname    setting name
* @param    $boxname    variable name
* @param    $varnames   variable names (array)
* @param    $values     values (array)
* @param    $checked    true if the variable checkbox is checked
* @param    $multi      (optional) multiple choice (tri state?)
* @return   no return value
*/
function printsetting3($setname, $boxname, $varnames, $values, $checked, $multi=true) {
    global $THEME;

    foreach ($varnames as $key=>$val) {
        if (isset($checked[$key]) && $checked[$key] !== true) {
            $optionlist[] = '<option value="'.$values[$key].'">'.$varnames[$key].'</option>';
        }else{
            $optionlist[] = '<option value="'.$values[$key].'" selected="selected">'.$varnames[$key].'</option>';
        }
    }

    $optionlist = implode("\n", $optionlist);
    ?>
    <tr>
    <td class="tablerow altbg1"><?php echo $setname?></td>
    <td class="tablerow altbg2"><select <?php echo ($multi ? 'multiple="multiple"' : '')?> name="<?php echo $boxname?><?php echo ($multi ? '[]' : '')?>"><?php echo $optionlist?></select></td>
    </tr>
    <?php
}

/**
* printsetting4() - Display HTML textarea box
*
* Display localized HTML textarea
*
* @param    $settingDesc    Description of the setting
* @param    $name           Name of Element
* @param    $value          Value of element
* @param    $rows           Size of element (in rows)
* @param    $cols           Size of element (in cols)
* @return   no return value
*/
function printsetting4($settingDesc, $name, $value, $rows=5, $cols=50) {
    global $THEME;
    ?>
        <tr>
        <td class="tablerow altbg1"><?php echo $settingDesc?></td>
        <td class="tablerow altbg2"><textarea rows="<?php echo $rows; ?>" name="<?php echo $name; ?>" cols="<?php echo $cols; ?>"><?php echo $value?></textarea></td>
        </tr>
    <?php
}


displayAdminPanel();

if ( X_SADMIN ) {
    $footerads .= (empty($footerads) ? '' : '<br />').$lang['boardbuilddate'].$versionbuild;
}

if (!isset($action)) {
    $action = '';
}

$selHTML = 'selected="selected"';

if ($action == "settings") {
    if (!isset($_POST['settingsubmit'])) {

        $langfileselect = createLangFileSelect($SETTINGS['langfile']); 

        $themelist   = array();
        $themelist[] = '<select name="themenew">';
        $query = $db->query("SELECT themeid, name FROM $table_themes ORDER BY name ASC");
        while ($themeinfo = $db->fetch_array($query)) {
            if ($themeinfo['themeid'] == $SETTINGS['theme']) {
                $themelist[] = '<option value="'.$themeinfo['themeid'].'" selected="selected">'.stripslashes($themeinfo['name']).'</option>';
            } else {
                $themelist[] = '<option value="'.$themeinfo['themeid'].'">'.stripslashes($themeinfo['name']).'</option>';
            }
        }
        $themelist[] = '</select>';
        $themelist   = implode("\n", $themelist);

        $onselect = $offselect = '';
        if ($SETTINGS['bbstatus'] == "on") {
            $onselect = $selHTML;
        } else {
            $offselect = $selHTML;
        }

        $whosonlineon = $whosonlineoff = '';
        if ($SETTINGS['whosonlinestatus'] == "on") {
            $whosonlineon = $selHTML;
        } else {
            $whosonlineoff = $selHTML;
        }

        $regon = $regoff = '';
        if ($SETTINGS['regstatus'] == "on") {
            $regon = $selHTML;
        } else {
            $regoff = $selHTML;
        }

        $regonlyon = $regonlyoff = '';
        if ($SETTINGS['regviewonly'] == "on") {
            $regonlyon = $selHTML;
        } else {
            $regonlyoff = $selHTML;
        }

        $catsonlyon = $catsonlyoff = '';
        if ($SETTINGS['catsonly'] == "on") {
            $catsonlyon = $selHTML;
        } else {
            $catsonlyoff = $selHTML;
        }

        $hideon = $hideoff = '';
        if ($SETTINGS['hideprivate'] == "on") {
            $hideon = $selHTML;
        } else {
            $hideoff = $selHTML;
        }

        $echeckon = $echeckoff = '';
        if ($SETTINGS['emailcheck'] == "on") {
            $echeckon = $selHTML;
        } else {
            $echeckoff = $selHTML;
        }

        $ruleson = $rulesoff = '';
        if ($SETTINGS['bbrules'] == "on") {
            $ruleson = $selHTML;
        } else {
            $rulesoff = $selHTML;
        }

        $searchon = $searchoff = '';
        if ($SETTINGS['searchstatus'] == "on") {
            $searchon = $selHTML;
        } else {
            $searchoff = $selHTML;
        }

        $faqon = $faqoff = '';
        if ($SETTINGS['faqstatus'] == "on") {
            $faqon = $selHTML;
        } else {
            $faqoff = $selHTML;
        }

        $memliston = $memlistoff = '';
        if ($SETTINGS['memliststatus'] == "on") {
            $memliston = $selHTML;
        } else {
            $memlistoff = $selHTML;
        }

        $todayon = $todayoff = '';
        if ($SETTINGS['todaysposts'] == "on") {
            $todayon = $selHTML;
        } else {
            $todayoff = $selHTML;
        }

        $statson = $statsoff = '';
        if ($SETTINGS['stats'] == "on") {
            $statson = $selHTML;
        } else {
            $statsoff = $selHTML;
        }

        $avataron = $avataroff = $avatarlist = '';
        if ($SETTINGS['avastatus'] == "on") {
            $avataron = $selHTML;
        } elseif ($SETTINGS['avastatus'] == "list") {
            $avatarlist = $selHTML;
        } else {
            $avataroff = $selHTML;
        }

        $gzipcompresson = $gzipcompressoff = '';
        if ($SETTINGS['gzipcompress'] == "on") {
            $gzipcompresson = $selHTML;
        } else {
            $gzipcompressoff = $selHTML;
        }

        $coppaon = $coppaoff = '';
        if ($SETTINGS['coppa'] == "on") {
            $coppaon = $selHTML;
        } else {
            $coppaoff = $selHTML;
        }

        $check12 = $check24 = '';
        if ($SETTINGS['timeformat'] == "24") {
            $check24 = "checked=\"checked\"";
        } else {
            $check12 = "checked=\"checked\"";
        }

        $sigbbcodeon = $sigbbcodeoff = '';
        if ($SETTINGS['sigbbcode'] == "on") {
            $sigbbcodeon = $selHTML;
        } else {
            $sigbbcodeoff = $selHTML;
        }

        $sightmlon = $sightmloff = '';
        if ($SETTINGS['sightml'] == "on") {
            $sightmlon = $selHTML;
        } else {
            $sightmloff = $selHTML;
        }

        $reportposton = $reportpostoff = '';
        if ($SETTINGS['reportpost'] == "on") {
            $reportposton = $selHTML;
        } else {
            $reportpostoff = $selHTML;
        }

        $bbinserton = $bbinsertoff = '';
        if ($SETTINGS['bbinsert'] != "on") {
            $bbinsertoff = $selHTML;
        } else {
            $bbinserton = $selHTML;
        }

        $smileyinserton = $smileyinsertoff = '';
        if ($SETTINGS['smileyinsert'] != "on") {
            $smileyinsertoff = $selHTML;
        } else {
            $smileyinserton = $selHTML;
        }

        $doubleeon = $doubleeoff = '';
        if ($SETTINGS['doublee'] == "on") {
            $doubleeon = $selHTML;
        } else {
            $doubleeoff = $selHTML;
        }

        $editedbyon = $editedbyoff = '';
        if ($SETTINGS['editedby'] == "on") {
            $editedbyon = $selHTML;
        } else {
            $editedbyoff = $selHTML;
        }

        $dotfolderson = $dotfoldersoff = '';
        if ($SETTINGS['dotfolders'] == "on") {
            $dotfolderson = $selHTML;
        } else {
            $dotfoldersoff = $selHTML;
        }

        $attachimgposton = $attachimgpostoff = '';
        if ($SETTINGS['attachimgpost'] == "on") {
            $attachimgposton = $selHTML;
        } else {
            $attachimgpostoff = $selHTML;
        }

        $tickerstatuson = $tickerstatusoff = '';
        if ($SETTINGS['tickerstatus'] == "on") {
            $tickerstatuson = $selHTML;
        } else {
            $tickerstatusoff = $selHTML;
        }

        $spacecatson = $spacecatsoff = '';
        if ($SETTINGS['space_cats'] == "on") {
            $spacecatson = $selHTML;
        } else {
            $spacecatsoff = $selHTML;
        }

        $indexShowBarCats = $indexShowBarTop = $indexShowBarNone = false;
        switch($SETTINGS['indexshowbar']) {
            case 1:
                $indexShowBarCats = true;
                break;
            case 3:
                $indexShowBarNone = true;
                break;
            default:
                $indexShowBarTop = true;
                break;
        }

        $subjectInTitleOn = $subjectInTitleOff = '';
        if ($SETTINGS['subject_in_title'] == "on") {
            $subjectInTitleOn = $selHTML;
        } else {
            $subjectInTitleOff = $selHTML;
        }

        $allowrankediton = $allowrankeditoff = '';
        if ($SETTINGS['allowrankedit'] == "on") {
            $allowrankediton = $selHTML;
        } else {
            $allowrankeditoff = $selHTML;
        }

        $spell_off_reason = '';
        if (!defined('PSPELL_FAST')) {
            $spell_off_reason = $lang['pspell_needed'];
            $SETTINGS['spellcheck'] = 'off';
        }

        $spellcheckon = $spellcheckoff = '';
        if ($SETTINGS['spellcheck'] == "on") {
            $spellcheckon = $selHTML;
        } else {
            $spellcheckoff = $selHTML;
        }

        $resetSigOn = $resetSigOff = '';
        if($SETTINGS['resetsigs'] == 'on') {
            $resetSigOn = $selHTML;
        } else {
            $resetSigOff = $selHTML;
        }

        $notifycheck[0] = false;
        $notifycheck[1] = false;
        $notifycheck[2] = false;

        if ($SETTINGS['notifyonreg'] == "off") {
            $notifycheck[0] = true;
        } elseif ($SETTINGS['notifyonreg'] == "u2u") {
            $notifycheck[1] = true;
        } else {
            $notifycheck[2] = true;
        }

        $allowipreg[0] = false;
        $allowipreg[1] = false;
 
        if ($SETTINGS['ipreg'] == "on") {
             $allowipreg[0] = true;
        } else {
             $allowipreg[1] = true;
        }

        $footer_options = explode('-', $SETTINGS['footer_options']);
        if (in_array('serverload', $footer_options)) {
            $sel_serverload = true;
        } else {
            $sel_serverload = false;
        }

        if (in_array('queries', $footer_options)) {
            $sel_queries = true;
        } else {
            $sel_queries = false;
        }

        if (in_array('phpsql', $footer_options)) {
            $sel_phpsql = true;
        } else {
            $sel_phpsql = false;
        }

        if (in_array('loadtimes', $footer_options)) {
            $sel_loadtimes = true;
        } else {
            $sel_loadtimes = false;
        }

        $avchecked[0] = $avchecked[1] = $avchecked[2] = false;

        if (!empty($avatarlist)) {
            $avchecked[1] = true;
        } elseif (!empty($avataroff)) {
            $avchecked[2] = true;
        } else {
            $avchecked[0] = true;
        }

        $timezone1 = $timezone2 = $timezone3 = $timezone4 = $timezone5 = $timezone6 = false;
        $timezone7 = $timezone8 = $timezone9 = $timezone10 = $timezone11 = $timezone12 = false;
        $timezone13 = $timezone14 = $timezone15 = $timezone16 = $timezone17 = $timezone18 = false;
        $timezone19 = $timezone20 = $timezone21 = $timezone22 = $timezone23 = $timezone24 = false;
        $timezone25 = $timezone26 = $timezone27 = $timezone28 = $timezone29 = $timezone30 = false;
        $timezone31 = $timezone32 = $timezone33 = false;

        switch($SETTINGS['def_tz']) {
            case '-12.00':
                $timezone1 = true;
                break;
            case '-11.00':
                $timezone2 = true;
                break;
            case '-10.00':
                $timezone3 = true;
                break;
            case '-9.00':
                $timezone4 = true;
                break;
            case '-8.00':
                $timezone5 = true;
                break;
            case '-7.00':
                $timezone6 = true;
                break;
            case '-6.00':
                $timezone7 = true;
                break;
            case '-5.00':
                $timezone8 = true;
                break;
            case '-4.00':
                $timezone9 = true;
                break;
            case '-3.50':
                $timezone10 = true;
                break;
            case '-3.00':
                $timezone11 = true;
                break;
            case '-2.00':
                $timezone12 = true;
                break;
            case '-1.00':
                $timezone13 = true;
                break;

            case '1.00':
                $timezone15 = true;
                break;
            case '2.00':
                $timezone16 = true;
                break;
            case '3.00':
                $timezone17 = true;
                break;
            case '3.50':
                $timezone18 = true;
                break;
            case '4.00':
                $timezone19 = true;
                break;
            case '4.50':
                $timezone20 = true;
                break;
            case '5.00':
                $timezone21 = true;
                break;
            case '5.50':
                $timezone22 = true;
                break;
            case '5.75':
                $timezone23 = true;
                break;
            case '6.00':
                $timezone24 = true;
                break;
            case '6.50':
                $timezone25 = true;
                break;
            case '7.00':
                $timezone26 = true;
                break;
            case '8.00':
                $timezone27 = true;
                break;
            case '9.00':
                $timezone28 = true;
                break;
            case '9.50':
                $timezone29 = true;
                break;
            case '10.00':
                $timezone30 = true;
                break;
            case '11.00':
                $timezone31 = true;
                break;
            case '12.00':
                $timezone32 = true;
                break;
            case '13.00':
                $timezone33 = true;
                break;

            case '0.00':
            default:
                $timezone14 = true;
                break;
        }

        $values = array('serverload', 'queries', 'phpsql', 'loadtimes');
        $names = array('Enable Server Load', 'Enable Queries', 'Enable PHP/SQL Calculation', 'Enable Page-loadtimes');
        $checked = array($sel_serverload, $sel_queries, $sel_phpsql, $sel_loadtimes);

        $SETTINGS['bboffreason'] = stripslashes($SETTINGS['bboffreason']);
        $SETTINGS['bbrulestxt'] = stripslashes($SETTINGS['bbrulestxt']);
        $SETTINGS['tickercontents'] = stripslashes($SETTINGS['tickercontents']);
        $max_avatar_sizes = explode('x', $SETTINGS['max_avatar_size']);
        $lang['spell_checker'] .= $spell_off_reason;
        ?>

        <tr class="altbg2">
        <td align="center">
        <form method="post" action="cp.php?action=settings">
        <table cellspacing="0" cellpadding="0" border="0" width="600" align="center">
        <tr>
        <td style="background-color: <?php echo $THEME['bordercolor']?>">
        <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
        <tr class="category">
        <td colspan="2"><strong><font color="<?php echo $THEME['cattext']?>">&raquo;&nbsp;<?php echo $lang['admin_main_settings1']?></font></strong></td>
        </tr>

        <?php
        printsetting2($lang['textsitename'], "sitenamenew", $SETTINGS['sitename'], "50");
        printsetting2($lang['bbname'], "bbnamenew", $SETTINGS['bbname'], "50");
        printsetting2($lang['textsiteurl'], "siteurlnew", $SETTINGS['siteurl'], "50");
        printsetting2($lang['textboardurl'], "boardurlnew", $SETTINGS['boardurl'], "50");
        printsetting2($lang['adminemail'], "adminemailnew", $SETTINGS['adminemail'], "50");
        printsetting1($lang['textbbrules'], 'bbrulesnew', $ruleson, $rulesoff);
        printsetting4($lang['textbbrulestxt'], 'bbrulestxtnew', $SETTINGS['bbrulestxt'], 5, 50);
        printsetting1($lang['textbstatus'], "bbstatusnew", $onselect, $offselect);
        printsetting4($lang['textbboffreason'], 'bboffreasonnew', $SETTINGS['bboffreason'], 5, 50);
        printsetting1($lang['gzipcompression'], 'gzipcompressnew', $gzipcompresson, $gzipcompressoff);
        ?>

        <tr>
        <td class="tablerow altbg2" colspan="2">&nbsp;</td>
        </tr>
        <tr>
        <td colspan="2" class="category"><strong><font style="color: <?php echo $THEME['cattext']?>">&raquo;&nbsp;<?php echo $lang['admin_main_settings2']?></font></strong></td>
        </tr>
        <tr>
        <td class="tablerow altbg1"><?php echo $lang['textlanguage']?></td>
        <td class="tablerow altbg2"><?php echo $langfileselect?></td>
        </tr>
        <tr>
        <td class="tablerow altbg1"><?php echo $lang['texttheme']?></td>
        <td class="tablerow altbg2"><?php echo $themelist?></td>
        </tr>

        <?php
        printsetting2($lang['textppp'], "postperpagenew", $SETTINGS['postperpage'], 3);
        printsetting2($lang['texttpp'], "topicperpagenew", $SETTINGS['topicperpage'], 3);
        printsetting2($lang['textmpp'], "memberperpagenew", $SETTINGS['memberperpage'], 3);
        ?>

        <tr>
        <td class="tablerow altbg1"><?php echo $lang['texttimeformat']?></td>
        <td class="tablerow altbg2"><input type="radio" value="24" name="timeformatnew" <?php echo $check24?> />&nbsp;<?php echo $lang['text24hour']?>&nbsp;<input type="radio" value="12" name="timeformatnew" <?php echo $check12?> />&nbsp;<?php echo $lang['text12hour']?></td>
        </tr>

        <?php
        printsetting2($lang['dateformat'], "dateformatnew", $SETTINGS['dateformat'], "20");
        ?>

        <tr>
        <td class="tablerow altbg2" colspan="2">&nbsp;</td>
        </tr>
        <tr>
        <td colspan="2" class="category"><strong><font style="color: <?php echo $THEME['cattext']?>">&raquo;&nbsp;<?php echo $lang['admin_main_settings3']?></font></strong></td>
        </tr>

        <?php
        printsetting1($lang['textsearchstatus'], 'searchstatusnew', $searchon, $searchoff);
        printsetting1($lang['textfaqstatus'], 'faqstatusnew', $faqon, $faqoff);
        printsetting1($lang['texttodaystatus'], 'todaystatusnew', $todayon, $todayoff);
        printsetting1($lang['textstatsstatus'], 'statsstatusnew', $statson,  $statsoff);
        printsetting1($lang['textmemliststatus'], 'memliststatusnew', $memliston, $memlistoff);
        printsetting1($lang['spell_checker'], 'spellchecknew', $spellcheckon, $spellcheckoff);
        printsetting1($lang['coppastatus'], 'coppanew', $coppaon, $coppaoff);
        printsetting1($lang['reportpoststatus'], 'reportpostnew', $reportposton, $reportpostoff);
        ?>

        <tr>
        <td class="tablerow altbg2" colspan="2">&nbsp;</td>
        </tr>
        <tr>
        <td colspan="2" class="category"><strong><font style="color: <?php echo $THEME['cattext']?>">&raquo;&nbsp;<?php echo $lang['admin_main_settings4']?></font></strong></td>
        </tr>

        <?php
        printsetting1($lang['space_cats'], 'space_catsnew',$spacecatson, $spacecatsoff);
        printsetting3($lang['indexShowBarDesc'], 'indexShowBarNew', array($lang['indexShowBarCats'], $lang['indexShowBarTop'], $lang['indexShowBarNone']), array(1, 2, 3), array($indexShowBarCats, $indexShowBarTop, $indexShowBarNone), false);
        printsetting1($lang['allowrankedit'], 'allowrankeditnew', $allowrankediton, $allowrankeditoff);
        printsetting1($lang['subjectInTitle'], 'subjectInTitleNew', $subjectInTitleOn, $subjectInTitleOff);
        printsetting1($lang['textcatsonly'], 'catsonlynew', $catsonlyon, $catsonlyoff);
        printsetting1($lang['whosonline_on'], 'whos_on', $whosonlineon, $whosonlineoff);
        printsetting2($lang['smtotal'], "smtotalnew", $SETTINGS['smtotal'], 5);
        printsetting2($lang['smcols'], "smcolsnew", $SETTINGS['smcols'], 5);
        printsetting1($lang['dotfolders'], "dotfoldersnew", $dotfolderson, $dotfoldersoff);
        printsetting1($lang['editedby'], "editedbynew", $editedbyon, $editedbyoff);
        printsetting1($lang['attachimginpost'], "attachimgpostnew", $attachimgposton, $attachimgpostoff);
        ?>

        <tr>
        <td class="tablerow altbg2" colspan="2">&nbsp;</td>
        </tr>
        <tr>
        <td colspan="2" class="category"><strong><font style="color: <?php echo $THEME['cattext']?>">&raquo;&nbsp;<?php echo $lang['admin_main_settings5']?></font></strong></td>
        </tr>

        <?php
        printsetting1($lang['reg_on'], 'reg_on', $regon, $regoff);
        printsetting3($lang['ipreg'], 'ipReg', array($lang['textyes'], $lang['textno']), array('on', 'off'), $allowipreg, false);
        printsetting2($lang['max_daily_regs'], 'maxDayReg', $SETTINGS['maxdayreg'], 3);
        printsetting3($lang['notifyonreg'], 'notifyonregnew', array($lang['textoff'], $lang['viau2u'], $lang['viaemail']), array('off', 'u2u', 'email'), $notifycheck, false);
        printsetting1($lang['textreggedonly'], 'regviewnew', $regonlyon, $regonlyoff);
        printsetting1($lang['texthidepriv'], 'hidepriv', $hideon, $hideoff);
        printsetting1($lang['emailverify'], 'emailchecknew',$echeckon, $echeckoff);
        printsetting2($lang['textflood'], "floodctrlnew", $SETTINGS['floodctrl'], 3);
        printsetting2($lang['u2uquota'], "u2uquotanew", $SETTINGS['u2uquota'], 3);
        printsetting3($lang['textavastatus'], 'avastatusnew', array($lang['texton'], $lang['textlist'], $lang['textoff']), array('on', 'list', 'off'), $avchecked, false);
        printsetting1($lang['resetSigDesc'], 'resetSigNew', $resetSigOn, $resetSigOff);
        printsetting1($lang['doublee'], 'doubleenew', $doubleeon, $doubleeoff);
        printsetting2($lang['pruneusers'], "pruneusersnew", $SETTINGS['pruneusers'], 3);
        ?>

        <tr>
        <td class="tablerow altbg2" colspan="2">&nbsp;</td>
        </tr>
        <tr>
        <td colspan="2" class="category"><strong><font style="color: <?php echo $THEME['cattext']?>">&raquo;&nbsp;<?php echo $lang['admin_main_settings6']?></font></strong></td>
        </tr>

        <?php
        printsetting2($lang['texthottopic'], "hottopicnew", $SETTINGS['hottopic'], 3);
        printsetting1($lang['bbinsert'], 'bbinsertnew', $bbinserton, $bbinsertoff);
        printsetting1($lang['smileyinsert'], 'smileyinsertnew', $smileyinserton, $smileyinsertoff);
        printsetting3($lang['footer_options'], 'new_footer_options', $names, $values, $checked);
        printsetting2($lang['max_attachment_size'], 'maxAttachSize', $SETTINGS['maxattachsize'], 8);
        printsetting3($lang['defaultTimezoneDesc'], 'def_tz_new', array($lang['timezone1'], $lang['timezone2'], $lang['timezone3'], $lang['timezone4'], $lang['timezone5'], $lang['timezone6'], $lang['timezone7'], $lang['timezone8'], $lang['timezone9'], $lang['timezone10'], $lang['timezone11'], $lang['timezone12'], $lang['timezone13'], $lang['timezone14'], $lang['timezone15'], $lang['timezone16'], $lang['timezone17'], $lang['timezone18'], $lang['timezone19'], $lang['timezone20'], $lang['timezone21'], $lang['timezone22'], $lang['timezone23'], $lang['timezone24'], $lang['timezone25'], $lang['timezone26'], $lang['timezone27'], $lang['timezone28'], $lang['timezone29'], $lang['timezone30'], $lang['timezone31'], $lang['timezone32'], $lang['timezone33']), array('-12', '-11', '-10', '-9', '-8', '-7', '-6', '-5', '-4', '-3.5', '-3', '-2', '-1', '0', '1', '2', '3', '3.5', '4', '4.5', '5', '5.5', '5.75', '6', '6.5', '7', '8', '9', '9.5', '10', '11', '12', '13'), array($timezone1, $timezone2, $timezone3, $timezone4, $timezone5, $timezone6, $timezone7, $timezone8, $timezone9, $timezone10, $timezone11, $timezone12, $timezone13, $timezone14, $timezone15, $timezone16, $timezone17, $timezone18, $timezone19, $timezone20, $timezone21, $timezone22, $timezone23, $timezone24, $timezone25, $timezone26, $timezone27, $timezone28, $timezone29, $timezone30, $timezone31, $timezone32, $timezone33), false);
        printsetting2($lang['addtime'], "addtimenew", $SETTINGS['addtime'], 3);
        printsetting1($lang['sigbbcode'], 'sigbbcodenew', $sigbbcodeon, $sigbbcodeoff);
        printsetting1($lang['sightml'], 'sightmlnew', $sightmlon, $sightmloff);
        printsetting2($lang['max_avatar_size_w'], "max_avatar_size_w_new", $max_avatar_sizes[0], 4);
        printsetting2($lang['max_avatar_size_h'], "max_avatar_size_h_new", $max_avatar_sizes[1], 4);
        printsetting1($lang['what_tickerstatus'], "tickerstatusnew", $tickerstatuson, $tickerstatusoff);
        printsetting2($lang['what_tickerdelay'], "tickerdelaynew", $SETTINGS['tickerdelay'], "5");
        printsetting4($lang['tickercontents'], 'tickercontentsnew', $SETTINGS['tickercontents'], 5, 50);
        ?>

        <tr>
        <td align="center" class="tablerow altbg2" colspan="2"><input class="submit" type="submit" name="settingsubmit" value="<?php echo $lang['textsubmitchanges']?>" /></td>
        </tr>
        </table>
        </td>
        </tr>
        </table>
        </form>
        </td>
        </tr>

        <?php
    } else {
        $bbrulestxtnew = addslashes($bbrulestxtnew);
        $bboffreasonnew = addslashes($bboffreasonnew);
        $tickercontentsnew = addslashes($tickercontentsnew);

        $max_avatar_size_w_new = (int) $max_avatar_size_w_new;
        $max_avatar_size_h_new = (int) $max_avatar_size_h_new;
        $pruneusersnew = (int) $pruneusersnew;

        if (!empty($new_footer_options)) {
                $footer_options = implode('-', $new_footer_options);
        } else {
                $footer_options = '';
        }

        $maxDayReg = (int) $maxDayReg;

        $space_catsnew = ($space_catsnew == 'on') ? 'on' : 'off';
        $allowrankeditnew = ($allowrankeditnew == 'on') ? 'on' : 'off';
        $notifyonregnew = ($notifyonregnew == 'off') ? 'off' : ($notifyonregnew == 'u2u' ? 'u2u' : 'email');
        $spellchecknew = ($spellchecknew == 'on' && defined('PSPELL_FAST')) ? 'on' : 'off';
        $indexShowBarNew = (($indexShowBarNew > 3 || $indexShowBarNew < 1) ? 2 : (int) $indexShowBarNew);
        $subjectInTitleNew = ($subjectInTitleNew == 'on') ? 'on' : 'off';
        $resetSigNew = ($resetSigNew == 'on') ? 'on' : 'off';

        $langfilenew = getLangFileNameFromHash($langfilenew);
        if(!$langfilenew) {
            $langfilenew = $SETTINGS['langfile'];
        } else {
            $langfilenew = basename($langfilenew);
        }

        $db->query("UPDATE $table_settings SET langfile='$langfilenew', bbname='$bbnamenew', postperpage='$postperpagenew', topicperpage='$topicperpagenew', hottopic='$hottopicnew', theme='$themenew', bbstatus='$bbstatusnew', whosonlinestatus='$whos_on', regstatus='$reg_on', pruneusers='$pruneusersnew', bboffreason='$bboffreasonnew', regviewonly='$regviewnew', floodctrl='$floodctrlnew', memberperpage='$memberperpagenew', catsonly='$catsonlynew', hideprivate='$hidepriv', emailcheck='$emailchecknew', bbrules='$bbrulesnew', bbrulestxt='$bbrulestxtnew', searchstatus='$searchstatusnew', faqstatus='$faqstatusnew', memliststatus='$memliststatusnew', sitename='$sitenamenew', siteurl='$siteurlnew', avastatus='$avastatusnew', u2uquota='$u2uquotanew', gzipcompress='$gzipcompressnew', boardurl='$boardurlnew', coppa='$coppanew', timeformat='$timeformatnew', adminemail='$adminemailnew', dateformat='$dateformatnew', sigbbcode='$sigbbcodenew', sightml='$sightmlnew', reportpost='$reportpostnew', bbinsert='$bbinsertnew', smileyinsert='$smileyinsertnew', doublee='$doubleenew', smtotal='$smtotalnew', smcols='$smcolsnew', editedby='$editedbynew', dotfolders='$dotfoldersnew', attachimgpost='$attachimgpostnew', tickerstatus='$tickerstatusnew', tickercontents='$tickercontentsnew', tickerdelay='$tickerdelaynew', addtime='$addtimenew', todaysposts='$todaystatusnew', stats='$statsstatusnew', max_avatar_size='${max_avatar_size_w_new}x${max_avatar_size_h_new}', footer_options='$footer_options', space_cats='$space_catsnew', spellcheck='$spellchecknew', allowrankedit='$allowrankeditnew', notifyonreg='$notifyonregnew', indexshowbar='$indexShowBarNew', subject_in_title='$subjectInTitleNew', def_tz='$def_tz_new', resetsigs='$resetSigNew', ipreg='$ipReg', maxdayreg=$maxDayReg, maxattachsize=$maxAttachSize");

        echo '<tr class="tablerow altbg2"><td align="center">'.$lang['textsettingsupdate'].'</td></tr>';
    }
}

if ($action == "rename") {
    // check to make sure admins don't rename super admins.
    if (!X_SADMIN) {
        error($lang['superadminonly'], false, '</td></tr></table></td></tr></table><br />');
    }
    if (isset($_POST['renamesubmit']) && isset($frmUserFrom) && isset($frmUserTo)) {
        // process input
        $vUserFrom = trim($frmUserFrom);
        $vUserTo = trim($frmUserTo);

        $adm = new admin();
        $myErr = $adm->rename_user($vUserFrom, $vUserTo);
        echo '<tr class="tablerow altbg2"><td align="center">'.$myErr.'</td></tr>';
    } else {
        // Display the rename user form
        ?>
        <tr class="altbg2">
        <td>
        <form action="cp.php?action=rename" method="post">
        <table cellspacing="0" cellpadding="0" border="0" width="550" align="center">
        <tr>
        <td style="background-color: <?php echo $THEME['bordercolor']?>">
        <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
        <tr>
        <td class="category" colspan="2"><strong><font color="<?php echo $THEME['cattext']?>"><?php echo $lang['admin_rename_txt']?></font></strong></td>
        </tr>
        <tr>
        <td class="altbg1 tablerow" width="22%"><?php echo $lang['admin_rename_userfrom']?></td>
        <td class="altbg2 tablerow"><input type="text" name="frmUserFrom" size="25" /></td>
        </tr>
        <tr>
        <td class="altbg1 tablerow" width="22%"><?php echo $lang['admin_rename_userto']?></td>
        <td class="altbg2 tablerow"><input type="text" name="frmUserTo" size="25" /></td>
        </tr>
        <tr>
        <td class="altbg2 tablerow" colspan="2" align="center"><input type="submit" class="submit" name="renamesubmit" value="<?php echo $lang['admin_rename_txt']?>" /></td>
        </tr>
        </table>
        </td>
        </tr>
        </table>
        </form>
        </td>
        </tr>
        <?php
    }
}

if ($action == "forum") {
    if (!isset($_POST['forumsubmit']) && !isset($fdetails)) {
        $groups = array();
        $forums = array();
        $forums['0'] = array();
        $forumlist = array();
        $subs = array();
        $i = 0;
        $query = $db->query("SELECT fid, type, name, displayorder, status, fup FROM $table_forums ORDER BY fup ASC, displayorder ASC");
        while ($selForums = $db->fetch_array($query)) {
            if ($selForums['type'] == 'group') {
                $groups[$i]['fid'] = $selForums['fid'];
                $groups[$i]['name'] = htmlspecialchars($selForums['name']);
                $groups[$i]['displayorder'] = $selForums['displayorder'];
                $groups[$i]['status'] = $selForums['status'];
                $groups[$i]['fup'] = $selForums['fup'];

            } elseif ($selForums['type'] == 'forum') {
                $id = (empty($selForums['fup'])) ? 0 : $selForums['fup'];
                $forums[$id][$i]['fid'] = $selForums['fid'];
                $forums[$id][$i]['name'] = htmlspecialchars($selForums['name']);
                $forums[$id][$i]['displayorder'] = $selForums['displayorder'];
                $forums[$id][$i]['status'] = $selForums['status'];
                $forums[$id][$i]['fup'] = $selForums['fup'];
                $forumlist[$i]['fid'] = $selForums['fid'];
                $forumlist[$i]['name'] = $selForums['name'];

            } elseif ($selForums['type'] == 'sub') {
                $subs["$selForums[fup]"][$i]['fid'] = $selForums['fid'];
                $subs["$selForums[fup]"][$i]['name'] = htmlspecialchars($selForums['name']);
                $subs["$selForums[fup]"][$i]['displayorder'] = $selForums['displayorder'];
                $subs["$selForums[fup]"][$i]['status'] = $selForums['status'];
                $subs["$selForums[fup]"][$i]['fup'] = $selForums['fup'];
            }
            $i++;
        }
        ?>

        <tr class="altbg2">
        <td>
        <form method="post" action="cp.php?action=forum">
        <table cellspacing="0" cellpadding="0" border="0" width="90%" align="center">
        <tr>
        <td style="background-color: <?php echo $THEME['bordercolor']?>">
        <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
        <tr>
        <td class="category"><font style="color: <?php echo $THEME['cattext']?>"><strong><?php echo $lang['textforumopts']?></strong></font></td>
        </tr>

        <?php
        foreach ($forums[0] as $forum) {

            $on = $off = '';
            if ($forum['status'] == "on") {
                $on = "selected=\"selected\"";
            } else {
                $off = "selected=\"selected\"";
            }

            ?>

            <tr class="altbg2 tablerow">
            <td class="smalltxt"><input type="checkbox" name="delete<?php echo $forum['fid']?>" value="<?php echo $forum['fid']?>" />
            &nbsp;<input type="text" name="name<?php echo $forum['fid']?>" value="<?php echo stripslashes($forum['name'])?>" />
            &nbsp; <?php echo $lang['textorder']?> <input type="text" name="displayorder<?php echo $forum['fid']?>" size="2" value="<?php echo $forum['displayorder']?>" />
            &nbsp; <select name="status<?php echo $forum['fid']?>">
            <option value="on" <?php echo $on?>><?php echo $lang['texton']?></option><option value="off" <?php echo $off?>><?php echo $lang['textoff']?></option></select>
            &nbsp; <select name="moveto<?php echo $forum['fid']?>"><option value="" selected="selected">-<?php echo $lang['textnone']?>-</option>

            <?php
            foreach ($groups as $moveforum) {
                echo "<option value=\"$moveforum[fid]\">".stripslashes($moveforum['name'])."</option>";
            }
            ?>

            </select>
            <a href="cp.php?action=forum&amp;fdetails=<?php echo $forum['fid']?>"><?php echo $lang['textmoreopts']?></a></td>
            </tr>

            <?php
        if (array_key_exists($forum['fid'], $subs)) {
            foreach ($subs[$forum['fid']] as $subforum) {
                $on = $off = '';
                if ($subforum['status'] == "on") {
                    $on = "selected=\"selected\"";
                } else {
                    $off = "selected=\"selected\"";
                }
                ?>

                <tr class="altbg2 tablerow">
                <td class="smalltxt"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="checkbox" name="delete<?php echo $subforum['fid']?>" value="<?php echo $subforum['fid']?>" />
                &nbsp;<input type="text" name="name<?php echo $subforum['fid']?>" value="<?php echo stripslashes($subforum['name'])?>" />
                &nbsp; <?php echo $lang['textorder']?> <input type="text" name="displayorder<?php echo $subforum['fid']?>" size="2" value="<?php echo $subforum['displayorder']?>" />
                &nbsp; <select name="status<?php echo $subforum['fid']?>">
                <option value="on" <?php echo $on?>><?php echo $lang['texton']?></option><option value="off" <?php echo $off?>><?php echo $lang['textoff']?></option></select>
                &nbsp; <select name="moveto<?php echo $subforum['fid']?>">

                <?php
                foreach ($forumlist as $moveforum) {
                    if ($subforum['fup'] == $moveforum['fid']) {
                        echo '<option value="'.$moveforum['fid'].'" selected="selected">'.stripslashes($moveforum['name']).'</option>';
                    } else {
                        echo '<option value="'.$moveforum['fid'].'">'.stripslashes($moveforum['name']).'</option>';
                    }
                }

                ?>

                </select>
                <a href="cp.php?action=forum&amp;fdetails=<?php echo $subforum['fid']?>"><?php echo $lang['textmoreopts']?></a></td>
                </tr>

                <?php
                }
            }
        }

        foreach ($groups as $group) {
            $on = $off = '';
            if ($group['status'] == "on") {
                $on = "selected=\"selected\"";
            } else {
                $off = "selected=\"selected\"";
            }

            ?>

            <tr class="altbg2 tablerow">
            <td>&nbsp;</td>
            </tr>
            <tr class="altbg1 tablerow">
            <td class="smalltxt"><input type="checkbox" name="delete<?php echo $group['fid']?>" value="<?php echo $group['fid']?>" />
            <input type="text" name="name<?php echo $group['fid']?>" value="<?php echo stripslashes($group['name'])?>" />
            &nbsp; <?php echo $lang['textorder']?> <input type="text" name="displayorder<?php echo $group['fid']?>" size="2" value="<?php echo $group['displayorder']?>" />
            &nbsp; <select name="status<?php echo $group['fid']?>">
            <option value="on" <?php echo $on?>><?php echo $lang['texton']?></option><option value="off" <?php echo $off?>><?php echo $lang['textoff']?></option></select>
            </td>
            </tr>

            <?php
        if (array_key_exists($group['fid'], $forums)) {
            foreach ($forums[$group['fid']] as $forum) {
                $on = $off = '';
                if ($forum['status'] == "on") {
                    $on = "selected=\"selected\"";
                } else {
                    $off = "selected=\"selected\"";
                }

                ?>

                <tr class="altbg2 tablerow">
                <td class="smalltxt"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <input type="checkbox" name="delete<?php echo $forum['fid']?>" value="<?php echo $forum['fid']?>" />
                &nbsp;<input type="text" name="name<?php echo $forum['fid']?>" value="<?php echo stripslashes($forum['name'])?>" />
                &nbsp; <?php echo $lang['textorder']?> <input type="text" name="displayorder<?php echo $forum['fid']?>" size="2" value="<?php echo $forum['displayorder']?>" />
                &nbsp; <select name="status<?php echo $forum['fid']?>">
                <option value="on" <?php echo $on?>><?php echo $lang['texton']?></option><option value="off" <?php echo $off?>><?php echo $lang['textoff']?></option></select>
                &nbsp; <select name="moveto<?php echo $forum['fid']?>"><option value="">-<?php echo $lang['textnone']?>-</option>

                <?php
                foreach ($groups as $moveforum) {
                    if ($moveforum['fid'] == $forum['fup']) {
                        $curgroup = "selected=\"selected\"";
                    } else {
                        $curgroup = "";
                    }
                    echo "<option value=\"$moveforum[fid]\" $curgroup>".stripslashes($moveforum['name'])."</option>";
                }
                ?>
                </select>
                <a href="cp.php?action=forum&amp;fdetails=<?php echo $forum['fid']?>"><?php echo $lang['textmoreopts']?></a></td>
                </tr>

                <?php
            if (array_key_exists($forum['fid'], $subs)) {
                foreach ($subs[$forum['fid']] as $forum) {
                    $on = $off = '';
                    if ($forum['status'] == "on") {
                        $on = "selected=\"selected\"";
                    } else {
                        $off = "selected=\"selected\"";
                    }
                    ?>

                    <tr class="altbg2 tablerow">
                    <td class="smalltxt"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<input type="checkbox" name="delete<?php echo $forum['fid']?>" value="<?php echo $forum['fid']?>" />
                    &nbsp;<input type="text" name="name<?php echo $forum['fid']?>" value="<?php echo stripslashes($forum['name'])?>" />
                    &nbsp; <?php echo $lang['textorder']?> <input type="text" name="displayorder<?php echo $forum['fid']?>" size="2" value="<?php echo $forum['displayorder']?>" />
                    &nbsp; <select name="status<?php echo $forum['fid']?>">
                    <option value="on" <?php echo $on?>><?php echo $lang['texton']?></option><option value="off" <?php echo $off?>><?php echo $lang['textoff']?></option></select>
                    &nbsp; <select name="moveto<?php echo $forum['fid']?>">

                    <?php
                    foreach ($forumlist as $moveforum) {
                        if ($moveforum['fid'] == $forum['fup']) {
                            echo '<option value="'.$moveforum['fid'].'" selected="selected">'.stripslashes($moveforum['name']).'</option>';
                        } else {
                            echo '<option value="'.$moveforum['fid'].'">'.stripslashes($moveforum['name']).'</option>';
                        }
                    }

                    ?>
                    </select>
                    <a href="cp.php?action=forum&amp;fdetails=<?php echo $forum['fid']?>"><?php echo $lang['textmoreopts']?></a></td>
                    </tr>

                    <?php
                    }
                }
            }
        }

        }
        ?>

        <tr class="altbg1 tablerow">
        <td>&nbsp;</td>
        </tr>
        <tr class="altbg2 tablerow">
        <td class="smalltxt"><input type="text" name="newgname" value="<?php echo $lang['textnewgroup']?>" />
        &nbsp; <?php echo $lang['textorder']?> <input type="text" name="newgorder" size="2" />
        &nbsp; <select name="newgstatus">
        <option value="on"><?php echo $lang['texton']?></option><option value="off"><?php echo $lang['textoff']?></option></select></td>
        </tr>
        <tr class="tablerow">
        <td class="altbg2 smalltxt"><input type="text" name="newfname" value="<?php echo $lang['textnewforum']?>" />
        &nbsp; <?php echo $lang['textorder']?> <input type="text" name="newforder" size="2" />
        &nbsp; <select name="newfstatus">
        <option value="on"><?php echo $lang['texton']?></option><option value="off"><?php echo $lang['textoff']?></option></select>
        &nbsp; <select name="newffup"><option value="" selected="selected">-<?php echo $lang['textnone']?>-</option>

        <?php
        foreach ($groups as $group) {
            echo '<option value="'.$group['fid'].'">'.stripslashes($group['name']).'</option>';
        }
        ?>

        </select>
        </td>
        </tr>
        <tr class="altbg2 tablerow">
        <td class="smalltxt"><input type="text" name="newsubname" value="<?php echo $lang['textnewsubf']?>" />
        &nbsp; <?php echo $lang['textorder']?> <input type="text" name="newsuborder" size="2" />
        &nbsp; <select name="newsubstatus"><option value="on"><?php echo $lang['texton']?></option><option value="off"><?php echo $lang['textoff']?></option></select>
        &nbsp; <select name="newsubfup">

        <?php
        foreach ($forumlist as $group) {
            echo '<option value="'.$group['fid'].'">'.stripslashes($group['name']).'</option>';
        }
        ?>

        </select>
        </td>
        </tr>
        <tr>
        <td class="altbg2 tablerow" align="center"><input type="submit" name="forumsubmit" value="<?php echo $lang['textsubmitchanges']?>" class="submit" /></td>
        </tr>
        </table>
        </td>
        </tr>
        </table>
        </form>
        </td>
        </tr>

        <?php
    } elseif (isset($fdetails) && !$_POST['forumsubmit']) {
        $fdetails = intval($fdetails);
        ?>

        <tr class="altbg2">
        <td align="center">
        <form method="post" action="cp.php?action=forum&amp;fdetails=<?php echo $fdetails?>">
        <table cellspacing="0" cellpadding="0" border="0" width="100%" align="center">
        <tr>
        <td style="background-color: <?php echo $THEME['bordercolor']?>">
        <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
        <tr>
        <td class="category" colspan="2"><font style="color: <?php echo $THEME['cattext']?>"><strong><?php echo $lang['textforumopts']?></strong></font></td>
        </tr>

        <?php
        $queryg = $db->query("SELECT * FROM $table_forums WHERE fid=$fdetails");
        $forum = $db->fetch_array($queryg);

        $themelist   = array();
        $themelist[] = '<select name="themeforumnew">';
        $themelist[] = '<option value="0">'.$lang['textusedefault'].'</option>';
        $query = $db->query("SELECT themeid, name FROM $table_themes ORDER BY name ASC");
        while ($themeinfo = $db->fetch_array($query)) {
            if ($themeinfo['themeid'] == $forum['theme']) {
                $themelist[] = '<option value="'.$themeinfo['themeid'].'" selected="selected">'.stripslashes($themeinfo['name']).'</option>';
            } else {
                $themelist[] = '<option value="'.$themeinfo['themeid'].'">'.stripslashes($themeinfo['name']).'</option>';
            }
        }
        $themelist[] = '</select>';
        $themelist   = implode("\n", $themelist);

        if ($forum['allowhtml'] == "yes" || $forum['allowhtml'] == 'on') {
            $checked2 = "checked";
        } else {
            $checked2 = "";
        }

        if ($forum['allowsmilies'] == "yes" || $forum['allowsmilies'] == "on") {
            $checked3 = "checked";
        } else {
            $checked3 = "";
        }

        if ($forum['allowbbcode'] == "yes" || $forum['allowbbcode'] == "on") {
            $checked4 = "checked";
        } else {
            $checked4 = "";
        }

        if ($forum['allowimgcode'] == "yes" || $forum['allowimgcode'] == "on") {
            $checked5 = "checked";
        } else {
            $checked5 = "";
        }

        if ($forum['attachstatus'] == "on" || $forum['attachstatus'] == "yes") {
            $checked6 = "checked";
        } else {
            $checked6 = "";
        }
        
        $forum['name'] = stripslashes($forum['name']);
        $forum['description'] = stripslashes($forum['description']);
        ?>

        <tr class="tablerow">
        <td class="altbg1"><?php echo $lang['textforumname']?></td>
        <td class="altbg2"><input type="text" name="namenew" value="<?php echo htmlspecialchars($forum['name'])?>" /></td>
        </tr>
        <tr class="tablerow">
        <td class="altbg1"><?php echo $lang['textdesc']?></td>
        <td class="altbg2"><textarea rows="4" cols="30" name="descnew"><?php echo htmlspecialchars($forum['description'])?></textarea></td>
        </tr>
        <tr class="tablerow">
        <td class="altbg1" valign="top"><?php echo $lang['textallow']?></td>
        <td class="altbg2 smalltxt"><input type="checkbox" name="allowhtmlnew" value="yes" <?php echo $checked2?> /><?php echo $lang['texthtml']?><br />
        <input type="checkbox" name="allowsmiliesnew" value="yes" <?php echo $checked3?> /><?php echo $lang['textsmilies']?><br />
        <input type="checkbox" name="allowbbcodenew" value="yes" <?php echo $checked4?> /><?php echo $lang['textbbcode']?><br />
        <input type="checkbox" name="allowimgcodenew" value="yes" <?php echo $checked5?> /><?php echo $lang['textimgcode']?><br />
        <input type="checkbox" name="attachstatusnew" value="on" <?php echo $checked6?> /><?php echo $lang['attachments']?><br />
        </td>
        </tr>
        
        <tr class="tablerow">
        <td class="altbg1"><?php echo $lang['texttheme']?></td>
        <td class="altbg2"><?php echo $themelist?></td>
        </tr>
        
        <tr class="tablerow">
        <td class="altbg1"><?php echo $lang['forumpermissions']?></td>
        <td class="altbg2"><table style="width: 100%;">
        <?php
        $perms = explode(',', $forum['postperm']);
        $statusList = array(
            'Super Administrator'   => 1,
            'Administrator'         => 2,
            'Super Moderator'       => 4,
            'Moderator'             => 8,
            'Member'                => 16,
            'guest'                 => 32);
         ?>
        <tr>
            <td class="tablerow" style="width: 25ex;">&nbsp;</td>
            <td class="category" style="color: <?php echo $THEME['cattext']?>; font-weight: bold;"><?php echo $lang['polls'];   ?></td>
            <td class="category" style="color: <?php echo $THEME['cattext']?>; font-weight: bold;"><?php echo $lang['threads']; ?></td>
            <td class="category" style="color: <?php echo $THEME['cattext']?>; font-weight: bold;"><?php echo $lang['replies']; ?></td>
            <td class="category" style="color: <?php echo $THEME['cattext']?>; font-weight: bold;"><?php echo $lang['view'];    ?></td>
        </tr>
        <?php
        foreach($statusList as $key=>$val) {
            if(!X_SADMIN and $key == 'Super Administrator') {
                $disabled = 'disabled="disabled"';
            } else {
                $disabled = '';
            }
            ?>
            <tr class="tablerow">
                <td class="category" style="color: <?php echo $THEME['cattext']?>; font-weight: bold;"><?php echo ucwords($key);?></td>
                <td class="altbg1 ctrtablerow"><input type="checkbox" name="permsNew[0][]" value="<?php echo $val;?>" <?php echo ((($perms[X_PERMS_POLL]&$val) == $val) ? 'checked="checked"' : ''); ?> <?php echo $disabled;?> /></td>
                <td class="altbg1 ctrtablerow"><input type="checkbox" name="permsNew[1][]" value="<?php echo $val;?>" <?php echo ((($perms[X_PERMS_THREAD]&$val) == $val) ? 'checked="checked"' : ''); ?> <?php echo $disabled;?> /></td>
                <td class="altbg1 ctrtablerow"><input type="checkbox" name="permsNew[2][]" value="<?php echo $val;?>" <?php echo ((($perms[X_PERMS_REPLY]&$val) == $val) ? 'checked="checked"' : ''); ?> <?php echo $disabled;?> /></td>
                <td class="altbg1 ctrtablerow"><input type="checkbox" name="permsNew[3][]" value="<?php echo $val;?>" <?php echo ((($perms[X_PERMS_VIEW]&$val) == $val) ? 'checked="checked"' : ''); ?> <?php echo $disabled;?> /></td>
            </tr>
            <?php
        }
        ?>
        </table>
        </tr>
        <tr class="tablerow">
        <td class="altbg1"><?php echo $lang['textuserlist']?></td>
        <td class="altbg2"><textarea rows="4" cols="30" name="userlistnew"><?php echo $forum['userlist']?></textarea></td>
        </tr>
        <tr class="tablerow">
        <td class="altbg1"><?php echo $lang['forumpw']?></td>
        <td class="altbg2"><input type="text" name="passwordnew" value="<?php echo htmlspecialchars($forum['password'])?>" /></td>
        </tr>
        <tr class="tablerow">
        <td class="altbg1"><?php echo $lang['textdeleteques']?></td>
        <td class="altbg2"><input type="checkbox" name="delete" value="<?php echo $forum['fid']?>" /></td>
        </tr>
        <tr>
        <td class="altbg2 tablerow" align="center" colspan="2"><input type="submit" name="forumsubmit" value="<?php echo $lang['textsubmitchanges']?>" class="submit" /></td>
        </tr>
        </table>
        </td>
        </tr>
        </table>
        </form>
        </td>
        </tr>

        <?php
    } elseif ($_POST['forumsubmit']) {
        if (!isset($fdetails)) {
            $queryforum = $db->query("SELECT fid, type FROM $table_forums WHERE type='forum' OR type='sub'");
            $db->query("DELETE FROM $table_forums WHERE name=''");
            while ($forum = $db->fetch_array($queryforum)) {
                $displayorder = "displayorder$forum[fid]";
                $displayorder = "${$displayorder}";
                $name = "name$forum[fid]";
                $name = "${$name}";
                $self['status'] = "status$forum[fid]";
                $self['status'] = "${$self['status']}";
                $delete = "delete$forum[fid]";
                $delete = isset(${$delete}) ? "${$delete}" : '';
                $moveto = "moveto$forum[fid]";
                $moveto = "${$moveto}";

                if ($delete != "") {
                    $db->query("DELETE FROM $table_forums WHERE (type='forum' OR type='sub') AND fid='$delete'");

                    $querythread = $db->query("SELECT tid, author FROM $table_threads WHERE fid='$delete'");
                    while ($thread = $db->fetch_array($querythread)) {
                        $db->query("DELETE FROM $table_threads WHERE tid='$thread[tid]'");
                        $db->query("DELETE FROM $table_favorites WHERE tid='$thread[tid]'");
                        $db->query("UPDATE $table_members SET postnum=postnum-1 WHERE username='$thread[author]'");

                        $querypost = $db->query("SELECT pid, author FROM $table_posts WHERE tid='$thread[tid]'");
                        while ($post = $db->fetch_array($querypost)) {
                            $db->query("DELETE FROM $table_posts WHERE pid='$post[pid]'");
                            $db->query("UPDATE $table_members SET postnum=postnum-1 WHERE username='$post[author]'");
                        }
                        $db->free_result($querypost);
                    }
                    $db->free_result($querythread);
                }
                $name = addslashes($name);
                $db->query("UPDATE $table_forums SET name='$name', displayorder=".(int)$displayorder.", status='$self[status]', fup=".(int)$moveto." WHERE fid='$forum[fid]'");
            }

            $querygroup = $db->query("SELECT fid FROM $table_forums WHERE type='group'");
            while ($group = $db->fetch_array($querygroup)) {
                $name = "name$group[fid]";
                $name = "${$name}";
                $displayorder = "displayorder$group[fid]";
                $displayorder = "${$displayorder}";
                $self['status'] = "status$group[fid]";
                $self['status'] = "${$self['status']}";
                $delete = "delete$group[fid]";
                $delete = isset(${$delete}) ? "${$delete}" : '';

                if ($delete != "") {
                    $query = $db->query("SELECT fid FROM $table_forums WHERE type='forum' AND fup='$delete'");
                    while ($forum = $db->fetch_array($query)) {
                        $db->query("UPDATE $table_forums SET fup=0 WHERE type='forum' AND fup='$delete'");
                    }

                    $db->query("DELETE FROM $table_forums WHERE type='group' AND fid='$delete'");
                }
                $name = addslashes($name);
                $db->query("UPDATE $table_forums SET name='$name', displayorder=".(int)$displayorder.", status='$self[status]' WHERE fid='$group[fid]'");
            }

            if ($newfname != $lang['textnewforum']) {
                $newfname = addslashes($newfname);
                $db->query("INSERT INTO $table_forums ( type, name, status, lastpost, moderator, displayorder, description, allowhtml, allowsmilies, allowbbcode, userlist, theme, posts, threads, fup, postperm, allowimgcode, attachstatus, password ) VALUES ('forum', '$newfname', '$newfstatus', '', '', ".(int)$newforder.", '', 'no', 'yes', 'yes', '', 0, 0, 0, ".(int)$newffup.", '31,31,31,63', 'yes', 'on', '')");
            }

            if ($newgname != $lang['textnewgroup']) {
                $newgname = addslashes($newgname);
                $db->query("INSERT INTO $table_forums ( type, name, status, lastpost, moderator, displayorder, description, allowhtml, allowsmilies, allowbbcode, userlist, theme, posts, threads, fup, postperm, allowimgcode, attachstatus, password ) VALUES ('group', '$newgname', '$newgstatus', '', '', ".(int)$newgorder.", '', '', '', '', '', 0, 0, 0, 0, '', '', '', '')");
            }

            if ($newsubname != $lang['textnewsubf']) {
                $newsubname = addslashes($newsubname);
                $db->query("INSERT INTO $table_forums ( type, name, status, lastpost, moderator, displayorder, description, allowhtml, allowsmilies, allowbbcode, userlist, theme, posts, threads, fup, postperm, allowimgcode, attachstatus, password ) VALUES ('sub', '$newsubname', '$newsubstatus', '', '', ".(int)$newsuborder.", '', 'no', 'yes', 'yes', '', 0, 0, 0, ".(int)$newsubfup.", '31,31,31,63', 'yes', 'on', '')");
            }

            echo '<tr class="altbg2 tablerow"><td align="center">'.$lang['textforumupdate'].'</td></tr>';
        } else {
            $fdetails = intval($fdetails);
            if(!X_SADMIN) {
                $overrule = array(0,0,0,0);
                $forum = $db->fetch_array($db->query("SELECT postperm FROM $table_forums WHERE fid=$fdetails"));
                $parts = explode(',', $forum['postperm']);
                foreach($parts as $p=>$v) {
                    if($v & 1 == 1) {
                        // super admin status set
                        $overrule[$p] = 1;
                    }
                }
            } else {
                $overrule = array(0,0,0,0);
            }
            $check_vars = array('allowhtmlnew', 'allowsmiliesnew', 'allowbbcodenew', 'allowimgcodenew', 'attachstatusnew');
            foreach ($check_vars as $key) {
                if ($$key != 'on' && $$key != 'yes') {
                    $$key = 'off';
                }
            }

            $namenew = addslashes($namenew);
            $descnew = addslashes($descnew);
            
            $perms = array(0,0,0,0);
            foreach($permsNew as $key=>$val) {
                $perms[$key] = array_sum($val);
                $perms[$key] |= $overrule[$key];
            }
            $perms = implode(',', $perms);

            $db->query("UPDATE $table_forums SET name='$namenew', description='$descnew', allowhtml='$allowhtmlnew', allowsmilies='$allowsmiliesnew', allowbbcode='$allowbbcodenew', theme='$themeforumnew', userlist='$userlistnew', postperm='$perms', allowimgcode='$allowimgcodenew', attachstatus='$attachstatusnew', password='$passwordnew' WHERE fid=$fdetails");
            if ($delete != "") {
                $db->query("DELETE FROM $table_forums WHERE fid='$delete'");
            }

            echo '<tr class="tablerow altbg2"><td align="center">'.$lang['textforumupdate'].'</td></tr>';
        }
    }
}

if ($action == "mods") {
    if (!isset($_POST['modsubmit'])) {
        ?>

        <tr class="altbg2">
        <td>
        <form method="post" action="cp.php?action=mods">
        <table cellspacing="0" cellpadding="0" border="0" width="90%" align="center">
        <tr>
        <td style="background-color: <?php echo $THEME['bordercolor']?>">
        <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
        <tr class="category">
        <td><strong><font style="color: <?php echo $THEME['cattext']?>"><?php echo $lang['textforum']?></font></strong></td>
        <td><strong><font style="color: <?php echo $THEME['cattext']?>"><?php echo $lang['textmoderator']?></font></strong></td>
        </tr>

        <?php
        $oldfid = 0;
        $query = $db->query("SELECT f.moderator, f.name, f.fid, c.name as cat_name, c.fid as cat_fid FROM $table_forums f LEFT JOIN $table_forums c ON (f.fup = c.fid) WHERE (c.type='group' AND f.type='forum') OR (f.type='forum' AND f.fup='') ORDER BY c.displayorder, f.displayorder");
        while ($forum = $db->fetch_array($query)) {
            if ($oldfid != $forum['cat_fid']) {
                $oldfid = $forum['cat_fid']
                ?>
                <tr class="altbg1 tablerow">
                <td colspan="2"><strong><?php echo stripslashes($forum['cat_name'])?></strong></td>
                </tr>
                <?php
            }
            ?>

            <tr class="altbg2 tablerow">
            <td><?php echo stripslashes($forum['name'])?></td>
            <td><input type="text" name="mod[<?php echo $forum['fid']?>]"" value="<?php echo $forum['moderator']?>" /></td>
            </tr>

            <?php
            $querys = $db->query("SELECT name, fid, moderator FROM $table_forums WHERE fup='$forum[fid]' AND type='sub'");
            while ($sub = $db->fetch_array($querys)) {
                ?>
                <tr class="altbg2 tablerow">
                <td><?php echo $lang['4spaces']?><?php echo $lang['4spaces']?><em><?php echo stripslashes($sub['name'])?></em></td>
                <td><input type="text" name="mod[<?php echo $sub['fid']?>]"" value="<?php echo $sub['moderator']?>" /></td>
                </tr>
                <?php
            }
        }
        ?>
        <tr>
        <td colspan="2" class="tablerow altbg1"><span class="smalltxt"><?php echo $lang['multmodnote']?></span></td>
        </tr>
        <tr>
        <td align="center" colspan="2" class="tablerow altbg2"><input type="submit" class="submit" name="modsubmit" value="<?php echo $lang['textsubmitchanges']?>" /></td>
        </tr>
        </table>
        </td>
        </tr>
        </table>
        </form>
        </td>
        </tr>

        <?php
    } else {
        if (is_array($mod)) {
            foreach ($mod as $fid=>$mods) {
                $db->query("UPDATE $table_forums SET moderator='$mods' WHERE fid='$fid'");
            }
        }

        echo '<tr class="tablerow altbg2"><td align="center">'.$lang['textmodupdate'].'</td></tr>';
    }
}

if ( $action == "members") {
    if (!isset($_POST['membersubmit'])) {
        if (!isset($members)) {
            ?>

            <tr class="altbg2">
            <td>
            <form method="post" action="cp.php?action=members&amp;members=search">
            <table cellspacing="0" cellpadding="0" border="0" width="90%" align="center">
            <tr>
            <td style="background-color: <?php echo $THEME['bordercolor']?>">
            <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
            <tr>
            <td class="category" colspan="2"><font style="color: <?php echo $THEME['cattext']?>"><strong><?php echo $lang['textmembers']?></strong></font></td>
            </tr>
            <tr>
            <td class="tablerow altbg1" width="22%"><?php echo $lang['textsrchusr']; ?></td>
            <td class="tablerow altbg2"><input type="text" name="srchmem" /></td>
            </tr>
            <tr>
            <td class="tablerow altbg1" width="22%"><?php echo $lang['textwithstatus']?></td>
            <td class="tablerow altbg2">
            <select name="srchstatus">
            <option value="0"><?php echo $lang['anystatus']?></option>
            <option value="Super Administrator"><?php echo $lang['superadmin']?></option>
            <option value="Administrator"><?php echo $lang['textadmin']?></option>
            <option value="Super Moderator"><?php echo $lang['textsupermod']?></option>
            <option value="Moderator"><?php echo $lang['textmod']?></option>
            <option value="Member"><?php echo $lang['textmem']?></option>
            <option value="Banned"><?php echo $lang['textbanned']?></option>
            </select>
            </td>
            </tr>
            <tr>
            <td class="altbg2 tablerow" align="center" colspan="2"><input type="submit" class="submit" value="<?php echo $lang['textgo']?>" /></td>
            </tr>
            </table>
            </td>
            </tr>
            </table>
            </form>
            </td>
            </tr>

            <?php
        } elseif ($members == "search") {
            ?>

            <tr class="altbg2">
            <td align="center">
            <form method="post" action="cp.php?action=members">
            <table cellspacing="0" cellpadding="0" border="0" width="91%" align="center">
            <tr>
            <td style="background-color: <?php echo $THEME['bordercolor']?>">
            <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
            <tr class="category">
            <td align="center" width="3%"><strong><font style="color: <?php echo $THEME['cattext']?>"><?php echo $lang['textdeleteques']?></font></strong></td>
            <td><strong><font style="color: <?php echo $THEME['cattext']?>"><?php echo $lang['textusername']?></font></strong></td>
            <td><strong><font style="color: <?php echo $THEME['cattext']?>"><?php echo $lang['textnewpassword']?></font></strong></td>
            <td><strong><font style="color: <?php echo $THEME['cattext']?>"><?php echo $lang['textposts']?></font></strong></td>
            <td><strong><font style="color: <?php echo $THEME['cattext']?>"><?php echo $lang['textstatus']?></font></strong></td>
            <td><strong><font style="color: <?php echo $THEME['cattext']?>"><?php echo $lang['textcusstatus']?></font></strong></td>
            <td><strong><font style="color: <?php echo $THEME['cattext']?>"><?php echo $lang['textbanfrom']?></font></strong></td>
            </tr>

            <?php
            if ($srchstatus == "0") {
                $query = $db->query("SELECT * FROM $table_members WHERE username LIKE '%$srchmem%' ORDER BY username");
            } else {
                $query = $db->query("SELECT * FROM $table_members WHERE username LIKE '%$srchmem%' AND status='$srchstatus' ORDER BY username");
            }

            $sadminselect   = "";
            $adminselect    = "";
            $smodselect     = "";
            $modselect      = "";
            $memselect      = "";
            $banselect      = "";
            $noban          = "";
            $u2uban         = "";
            $postban        = "";
            $bothban        = "";

            while ($member = $db->fetch_array($query)) {
                switch ($member['status']) {
                    case 'Super Administrator':
                        $sadminselect = $selHTML;
                        break;

                    case 'Administrator':
                        $adminselect = $selHTML;
                        break;

                    case 'Super Moderator':
                        $smodselect = $selHTML;
                        break;

                    case 'Moderator':
                        $modselect = $selHTML;
                        break;

                    case 'Member':
                        $memselect = $selHTML;
                        break;

                    case 'Banned':
                        $banselect = $selHTML;
                        break;

                    default:
                        $memselect = $selHTML;
                        break;
                }

                switch ($member['ban']) {
                    case 'u2u':
                        $u2uban = $selHTML;
                        break;

                    case 'posts':
                        $postban = $selHTML;
                        break;

                    case 'both':
                        $bothban = $selHTML;
                        break;

                    default:
                        $noban = $selHTML;
                        break;
                }
                ?>

                <tr class="altbg2 tablerow">
                <td align="center"><input type="checkbox" name="delete<?php echo $member['uid']?>" onclick="confirmActionCheckbox('<?php echo $lang['confirmDeleteUser']?>', this, true, false);" value="<?php echo $member['uid']?>" /></td>
                <td><a href="member.php?action=viewpro&amp;member=<?php echo rawurlencode($member['username'])?>"><?php echo $member['username']?></a>
                <br /><a href="javascript:confirmAction('<?php echo addslashes($lang['confirmDeletePosts']);?>', 'cp.php?action=deleteposts&amp;member=<?php echo rawurlencode($member['username'])?>', false);"><strong><?php echo $lang['cp_deleteposts']?></strong></a>
                </td>
                <td><input type="text" size="12" name="pw<?php echo $member['uid']?>"></td>
                <td><input type="text" size="3" name="postnum<?php echo $member['uid']?>" value="<?php echo $member['postnum']?>"></td>
                <td><select name="status<?php echo $member['uid']?>">
                <option value="Super Administrator" <?php echo $sadminselect?>><?php echo $lang['superadmin']?></option>
                <option value="Administrator" <?php echo $adminselect?>><?php echo $lang['textadmin']?></option>
                <option value="Super Moderator" <?php echo $smodselect?>><?php echo $lang['textsupermod']?></option>
                <option value="Moderator" <?php echo $modselect?>><?php echo $lang['textmod']?></option>
                <option value="Member" <?php echo $memselect?>><?php echo $lang['textmem']?></option>
                <option value="Banned" <?php echo $banselect?>><?php echo $lang['textbanned']?></option>
                </select></td>
                <td><input type="text" size="16" name="cusstatus<?php echo $member['uid']?>" value="<?php echo htmlspecialchars(stripslashes($member['customstatus']))?>" /></td>
                <td><select name="banstatus<?php echo $member['uid']?>">
                <option value="" <?php echo $noban?>><?php echo $lang['noban']?></option>
                <option value="u2u" <?php echo $u2uban?>><?php echo $lang['banu2u']?></option>
                <option value="posts" <?php echo $postban?>><?php echo $lang['banpost']?></option>
                <option value="both" <?php echo $bothban?>><?php echo $lang['banboth']?></option>
                </select></td>
                </tr>

                <?php
                $sadminselect   = "";
                $adminselect    = "";
                $smodselect     = "";
                $modselect      = "";
                $memselect      = "";
                $banselect      = "";
                $noban          = "";
                $u2uban         = "";
                $postban        = "";
                $bothban        = "";
            }
            ?>

            <tr>
            <td class="altbg2 tablerow" align="center" colspan="7"><input type="submit" class="submit" name="membersubmit" value="<?php echo $lang['textsubmitchanges']?>" /><input type="hidden" name="srchmem" value="<?php echo $srchmem?>" /><input type="hidden" name="srchstatus" value="<?php echo $srchstatus?>" /></td>
            </tr>
            </table>
            </td>
            </tr>
            </table>
            </form>
            </td>
            </tr>

            <?php
        }
    } elseif ($_POST['membersubmit']) {
        /*
        Get the uid first Super Administrator (the first to register and thus most likely to be the 'top level' admin) to compare against the delete uid. This member should *never* be deleted this way.
        */
        $query = $db->query("SELECT MIN(`uid`) FROM `" . $table_members . "` WHERE `status`='Super Administrator'");
        $sa_uid = $db->result($query, 0);
        $db->free_result($query);

        if ($srchstatus == "0") {
            $query = $db->query("SELECT uid, username, password, status FROM $table_members WHERE username LIKE '%$srchmem%'");
        } else {
            $query = $db->query("SELECT uid, username, password, status FROM $table_members WHERE username LIKE '%$srchmem%' AND status='$srchstatus'");
        }

        while ($mem = $db->fetch_array($query)) {
            $to['status'] = 'status'.$mem['uid'];
            $to['status'] = ${$to['status']};

            // Fix a race condition noted by wrdyjoey
            if ( trim($to['status']) == '' ) {
                $to['status'] = 'Member';
            }

            $origstatus = '';
            $origstatus = $mem['status'];

            $banstatus = "banstatus$mem[uid]";
            $banstatus = "${$banstatus}";

            $cusstatus = "cusstatus$mem[uid]";
            $cusstatus = "${$cusstatus}";

            $pw = "pw$mem[uid]";
            $pw = "${$pw}";

            $postnum = "postnum$mem[uid]";
            $postnum = "${$postnum}";

            $delete = "delete$mem[uid]";
            $delete = "${$delete}";

            if ($pw != "") {
                $newpw = md5(trim($pw));
                $queryadd = " , password='$newpw'";
            } else {
                $newpw = $mem['password'];
                $queryadd = " , password='$newpw'";
            }

            if (!X_SADMIN && ($origstatus == "Super Administrator" || $to['status'] == "Super Administrator")) {
                continue;
            }

            if($origstatus == 'Super Administrator' && $to['status'] != 'Super Administrator') {
                if($db->result($db->query("SELECT count(uid) FROM $table_members WHERE status='Super Administrator'"), 0) == 1) {
                    error($lang['lastsadmin'], false, '</td></tr></table></td></tr></table><br />');
                }
            }

            if ($delete != "" && $delete != $self['uid'] && $delete != $sa_uid) {
                $db->query("DELETE FROM $table_members WHERE uid='$delete'");
            } else {
                if (strpos($pw, '"') !== false || strpos($pw, "'") !== false) {
                    $lang['textmembersupdate'] = $mem['username'].': '.$lang['textpwincorrect'];
                } else {
                    $newcustom = addslashes($cusstatus);
                    $db->query("UPDATE $table_members SET ban='$banstatus', status='$to[status]', postnum='$postnum', customstatus='$newcustom'$queryadd WHERE uid='$mem[uid]'");
                    $newpw="";
                }
            }
        }

        echo '<tr class="tablerow altbg2"><td align="center">'.$lang['textmembersupdate'].'</td></tr>';
    }
}

if ($action == "ipban") {
    if (!isset($_POST['ipbansubmit'])) {
    
        echo '<tr class="altbg2"><td align="center">';
        echo '<form name="ipban" method="post" action="cp.php?action=ipban">';
        echo '<table cellspacing="0" cellpadding="0" border="0" width="550" align="center">';
        echo '<tr><td style="background-color: '.$THEME['bordercolor'].'">';
        echo '<table border="0" cellspacing="'.$THEME['borderwidth'].'" cellpadding="'.$THEME['tablespace'].'" width="100%"><tr class="category">';
        
        echo '<td><font style="color: '.$THEME['cattext'].'; font-weight: bold;">'.$lang['textdeleteques'].'</font></td>';
        echo '<td><font style="color: '.$THEME['cattext'].'; font-weight: bold;">'.$lang['textip'].':</font></td>';
        echo '<td><font style="color: '.$THEME['cattext'].'; font-weight: bold;">'.$lang['textipresolve'].':</font></td>';
        echo '<td><font style="color: '.$THEME['cattext'].'; font-weight: bold;">'.$lang['textadded'].'</font></td></tr>';
        
        $query = $db->query("SELECT * FROM $table_banned ORDER BY dateline");
        while ($ipadr = $db->fetch_array($query)) {

            for ($i=1; $i<=4; ++$i) {
                $j = "ip" . $i;
                if ( $ipadr[$j] == -1) {
                    $ipadr[$j] = "*";
                }
            }
            $ipdate = printGmDate($ipadr['dateline']).'&nbsp;'.$lang['textat'].'&nbsp;'.printGmTime($ipadr['dateline']);
            $theip = "$ipadr[ip1].$ipadr[ip2].$ipadr[ip3].$ipadr[ip4]";
            
            echo '<tr class="altbg1"><td class="tablerow" align="center"><input type="checkbox" name="delete['.$ipadr['id'].']" value="1" /></td>';
            echo '<td class="tablerow">'.$theip.'</td>';
            echo '<td class="tablerow">'.@gethostbyaddr($theip).'</td>';
            echo '<td class="tablerow">'.$ipdate.'</td></tr>';
        }

        $query = $db->query("SELECT id FROM $table_banned WHERE (ip1='$ips[0]' OR ip1='-1') AND (ip2='$ips[1]' OR ip2='-1') AND (ip3='$ips[2]' OR ip3='-1') AND (ip4='$ips[3]' OR ip4='-1')");
        $result = $db->fetch_array($query);
        if ($result) {
            $warning = $lang['ipwarning'];
        } else {
            $warning = '';
        }
        $newipmsg = $lang['textnewip'];
        
        echo '</table></td></tr></table><br />'; // End Current IP List
        
        echo '<table cellspacing="0" cellpadding="0" border="0" width="550" align="center">';
        echo '<tr><td style="background-color: '.$THEME['bordercolor'].'">';
        echo '<table border="0" cellspacing="'.$THEME['borderwidth'].'" cellpadding="'.$THEME['tablespace'].'" width="100%">';
        echo '<tr style="background-color: '.$THEME['bordercolor'].'">';
        echo '<td colspan="4" class="category"><font style="color: '.$THEME['cattext'].'; font-weight: bold;">'.$newipmsg.'</font></td></tr>';
        echo '<tr><td class="altbg2"><input type="text" name="newip1" size="3" maxlength="3" onKeyUp="return autotab(this, document.ipban.newip2);" />.<input type="text" name="newip2" size="3" maxlength="3" onKeyUp="return autotab(this, document.ipban.newip3);" />.<input type="text" name="newip3" size="3" maxlength="3" onKeyUp="return autotab(this, document.ipban.newip4);" />.<input type="text" name="newip4" size="3" maxlength="3"  /></td>';
        echo '<td class="altbg2" colspan="3"><font class="smalltxt">'.$lang['multipnote'].'</font></td></tr>';
        echo '<tr><td class="altbg2" colspan="4" align="center"><font class="smalltxt">'.$lang['currentip'].'&nbsp;<strong>'.$onlineip.'</strong>'.$warning.'</font></td>';
        echo '</tr></table></td></tr></table><br />';
        echo '<div align="center"><input type="submit" class="submit" name="ipbansubmit" value="'.$lang['textsubmitchanges'].'" /></div></form></td></tr>'; // Submit changes, and end new IPs

    } else {
        if ( isset($delete) ) {
            $dels = array();
            foreach($delete as $id=>$del) {
                if($del == 1) {
                    $dels[] = $id;
                }
            }
            if(count($dels) > 0) {
                $dels = implode(',', $dels);
                $db->query("DELETE FROM $table_banned WHERE id IN($dels)");
            }
        }
        $self['status'] = $lang['textipupdate'];

        if ($newip1 != "" || $newip2 != "" || $newip3 != "" || $newip4 != "") {
            $invalid = 0;

            for ($i=1;$i<=4 && !$invalid;++$i) {
                $newip = "newip$i";
                $newip = "${$newip}";
                $newip = trim($newip);

                if ($newip == "*") {
                    $ip[$i] = -1;
                } elseif (preg_match("#^[0-9]+$#", $newip)) {
                    $ip[$i] = $newip;
                } else {
                    $invalid = 1;
                }
            }

            if ($invalid) {
                $self['status'] = $lang['invalidip'];
            } else {
                if ($ip[1] == '-1' && $ip[2] == '-1' && $ip[3] == '-1' && $ip[4] == '-1') {
                    $self['status'] = $lang['impossiblebanall'];
                } else {
                    $query = $db->query("SELECT id FROM $table_banned WHERE (ip1='$ip[1]' OR ip1='-1') AND (ip2='$ip[2]' OR ip2='-1') AND (ip3='$ip[3]' OR ip3='-1') AND (ip4='$ip[4]' OR ip4='-1')");
                    $result = $db->fetch_array($query);
                    if ($result) {
                        $self['status'] = $lang['existingip'];
                    } else {
                        $query = $db->query("INSERT INTO $table_banned (ip1, ip2, ip3, ip4, dateline) VALUES ('$ip[1]', '$ip[2]', '$ip[3]', '$ip[4]', $onlinetime)");
                    }
                }
            }
        }

        echo '<tr class="altbg2"><td align="center" class="tablerow">'.$self['status'].'</td></tr>';
    }
}

if ($action == "deleteposts") {
    $queryd = $db->query("DELETE FROM $table_posts WHERE author='$member'");
    $queryt = $db->query("SELECT * FROM $table_threads");
    while($threads = $db->fetch_array($queryt)) {
        $query = $db->query("SELECT COUNT(tid) FROM $table_posts WHERE tid=$threads[tid]");
        $replynum = $db->result($query, 0);
        $replynum--;
        $db->query("UPDATE $table_threads SET replies=replies-1 WHERE tid=$threads[tid]");
        $db->query("DELETE FROM $table_threads WHERE author='$member'");
    }
}

if ($action == "upgrade") {
    if (!X_SADMIN) {
        error($lang['superadminonly'], false, '</td></tr></table></td></tr></table><br />');
    }

    if (isset($_POST['upgradesubmit'])) {
        if (isset($_FILES['sql_file'])) {
            $add = get_attached_file($_FILES['sql_file'], 'on');
            if ( $add !== false) {
                $upgrade .= $add;
            }
        }

        $upgrade = str_replace('$table_', $tablepre, $upgrade);

        $explode = explode(";", $upgrade);
        $count = count($explode);

        if (strlen(trim($explode[$count-1])) == 0) {
            unset($explode[$count-1]);
            $count--;
        }

        echo "</table></td></tr></table>";

        for ($num=0;$num<$count;$num++) {
            $explode[$num] = stripslashes($explode[$num]);

            if ($allow_spec_q !== true) {
                if (strtoupper(substr(trim($explode[$num]), 0, 3)) == 'USE' || strtoupper(substr(trim($explode[$num]), 0, 14)) == 'SHOW DATABASES') {
                    error($lang['textillegalquery'], false, '</td></tr></table></td></tr></table><br />');
                }
            }

            if ($explode[$num] != "") {
                $query = $db->query($explode[$num], true);
            }

            echo '<br />';
            ?>

            <table cellspacing="0" cellpadding="0" border="0" style="width: <?php echo $THEME['tablewidth']?>" align="center">
            <tr>
            <td style="background-color: <?php echo $THEME['bordercolor']?>">
            <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
            <tr class="altbg2 tablerow">
            <td colspan="<?php echo $db->num_fields($query)?>"><strong><?php echo $lang['upgraderesults']?></strong>&nbsp;<?php echo $explode[$num]?>

            <?php
            $xn = strtoupper($explode[$num]);
            if (strpos($xn, 'SELECT') !== false || strpos($xn, 'SHOW') !== false || strpos($xn, 'EXPLAIN') !== false || strpos($xn, 'DESCRIBE') !== false) {
                dump_query($query, true);
            } else {
                $selq=false;
            }
            ?>

            </td>
            </tr>
            </td>
            </tr>
            </table>
            </td>
            </tr>
            </table>

            <?php
        }
        ?>

        <br />
        <table cellspacing="0" cellpadding="0" border="0" style="width: <?php echo $THEME['tablewidth']?>" align="center">
        <tr>
        <td style="background-color: <?php echo $THEME['bordercolor']?>">
        <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
        <tr class="altbg2 tablerow">
        <td><?php echo $lang['upgradesuccess']?></td>
        </tr>
        </table>
        </td>
        </tr>
        </table>

        <?php
        end_time();
        eval("echo \"".template("footer")."\";");
        exit();
    } else {
        ?>

        <tr class="altbg2">
        <td align="center">
        <form method="post" action="cp.php?action=upgrade" enctype="multipart/form-data">
        <table cellspacing="0" cellpadding="0" border="0" width="550" align="center">
        <tr>
        <td style="background-color: <?php echo $THEME['bordercolor']?>">
        <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
        <tr>
        <td class="tablerow altbg1" colspan="2"><strong><?php echo $lang['textupgrade']?></strong></td>
        </tr>
        <tr>
        <td class="altbg2 tablerow" colspan="2"><?php echo $lang['upgrade']?></td>
        </tr>
        <tr>
        <td class="altbg1 tablerow" valign="top"><textarea cols="85" rows="10" name="upgrade"></textarea></td>
        </tr>
        <tr>
        <td class="altbg2 tablerow" colspan="2"><input type="file" name="sql_file" /></td>
        </tr>
        <tr>
        <td class="altbg1 tablerow" colspan="2"><?php echo $lang['upgradenote']?></td>
        </tr>
        <tr>
        <td class="ctrtablerow altbg2" colspan="2"><input type="submit" class="submit" name="upgradesubmit" value="<?php echo $lang['textsubmitchanges']?>" /></td>
        </tr>
        </table>
        </td>
        </tr>
        </table>
        </form>
        </td>
        </tr>

        <?php
    }
}

if ($action == "search") {
    if (isset($_POST['searchsubmit'])) {
        $found = 0;
        $list = array();
        if ($userip && !empty($userip)) {
            $query = $db->query("SELECT * FROM $table_members WHERE regip = '$userip'");
            while ($users = $db->fetch_array($query)) {
                $link = "./member.php?action=viewpro&amp;member=$users[username]";
                $list[] = "<a href = \"$link\">$users[username]<br />";
                $found++;
            }
        }

        if ($postip && !empty($postip)) {
            $query = $db->query("SELECT * FROM $table_posts WHERE useip = '$postip'");
            while ($users = $db->fetch_array($query)) {
                $link = "./viewthread.php?tid=$users[tid]#pid$users[pid]";
                if (!empty($users[subject])) {
                    $list[] = "<a href = \"$link\">$users[subject]<br />";
                } else {
                    $list[] = "<a href = \"$link\">- - No subject - -<br />";
                }
                $found++;
            }
        }

        if ($profileword && !empty($profileword)) {
            $query = $db->query("SELECT * FROM $table_members WHERE bio = '%$profileword%'");
            while ($users = $db->fetch_array($query)) {
                $link = "./member.php?action=viewpro&amp;member=$users[username]";
                $list[] = "<a href = \"$link\">$users[username]<br />";
                $found++;
            }
        }

        if ($postword && !empty($postword)) {
            $query = $db->query("SELECT * FROM $table_posts WHERE subject LIKE '%".$postword."%' OR message LIKE '%".$postword."%'");
            while ($users = $db->fetch_array($query)) {
                $link = "./viewthread.php?tid=$users[tid]#pid$users[pid]";
                if (!empty($users[subject])) {
                    $list[] = "<a href = \"$link\">$users[subject]<br />";
                } else {
                    $list[] = "<a href = \"$link\">- - No subject - -<br />";
                }
                $found++;
            }
        }
        ?>

        <tr class="altbg2 tablerow">
        <td align="left" colspan="2">
        <strong><?php echo $found?></strong> <?php echo $lang['beenfound']?>
        <br />
        </td>
        </tr>

        <?php
        foreach ($list as $num=>$val) {
            ?>
            <tr class="tablerow" width="5%">
            <td align="left" class="altbg2">
            <strong><?php echo ($num+1)?>.</strong>
            </td>
            <td align="left" width="95%" class="altbg1">
            <?php echo $val?>
            </td>
            </tr>

            <?php
         }
    } else {
        ?>

        <tr class="altbg2">
        <td align="center">
        <form method="post" action="cp.php?action=search">
        <table cellspacing="0" cellpadding="0" border="0" width="550" align="center">
        <tr>
        <td style="background-color: <?php echo $THEME['bordercolor']?>">
        <table border="0" cellspacing="<?php echo $THEME['borderwidth']?>" cellpadding="<?php echo $THEME['tablespace']?>" width="100%">
        <tr class="category">
        <td colspan=2><strong><font style="color: <?php echo $THEME['cattext']?>"><?php echo $lang['insertdata']?>:</font></strong></td>
        </tr>
        <tr class="altbg2 tablerow">
        <td valign="top"><div align="center"><br />
        <?php echo $lang['userip']?><br /><input type="text" name="userip" /></input><br /><br />
        <?php echo $lang['postip']?><br /><input type="text" name="postip" /></input><br /><br />
        <?php echo $lang['profileword']?><br /><input type="text" name="profileword" /></input><br /><br />
        <?php echo $lang['postword']?><br />

        <?php
        $query = $db->query("SELECT find FROM $table_words");
        $select = "<select name=\"postword\"><option value=\"\"></option>";
        while ($temp = $db->fetch_array($query)) {
            $select .= "<option value=\"$temp[find]\">$temp[find]</option>";
        }
        $select .= "</select>";
        echo $select;
        ?>

        <br /><br />
        <div align="center"><br /><input type="submit" class="submit" name="searchsubmit" value="Search now" /><br /><br /></div>
        </td>
        </tr>
        </table>
        </td></tr></table>
        </form>
        </td>
        </tr>
        <?php

    }
}

echo "</table></td></tr></table>";

end_time();

eval("echo (\"".template('footer')."\");");
?>
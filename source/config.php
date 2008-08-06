<?php
/**
 * eXtreme Message Board
 * XMB 1.9.11 Alpha Zero - This software should not be used for any purpose after 31 August 2008.
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

if (!defined('IN_CODE')) {
    exit("Not allowed to run this file directly.");
}

// Database connection settings
    $dbname         = 'DB/NAME';   // Name of your database
    $dbuser         = 'DB/USER';   // Username used to access it
    $dbpw           = 'DB/PW';     // Password used to access it
    $dbhost         = 'localhost'; // Database host, usually 'localhost'
    $database       = 'mysql';     // Database type, currently only mysql is supported.
    $pconnect       = 0;           // Persistent connection, 1 = on, 0 = off, use if 'too many connections'-errors appear

// Table Settings
    $tablepre       = 'TABLE/PRE'; // XMB will prefix each table name with the string you specify here.  'xmb_' is a common choice.

// Path-settings
    // In full_url, put the full URL you see when you go to your boards, WITHOUT the filename though!!
    // And please, remember to add the / at the end...
    $full_url       = 'FULLURL';

// Other settings
    // There are situations where you don't want to see the <!-- template start: index -->...<!-- template end: index -->
    // tags around each template. In those cases, change the following to false, or true to turn it back on.
    // Default value: false;
    $comment_output = FALSE;

    // Alternative mailer
    // some hosts prevent the direct use of sendmail, which php uses to send out emails by default.
    // To get around this, we have included code which will contact a separate SMTP server of your
    // choice, and will send the mail trough that. The following mailer-options are available:
    // 'default'        => php's internal mail() function. No additional values need to be set:
    //                     (does not require a username/password/host/port)
    // 'socket_SMTP'    => a connection to the SMTP server trough sockets. Requires the username,
    //                     password, host and port values to be entered correctly to work.
    $mailer['type']     = 'default';

    // mailer-options (for socket_SMTP only, currently)
    $mailer['username'] = 'MAILER_USER';
    $mailer['password'] = 'MAILER_PASS';
    $mailer['host']     = 'MAILER_HOST';
    $mailer['port']     = 'MAILER_PORT';

// Plugin Settings
    $i = 1;
    // Plugins are the links in the navigation part of the Header. Plugins built-in by default include Search, FAQ, Member List, Today's Posts, Stats and Board Rules.
    // To add extra plugins (links of your own), just edit the code between Start Plugin Code and End Plugin Code. If you with to add more than one, simply copy that block, paste it and add the second one.

    // Start Plugin code
    $plugname[$i]    = '';    // This is the name of your plugin. eg. Avatar Gallery, TeddyBear, etc.
    $plugurl[$i]     = '';    // This is the location, link, or URL to the plugin
    $plugadmin[$i]   = false; // Is this plugin only for admins? Set to true if the plugin can only be seen/used by (super-)admins, false when it's can be used by anyone
    $plugimg[$i]     = '';    // This is the path (full URL) to the image to show in front of the text.
    $i++;
    // End plugin code.

    // Start Plugin code for plugin #2
    $plugname[$i]    = '';    // This is the name of your plugin. eg. Avatar Gallery, TeddyBear, etc.
    $plugurl[$i]     = '';    // This is the location, link, or URL to the plugin
    $plugadmin[$i]   = false; // Is this plugin only for admins? Set to true if the plugin can only be seen/used by (super-)admins, false when it's can be used by anyone
    $plugimg[$i]     = '';    // This is the path (full URL) to the image to show in front of the text.
    $i++;
    // End plugin code for plugin #2

    // To make multiple plugins, copy and paste this plugin-code, so you have multiple entries.

// Registration settings
    /***************
     * The ipcheck, checks if your IP is a valid IPv4 or IPv6 type, if none of these, it will kill.
     * this might shut a few users out, so you can turn it off by changing the $ipcheck variable to 'off'
     ****************
     * The allow_spec_q variable specifies if Special queries (eg. USE database and SHOW DATABASES) are allowed.
     * By default, they are not, meaning $allow_spec_q = FALSE;
     * To allow them, change $allow_spec_q to true ($allow_spec_q = true;)
     ****************
     * The show_full_info variable lets you decide wether to show the Build and Alpha/Beta/SP markings in the HTML or not.
     * Change the value to true to show them, or false to turn them off.
     * Default = TRUE;
     ****************/

    $ipcheck        = 'off';
    $allow_spec_q   = FALSE;
    $show_full_info = TRUE;

// Resolving serveral modes (currently, 2)
// Debug-mode
    /*
    / To turn on DEBUG mode (you can then see ALL queries done at the bottom of each screen (except buddy-list & u2u)
    / just uncomment this variable. These queries are ONLY visible to the user currently loading that page
    / and ONLY visible to Super Administrators
    /
    / SECURITY NOTICE: DO NOT COMMENT OUT UNLESS YOU KNOW WHAT YOU'RE DOING!
    */
    define('DEBUG', FALSE);
    // define('DEBUG', TRUE);
    //
    /*
    / Comment first line and uncomment second line to use debug mode (1.9+ only). Only one define can be
    / active as define is immutable once set.
    */
    //
    /*
    / To enable logging of all MySQL errors (necessary in the case of registration or login errors), comment first
    / line and uncomment second line.  Note the log file will be visible to the public unless it is protected
    / by your web server configuration.  The file name will be 'error_log' unless you change the PHP configuration.
    */
    define('LOG_MYSQL_ERRORS', FALSE);
    // define('LOG_MYSQL_ERRORS', TRUE);
?>
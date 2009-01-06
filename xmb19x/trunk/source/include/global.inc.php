<?php
/**
 * eXtreme Message Board
 * XMB 1.9.11 Beta 3 - This software should not be used for any purpose after 30 February 2009.
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

if (!defined('IN_CODE')) {
    header('HTTP/1.0 403 Forbidden');
    exit("Not allowed to run this file directly.");
}

// This is an old compatibility trick, kept in case superglobals are disabled.
if (!isset($_SERVER)) {
    $_GET = &$HTTP_GET_VARS;
    $_POST = &$HTTP_POST_VARS;
    $_ENV = &$HTTP_ENV_VARS;
    $_SERVER = &$HTTP_SERVER_VARS;
    $_COOKIE = &$HTTP_COOKIE_VARS;
    $_FILES = &$HTTP_POST_FILES;
    $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
}

// make sure magic_quotes_runtime doesn't kill XMB
@set_magic_quotes_runtime(0);

// force registerglobals
if (is_array($_REQUEST)) {
    extract($_REQUEST, EXTR_SKIP);
}
?>

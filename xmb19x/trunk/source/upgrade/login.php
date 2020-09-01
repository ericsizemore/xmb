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

define('ROOT', '../');
define('X_SCRIPT', 'upgrade.php');
define('X_SET_HEADER', 1);
define('X_SET_JS', 2);

require ROOT.'header.php';

$username = postedVar( 'username', '', TRUE, FALSE );

if ( strlen( $username ) == 0 ) {
    put_cookie( 'xmbuser' );  // Make sure user is logged out.
    ?>
    <form method="post" action="">
        <label>Username: <input type="text" name="username" /></label><br />
        <label>Password: <input type="password" name="password" /></label><br />
        <input type="submit" />
    </form>
    <?php
} else if ( ! X_SADMIN ) { {
    echo "This script may be run only by a Super Administrator.<br />Please <a href='{$full_url}upgrade/login.php'>Try Again</a>.<br />";
    trigger_error('Upgrade login failure by '.$_SERVER['REMOTE_ADDR'], E_USER_ERROR);
} else {
    if ( $SETTINGS['schema_version'] >= 5 ) {
        // Already logged in by \XMB\Session\Manager
    } else {
        put_cookie( 'xmbuser', $username );
        put_cookie( 'xmbpw', md5( $_POST['password'] ) );
    }

    echo "Cookies set.  <a href='{$full_url}upgrade/'>Return to upgrade.</a>";
}

?>

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

declare(strict_types=1);

namespace XMB\Session;

if (!defined('IN_CODE')) {
    header('HTTP/1.0 403 Forbidden');
    exit("Not allowed to run this file directly.");
}

/**
 * Session Data objects are used to pass results between functions.
 *
 * @since 1.9.12
 */
class Data {
    public $member;    // Must be the member record array from the database, or an empty array.
    public $permanent; // True if the session should be saved by the client, otherwise false.
    public $status;    // Session input level.  Must be 'good', 'bad', or 'none'.
    
    public function __construct() {
        $this->member = [];
        $this->permanent = false;
        $this->status = 'none';
    }
}

/**
 * The Session Manager provides Session Data, and it coordinates authentication
 * and persistence of browser tokens.
 *
 * The actual mechanisms of authentication and session persistence are defined
 * in separate Mechanism classes to create a high degree of flexibility.
 *
 * @since 1.9.12
 */
class Manager {
    private $mechanisms;
    private $status;    // Login failure code, or 'good'.
    private $saved;     // Data object.
    
    /**
     * @param string $mode Must be one of 'login', 'logout', or 'resume'
     */
    public function __construct(string $mode) {
        $this->mechanisms = [new FormsAndCookies];
        $this->status = '';
        
        switch ($mode) {
        case 'login':
            $this->login();
            break;
        case 'logout':
            $this->logout();
            break;
        case 'resume':
        default:
            $this->resume();
            break;
        }
    }
    
    /**
     * Session Status
     *
     * Returns 'good' when the login mode or resume mode is successful.
     * Otherwise, various error codes that must be handled by the caller.
     */
    public function getStatus(): string {
        return $this->status;
    }

    /**
     * Member Record
     *
     * Provides the member array from the database, or an empty array.
     */
    public function getMember(): array {
        return $this->saved->member;
    }
    
    /**
     * Deletes all tokens for all sessions after the user sets new credentials.
     *
     * @param string $username If not specified, logs out the member linked to the current session.
     */
    public function logoutAll( string $username = '' ) {
        if ( '' == $username ) {
            $current_client = true;
            if ( 'good' == $this->status ) {
                $username = $this->saved->member['username'];
            } else {
                return;
            }
        } else {
            $current_client = false;
        }
        foreach($this->mechanisms as $session) {
            $session->logoutAll( $username, $current_client );
        }
    }

    /**
     * Initialize the Session Manager for a login action.
     */
    private function login() {
        $this->status = 'login-no-input';

        // First, check that all mechanisms are working and not already in a session.
        foreach($this->mechanisms as $session) {
            if ( $session->checkSavedSession()->status == 'good' ) {
                $this->status = 'already-logged-in';
                $this->saved = new Data;
                return;
            }
            if ( ! $session->checkClientEnabled() ) {
                $this->status = 'login-client-disabled';
                $this->saved = new Data;
                return;
            }
        }
        
        // Next, authenticate the login.
        foreach($this->mechanisms as $session) {
            $data = $session->checkCredentials();
            
            // Check for errors
            if ( 'good' == $data->status ) {
                // We have authentication, now check authorization.
                $this->status = loginAuthorization( $data->member );
                if ( 'good' != $this->status ) {
                    $data->status = 'bad';
                }
            } elseif ( 'bad' == $data->status ) {
                $this->status = 'bad-password';
            }

            // Update the Mechanism
            if ( 'good' == $data->status ) {
                $session->saveClientData( $data );
                break;
            } elseif ( 'bad' == $data->status ) {
                $session->deleteClientData();
                break;
            } else {
                // Try any remaining Mechanisms.
                continue;
            }
        }

        // Save the results
        $this->saved = $data;
    }

    /**
     * Initialize the Session Manager for a logout action.
     */
    private function logout() {
		$this->saved = new Data;
		$this->status = 'logged-out';
        foreach($this->mechanisms as $session) {
            $data = $session->logout();
            if ( $data->status == 'none' ) {
                continue;
            } else {
                // Still logged in.
                $this->status = 'good';
                $this->saved = $data;
                break;
            }
        }
    }

    /**
     * Initialize the Session Manager for a new or existing session.
     *
     * Assumes it is only possible to login one mechanism at a time.
     */
    private function resume() {
        $this->status = 'session-no-input';

        // Authenticate any session token.
        foreach($this->mechanisms as $session) {
            $data = $session->checkSavedSession();
            
            // Check for errors
            if ( 'good' == $data->status ) {
                // We have authentication, now check authorization.
                $this->status = loginAuthorization( $data->member );
                if ( 'good' != $this->status ) {
                    $data->status = 'bad';
                }
            } elseif ( 'bad' == $data->status ) {
                $this->status = 'invalid-session';
            }
            
            // Update the Mechanism
            if ( 'good' == $data->status ) {
                // Current session found.  Done looping.
                break;
            } elseif ( 'bad' == $data->status ) {
                $session->deleteClientData();
                continue;
            } else {
                // XMB does not actively track guest devices.  We just need to know if cookies are enabled.
                $session->checkClientEnabled();
                continue;
            }
        }

        // Save the results
        $this->saved = $data;
    }
}

/**
 * A Session Mechanism is an abstraction that handles the tasks of authenticating 
 * credentials and saving the client's session token.
 *
 * Historically, XMB always used Form Data and Cookies to do this.  For added
 * flexibility, this interface can be implemented by any number of technologies
 * that might be used separately or simultaneously.
 *
 * Note, however, the Session Manager assumes that a client may only login using
 * one Mechanism.  Carefully consider this point when extending the session system.
 *
 * @since 1.9.12
 */
interface Mechanism {
    /**
     * Did the client provide a valid ID and secret that matches an XMB member?
     *
     * Called only when a guest client tries to login from an XMB native authentication system.
     * Foreign account systems should always treat this as a user navigation error.
     *
     * @return Data
     */
    public function checkCredentials(): Data;

    /**
     * Did the client respond to this mechanism in any previous visit?
     *
     * For example, does the client support cookies, or not?
     * If the mechanism is incapable of testing support
     * prior to login, then it should return true.
     *
     * @return bool
     */
    public function checkClientEnabled(): bool;

    /**
     * Did the client provide a session token, and is it valid?
     *
     * Any mechanism that is not tokenized will implement its own
     * logic to validate and retrieve the XMB user record.
     *
     * Foreign account systems will consider the need to
     * auto-register any user who is not yet an XMB member.
     *
     * @return Data
     */
    public function checkSavedSession(): Data;

    /**
     * Delete tokens from both client and server for logout.
     *
     * Most mechanisms should have a logout method.
     * One exception is client certificate authentication, where
     * both clients and servers tend to have poor facilities
     * for ending a session.  In that case, the website
     * should return the object from checkSavedSession()
     * and provide instructions to manually shut down the client.
     *
     * @return Data
     */
    public function logout(): Data;
    
    /**
     * Delete all tokens for all sessions after setting new credentials.
     *
     * The mechanism must clear all tokens associated with this member.
     *
     * @param string $username The member being deleted from the sessions table.
     * @param bool $current_client Is it safe to call deleteClientData() for the current session?
     */
    public function logoutAll( string $username, bool $current_client );

    /**
     * Delete tokens from client for logout.
     *
     * When a session is already expired or invalid, we only need to
     * clean up the client side of the session.
     *
     * Any mechanism that does not send XMB session data to the client
     * might have an empty implementation.
     */
    public function deleteClientData();

    /**
     * Create and send tokens to client for login.
     *
     * Any mechanism that does not send XMB session data to the client
     * might have an empty implementation.
     *
     * @param Data
     */
    public function saveClientData( Data $data );
}

/**
 * Cookies are the default client storage system for session tokens in all versions of XMB.
 *
 * @since 1.9.12
 */
class FormsAndCookies implements Mechanism {

    // Mechanism configuration.
    const REGEN_AFTER = 3600;
    const REGEN_ENABLED = true;
    const SESSION_LIFE_LONG = 86400 * 30;
    const SESSION_LIFE_SHORT = 3600 * 12;
    const TEST_DATA = 'xmb';
    const TOKEN_BYTES = 16;
    const USER_MIN_LEN = 3;
    
    // Cookie names.
    const REGEN_COOKIE = 'id2';
    const SESSION_COOKIE = 'xmbpw';
    const TEST_COOKIE = 'test';
    const USER_COOKIE = 'xmbuser';
    
    public function checkCredentials(): Data {
        $data = new Data;
        $uinput = postedVar('username', '', true, false);
        $pinput = $_POST['password'];

        if ( strlen($uinput) < self::USER_MIN_LEN || empty($pinput) ) {
            $data->status = 'none';
            return $data;
        }
        
        $pinput = md5( $pinput );

        $member = \XMB\SQL\getMemberByName( $uinput );
        
        if ( empty($member) ) {
            $data->status = 'bad';
            return $data;
        }
        if ( $member['password'] != $pinput ) {
            auditBadLogin( $uinput );
            $data->status = 'bad';
            return $data;
        }
        
        $member['password'] = '';
        $data->member = &$member;
        $data->status = 'good';
        $data->permanent = formYesNo('secure') == 'no';
        return $data;
    }

    public function checkClientEnabled(): bool {
        $uinput = $this->get_cookie( self::USER_COOKIE );
        $test   = $this->get_cookie( self::TEST_COOKIE );

        if ( strlen($uinput) >= self::USER_MIN_LEN || self::TEST_DATA == $test ) {
            return true;
        } else {
            put_cookie( 'test', self::TEST_DATA, time() + (86400*365) );
            return false;
        }
    }

    public function checkSavedSession(): Data {
        $data = new Data;

        $pinput = $this->get_cookie( self::SESSION_COOKIE );
        $uinput = $this->get_cookie( self::USER_COOKIE );

        if ( strlen($uinput) < self::USER_MIN_LEN || strlen($pinput) != self::TOKEN_BYTES * 2 ) {
            $data->status = 'none';
            return $data;
        }
        
        $member = \XMB\SQL\getMemberByName( $uinput );
        
        if ( empty( $member ) ) {
            $data->status = 'none';
            return $data;
        }
        
        $member['password'] = '';
        
        $details = \XMB\SQL\getSession( $pinput, $uinput );

        if ( empty( $details ) ) {
            auditBadSession( $member );
            $data->status = 'bad';
            return $data;
        }
        
        if ( time() > $details['expire'] ) {
            auditBadSession( $member );
            $data->status = 'bad';
            return $data;
        }
        
        // Token Regeneration
        if ( self::REGEN_ENABLED ) {
            // Figure out where we are in the regeneration cycle.
            $cookie2 = $this->get_cookie( self::REGEN_COOKIE );
            if ( time() > $details['regenerate'] ) {
                // Current session needs to be regenerated.
                $newdetails = \XMB\SQL\getSessionReplacement( $pinput, $uinput );
                if ( empty( $newdetails ) ) {
                    // Normal: This is the first stale hit. New token is needed.
                    $this->regenerate( $details );
                } else {
                    // Abnormal: Client responded with old token after new token was created.
                    // Caused by interruption or race conditions.
                    $this->recover( $newdetails );
                }
            } elseif ( $cookie2 != '' && $cookie2 == $details['replaces'] ) {
                // Normal: Client responded with both the new token and the old token. Ready to delete old token.
                \XMB\SQL\deleteSession( $details['replaces'] );
                \XMB\SQL\clearSessionParent( $details['token'] );
                $details['replaces'] = '';
                $this->delete_cookie( self::REGEN_COOKIE );
            } elseif ( $details['replaces'] != '' ) {
                // Abnormal: Client responded with the new token but doesn't posess the current (old) token.
                // Regeneration is compromised.  Both tokens must be destroyed.
                \XMB\SQL\deleteSession( $details['replaces'] );
                \XMB\SQL\deleteSession( $details['token'] );
                auditBadSession( $member );
                $data->status = 'bad';
                return $data;
            } elseif ( $cookie2 != '' ) {
                // Abnormal: Client responded with both tokens after the old token was deleted.
                // Caused by interruption or race conditions.
                $this->delete_cookie( self::REGEN_COOKIE );
            } else {
                // Current session is stable.
            }
        }
        
        $data->member = &$member;
        $data->status = 'good';
        return $data;
    }
    
    public function logout(): Data {
        $data = $this->checkSavedSession();
        
        if ( 'none' == $data->status ) {
            return $data;
        }
        
        if ( 'good' == $data->status ) {
            $token = $this->get_cookie( self::SESSION_COOKIE );
            $child = \XMB\SQL\getSessionReplacement( $token, $data->member['username'] );

            \XMB\SQL\deleteSession( $token );
            if ( ! empty( $child ) ) {
                \XMB\SQL\deleteSession( $child['token'] );
            }
        }
        
        $this->deleteClientData();
        
        return new Data;
    }
    
    public function logoutAll( string $username, bool $current_client ) {
        \XMB\SQL\deleteSessionsByName( $username );
        if ( $current_client ) {
            $this->deleteClientData();
        }
    }
    
    public function deleteClientData() {
        $this->delete_cookie( self::REGEN_COOKIE );
        $this->delete_cookie( self::SESSION_COOKIE );
        $this->delete_cookie( self::USER_COOKIE );
        $this->delete_cookie( 'oldtopics' );
        $this->delete_cookie( 'xmblva' );
        $this->delete_cookie( 'xmblvb' );

        foreach($_COOKIE as $key=>$val) {
            if (preg_match('#^fidpw([0-9]+)$#', $key)) {
                $this->delete_cookie($key);
            }
        }

        // Remember to check that these cookies will not be reset after initializing the session.
        // Maybe poison the function put_cookie() itself.
    }

    /**
     * Creates a new session token and cookies for a client who authenticated during this request.
     *
     * @param Data
     */
    public function saveClientData( Data $data ) {
        // Create a new session here.
        $token = bin2hex( random_bytes( self::TOKEN_BYTES ) );

        if ( $data->permanent ) {
            $expires = time() + self::SESSION_LIFE_LONG;
        } else {
            $expires = time() + self::SESSION_LIFE_SHORT;
        }

        $regenerate = time() + self::REGEN_AFTER;

        $replaces = '';

        $agent = '';
        if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
            $agent = substr( $_SERVER['HTTP_USER_AGENT'], 0, 255 );
        }

        $success = \XMB\SQL\saveSession( $token, $data->member['username'], time(), $expires, $regenerate, $replaces, $agent );

        if ( ! $success ) {
            // Retry once.
            $token = bin2hex( random_bytes( self::TOKEN_BYTES ) );
            $success = \XMB\SQL\saveSession( $token, $data->member['username'], time(), $expires, $regenerate, $replaces, $agent );
        }

        if ( ! $success ) {
            trigger_error( 'XMB was unable to save a new session token.', E_USER_ERROR );
        }

        if ( ! $data->permanent ) {
            $expires = 0;
        }

        put_cookie( self::USER_COOKIE, $data->member['username'], $expires );
        put_cookie( self::SESSION_COOKIE, $token, $expires );
    }
    
    /**
     * Creates a new session token and cookies for a client whose session has become stale.
     *
     * @param array $oldsession
     */
    private function regenerate( array $oldsession ) {
        $token = bin2hex( random_bytes( self::TOKEN_BYTES ) );

        $regenerate = time() + self::REGEN_AFTER;

        $replaces = $oldsession['token'];

        $agent = '';
        if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
            $agent = substr( $_SERVER['HTTP_USER_AGENT'], 0, 255 );
        }

        $success = \XMB\SQL\saveSession( $token, $oldsession['username'], (int) $oldsession['login_date'], (int) $oldsession['expire'], $regenerate, $replaces, $agent );

        if ( ! $success ) {
            // Retry once.
            $token = bin2hex( random_bytes( self::TOKEN_BYTES ) );
            $success = \XMB\SQL\saveSession( $token, $oldsession['username'], (int) $oldsession['login_date'], (int) $oldsession['expire'], $regenerate, $replaces, $agent );
        }

        if ( ! $success ) {
            trigger_error( 'XMB was unable to save a new session token.', E_USER_ERROR );
        }

        if ( $oldsession['expire'] > time() + self::SESSION_LIFE_SHORT ) {
            $expires = $oldsession['expire'];
        } else {
            $expires = 0;
        }

        put_cookie( self::USER_COOKIE, $oldsession['username'], $expires );
        put_cookie( self::SESSION_COOKIE, $token, $expires );
        put_cookie( self::REGEN_COOKIE, $replaces, $expires );
    }

    /**
     * Resets session cookies for a client who has authenticated but lost the regeneration data.
     *
     * @param array $newsession
     */
    private function recover( array $newsession ) {
        if ( $newsession['expire'] > time() + self::SESSION_LIFE_SHORT ) {
            $expires = $newsession['expire'];
        } else {
            $expires = 0;
        }

        put_cookie( self::USER_COOKIE, $newsession['username'], $expires );
        put_cookie( self::SESSION_COOKIE, $newsession['token'], $expires );
        put_cookie( self::REGEN_COOKIE, $newsession['replaces'], $expires );
    }

    private function get_cookie( string $name ): string {
        return postedVar( $name, '', false, false, false, 'c' );
    }
    
    private function delete_cookie( string $name ) {
        if ( $this->get_cookie( $name ) != '' ) {
            put_cookie( $name );
        }
    }
}

return;
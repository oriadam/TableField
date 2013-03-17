<?php

/* * *************************************************************** */
/* custom/auth.php                                                */
/* This file defines custom authentication functions.             */
/* Users are saved in tf table users.                             */
/* Passwords are saved as their md5 hash.                         */
/* Called from: inc/auth.php, used in many files.
  /*
  All of the following functions/settings can be overwritten by custom/auth.php.
  "By default" means if this function was not previously declared by custom/auth.php.

  This file can set:

  - $tf['user.group'] = current logged-in user group.
  When not set, inc/auth.php will try to get the current user group from $_SESSION[$tf['db.pre'].'USERTYPE']
  When config option 'auth.anonymous' is true: not logged-in visitors get the group 'anonymous'. Otherwise it is just false.
  Super-user group is 'master'.
  Used by: tfGetUserGroup()

  - $tf['user.login'] = current loggin-in user id as it is saved in tf users.
  When not set, inc/auth.php will try to get the current user group from $_SESSION[$tf['db.pre'].'USER']
  Used by: tfGetUser()

  - $tf['user.name'] = current loggin-in user display name (any language, symbols, etc).
  When not set, inc/auth.php will try to get the current user group from $_SESSION[$tf['db.pre'].'USERNAME']

  - tfGetUserGroup() - return string that represents the current user group (type) in tf info table.
  By default returns $tf['user.group']

  - tfGetUser()      - return current user id (string) in tf users table.
  By default returns $tf['user.login']

  - tfCheckLogin()   - return boolean, true for logged in, false for not
  By default validates that $_SESSION[$tf['db.pre'].'LOGIN_IP'] == $_SERVER['REMOTE_ADDR']

  - tfShowLogin()    - show the login form and process the post data. in other words, handle the login flow
  By default when tfCheckLogin() is false, it creates a very basic html login form that supports login with either email or user-id.
  On success login it sets all the session variables above,
  and then redirect to GET query 'goto' if set. If not, it refreshes the page using javascript (there is a session-based endless loop detection).
  Uses tfValidateUserPass().

  - tfClearLogin()   - log off
  By default set the session and $tf variables above to false.

  - tfChPass($user,$newpass) - change user password. return true for success, or a string representing the error.
  By default will make sure that $newpass is not too weak using tfPassGoodEnough(), and update the tf users table with the md5 of the new password.

  - tfPassGoodEnough($pass) - return true for good passwords, string representing the error for bad passwords.
  By default: $pass needs to be at least 4 chars long (6 or more for numeric passwords). passwords like '123456' or 'aaaa' are not ok.

  - tfValidateUserPass($user,$pass) - return false for incorrect user/password, or the user data as an associative array.
  By default it looks for $user in tf users table by 'login' or 'email', and checks that md5($pass) = user's pass field in database.

  - tfIsUser($user)  - return true if user exists, false otherwise
  By default look for $user in tf users table by 'login'

  - tfGetUserData($user)  - return user data in an associative array when user exists, false otherwise
  By default look for $user in tf users table by 'login'
 */

global $tf;



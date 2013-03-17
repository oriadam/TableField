<?php

/* * *************************************************************** */
/* inc/auth.php                                                   */
/* This file defines the authentication functions.                */
/* Users are saved in tf table users.                             */
/* Passwords are saved as their md5 hash.                         */
/*
  /*
  All of the following functions/settings can be overwritten by custom/auth.php.
  "By default" means if this function was not previously declared by custom/auth.php.

  This file set:

  - $tf['user.group'] = current logged-in user group.
  When not set, inc/auth.php will try to get the current user group from $_SESSION[$tf['db.pre'].'USERGROUP']
  When config option 'auth.anonymous' is true: not logged-in visitors get the group 'anonymous'. Otherwise it is just false.
  Super-user group is 'root'.
  Used by: tfGetUserGroup()

  - $tf['user.login'] = current loggin-in user id as it is saved in tf users.
  When not set, inc/auth.php will try to get the current user group from $_SESSION[$tf['db.pre'].'USER']
  Used by: tfGetUserLogin()

  - $tf['user.name'] = current loggin-in user display name (any language, symbols, etc).
  When not set, inc/auth.php will try to get the current user group from $_SESSION[$tf['db.pre'].'USERNAME']

  - tfGetUserGroup() - return string that represents the current user group in tf info table.
  By default returns $tf['user.group']

  - tfGetUserLogin()      - return current user id (string) in tf users table.
  By default returns $tf['user.login']

  - tfCheckLogin()   - return boolean, true for logged in, false for not
  By default validates that $_SESSION[$tf['db.pre'].'LOGIN_IP'] == $_SERVER['REMOTE_ADDR']

  - tfHandleLogOff() - return boolean, true on a log off event, false otherwise
  Check the $_GET for ?logoff and call tfClearLogin() then to tfShowLogin()

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
chdir(__DIR__.'/..');

if (session_id() == '')
	session_start();

global $tf;

if (file_exists(__DIR__.'/../custom/auth.php'))
	require_once(__DIR__.'/../custom/auth.php');

if (!function_exists('tfCheckLogin')) {

	function tfCheckLogin() {
		global $tf;
		if (empty($tf) || (!array_key_exists('db.pre',$tf)) || empty($_SESSION[$tf['db.pre'].'LOGIN_IP'])) return false;
		return ($_SESSION[$tf['db.pre'].'LOGIN_IP']==$_SERVER['REMOTE_ADDR']);
	}

}

if (!function_exists('tfHandleLogOff')) {

	function tfHandleLogOff() {
		if (array_key_exists('logoff',$_GET)) {
			tfClearLogin();
			unset($_GET['logoff']);
			if (headers_sent()) {
				echo "<script>document.location.href='./?".http_build_query($_GET)."';</script>";
			} else {
				header("Location: ".preg_replace('/\?.*$/','',$_SERVER['REQUEST_URI']).'?'.http_build_query($_GET));
			}
			exit;
			return true;
		}
		return false;
	}

}

if (!function_exists('tfGetUserName')) {
	function tfGetUserName() {
		//if (!tfCheckLogin()) return false;
		global $tf;
		if (empty($_SESSION[$tf['db.pre'].'USERNAME']))
			return false;
		else
			return $_SESSION[$tf['db.pre'].'USERNAME'];
	}
}


if (!function_exists('tfGetUserLogin')) {
	function tfGetUserLogin() {
		//if (!tfCheckLogin()) return false;
		global $tf;
		if (empty($_SESSION[$tf['db.pre'].'USER']))
			return false;
		else
			return $_SESSION[$tf['db.pre'].'USER'];
	}
}

if (!function_exists('tfGetUserGroup')) {

	function tfGetUserGroup() {
		//if (!tfCheckLogin()) return false;
		global $tf;
		if (empty($_SESSION[$tf['db.pre'].'USERGROUP'])) {
			if (empty($tf['auth.anonymous'])) {
				return false;
			} else {
				return 'anonymous';
			}
		} else {
			return $_SESSION[$tf['db.pre'].'USERGROUP'];
		}
	}
}

if (!function_exists('tfPassGoodEnough')) {

	function tfPassGoodEnough($pass) {
		if (strlen($pass) < 4)
			return "Please use a password of at least 4 chars.";
		if (preg_match('/^`?[0-9]+[\-=\+]?$/', $pass) && strlen($pass) < 6)
			return "This kind of password must be at least 6 chars.";
		$badnumbers = array(
		    '012345', '123456', '234567', '345678', '456789', '567890',
		    '0123456', '1234567', '2345678', '3456789', '4567890',
		    '01234567', '12345678', '23456789', '34567890',
		    '012345678', '123456789', '234567890',
		    '1234567890', '01234567890');
		if (in_array($pass, $badnumbers) // one of the obvious number based passwords
			   || preg_match('/^(.)\1+$/', $pass) // same char repeated
			)	return "Password is too obvious.";

		return true;
	}

}

if (!function_exists('tfChPass')) {

	function tfChPass($user, $pass) {
		global $tf;
		$ok = tfPassGoodEnough($pass);
		if ($ok !== true)
			return $ok;
		mysql_query("UPDATE `" . $tf['tbl.users'] . "` SET `pass`='" . md5($pass) . "' WHERE `login`='" . mysql_real_escape_string($user) . "'");
		if ($ok = mysql_error())
			return $ok;
		return tfValidateUserPass($user, $pass) !== false;
	}

}

if (!function_exists('tfValidateUserPass')) {

	function tfValidateUserPass($user, $pass) {
		global $tf;
		if (strpos($user, '@')) {
			// also search emails
			$sql = "SELECT * FROM `" . $tf['tbl.users'] . "` WHERE (login='" . mysql_real_escape_string($user) . "' OR email='" . mysql_real_escape_string($user) . "') AND (pass='".md5($pass)."') LIMIT 1";
		} else {
			// search only names
			$sql = "SELECT * FROM `" . $tf['tbl.users'] . "` WHERE (login='" . mysql_real_escape_string($user) . "') AND (pass='".md5($pass)."') LIMIT 1";
		}
		$res = mysql_query($sql);
		if ($res)
			if ($row = mysql_fetch_assoc($res))
				return $row;
		return false;
	}

}

if (!function_exists('tfIsUser')) {

	function tfIsUser($user) {
		global $tf;
		$res = mysql_query("SELECT COUNT(*) FROM `" . $tf['tbl.users'] . "` WHERE login='" . mysql_real_escape_string($user) . "' LIMIT 1");
		$row = mysql_fetch_row($res);
		return $row[0] > 0;
	}

}

if (!function_exists('tfGetUserData')) {

	function tfGetUserData($user) {
		global $tf;
		$res = mysql_query("SELECT * FROM `" . $tf['tbl.users'] . "` WHERE login='" . mysql_real_escape_string($user) . "' LIMIT 1");
		if ($res)
			if ($row = mysql_fetch_assoc($res))
				return $row;
		return false;
	}

}


if (!function_exists('tfClearLogin')) {

	function tfClearLogin() {
		global $tf;
		// clear session auth values
		$_SESSION[$tf['db.pre'].'LOGIN_IP'] = false;
		$_SESSION[$tf['db.pre'].'USER'] = false;
		$_SESSION[$tf['db.pre'].'USERLOGIN'] = false;
		$_SESSION[$tf['db.pre'].'USERID'] = false;
		$_SESSION[$tf['db.pre'].'USERNAME'] = false;
		$_SESSION[$tf['db.pre'].'USERGROUP'] = false;
		// clear $tf values if they exist
		if (empty($tf['auth.anonymous'])) {
			$tf['user.group'] = false;
		} else {
			$tf['user.group'] = 'anonymous';
		}
		$tf['user.login'] = false;
		$tf['user.name'] = false;
	}

}

if (!function_exists('tfShowLogin')) {

	function tfShowLogin() {
		global $tf;
		if (!empty($_POST['u']) && !empty($_POST['p'])) {
			$row = tfValidateUserPass($_POST['u'], $_POST['p']);
			if ($row) {
				$_SESSION[$tf['db.pre'].'LOGIN_IP'] = $_SERVER['REMOTE_ADDR'];
				$_SESSION[$tf['db.pre'].'USER'] = $row['login'];
				$_SESSION[$tf['db.pre'].'USERNAME'] = $row['name'];
				$_SESSION[$tf['db.pre'].'USERID'] = $row['id'];
				$_SESSION[$tf['db.pre'].'USERGROUP'] = $row['group'];
				if (empty($_SESSION[$tf['db.pre'].'ENDLESS_RELOAD']))
					$_SESSION[$tf['db.pre'].'ENDLESS_RELOAD'] = 0;
				if (($_SESSION[$tf['db.pre'].'ENDLESS_RELOAD']++) > 20) {
					fatal('<h1 class="text-error">Endless reload loop detected<h1><h2>You are logged in as $row[name] ($row[group])</h1><h2>Please navigate to <A href="?">the main page</a></h2><h2>If you get back here, please contact administrator</h2><p>Endless reloads counter=' . $_SESSION[$tf['db.pre'].'ENDLESS_RELOAD'] . '</p></body></html>');
				}

				// redirect on successful login:
				if (!empty($_GET['goto']) && strpos(':', $_GET['goto']) === false) { // do not allow redirection to different domain
					if (headers_sent()) {
						echo ("<html><head><script>location.href='".fix4js1($_GET['goto'])."';</script></head></html>");
					} else {
						header("Location: $_GET[goto]"); // do not use - it causes Firefox to show the annoying "resend - are you sure?"
					}
					exit;
				} else {
					if (headers_sent()) {
						echo "<script>document.location.href='./?".http_build_query($_GET)."';</script>";
					} else {
						header("Location: ".preg_replace('/\?.*$/','',$_SERVER['REQUEST_URI']).'?'.http_build_query($_GET));
					}
					exit;
				}
			} else {
				include_once(__DIR__.'/header.php');
				echo '<strong id="wronguserpass" class="span15 controls text-error">Wrong username/password</strong>';
				sleep(mt_rand(0,6)); // against brute force
			}
		}

		if (!tfCheckLogin()) {
			include_once(__DIR__.'/header.php');
			echo '
			<form method="post">
			  <div class="span15">
				<div class="control-group"><label><div class="control-label">'._('User name or Email').'</div><div class="controls"><input size="30" name="u" type="text" value="' . str_replace(array('"',"\n"), "", @$_POST['u']) . '"></div></label></div>
				<div class="control-group"><label><div class="control-label">'._('Password').'</div><div class="controls"><input size="30" name="p" type="password"></div></label></div>
				<div class="control-group"><div class="controls"><input class="btn" type="submit" value="'._('Login').'"></div></div>
			  </div>
			</form>';

			include_once(__DIR__.'/footer.php');
			exit;
		}
	}

}

/////////////////////////////////////// SHOW LOGIN ////////////////////////////////////
if (!function_exists('tfAuthFlow')) {
	function tfAutoFlow() {
		if (!tfHandleLogOff()) {
			if (!tfCheckLogin()) {
				include_once(__DIR__.'/header.php');
				tfShowLogin();
				include_once(__DIR__.'/footer.php');
				exit;
			}
		}
	}
}

/////////////////////////////////////////////////////////////////////////////////
if (!empty($tf['db.name'])) { // dont test authorization when database is not yet set
	tfAutoFlow();
}

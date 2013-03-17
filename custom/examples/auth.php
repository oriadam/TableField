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

// add statistics information to database
function addLog($what, $who = '', $ex = '') {
	global $tf;
	if (empty($who))
		$who = tfGetUser();
	if (empty($who))
		$who = '';
	sqlRun("INSERT INTO " . fix4sqlf($tf['tbl.log']) . " (`what`,`who`,`ip`,`ex`) VALUES(" . fix4sqlv($what) . "," . fix4sqlv($who) . ",'$_SERVER[REMOTE_ADDR]'," . fix4sqlv($ex) . ")");
}

function tfGetUserGroup() {
	if (!empty($_SESSION['TF_USERTYPE']))
		return $_SESSION['TF_USERTYPE'];
	else
		return false;
}

function tfGetUser() {
	if (!empty($_SESSION['TF_USERID']))
		return $_SESSION['TF_USERID'];
	else
		return false;
}

function tfGetUserId() {
	return tfGetUser();
}

function tfGetUserName() {
	if (!empty($_SESSION['TF_USERNAME']))
		return $_SESSION['TF_USERID'];
	else
		return false;
}

function tfCheckLogin($clearLogin = true) {
	global $tf;
	// check basic session login parameters
	$ok = !empty($_SESSION['TF_USERID']) && !empty($_SESSION['TF_USERTYPE']) && !empty($_SESSION['TF_USERNAME']) && !empty($_SESSION['TF_IP']);
	// limit the session to a specific ip to protect from session hijack
	$ok = $ok && $_SESSION['TF_IP'] == $_SERVER['REMOTE_ADDR'];
	// validate important user parameters in db
	if ($ok) {
		$res = sqlRun("SELECT COUNT(*) FROM " . fix4sqlf($tf['tbl.users']) . " WHERE `id`=" . fix4sqlv($_SESSION['TF_USERID']) . " AND `type`=" . fix4sqlv($_SESSION['TF_USERTYPE']));
		$res = mysql_fetch_row($res);
		$res = $res[0];
		$ok = $ok && $res[0];
	}
	// if not ok - clear login parameters from session
	if (!$ok && $clearLogin) {
		tfClearLogin();
	}
	return $ok;
}

function tfClearLogin() {
	unset($_SESSION['TF_IP']);
	unset($_SESSION['TF_USERNAME']);
	unset($_SESSION['TF_USERTYPE']);
	unset($_SESSION['TF_USERID']);
}

function tfChPass($user, $newpass) {
	global $tf;
	if (!sqlRun("UPDATE " . fix4sqlf($tf['tbl.users']) . " SET `pass`=" . fix4sqlv(md5($newpass)) . " WHERE `id`=" . fix4sqlv($user)))
		return mysql_error();
	else
		return true;
}

function tfValidateUserPass($user, $pass) {
	global $tf;
	$res = sqlRun("SELECT COUNT(`id`) FROM " . fix4sqlf($tf['tbl.users']) . " WHERE `id`=" . fix4sqlv($user) . " AND `pass`=" . fix4sqlv(md5($pass)));
	$row = mysql_fetch_row($res);
	return $row[0] > 0;
}

function tfIsUser($user) {
	global $tf;
	$res = sqlRun("SELECT COUNT(`id`) FROM " . fix4sqlf($tf['tbl.users']) . " WHERE `id`=" . fix4sqlv($user));
	$row = mysql_fetch_row($res);
	return $row[0] > 0;
}

function tfShowLogin() {
	global $tf;
	$error = '';
	if (!empty($_POST['p']) && !empty($_POST['u'])) {
		if (!tfIsUser($_POST['u'])) {
			$error = 'המשתמש לא קיים';
			addLog('login no user', $_POST['u']);
		} else {
			if (!tfValidateUserPass($_POST['u'], $_POST['p'])) {
				$error = 'סיסמה שגויה';
				addLog('login wrong pass', $_POST['u']);
				sleep(mt_rand(1, 4));
			} else {
				addLog('login ok', $_POST['u']);
				$res = sqlRun("SELECT * FROM " . fix4sqlf($tf['tbl.users']) . " WHERE `id`=" . fix4sqlv($_POST['u']));
				$row = mysql_fetch_assoc($res);
				$_SESSION['TF_IP'] = $_SERVER['REMOTE_ADDR'];
				$_SESSION['TF_USERTYPE'] = $row['type'];
				$_SESSION['TF_USERNAME'] = $row['name'];
				$_SESSION['TF_USERID'] = $_POST['u'];
				return true;
			}// else wrong pass
		}
	}

	if (!empty($error)) {
		echo "<div id=idLoginError class=csError>$error</div>";
	}
	?>
	<form id=idLoginForm name=frm method=post>
		<table id=idLoginTable border=0>
			<tr>
				<td id=idLoginName1Td> name: </td><td id=idLoginName2Td> <input id=idLoginNameInput name=u type=text value="<? echo Post('u'); ?>"></td>
			</tr><tr>
				<td id=idLoginPass1Td> pass: </td><td id=idLoginPass2Td> <input id=idLoginPassInput name=imppw type=password value=""></td>
			</tr><tr>
				<td id=idLoginSubmit1Td> </td><td id=idLoginSubmit2Td><input type=submit id=idLoginSubmitInput value=" Go " class=but></td>
			</tr>
		</table>
	</form>
	<?
}


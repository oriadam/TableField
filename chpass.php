<?php
require_once(__DIR__.'/inc/include.php');
global $tf;

if (!tfCheckLogin()) {
	header("Location: ./?");
	exit;
}

include(__DIR__.'/inc/header.php');

$user=tfGetUserLogin();
if (empty($user))
	fatal('Error getting user login name');

?>
<div class="container-fluid span20">
<form name=frm method=post class="bs-docs-example">
    <div class="bs-docs-example-legend"><?=_("Change Password")?></div>
    <div id=idLoginError class=text-error>
<?
		if (isset($_POST['p1']) && isset($_POST['p2']) && isset($_POST['old'])) {
			if ($_POST['p1'] != $_POST['p2']) {
				echo _("Password validation doesn't match");
			} else {
				$ok = tfPassGoodEnough($_POST['p1']);
				if ($ok !== true) {
					echo _("Password is too easy to guess");
				} else {
					if (!tfValidateUserPass($user, $_POST['old'])) {
						echo _("Wrong current password");
					} else {
						$ok = tfChPass($user, $_POST['p1']);
						if ($ok !== true) {
							echo _("Password change failed");
						} else {
							if (!tfValidateUserPass($user, $_POST['p1'])) {
								echo _("Something went wrong with changing the password");
							} else {
								echo "</div></form><div id=idLoginOK class=text-success>"._("Password changed successfully")."</div>";
								include(__DIR__.'/inc/footer.php');
								exit;
							}
						}
					}
				}
			}
		}
?>
	</div>
    <div class="control-group"><div class="control-label"><?=_("Current password")?></div><input class="controls" type="password" name="old"></div>
    <div class="control-group"><div class="control-label"><?=_("New Password")?></div><input class="controls" type="password" name="p1"></div>
    <div class="control-group"><div class="control-label"><?=_("...and again")?></div><input class="controls" type="password" name="p2"></div>
    <div class="control-group"><div class="control-label"></div><input class="controls btn" type="submit" value="<?=_("Change password")?>"></div>
  </form></div>
<?
include(__DIR__.'/inc/footer.php');

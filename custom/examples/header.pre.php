<?php

/* * *************************************************************** */
/* custom/header.pre.php                                          */
/* This file will be called just before every page starts.        */
/* You can use it to change $tf settings, but be careful.   */
/* One common tweak is to $tf['html.title'], as shown below */
/* Called from: inc/header.php                                    */
/* * *************************************************************** */

// Make sure session is started
if (session_id() == '')
	session_start();

// Change page title according to current logged on user
if (!tfCheckLogin()) {
	$tf['html.title'].=" - Guest";
} else {
	$tf['html.title'].=" - Welcome " . tfGetUser();
}

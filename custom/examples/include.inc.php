<?php

/* * *************************************************************** */
/* custom/include.inc.php                                         */
/* This file will be included everywhere.                         */
/* It should not change anything or print anything, just declare  */
/* functions and defines.                                         */
/* One common use is to declare the generic "die with error"      */
/* function called "function fatal($msg)" as shown below.         */
/* Called from: /include.php                                      */
/* * *************************************************************** */

// Change the way "fatal errors" are handled
function fatal($msg) {
	echo "<html>
		<head>
			<title>Error!</title>
		</head>
		<body>
			<h1 style='color:#a11'>Whoops...</h1>
			<h3>We got an error. Please try again or report to our support staff at <a href='mailto:support@example.com'>support@example.com</a></h3>
			<h1>$msg</h1>
		</body>";
	// ... log the error to a file or database ...
	die(1); // fatal() MUST die() at the end!!!
}


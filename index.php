<?php

require_once(__DIR__.'/inc/include.php');
if (file_exists(__DIR__.'/custom/index.php')) {
	include(__DIR__.'/custom/index.php');
}
include(__DIR__.'/tfadmin.php');

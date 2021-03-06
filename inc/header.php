<?php
global $tf;

if (file_exists(__DIR__.'/../custom/header.pre.php')) {
	include(__DIR__.'/../custom/header.pre.php');
}

// this file should include js files: prototype.js oria.js tf.js jquery and some jquery plugins
// and css files: inc/tf.css inc/tfrtl.css (if exists) custom/tf.css (if exists)
?><!DOCTYPE html>
<html>
<head>
	<?php
	if (!empty($tf['html.title'])) {
		echo '<title>' . htmlentities($tf['html.title'],ENT_QUOTES,'UTF-8') . '</title>';
	}
	?>
	<link href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/css/bootstrap.min.css" rel="stylesheet">
	<link href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/3.0.2/css/font-awesome.min.css" rel="stylesheet">

	<?
	if (false && !empty($tf['html.theme'])) { // todo: when jQueryUI css is included, the theme option stop working. oh well.
		//JQueryUI Theme:
		echo '<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/'.htmlentities($tf['html.theme']).'/jquery-ui.css" type="text/css" media="all">';
		//Bootstrap Theme:
		//echo '<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootswatch/2.3.0/'.htmlentities($tf['html.theme']).'/bootstrap.min.css" type="text/css" media="all">';
	} else {
		echo '<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css" rel="stylesheet">';
	}
	?>

	<link href="inc/tf.css" rel="stylesheet" type="text/css">

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.0.1/ckeditor.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/js/bootstrap.min.js"></script>
	<?
	if (!empty($tf['html.chosen'])) {
		// Chosen plugin uses position of -9000 to hide elemnts, for efficiency issues.
		// it causes subtables iframes to have enourmous auto width.
		// local chosen version edited to use display:none instead - but it was significantly slower.
		/*
		<link href="//cdnjs.cloudflare.com/ajax/libs/chosen/0.9.12/chosen.css" rel="stylesheet" type="text/css">
		<script src="//cdnjs.cloudflare.com/ajax/libs/chosen/0.9.12/chosen.jquery.min.js"></script>
		*/
		?>
		<script src="inc/chosen.jquery.js"></script>
		<link href="inc/chosen.css" rel="stylesheet" type="text/css">
		<?
	}
	?>
	<script type='text/javascript' src="inc/oria.js"></script>
	<script type='text/javascript' src="inc/tf.js"></script>

	<meta content="text/html; charset=<?php echo $tf['html.charset']; ?>" http-equiv="Content-Type">
	<?
	if (!empty($tf['html.rtl'])) {
		?><link href="inc/tfrtl.css" rel="stylesheet" type="text/css"><?php
	}
	if (file_exists(__DIR__.'/../custom/style.css')) {
		?><link href="custom/style.css" rel="stylesheet" type="text/css"><?php
	}
	if (file_exists(__DIR__.'/../custom/header.js')) {
		?><script type="text/javascript" src="custom/header.js"></script><?php
	}
	if (file_exists(__DIR__.'/../custom/header.head.php')) {
		include(__DIR__.'/../custom/header.head.php');
	}
	if (!empty($tf['html.head'])) {
		echo $tf['html.head'];
	}
	?>
</head>
<body class="<?=empty($tf['mini'])?'':'mini'?> layout-<?=@$tf['d']?> mode-<?=@$tf['mode']?> <?=@$tf['t']? 'table-'.@$tf['t']->tname:''?>">
<?php
if (file_exists(__DIR__.'/../custom/header.body.php'))
	include(__DIR__.'/../custom/header.body.php');
if (function_exists('tfCheckLogin') && !empty($tf['db.ok'])){
	if (tfCheckLogin() && empty($tf['notopbar'])) {
		displayNavBar();
	}
}
if (file_exists(__DIR__.'/../custom/header.body.html'))
	echo file_get_contents(__DIR__.'/../custom/header.body.html');
if (!empty($tf['html.body']))
	echo $tf['html.body'];



// top navbar
function displayNavBar() {
	global $tf;
	$tf['user']=tfGetUserGroup();

?>

<div id="topbar" class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container-fluid">
			<ul class="nav pull-left">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown"><img class="brandicon" src='tflogo2.png'> <?=_('Settings')?> <b class="caret"></b></a>
					<ul class="dropdown-menu">
						<? if (!empty($tf['user'])) {
							if (TftUserCan($tf['user'],TFEDIT,'','')) {
								?><li><a href="tfconfigure.php"><?=_('TF Configuration')?></a></li><?
							}
							if (TftUserCan($tf['user'],TFEDIT,$tf['tbl.info'],'')) {
								?><li><a href="./?t=<?=$tf['tbl.info']?>"><?=_('Tables&amp;Fields screen')?></a></li>
								<li><a href="./tftedit.php"><?=_('TF Tables Manager')?></a></li><?
							}
							if (TftUserCan($tf['user'],TFEDIT,$tf['tbl.users'],'')) {
								?><li><a href="./?t=<?=$tf['tbl.users']?>"><?=_('TF Users screen')?></a></li><?
							}
							if (TftUserCan($tf['user'],TFVIEW,$tf['tbl.log'],'')) {
								?><li><a href="./?t=<?=$tf['tbl.log']?>"><?=_('TF Log')?></a></li><?
							}
							if (TftUserCan($tf['user'],TFVIEW,'','')) {
								?><li><a href="tfbackup.php"><?=_('Backups')?></a></li><?
							}
						}?>
						<li class="divider"></li>
						<li><a href="http://www.tablefield.com" target="_blank">TableField.com</a></li>
					</ul>
				<li><a href="./?"><?=$tf['html.title']?></a></li>
				<?
				$links=explode("\n",$tf['html.toplinks']);
				foreach ($links as $link) {
					$v=explode('|',$link);
					$count=count($v);
					if ($count) {
						$v[0]=trim($v[0]);
						if ($count==1) {
							$v[1]=$v[0];
							$v[0]=str_ireplace(array('http://','https://'),'',$v[1]);
						}
						if ($count<3) $v[2]='';
						echo "<li><a href=\"$v[1]\" $v[2]>$v[0]</a></li>";
					}
				}?>
			</ul>
			<ul class="nav pull-right">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" title="<?=tfGetUserGroup()?>"><i class="icon-user icon-white"></i> <?=tfGetUserName()?>  <b class="caret"></b></a>
					<ul class="dropdown-menu">
						<? if (tfGetUserId()===false) { ?>
							<li><A><?=_('Hello, anonymous user')?></A></li>
							<li><a href="?login"><?=_('Log in')?></a></li>
						<? } else { ?>
							<li><A><?=_('Hello,')?> <?=tfGetUserName()?></A></li>
							<li><A><?=_('Username:')?> <?=tfGetUserLogin()?></A></li>
							<li><A><?=_('Group:')?> <?=tfGetUserGroup()?></A></li>
							<li class="divider"></li>
							<li><a href="chpass.php"><?=_('Change your password')?></a></li>
							<li class="divider"></li>
							<li><a href="?logoff"><?=_('Log off')?></a></li>
						<? } ?>
					</ul>
				</li>
			</ul>
		</div a="cointainer-fluid">
	</div a="navbar-inner">
</div a="topbar">
<?
}

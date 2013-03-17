<?php
global $tf;

if (!empty($tf['html.footerscript'])) {
	echo '<script>'.$tf['html.footerscript'].'</script>';
}

if (file_exists(__DIR__.'/../custom/footer.html')) {
	echo file_get_contents(__DIR__.'/../custom/footer.html');
}
if (file_exists(__DIR__.'/../custom/footer.php')) {
	echo include(__DIR__.'/../custom/footer.php');
}
if (!empty($tf['html.footer'])) {
	echo $tf['html.footer'];
}

///////////// Keep alive //////////////
if (!empty($tf['auth.keepalive'])) {
	?>
	<script>
		keepalive_object=new Image();
		window.setInterval(function() {
			keepalive_object.src='./tfconfigure.php?noop=1&random='+Math.random();
		},4*60000); // keep session every 4 minutes
	</script>
	<?
}
///////////// Chosen jQueryUI plugin the selectboxes /////////////
if (!empty($tf['html.chosen'])) {
	?>
	<script>
		if (!$.browser) $.browser={};
		$("#idForm select").chosen();
	</script>
	<?
}

///////////// Scheduled cron backups /////////////////
if (!empty($tf['db.autobackup']) && !defined('OUTDIR')) {
	define('NEXTBACKUP',__DIR__.'/../custom/nextbackup');
	if (!file_exists(NEXTBACKUP) || (1*file_get_contents(NEXTBACKUP))<time()) {
		$_GET=array('silent'=>true,'act'=>'dump','zip'=>'2');
		$tf['tf.tfout-no-user-check']=true;
		include(__DIR__.'/../tfout.php');
		if (file_exists(__DIR__.'/../'.LASTBACKUP)) {
			file_put_contents(NEXTBACKUP,time()-3600+($tf['db.autobackup']*86400)); // schedule next backup in X days
		} else {
			file_put_contents('backup-errors.log',date('Y-m-d H:i:s')."\t backup failed to ".LASTBACKUP,FILE_APPEND);
		}
	}
}
?>
</body>
</html>
<?php
	$link = mysql_connect('localhost', 'cubobillpro_user', 'BQcwBK3BGe2Zb7cL');
	if (!$link) {
	    die('Not connected : ' . mysql_error());
	}
	$con = mysql_select_db('cubobillpro', $link);
	
	mysql_set_charset("utf8");
	
	if (!$con) {
	    die ('Can\'t use db : ' . mysql_error());
	}
?>
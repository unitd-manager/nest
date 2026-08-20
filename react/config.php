<?php
	$mysqli = new mysqli("localhost", "cubobillpro", "m3U6T6GC4SK79K", "cubobillpro");

	// Check connection
	if ($mysqli -> connect_errno) {
		echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
		exit();
	}
?>
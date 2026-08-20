<?php
	if($_SERVER['REQUEST_METHOD']=='POST'){
		$sql_log_id   = $_POST['sql_log_id'];

		$docRoot = $_SERVER['DOCUMENT_ROOT'];
		require_once($docRoot.'/admin/Sync/configuration.php');

		$appendSQL = "";
		if($sql_log_id != ""){
			$appendSQL = "WHERE sql_log_id > '$sql_log_id'";
		}
		
		$sql = "
		SELECT sqllog
			  ,sql_log_id
		FROM sql_log 
		{$appendSQL}
		";
				
		$result = mysql_query($sql) or die("Error :".mysql_error());
		
		$sqllog = array();
		$sqllogList = array();
		if(mysql_num_rows($result) > 0){
			while($row = mysql_fetch_array($result)){
				$sqllog["sqllog"]     = $row['sqllog'];
				$sqllog["sql_log_id"] = $row['sql_log_id'];

				array_push($sqllogList,$sqllog);
			    $sqllog = array();
			}

			$response["data"]   = $sqllogList;
			echo(json_encode($response));	
		}
		else{
			$response["status"] = "Invalid Id";
			echo(json_encode($response));
		}	
	}else{
		$response["status"] = "Connection Failed";
		echo(json_encode($response));
	}
	
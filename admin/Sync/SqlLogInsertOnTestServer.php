<?php
	if($_SERVER['REQUEST_METHOD']=='POST'){
		$sql_log   = $_POST['sql_log'];

		$docRoot = $_SERVER['DOCUMENT_ROOT'];
		require_once($docRoot.'/admin/Sync/configuration.php');

		$sql_log = explode(";", $sql_log);

        $my_file = $docRoot.'/admin/Sync/clientSqlLog.txt';
		$handle  = fopen($my_file, 'a') or die('Cannot open file:  '.$my_file);

        $i = 0 ;
        foreach($sql_log as $key) {
            
            if($key != ""){
            	$keySlash = addslashes($key);

            	$SQLInsertLog = "
            	INSERT INTO `sql_log` (`sqllog`, `status`) VALUES
            	('$keySlash','No')
            	";
                $resultInsertLog = mysql_query($SQLInsertLog);

                $insertid = mysql_insert_id();

				$new_data = '['.$insertid.']'.$key."\n";
				fwrite($handle, $new_data);
            }

            $i++;
        }
		
		fclose($handle);		
		
		if($resultInsertLog){
			$response["status"] = "Success";
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
	
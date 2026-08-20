<?
$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once $docRoot.'/admin/Sync/Server.php'; //or include AbstractSync.php and Server.php

const SECRET = 'Syed786@Uss'; //make this long and complicated
const PATH = '/home/cubobillpro/public_html/admin'; //sync all files and folders below this path

$server = new Server(SECRET, PATH);
$server->run(); //process the request
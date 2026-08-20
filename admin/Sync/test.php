<?
$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once $docRoot.'/admin/Sync/Client.php';

const SECRET = 'Syed786@Uss'; //this must match the secret key on the server
//const PATH = 'C:/wamp64/www/cubobillpro/httpdocs/admin/images'; //target for files synced from server
const PATH = 'C:/Users/VUF/Desktop/Cubobillpro/admin'; 

$client = new Client(SECRET, PATH);
$client->run('http://cubobillpro.usoftdev.com/admin/Sync/packServer.php'); //connect to server and start sync
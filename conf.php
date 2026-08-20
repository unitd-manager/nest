<?
$config['cp.TimeZone'] = 'Asia/Kolkata';
date_default_timezone_set($config['cp.TimeZone']);

define('CP_HOST', $_SERVER['HTTP_HOST']);

$docRoot = $_SERVER['DOCUMENT_ROOT'];
//================================================================//
if (CP_HOST == "nest.unitdtechnologies.com") {
    define('CP_ENV', 'production');
    define('CP_CORE_PATH', $docRoot . '/cmspilotv30/');

} else if (CP_HOST == "demopos.smartprosoft.com") {
    define('CP_ENV', 'testing');
    define('CP_CORE_PATH', $docRoot . '/cmspilotv30/');

} else if (CP_HOST == "cubobillpro.testpilotweb3.com:92") {
    define('CP_ENV', 'development');
    define('CP_CORE_PATH', $docRoot . '/cmspilotv30/');

} else if (CP_HOST == "nest.localhost") {
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    $rootFolder = substr($docRoot, 0, stripos($docRoot, '/nest/'));

    define('CP_ENV', 'local');
    define('CP_CORE_PATH', $rootFolder . '/cmsPilot/v3.0/');
}

define('CP_PATH', CP_CORE_PATH . 'CP/');
//================================================================//
require_once(CP_PATH . 'common/lib/inc_path.php');

/*** Local Server **/
$config['local'] = array(
     'db' => array(
          'host'     => 'localhost'
         ,'username' => 'root'
         ,'password' => ''
         ,'dbname'   => 'nest'
     )
    ,'display_errors' => true
    ,'paymentGatewayMode' => 'test'
);

/*** Development Server **/
$config['development'] = $config['local'];
$config['development']['db']['username'] = 'cubobillpro';
$config['development']['db']['password'] = 't1r2a6d4i5ngdemo';
$config['development']['display_errors'] = false;

/*** Testing Server **/
$config['testing'] = $config['development'];
$config['testing']['db']['dbname']   = 'demopos';
$config['testing']['db']['username'] = 'demopos';
$config['testing']['db']['password'] = 'j1xnYxYbc1dSgYY';
$config['testing']['display_errors'] = false;

/*$config['testing'] = $config['development'];
$config['testing']['db']['dbname']   = 'engex';
$config['testing']['db']['username'] = 'engex_user';
$config['testing']['db']['password'] = 'Q7vjLAtCwDyPV4mt';
$config['testing']['display_errors'] = true;*/


/*** Production Server **/
$config['production'] = $config['testing'];
$config['production']['db']['dbname']   = 'nest';
$config['production']['db']['username'] = 'nest';
$config['production']['db']['password'] = '01597bdbe5c8d8';
$config['production']['display_errors'] = true;
$config['production']['paymentGatewayMode'] = 'live';
//================================================================//
require_once(CP_PATH . 'common/lib/Registry.php');
$cfgCommon = require_once(CP_PATH . 'common/lib/config.php');
$cfgMast = require_once($cfgCommon['cp.masterPath'] . 'lib/config.php');
$cfgLoc  = require_once($cfgCommon['cp.localPath'] . 'lib/config.php');
$cpCfg = array_merge($config, $cfgCommon, $cfgMast, $cfgLoc);
Zend_Registry::set('cpCfg',$cpCfg);
//================================================================//

<?
$cpCfg = Zend_Registry::get('cpCfg');
$fn    = Zend_Registry::get('fn');
$modulesArr = Zend_Registry::get('modulesArr');

$dashboard = getCPModuleObj('common_dashboard')->model;

$themePath = CP_THEMES_PATH_LOCAL_ALIAS . $cpCfg['cp.theme'] . '/';

$modulesArr['tradingsg_callRegistry']['title'] = 'Lead';

$arr = array();
$userGroupType = $fn->getSessionParam('userGroupType');
// $arr[] = $dashboard->getDasboardObj('tradingsg_restaurent1', array('subClass' => 'subcr p0 mr0'));
// $arr[] = $dashboard->getDasboardObj('tradingsg_restaurent2', array('subClass' => 'subcr p0 mr0'));
// $arr[] = $dashboard->getDasboardObj('tradingsg_restaurent3', array('subClass' => 'subcr p0 mr0'));
// $arr[] = $dashboard->getDasboardObj('tradingsg_restaurent4', array('subClass' => 'subcr p0 mr0'));
// $arr[] = $dashboard->getDasboardObj('tradingsg_restaurent5', array('subClass' => 'subcr p0 mr0'));
// $arr[] = $dashboard->getDasboardObj('tradingsg_restaurent6', array('subClass' => 'subcr p0 mr0'));
$arr[] = $dashboard->getDasboardObj('tradingsg_dailyCollectionChart', array('cssClass' => 'c100l'));
$arr[] = $dashboard->getDasboardObj('tradingsg_top10SellingProducts', array('cssClass' => 'c100l'));
$arr[] = $dashboard->getDasboardObj('tradingsg_stockMOLProducts', array('cssClass' => 'c100l'));
$arr[] = $dashboard->getDasboardObj('tradingsg_topSupplierOutstanding');
$arr[] = $dashboard->getDasboardObj('tradingsg_topCustomerOutstanding');
$arr[] = $dashboard->getDasboardObj('tradingsg_zeroTransactionProducts');
$arr[] = $dashboard->getDasboardObj('tradingsg_salesByMonthChart');
$arr[] = $dashboard->getDasboardObj('tradingsg_salesByYearChart', array('cssClass' => 'c100l'));
$arr[] = $dashboard->getDasboardObj('tradingsg_productDelivery');

$cpCfg['cp.dashboardArr'] = $arr;

$cssFilesArr = array();
$cssFilesArr[] = $themePath.'css/bootstrap.min.css';
$cssFilesArr[] = $themePath.'css/bootstrap-theme.min.css';
$jsFilesArr = array();
$jsFilesArr[] = "https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js";
$jsFilesArr[] = "https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js";
$jssKeys = array('fontAwesome-4.3.0', 'jqColorbox-1.4.15', 'jqUploadify3.2', 'dropzone', 'jqForm-3.15', 'custom-scrollbar');

CP_Common_Lib_Registry::arrayMerge('jsFilesArr', $jsFilesArr);
CP_Common_Lib_Registry::arrayMerge('jssKeys', $jssKeys);
CP_Common_Lib_Registry::arrayMerge('cssFilesArr', $cssFilesArr);
CP_Common_Lib_Registry::arrayMerge('cpCfg', $cpCfg);
CP_Common_Lib_Registry::arrayMerge('modulesArr', $modulesArr);

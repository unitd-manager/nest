<?
class CPL_Admin_Modules_Tradingsg_Home_Functions {

    /**
     *
     */
    function setModuleArray($modules){

        $modObj = $modules->getModuleObj('tradingsg_home');
        $modules->registerModule($modObj, array(
            'actBtnsList'   => array()
           ,'title'         => 'Home'
        ));
    }

}

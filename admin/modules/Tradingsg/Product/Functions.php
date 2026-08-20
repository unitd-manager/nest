<?
class CPL_Admin_Modules_Tradingsg_Product_Functions extends CP_Admin_Modules_Tradingsg_Product_Functions
{
    /**
     *
     */
    function setModuleArray($modules){

        /* Import Functionality already done in list. Activating button will do product import - ARIF */
        $cpCfg = Zend_Registry::get('cpCfg');
        $modObj = $modules->getModuleObj('tradingsg_product');
        $modules->registerModule($modObj, array(
            'hasMultiLang'  => 1
           ,'hasFlagInList' => 0
           ,'actBtnsList'   => array('import', 'export','new')
           ,'actBtnsDetail' => array('edit')
           ,'actBtnsEdit'   => array('save', 'apply')
        ));
    }

}
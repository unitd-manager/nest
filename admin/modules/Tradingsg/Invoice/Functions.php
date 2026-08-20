<?
class CPL_Admin_Modules_Tradingsg_Invoice_Functions extends CP_Admin_Modules_Tradingsg_Invoice_Functions
{
    /**
     *
     */
    function setModuleArray($modules){

        $modObj = $modules->getModuleObj('tradingsg_invoice');
        $modules->registerModule($modObj, array(
           'actBtnsDetail' => array()
          //,'actBtnsList' => array('printInvoicePDFList')
          ,'hasEditInList' => false
        ));
    }
}

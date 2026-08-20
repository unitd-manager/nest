<?
class CPL_Admin_Widgets_Tradingsg_InvoiceSummary_Model extends CP_Admin_Widgets_Tradingsg_InvoiceSummary_Model
{

    function getCurPfx() {
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        if ($cpCfg['m.project.hasMultiCurrency'] == 1){
            return $cpCfg['baseCurrency'];
        } else {
            return 'Rs ';
        }
    }

}
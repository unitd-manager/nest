<?
class CPL_Admin_Modules_Tradingsg_PurchaseOrder_Functions extends CP_Admin_Modules_Tradingsg_PurchaseOrder_Functions
{
    /**
     *
     */
    function setModuleArray($modules){

        $modObj = $modules->getModuleObj('tradingsg_purchaseOrder');
        $modules->registerModule($modObj, array(
            'tableName' => 'purchase_order'
           ,'keyField' => 'purchase_order_id'
           ,'title' => 'Purchase Order'
           ,'relatedTables' => array('purchase_order_items')
           ,'actBtnsList' => array('printPOPDFList', 'import')
           ,'actBtnsDetail' => array('edit')
           ,'actBtnsEdit' => array('save', 'apply')
        ));
    }

    /**
     *
     */
    function setLinksArray($inst) {
        $tv = Zend_Registry::get('tv');
        $cpCfg = Zend_Registry::get('cpCfg');

        $linkObj = $inst->getLinksArrayObj('tradingsg_purchaseOrder', 'tradingsg_productLink');
        $statusArr = $cpCfg['m.trading.purchaseOrder.poProductStatusArr'];

        $inst->registerLinksArray($linkObj, array(
            'historyTableName' => 'po_product'
           ,'historyTableKeyField' => 'po_product_id'
           ,'hasPortalEdit' => 0
           ,'hasPortalDetail' => 1
           ,'hasPortalDelete' => 0
           ,'hasPortalNew'=> 0
           ,'linkingType' => 'grid'
           ,'fieldlabel' => array('Product'
                                ,'Part Number'
                                ,'Cost Price'
                                ,'Quantity'
                                ,'Qty Delivered'
                                ,'Status'
            )
           ,'gridFieldTypeArray'  => array(
                array('type' => 'textbox', 'editable' => 0)
               ,array('type' => 'textbox', 'editable' => 0)
               ,array('type' => 'textbox', 'editable' => 0)
               ,array('type' => 'textbox', 'editable' => 0)
               ,array('type' => 'textbox', 'editable' => 0)
               ,array('type' => 'dropdown', 'ddArr' => $statusArr, 'useKey' => 0)
           )
        ));
    }
}
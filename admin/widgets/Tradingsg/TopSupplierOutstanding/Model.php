<?
class CPL_Admin_Widgets_Tradingsg_TopSupplierOutstanding_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        
        $SQL = "
        SELECT po.*
              ,SUM(pop.qty * pop.cost_price) AS amount
              ,su.company_name
              ,po.company_id_supplier
        FROM purchase_order po
        LEFT JOIN (`po_product` pop) ON (pop.purchase_order_id = po.purchase_order_id)
        LEFT JOIN (`supplier` su) ON (su.supplier_id = po.company_id_supplier)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'po';

        $last12Month = date('Y-m-d',mktime (0,0,0,date("m")-12,1, date("Y")));
        $today       = date('Y-m-d');

        $searchVar->sqlSearchVar[] = "(po.payment_status = 'Due' OR po.payment_status IS NULL OR po.payment_status = 'Partially Paid')";
        $searchVar->sqlSearchVar[] = "po.company_id_supplier > 0";
        $searchVar->groupBy   = "po.company_id_supplier";
        $searchVar->sortOrder = "su.company_name";
    }

    /**
     *
     * @param <type> $SQL
     * @return <type>
     */
    function getDataArray() {
        $ln = Zend_Registry::get('ln');
        $fn = Zend_Registry::get('fn');
        $dbUtil = Zend_Registry::get('dbUtil');

        $modelHelper = Zend_Registry::get('modelHelper');
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_topSupplierOutstanding');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

}
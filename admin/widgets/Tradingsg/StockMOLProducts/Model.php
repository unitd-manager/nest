<?
class CPL_Admin_Widgets_Tradingsg_StockMOLProducts_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        
        $SQL = "
        SELECT i.*
              ,p.product_id AS productId
              ,p.title AS product_name
              ,p.unit
              ,c.company_name
        FROM inventory i
        LEFT JOIN (product p) ON (p.product_id = i.product_id)
        LEFT JOIN (product_company pc) ON (pc.product_id = p.product_id)
        LEFT JOIN (supplier c) ON (c.supplier_id = pc.company_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'o';

        $last12Month = date('Y-m-d',mktime (0,0,0,date("m")-12,1, date("Y")));
        $today       = date('Y-m-d');

        $searchVar->sqlSearchVar[] = "i.actual_stock != ''";
        $searchVar->sqlSearchVar[] = "p.unit != ''";
        $searchVar->sqlSearchVar[] = "i.actual_stock > 0 ";

        //$searchVar->sqlSearchVar[] = "(o.order_date BETWEEN '{$last12Month}' AND '{$today}')";
        //$searchVar->groupBy = "DATE_FORMAT(o.order_date, '%Y-%m')";
        //$searchVar->sortOrder = "oi.qty DESC";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_stockMOLProducts');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

}
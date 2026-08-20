<?
class CPL_Admin_Widgets_Tradingsg_ZeroTransactionProducts_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        
        $SQL = "
        SELECT item_code
              ,title AS product_name
              ,qty_in_stock 
              ,product_id
        FROM product
        WHERE product_id NOT IN 
        (SELECT record_id FROM order_item ORDER BY qty_in_stock DESC)
        LIMIT 10
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'oi';

        $last12Month = date('Y-m-d',mktime (0,0,0,date("m")-12,1, date("Y")));
        $today       = date('Y-m-d');

        //$searchVar->groupBy   = "product_id";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_zeroTransactionProducts');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

}
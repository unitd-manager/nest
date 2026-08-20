<?
class CPL_Admin_Widgets_Tradingsg_Restaurent5_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        
        $SQL = "
        SELECT oi.*
              ,SUM(oi.qty) AS sold_qty
        FROM `order_item` oi
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

        //$searchVar->sqlSearchVar[] = "(o.order_date BETWEEN '{$last12Month}' AND '{$today}')";
        $searchVar->groupBy   = "oi.record_id";
        $searchVar->sortOrder = "sold_qty DESC LIMIT 0,10";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_top10SellingProducts');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

}
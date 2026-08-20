<?
class CPL_Admin_Widgets_Tradingsg_ProductDelivery_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        
        $SQL = "
        SELECT o.*
              ,oi.item_title
              ,c.company_name
              ,c.mobile
        FROM `order` o
        LEFT JOIN (`order_item` oi) ON (oi.order_id = o.order_id)
        LEFT JOIN (`company` c) ON (c.company_id = o.company_id)
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

        //$searchVar->sqlSearchVar[] = "(o.order_date BETWEEN '{$last12Month}' AND '{$today}')";
        //$searchVar->groupBy = "DATE_FORMAT(o.order_date, '%Y-%m')";
        $searchVar->sqlSearchVar[] = "oi.item_title != ''";
        $searchVar->groupBy = "order_id";
        $searchVar->sortOrder = "oi.item_title DESC";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_productDelivery');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

}
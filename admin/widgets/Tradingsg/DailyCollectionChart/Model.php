<?
class CPL_Admin_Widgets_Tradingsg_DailyCollectionChart_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        
        $SQL = "
        SELECT r.*
              ,SUM(r.amount) as receipt_amount
              ,o.record_type
              ,o.order_id
              ,SUM(srh.qty_return * srh.price) As sales_return_amount
        FROM receipt r
        LEFT JOIN (`order` o) ON (r.order_id = o.order_id)
        LEFT JOIN (`sales_return_history` srh) ON (r.order_id = srh.order_id)
        ";


        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'r';
        $month      = date('m');
        $year       = date('Y');

        $start_date = $year . '-' . $month . '-' . '01';
        $end_date = $year . '-' . $month . '-' . '31';
        $searchVar->sqlSearchVar[] = "(r.receipt_status = 'Paid' OR r.receipt_status = 'Partial Payment')";
        $searchVar->sqlSearchVar[] = "(r.date BETWEEN '{$start_date}' AND '{$end_date}')";
        $searchVar->groupBy = "r.date";


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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_dailyCollectionChart');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

}
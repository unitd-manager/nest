<?
class CPL_Admin_Widgets_Tradingsg_SalesByYearChart_Model extends CP_Admin_Widgets_Tradingsg_SalesByYearChart_Model
{
    /**
     *
     */
    function getSQL(){

        $SQL = "
        SELECT
            CASE WHEN MONTH(o.order_date)>=4 THEN
              concat(YEAR(o.order_date), '-',YEAR(o.order_date)+1)
            ELSE concat(YEAR(o.order_date)-1,'-', YEAR(o.order_date)) END AS order_year
            ,(SUM(oi.unit_price))AS order_amount_yearly
        FROM `order` o
        LEFT JOIN order_item oi ON (o.order_id = oi.order_id)
        ";

        $SQL = "
        SELECT CASE WHEN MONTH(i.invoice_date)>=4 THEN
                 concat(YEAR(i.invoice_date), '-',YEAR(i.invoice_date)+1)
               ELSE concat(YEAR(i.invoice_date)-1,'-', YEAR(i.invoice_date)) END AS invoice_year
              ,(SUM(i.invoice_amount)) AS invoice_amount_yearly
              ,YEAR(i.invoice_date) AS start_Year
              ,(YEAR(i.invoice_date)+1) AS end_Year
        FROM invoice i
        LEFT JOIN (`order` o) ON (o.order_id = i.order_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'i';
        //$searchVar->groupBy =  "order_year";

        //$searchVar->sqlSearchVar[] = "o.order_status != 'Cancelled'";
        $searchVar->sqlSearchVar[] = "i.status !='Cancelled'";
        $searchVar->groupBy = "invoice_year";
    }

    /**
     *
     */
    function getDataArray() {

        $modelHelper = Zend_Registry::get('modelHelper');
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_salesByYearChart');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

}
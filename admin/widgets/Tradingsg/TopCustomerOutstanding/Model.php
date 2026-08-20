<?
class CPL_Admin_Widgets_Tradingsg_TopCustomerOutstanding_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        
        /*$SQL = "
        SELECT i.*
              ,c.company_id
              ,c.company_name
              ,SUM(i.invoice_amount) AS company_invoice_amount
        FROM invoice i
        LEFT JOIN (`order` o) ON (i.order_id   = o.order_id)
        LEFT JOIN (company c) ON (o.company_id = c.company_id)
        ";*/

        $SQL = "
        SELECT inv.*
              ,c.company_id
              ,c.company_name
              ,SUM(inv. invoice_amount) AS invoice_amount
              ,(SELECT SUM(invh.amount)
                FROM invoice_receipt_history invh
                LEFT JOIN (receipt rcp) ON (invh.receipt_id = rcp.receipt_id)
                WHERE invh.invoice_id = inv.invoice_id
                  AND rcp.receipt_status = 'Paid'
                ) AS total_amount_paid
              ,if(
                (SELECT SUM(srh.qty_return*srh.price)
                FROM sales_return_history srh
                WHERE srh.invoice_id = inv.invoice_id
                AND srh.status IS NULL
                ),
                (SELECT SUM(srh.qty_return*srh.price)
                FROM sales_return_history srh
                WHERE srh.invoice_id = inv.invoice_id
                AND srh.status IS NULL
                )
                ,''
              )as sales_return_amount
        FROM invoice inv
        LEFT JOIN `order` o ON (o.order_id = inv.order_id)
        LEFT JOIN (company c) ON (o.company_id = c.company_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'inv';

        $today       = date('Y-m-d');

        $searchVar->sqlSearchVar[] = "inv.status != 'Cancelled'";
        $searchVar->sqlSearchVar[] = "inv.invoice_amount > 0";
        $searchVar->sqlSearchVar[] = "o.company_id > 0";
        $searchVar->groupBy   = "c.company_id";
        $searchVar->sortOrder = "c.company_name";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_topCustomerOutstanding');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

}
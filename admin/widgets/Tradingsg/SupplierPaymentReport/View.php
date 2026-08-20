<?
class CPL_Admin_Widgets_Tradingsg_SupplierPaymentReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $dateUtil = Zend_Registry::get('dateUtil');

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');

        if ($start_date != '' && $end_date == '') {
            $start_date = $start_date;
            $end_date   = $current_date;
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $start_date = $start_date;
            $end_date   = $end_date;
        } else if ($start_date != '' && $end_date != '') {
            $start_date = $start_date;
            $end_date   = $end_date;
        } else {
            $start_date = $current_date;
            $end_date   = $current_date;
        }

        $start_date_formatted = $dateUtil->formatDate($start_date, 'DD/MM/YYYY');
        $end_date_formatted   = $dateUtil->formatDate($end_date, 'DD/MM/YYYY');

        $text = "
        <h2>Supplier Payment Report</h2>
        <table class='thinlist summaryTable mb20'>
            <thead>
                <th colspan='6'>Summary</th>
            </thead>
            <tr>
                <td>Start Date : {$start_date_formatted}</td>
                <td>End Date : {$end_date_formatted}</td>
            </tr>
        </table>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th class='purchaseOrderValLbl'>Supplier Name</th>
                        <th>Total Amount</th>
                        <th>Amount Paid</th>
                        <th>Outstanding Amount</th>
                    </tr>
                </thead>
                    {$this->getRowsHTML()}
            </table>
        </div>
        ";
        return $text;
    }

    /**
     *
     */
    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        
        $rows = '';
        $count = 1;
        $TotalAmount = 0;
        $PaidAmount = 0;
        $BalanceAmount = 0;
        $totalAmount = 0;
        foreach($this->model->dataArray as $row){

            $purchase_details = $this->getPurchaseDetails($row['purchase_order_id'], $row['company_id_supplier'], $start_date, $end_date, $month, $year);
            $current_date   = date('Y-m-d');
            $month          = date('m');
            $year           = date('Y');

            $appendSqlDate = '';
            if ($start_date != '' && $end_date == '') {
                $appendSqlDate = "AND purchase_order_date >= '{$start_date}' AND purchase_order_date      <= '{$current_date}'";
            } else if ($start_date == '' && $end_date != ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $appendSqlDate = "AND purchase_order_date >= '{$start_date}' AND purchase_order_date      <= '{$end_date}'";
            } else if ($start_date != '' && $end_date != '') {
                $appendSqlDate = "AND purchase_order_date >= '{$start_date}' AND purchase_order_date      <= '{$end_date}'";
            } else {
                $appendSqlDate = "AND purchase_order_date  = '{$current_date}'" ;
            }

            $SQLPO = "
            SELECT purchase_order_id
            FROM purchase_order
            WHERE company_id_supplier = {$row['company_id_supplier']}
            {$appendSqlDate}
            ";
            $resultPO = $db->sql_query($SQLPO);
            $totalAmount   = 0;
            $PaidAmount    = 0;
            $BalanceAmount = 0;
            while($rowPO    = $db->sql_fetchrow($resultPO)){
                $SQLPaid = "
                SELECT SUM(pop.qty*pop.cost_price) AS po_amount
                FROM purchase_order p
                LEFT JOIN po_product pop ON (pop.purchase_order_id = p.purchase_order_id)
                WHERE p.purchase_order_id IN ({$rowPO['purchase_order_id']})
                ";
                $resultPaid = $db->sql_query($SQLPaid);
                $rowPaid    = $db->sql_fetchrow($resultPaid);

                $SQLPartialPayment = "
                SELECT SUM(srh.amount) AS Po_partial_payment
                FROM supplier_receipt_history srh
                LEFT JOIN (purchase_order p) ON (srh.purchase_order_id = p.purchase_order_id)
                LEFT JOIN supplier_receipt sr ON (sr.supplier_receipt_id = srh.supplier_receipt_id)
                WHERE p.purchase_order_id IN ({$rowPO['purchase_order_id']})
                  AND sr.receipt_status != 'Cancelled'
                ";
                $resultPartialPayment = $db->sql_query($SQLPartialPayment);
                $rowPartialPayment    = $db->sql_fetchrow($resultPartialPayment);

                if($rowPartialPayment['Po_partial_payment'] == ''){
                    $SQLPartialPayment = "
                    SELECT SUM(srh.amount) AS Po_partial_payment
                    FROM supplier_receipt_history srh
                    LEFT JOIN (purchase_order p) ON (srh.purchase_order_id = p.purchase_order_id)
                    LEFT JOIN supplier_receipt sr ON (sr.supplier_receipt_id = srh.supplier_receipt_id)
                    WHERE p.purchase_order_id IN ({$rowPO['purchase_order_id']})
                      AND sr.receipt_status != 'Cancelled'
                    ";
                    $resultPartialPayment = $db->sql_query($SQLPartialPayment);
                    $rowPartialPayment    = $db->sql_fetchrow($resultPartialPayment);
                }

                $totalAmount   += $rowPaid['po_amount'];
                $PaidAmount    += $rowPartialPayment['Po_partial_payment'];
                $BalanceAmount += $rowPaid['po_amount'] - $rowPartialPayment['Po_partial_payment'];
            }

            $totalCost     = number_format($totalAmount, 2);
            $PaidAmount    = number_format($PaidAmount, 2);
            $BalanceAmount = number_format($BalanceAmount, 2);

            $rows .= "

            <tbody class='supplierPaymentReport'>
            <tr>
                <td>{$count}</td>
                <td class='purchaseOrderVal'>{$row['company_name']}</td>
                <td class='txtRight'>{$totalCost}</td>
                <td class='txtRight'>{$PaidAmount}</td>
                <td>{$BalanceAmount}</td>
            </tr>
            <tr>
                <td class='purchaseOrderDetailsMain' colspan='4'>{$purchase_details}</td>
            </tr>
            </tbody>
            ";

            $count++;
        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }

    /**
     *
     */
    function getPurchaseDetails($purchase_order_id, $company_id_supplier, $start_date, $end_date, $month, $year) {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $rows  = '';
        $count = 1;

        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');

        $appendSqlDate = '';
        if ($start_date != '' && $end_date == '') {
            $appendSqlDate = "AND pc.purchase_order_date >= '{$start_date}' AND pc.purchase_order_date      <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $appendSqlDate = "AND pc.purchase_order_date >= '{$start_date}' AND pc.purchase_order_date      <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $appendSqlDate = "AND pc.purchase_order_date >= '{$start_date}' AND pc.purchase_order_date      <= '{$end_date}'";
        } else {
            $appendSqlDate = "AND pc.purchase_order_date  = '{$current_date}'" ;
        }

        $SQL="
        SELECT pc.*
        FROM purchase_order pc
        LEFT JOIN supplier su ON pc.company_id_supplier = su.supplier_id
        WHERE pc.company_id_supplier = {$company_id_supplier}
        AND (pc.payment_status = 'Due' || pc.payment_status = 'Partially Paid')
        {$appendSqlDate}
        ";
        $result   = $db->sql_query($SQL);
        $numRows = $db->sql_numrows($result);

        $count = 1;
        while ($row = $db->sql_fetchrow($result)) {

            $purchase_order_date = $fn->getCPDate($row['purchase_order_date'], 'd-m-Y');

            $SQLTotal = "
                SELECT SUM(round(
                (pop.qty * pop.cost_price),2)) AS total_cost
                FROM po_product pop WHERE pop.purchase_order_id = {$row['purchase_order_id']}
            ";
            $resultTotal = $db->sql_query($SQLTotal);
            $rowTotal = $db->sql_fetchrow($resultTotal);
            $totalCost = number_format($rowTotal['total_cost'], 2);

            $rows .= "
                <tr>
                    <td>{$purchase_order_date}</td>
                    <td>{$row['po_code']}</td>
                    <td>{$row['title']}</td>
                    <td>{$totalCost}</td>
                    <td>{$row['payment_status']}</td>
                </tr>
            ";
            $count++;
        }

        $text = "
        <div class='purchaseOrderDetails mt5'>
            <table class='thinlist paymentDetails tableInvRecDetail'>
            <tr>
                <th>PO Date</th>
                <th>PO Code</th>
                <th>Title</th>
                <th>PO Value</th>
                <th>Payment Status</th>
            </tr>
            {$rows}
            </table>
        </div>
        ";

        return $text;
    }
}
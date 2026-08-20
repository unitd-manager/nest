<?
class CPL_Admin_Widgets_Tradingsg_TopSupplierOutstanding_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Supplier Outstanding</h2>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Supplier Name</th>
                        <th class='txtRight'>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    {$this->getRowsHTML()}
                </tbody>
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
        
        $rows        = '';
        $count       = 1;
        $totalAmount = 0;
        foreach($this->model->dataArray as $row){

            $amount = number_format($row['amount'], 2, '.', '');

            $SQLPO = "
            SELECT purchase_order_id
            FROM purchase_order
            WHERE company_id_supplier = {$row['company_id_supplier']}
            ";
            $resultPO = $db->sql_query($SQLPO);
            $totalAmount = 0;
            while($rowPO = $db->sql_fetchrow($resultPO)){
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

                if ($rowPartialPayment['Po_partial_payment'] == 0){
                    $totalAmount += $rowPaid['po_amount'];
                } else {
                    $totalAmount += $rowPaid['po_amount'] - $rowPartialPayment['Po_partial_payment'];
                }
            }

            $totalAmount = number_format($totalAmount, 2);

            if($totalAmount > 0){
                $rows .= "
                <tr>
                    <td>{$count}</td>
                    <td>{$row['company_name']}</td>
                    <td class='txtRight'>{$totalAmount}</td>
                </tr>
                ";

                $count++;
            }
        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }
}
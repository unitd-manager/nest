<?
class CPL_Admin_Widgets_Tradingsg_StockTransferReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $dateUtil = Zend_Registry::get('dateUtil');

        $text = "
        <h2>Stock Transfer Report</h2>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Date</th>
                        <th>To Location</th>
                        <th>Status</th>
                        <th>Product</th>
                        <th>Qty Requested</th>
                        <th>Qty Delivered</th>
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
        
        $rows = '';
        $count = 1;
        $TotalAmount = 0;
        $PaidAmount = 0;
        $BalanceAmount = 0;
        $totalAmount = 0;
        foreach($this->model->dataArray as $row){
            $date = $fn->getCPDate($row['date'], 'd-m-Y');
            $rows .= "
            <tbody class='supplierPaymentReport'>
            <tr>
                <td>{$count}</td>
                <td>{$date}</td>
                <td class=''>{$row['to_location']}</td>
                <td class=''>{$row['status']}</td>
                <td>{$row['product_title']}</td>
                <td>{$row['qty_requested']}</td>
                <td>{$row['qty']}</td>
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

}
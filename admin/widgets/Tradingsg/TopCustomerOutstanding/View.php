<?
class CPL_Admin_Widgets_Tradingsg_TopCustomerOutstanding_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Customer Outstanding</h2>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Customer Name</th>
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
            $invoice_amount = $row['invoice_amount'] - $row['sales_return_amount'];
            $balance_amount  = $invoice_amount - $row['total_amount_paid'];
            $totalAmount = number_format($balance_amount, 2);

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
<?
class CPL_Admin_Widgets_Tradingsg_GSTREPORT_View extends CP_Common_Lib_WidgetViewAbstract
{
    /*
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $dateUtil = Zend_Registry::get('dateUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

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
            $start_date = substr($end_date, 0, 8) . '01';
            $end_date   = $end_date;
        } else if ($start_date != '' && $end_date != '') {
            $start_date = $start_date;
            $end_date   = $end_date;
        } else {
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date   = $year . '-' . $month . '-' . '31';
        }

        $start_date_formatted = $dateUtil->formatDate($start_date, 'DD/MM/YYYY');
        $end_date_formatted   = $dateUtil->formatDate($end_date, 'DD/MM/YYYY');

        $summaryRec = $this->model->getSqlForCount();
        $total_gst_amount   = number_format($summaryRec['total_gst_amount'], 2);
        $total_order_amount = number_format($summaryRec['total_order_amount'], 2);

        $text = "
        <h2>GST Report</h2>
        <table class='thinlist summaryTable mb20'>
            <thead>
                <th colspan='6'>Summary</th>
            </thead>
            <tr>
                <td><b>Start Date : {$start_date_formatted}</b></td>
                <td><b>End Date : {$end_date_formatted}</b></td>
                <td><b>Total GST Amount: {$total_gst_amount}</b></td>
                <td><b>Total Amount: {$total_order_amount}</b></td>
            </tr>
        </table>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice Code</th>
                        <th>Order Code</th>
                        <th class='txtCenter'>GST %</th>
                        <th class='txtRight'>GST Amount</th>
                        <th class='txtRight'>Amount(GST Excluded)</th>
                        <th>Company Name</th>
                        <th>GST No</th>
                    </tr>
                </thead>
                {$this->getRowsHTML()}
            </table>
        </div>
        ";
        return $text;
    }

    /*
     *
     */
    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        $rows = '';
        $total_order_amount   = 0;
        $total_gst_amount     = 0;
        $overall_order_amount = 0;

        foreach ($this->model->dataArray as $row) {

            $totalamount  = $row['gst_amount'] + $row['order_amount'];
            $totalamount  = number_format($totalamount,2);
            $order_amount = number_format($row['order_amount'], 2);
            $gstAmount    = number_format($row['gst_amount'], 2);

            $orderNoLink = "index.php?_topRm=order&module=tradingsg_order&_action=edit&order_id={$row['order_id']}";
            
            if($row['order_id'] < 10){
                $orderId = '0000' . $row['order_id'];
            }
            else if($row['order_id'] <= 99){
                $orderId = '000' . $row['order_id'];
            }
            else if($row['order_id'] <= 999){
                $orderId = '00' . $row['order_id'];
            }
            else if($row['order_id'] <= 9999){
                $orderId = '0' . $row['order_id'];
            }
            else{
                $orderId = $row['order_id'];
            }

            if($row['invoice_code'] == ""){
                $invoice_code = 'INV - '.$row['invoice_id']; 
            }
            else{
                $invoice_code = $row['invoice_code'];
            }

		    $rows .= "
			<tr>
                <td>{$row['order_date']}</td>
                <td>
                    <a href='{$orderNoLink}' target='_blank'>
                        <u>{$invoice_code}</u>
                    </a>
                </td>
                <td>
                    <a href='{$orderNoLink}' target='_blank'>
                        <u>{$orderId}</u>
                    </a>
                </td>
                <td align='center'>{$row['gst']}</td>
                <td align='right'>{$gstAmount}</td>
                <td align='right'>{$order_amount}</td>
                <td>{$row['companyName']}</td>
                <td>{$row['cust_gst_no']}</td>
			</tr>
			";

            $total_order_amount   += $row['order_amount'];
            $total_gst_amount     += $row['gst_amount'];
            $overall_order_amount += $row['gst_amount'] + $row['order_amount'];
        }

        $total_order_amount   = number_format($total_order_amount, 2);
        $total_gst_amount     = number_format($total_gst_amount, 2);
        $overall_order_amount = number_format($overall_order_amount, 2);

        $text = "
        {$rows}
        <tr>
            <td class='lastRowBgColor' colspan='4'>Total</td>
            <td class='txtRight lastRowBgColor'>{$total_gst_amount}</td>
            <td class='txtRight lastRowBgColor'>{$total_order_amount}</td>
            <td class='lastRowBgColor' colspan='2'></td>
        </tr>
        ";

        return $text;
    }

}
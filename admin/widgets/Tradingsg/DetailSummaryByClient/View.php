<?
class CPL_Admin_Widgets_Tradingsg_DetailSummaryByClient_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db       = Zend_Registry::get('db');
        $fn       = Zend_Registry::get('fn');
        $ln       = Zend_Registry::get('ln');
        $cpCfg    = Zend_Registry::get('cpCfg');
        $dateUtil = Zend_Registry::get('dateUtil');

        $company_id = $fn->getReqParam('company_id');
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

        $clientNameHeading    = "";
        $dateHeading          = "";
        $invoiceCodeHeading   = "";
        $invoiceAmountHeading = "";
        $amountPaidHeading    = "";
        $amountDueHeading     = "";
        if($cpCfg['cp.reportTamilHeadingShowHide'] == 1){
            $clientNameHeading    = " / ".$ln->gd('clientName');
            $dateHeading          = " / ".$ln->gd('date');
            $invoiceCodeHeading   = " / ".$ln->gd('invoiceCode');
            $invoiceAmountHeading = " / ".$ln->gd('invoiceAmount');
            $amountPaidHeading    = " / ".$ln->gd('amountPaid');
            $amountDueHeading     = " / ".$ln->gd('amountDue');
        }

        $company_name = '';
        if($company_id == ''){
            $company_name = "<th>Client Name{$clientNameHeading}</th>";
        }

        $company_Title = '';
        if($company_id != ''){
            $SQLCompany = "
            SELECT company_name
            FROM  company
            WHERE company_id = {$company_id}
            ";
            $resultCompany = $db->sql_query($SQLCompany);
            $rowCompany    = $db->sql_fetchrow($resultCompany);

            $company_Title = "<b>{$rowCompany['company_name']}</b>";

        }else{
            $company_Title = 'Client';
        }

        $text = "
        <h2>Detail Summary By {$company_Title}</h2>
		<div class = 'tableOuter scroll-pane'>
            <table class='thinlist summaryTable mb20'>
                <thead>
                    <th colspan='6'>Summary</th>
                </thead>
                <tr>
                    <td>Start Date : {$start_date_formatted}</td>
                    <td>End Date : {$end_date_formatted}</td>
                </tr>
            </table>
			<table class='thinlist'>
				<thead>
					<tr>
						{$company_name}
						<th>Date{$dateHeading}</th>
						<th>Invoice Code{$invoiceCodeHeading}</th>
						<th class='txtRight'>Invoice Amount{$invoiceAmountHeading}</th>
						<th class='txtRight'>Amount Paid{$amountPaidHeading}</th>
						<th class='txtRight'>Amount Due{$amountDueHeading}</th>
					</tr>
				</thead>
				{$this->getRowsHTML()}
			</table>
		</div>
        ";
        return $text;
    }

    function getRowsHTML() {
        $fn    = Zend_Registry::get('fn');
        $db    = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $ln    = Zend_Registry::get('ln');

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $company_id     = $fn->getReqParam('company_id');
        
        $startDateAppendSql = '';
        if ($start_date != '' && $end_date == '') {
            $startDateAppendSql = "AND o.order_date >= '{$start_date}' AND o.order_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $startDateAppendSql = "AND o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $startDateAppendSql = "AND o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else {
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $startDateAppendSql = "AND o.order_date >= '{$current_date}' AND o.order_date <= '{$current_date}'";
        }

        $company_id = $fn->getReqParam('company_id');

        $rows = '';
		$siteTitle = '' ;
        $totalInvoiceAmount = 0;
        $totalBalanceAmount = 0;
        $totalPaidAmount = 0;

        $dateHeading          = "";
        $invoiceAmountHeading = "";
        $amountPaidHeading    = "";
        $totalHeading         = "";
        if($cpCfg['cp.reportTamilHeadingShowHide'] == 1){
            $dateHeading          = " / ".$ln->gd('date');
            $invoiceAmountHeading = " / ".$ln->gd('invoiceAmount');
            $amountPaidHeading    = " / ".$ln->gd('amountPaid');
            $totalHeading         = " / ".$ln->gd('total');
        }

        foreach($this->model->dataArray as $row){
            $appendSql = "";
            if($row['company_id'] != ''){
                $appendSql = "AND o.company_id = {$row['company_id']}";
            }

            $appendGst  = "";
            $gst_status = $fn->getReqParam('gst_status');
            if($gst_status != ""){
                if($gst_status == 'GST'){
                    $appendGst = "AND o.gst_status = 'ON'";
                }
                else{
                    $appendGst = "AND o.gst_status = 'OFF'";
                }
            }

            $SQLInv = "
            SELECT inv.*
            ,o.order_id
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
            WHERE inv.status != 'Cancelled'
            AND inv.invoice_id = {$row['invoice_id']}
            AND inv.invoice_amount > 0
            {$startDateAppendSql}
            {$appendSql}
            {$appendGst}
            ";

            $resultInv = $db->sql_query($SQLInv);
            $numRows   = $db->sql_numrows($resultInv);
            $invoice_amount = '';
            $InvRecDetails  = '';
            while ($rowInv = $db->sql_fetchrow($resultInv)) {
        		$invoice_amount = $rowInv['invoice_amount'] - $rowInv['sales_return_amount'];
        		$balance_amount  = $invoice_amount - $rowInv['total_amount_paid'];
                $totalInvoiceAmount += $invoice_amount;
                $totalBalanceAmount += $balance_amount;
                $totalPaidAmount += $rowInv['total_amount_paid'];
                $invoice_amount = number_format($invoice_amount, 2);
                $balance_amount = number_format($balance_amount, 2);
        		$rowInv['total_amount_paid'] = number_format($rowInv['total_amount_paid'], 2);

                $todaylink = "<a target = '_blank' href = 'index.php?module=tradingsg_order&record_id={$rowInv['order_id']}&_action=edit'>";

                $company_name = '';
                //if($company_id == ''){
                    $company_name = "<td>{$row['company_name']}</td>";
                //}

                if($rowInv['invoice_code'] == ""){
                    $invoice_code = 'INV - '.$rowInv['invoice_id']; 
                }
                else{
                    $invoice_code = $rowInv['invoice_code'];
                }

                $SQLReceiptInvoice = "
                SELECT i.invoice_amount AS Amount
                      ,i.invoice_date AS DateRecord
                      ,'INVOICE' AS RecordType
                FROM `invoice` i
                WHERE i.invoice_id = '{$rowInv['invoice_id']}'
                AND i.order_id = '{$rowInv['order_id']}'
                AND i.status != 'Cancelled'
                UNION ALL
                SELECT r.amount AS Amount
                       ,r.date AS DateRecord
                       ,'RECEIPT' AS RecordType
                FROM `invoice_receipt_history` irh
                LEFT JOIN `receipt` r ON (r.receipt_id = irh.receipt_id)
                WHERE irh.invoice_id = '{$rowInv['invoice_id']}'
                AND r.order_id = '{$rowInv['order_id']}'
                AND r.receipt_status != 'Cancelled'
                ";
                $resultReceiptInvoice  = $db->sql_query($SQLReceiptInvoice);
                $numRowsReceiptInvoice = $db->sql_numrows($resultReceiptInvoice);
                $InvRecDetails = '';
                while ($rowReceiptInvoice = $db->sql_fetchrow($resultReceiptInvoice)) {
                    $amountFormatted = number_format($rowReceiptInvoice['Amount'], 2);
                    $dateFormatted   = $fn->getCPDate($rowReceiptInvoice['DateRecord'], 'd-m-Y');
                    if($rowReceiptInvoice['RecordType'] == "INVOICE"){
                        $InvRecDetails .= "
                        <tr>
                            <td width='22%' >{$dateFormatted}</td>
                            <td width='45%' class='txtRight'>{$amountFormatted}</td>
                            <td width='40%' class='txtRight'></td>
                        </tr>
                        ";
                    }else{
                        $InvRecDetails .= "
                        <tr>
                            <td width='22%' >{$dateFormatted}</td>
                            <td width='45%' class='txtRight'></td>
                            <td width='40%' class='txtRight'>{$amountFormatted}</td>
                        </tr>
                        ";
                    }
                }

                //$invoiceReceiptDetails = "";
                //if($numRowsReceiptInvoice > 0){
                    $invoiceReceiptDetails = "
                    <tr>
                        <td></td>
                        <td colspan='4'>
                            <div class='InvoiceReceiptDetails'>
                                <table class='thinlist tableInvRecDetail' width='90%'>
                                    <thead>
                                        <th width='22%' >Date{$dateHeading}</th>
                                        <th width='45%' class='txtRight'>Invoice Amount{$invoiceAmountHeading}</th>
                                        <th width='40%' class='txtRight'>Amount Paid{$amountPaidHeading}</th>
                                    </thead>
                                    <tbody>
                                        {$InvRecDetails}
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    ";
                //}

			    $rows .= "
                <tbody class='detailSummaryByClientPaymentDetails'>
    				<tr>
    					{$company_name}
    					<td class='InvoiceRecDetailsToggle'>{$fn->getCPDate($rowInv['invoice_date'], 'd-m-Y')}</td>
    					<td>{$todaylink}{$invoice_code}</a></td>
    					<td align='right'>{$invoice_amount}</td>
    					<td align='right'>{$rowInv['total_amount_paid']}</td>
    					<td align='right'>{$balance_amount}</td>
    				</tr>
                    {$invoiceReceiptDetails}
                </tbody>
				";
            }
        }

        $totalInvoiceAmount = number_format($totalInvoiceAmount, 2);
        $totalBalanceAmount = number_format($totalBalanceAmount, 2);
        $totalPaidAmount    = number_format($totalPaidAmount, 2);

        $total_th = '';
        if($company_id == ''){
            $total_th = "<th colspan='2'></th>";
        }else{
            $total_th = "<th></th>";
        }

        $text = "
        {$rows}
        <tr class='lastRowBgColor'>
            <th colspan='2'></th>
            <th>TOTAL{$totalHeading}</th>
            <th class='txtRight'>{$totalInvoiceAmount}</th>
            <th class='txtRight'>{$totalPaidAmount}</th>
            <th class='txtRight'>{$totalBalanceAmount}</th>
        </tr>
        ";

        return $text;
    }

}
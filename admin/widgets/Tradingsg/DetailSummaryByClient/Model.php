<?
class CPL_Admin_Widgets_Tradingsg_DetailSummaryByClient_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        $cpCfg = Zend_Registry::get('cpCfg');

        $SQL = "
        SELECT DISTINCT c.company_id
              ,c.company_name
              ,i.invoice_id
        FROM `invoice` i
        LEFT JOIN `order` o ON (o.order_id = i.order_id)
        LEFT JOIN `company` c ON (c.company_id = o.company_id)
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
        $searchVar->mainTableAlias = 'o';
        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $company_id    	= $fn->getReqParam('company_id');
        $gst_status     = $fn->getReqParam('gst_status');

        if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "o.order_date >= '{$start_date}' AND o.order_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $searchVar->sqlSearchVar[] = "o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else {
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $searchVar->sqlSearchVar[] = "o.order_date >= '{$current_date}' AND o.order_date <= '{$current_date}'";
        }

		if ($company_id != '') {
            $searchVar->sqlSearchVar[] = "c.company_id = {$company_id}";
		}

        if($gst_status != ""){
            if($gst_status == 'GST'){
                $searchVar->sqlSearchVar[] = "o.gst_status = 'ON'";
            }
            else{
                $searchVar->sqlSearchVar[] = "o.gst_status = 'OFF'";
            }
        }

        //$searchVar->sqlSearchVar[] 	= "o.record_type = 'Quote'";
        //$searchVar->sqlSearchVar[]  = "c.company_id !=''";

        if($cpCfg['cp.excludeStock'] == 1){
            $searchVar->sqlSearchVar[] = "o.link_stock = 1";
        }

        $searchVar->sortOrder 		= "c.company_name ASC";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_detailSummaryByClient');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

    /**
     */
    function getExportToExcel(){
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dateUtil = Zend_Registry::get('dateUtil');
        $fn = Zend_Registry::get('fn');
        $ln = Zend_Registry::get('ln');

        $location_id    = $fn->getReqParam('location_id');
        $company_id     = $fn->getReqParam('company_id');

        $clientNameHeading    = "";
        $dateHeading          = "";
        $invoiceCodeHeading   = "";
        $invoiceAmountHeading = "";
        $amountPaidHeading    = "";
        $amountDueHeading     = "";
        $totalHeading         = "";
        if($cpCfg['cp.reportTamilHeadingShowHide'] == 1){
            $clientNameHeading    = " / ".$ln->gd('clientName');
            $dateHeading          = " / ".$ln->gd('date');
            $invoiceCodeHeading   = " / ".$ln->gd('invoiceCode');
            $invoiceAmountHeading = " / ".$ln->gd('invoiceAmount');
            $amountPaidHeading    = " / ".$ln->gd('amountPaid');
            $amountDueHeading     = " / ".$ln->gd('amountDue');
            $totalHeading         = " / ".$ln->gd('total');
        }

        $rows = '';


        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "Detail_Summary_by_Client__" . date("d-m-Y") . ".xls";

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename={$file_name}");
        header("Content-Transfer-Encoding: binary ");

        $objPHPExcel = new PHPExcel();

        //--------------------------------------------------//
        $rowc = 1;
        $colc = 0;
        $appendSql = '';
        $actSheet = &$objPHPExcel->getActiveSheet();

        if($company_id == ''){
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Client Name'.$clientNameHeading);
        }

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Invoice Code'.$invoiceCodeHeading);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Date'.$dateHeading);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Invoice Amount'.$invoiceAmountHeading);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Amount Paid'.$amountPaidHeading);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Amount Due'.$amountDueHeading);
        /******************** FORMAT HEADER *******************/
        $headStyle = array(
            'font'  => array(
            'bold'  => true,
            'size'  => 10,
            'name'  => 'Arial'
        ));

        $lastCol    = $actSheet->getHighestColumn();
        $lastColInd = PHPExcel_Cell::columnIndexFromString($lastCol);
        $actSheet->getStyle("A1:{$lastCol}1")->applyFromArray($headStyle);

        for ($i=0; $i < $lastColInd; $i++){
            $colAlphabet = PHPExcel_Cell::stringFromColumnIndex($i);
            $actSheet->getColumnDimension($colAlphabet)->setAutoSize(true);
        }


        $linkToStock = '' ;
        if($cpCfg['cp.excludeStock'] == 1){
            $linkToStock = "AND o.link_stock = 1";
        }

        $companyId = '';
        if ($company_id != '') {
            $companyId = "AND c.company_id = {$company_id}";
        }

        $totalBalanceAmount = 0 ;
        $paymentTotalofInvoiceAmount = 0 ;
        $totalPaidAmount = 0 ;

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        
        $startDateAppendSql = '';
        if ($start_date != '' && $end_date == '') {
            $startDateAppendSql = "o.order_date >= '{$start_date}' AND o.order_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $startDateAppendSql = "o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $startDateAppendSql = "o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else {
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $startDateAppendSql = "o.order_date >= '{$current_date}' AND o.order_date <= '{$current_date}'";
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
         
        $SQL = "
        SELECT DISTINCT c.company_id
              ,c.company_name
              ,i.invoice_id
        FROM `invoice` i
        LEFT JOIN `order` o ON (o.order_id = i.order_id)
        LEFT JOIN `company` c ON (c.company_id = o.company_id)
        WHERE {$startDateAppendSql}
        {$companyId}
        {$linkToStock}
        {$appendGst}
        ORDER BY c.company_name ASC
        ";

        $result1 = $db->sql_query($SQL);

        while ($row = $db->sql_fetchrow($result1)) {
            $appendSql = "";
            if($row['company_id'] != ''){
                $appendSql = "AND o.company_id = {$row['company_id']}";
            }

            $SQLInv = "
            SELECT inv.*
            ,o.order_id
            ,c.company_name
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
            LEFT JOIN `company` c ON (c.company_id = o.company_id)
            WHERE {$startDateAppendSql}
            AND inv.status != 'Cancelled'
            AND inv.invoice_id = {$row['invoice_id']}
            AND inv.invoice_amount > 0
            {$appendSql}
            {$appendGst}
            ";

            $resultInv = $db->sql_query($SQLInv);
            $invoice_amount  = '';


            while ($rowInv = $db->sql_fetchrow($resultInv)) {

                $colc = 0;
                $rowc++;
                //$invoiceCode = $rowInv['invoice_code'];

                $invoice_amount = $rowInv['invoice_amount'] - $rowInv['sales_return_amount'];
                $balance_amount  = $invoice_amount - $rowInv['total_amount_paid'];
                $paymentTotalofInvoiceAmount += $invoice_amount;
                $totalBalanceAmount += $balance_amount;
                $totalPaidAmount += $rowInv['total_amount_paid'];
                $invoice_amount = number_format($invoice_amount, 2);
                $balance_amount = number_format($balance_amount, 2);
                $rowInv['total_amount_paid'] = number_format($rowInv['total_amount_paid'], 2);

                if($rowInv['invoice_code'] == ""){
                    $invoice_code = 'INV - '.$rowInv['invoice_id']; 
                }
                else{
                    $invoice_code = $rowInv['invoice_code'];
                }

                if($company_id == ''){
                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowInv['company_name']);
                }

                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $invoice_code);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $fn->getCPDate($rowInv['invoice_date'], 'd-m-Y'));
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $invoice_amount);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowInv['total_amount_paid']);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $balance_amount);

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

                if($numRowsReceiptInvoice > 0){
                    if ($company_id != '') {
                        $colc = 1;
                        $rowc++;
                    }else{
                        $colc = 2;
                        $rowc++;
                    }

                    $actSheet->getStyle($rowc)->applyFromArray($headStyle);
                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Date'.$dateHeading);
                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Invoice Amount'.$invoiceAmountHeading);
                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Amount Paid'.$amountPaidHeading);
                }

                $InvRecDetails = '';
                while ($rowReceiptInvoice = $db->sql_fetchrow($resultReceiptInvoice)) {
                    $amountFormatted = number_format($rowReceiptInvoice['Amount'], 2);
                    $dateFormatted   = $fn->getCPDate($rowReceiptInvoice['DateRecord'], 'd-m-Y');
                    if ($company_id != '') {
                        $colc = 1;
                        $rowc++;
                    }else{
                        $colc = 2;
                        $rowc++;
                    }

                    if($rowReceiptInvoice['RecordType'] == "INVOICE"){
                        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $dateFormatted);
                        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $amountFormatted);
                        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
                    }
                    else{
                        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $dateFormatted);
                        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
                        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $amountFormatted);
                    }
                }
            }
        }

        $colc = 0;
        $rowc++;

        if($company_id == ''){
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total'.$totalHeading);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, number_format($paymentTotalofInvoiceAmount,2));
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, number_format($totalPaidAmount,2));
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, number_format($totalBalanceAmount,2));
        }else{
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total'.$totalHeading);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, number_format($paymentTotalofInvoiceAmount,2));
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, number_format($totalPaidAmount,2));
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, number_format($totalBalanceAmount,2));
        }

        $actSheet->getStyle("A{$rowc}:F{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

}
<?
class CPL_Admin_Widgets_Tradingsg_SupplierPaymentReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        
        $SQL = "
        SELECT po.*
              ,su.company_name
        FROM purchase_order po
        LEFT JOIN (`supplier` su) ON (su.supplier_id = po.company_id_supplier)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'po';

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');

        if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "po.purchase_order_date    >= '{$start_date}' AND po.purchase_order_date      <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $searchVar->sqlSearchVar[] = "po.purchase_order_date    >= '{$start_date}' AND po.purchase_order_date      <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "po.purchase_order_date    >= '{$start_date}' AND po.purchase_order_date      <= '{$end_date}'";
        } else {
            $searchVar->sqlSearchVar[] = "po.purchase_order_date    = '{$current_date}'" ;
        }

        //$searchVar->sqlSearchVar[] = "po.payment_status = 'Due'";
        $searchVar->groupBy   = "po.company_id_supplier";
        //$searchVar->sortOrder = "amount DESC LIMIT 0,10";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_supplierPaymentReport');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }
    /**
     *
     */
    function getExportToExcel(){
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $cpUtil = Zend_Registry::get('cpUtil');
        $dateUtil = Zend_Registry::get('dateUtil');
        $fn = Zend_Registry::get('fn');

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "SupplierPaymentReport_" . date("d-m-Y") . ".xls";

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
        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $actSheet = &$objPHPExcel->getActiveSheet();

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'S.No');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Supplier Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total Amount');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Amount Paid');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Outstanding Amount');

        /******************** FORMAT HEADER *******************/
        $headStyle = array(
            'font' => array('bold' => true)
        );

        $lastCol    = $actSheet->getHighestColumn();
        $lastColInd = PHPExcel_Cell::columnIndexFromString($lastCol);
        $actSheet->getStyle("A1:{$lastCol}1")->applyFromArray($headStyle);

        for ($i=0; $i < $lastColInd; $i++){
            $colAlphabet = PHPExcel_Cell::stringFromColumnIndex($i);
            $actSheet->getColumnDimension($colAlphabet)->setAutoSize(true);
        }

        if ($start_date != '' && $end_date == '') {
            $startDateAppendSql = "po.purchase_order_date    >= '{$start_date}' AND po.purchase_order_date      <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $startDateAppendSql = "po.purchase_order_date    >= '{$start_date}' AND po.purchase_order_date      <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $startDateAppendSql = "po.purchase_order_date    >= '{$start_date}' AND po.purchase_order_date      <= '{$end_date}'";
        } else {
            $startDateAppendSql = "po.purchase_order_date    = '{$current_date}'" ;
        }


        $SQL = "
        SELECT po.*
              ,su.company_name
        FROM purchase_order po
        LEFT JOIN (`supplier` su) ON (su.supplier_id = po.company_id_supplier)
        WHERE
        {$startDateAppendSql}
        GROUP BY po.company_id_supplier
        ";

        $result = $db->sql_query($SQL);

        $rows = '';
        $count = 1;
        $TotalAmount = "";
        $PaidAmount = "";
        $BalanceAmount = "";

        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;

            $appendSqlDate2 = '';
            if ($start_date != '' && $end_date == '') {
                $appendSqlDate2 = "AND purchase_order_date >= '{$start_date}' AND purchase_order_date      <= '{$current_date}'";
            } else if ($start_date == '' && $end_date != ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $appendSqlDate2 = "AND purchase_order_date >= '{$start_date}' AND purchase_order_date      <= '{$end_date}'";
            } else if ($start_date != '' && $end_date != '') {
                $appendSqlDate2 = "AND purchase_order_date >= '{$start_date}' AND purchase_order_date      <= '{$end_date}'";
            } else {
                $appendSqlDate2 = "AND purchase_order_date  = '{$current_date}'" ;
            }

            $SQLPO = "
            SELECT purchase_order_id
            FROM purchase_order
            WHERE company_id_supplier = {$row['company_id_supplier']}
            {$appendSqlDate2}
            ";
            $resultPO = $db->sql_query($SQLPO);
            $totalAmount = 0;
            $PaidAmount  = 0;
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


            $rowc++;
            $colc=0 ;

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $count);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['company_name']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $totalCost);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $PaidAmount);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $BalanceAmount);
            $count++;

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

            $SQLPODisp="
            SELECT pc.*
            FROM purchase_order pc
            LEFT JOIN supplier su ON pc.company_id_supplier = su.supplier_id
            WHERE pc.company_id_supplier = {$row['company_id_supplier']}
            AND (pc.payment_status = 'Due' || pc.payment_status = 'Partially Paid')
            {$appendSqlDate}
            ";
            $resultPODisp  = $db->sql_query($SQLPODisp);
            $numRowsPODisp = $db->sql_numrows($resultPODisp);

            if($numRowsPODisp > 0){
                $colc = 1;
                $rowc++;

                $actSheet->getStyle($rowc)->applyFromArray($headStyle);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'PO Date');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'PO Code');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Title');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'PO Value');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Payment Status');
            }

            while ($rowPODisp = $db->sql_fetchrow($resultPODisp)) {
                $colc = 1;
                $rowc++;

                $purchase_order_date = $fn->getCPDate($rowPODisp['purchase_order_date'], 'd-m-Y');

                $SQLTotal = "
                    SELECT SUM(round(
                    (pop.qty * pop.cost_price),2)) AS total_cost
                    FROM po_product pop WHERE pop.purchase_order_id = {$rowPODisp['purchase_order_id']}
                ";
                $resultTotal = $db->sql_query($SQLTotal);
                $rowTotal = $db->sql_fetchrow($resultTotal);
                $totalCost = number_format($rowTotal['total_cost'], 2);

                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $purchase_order_date);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowPODisp['po_code']);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowPODisp['title']);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $totalCost);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowPODisp['payment_status']);
            }
        }

        $rowc++;
        $actSheet->getStyle("A{$rowc}:E{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

}
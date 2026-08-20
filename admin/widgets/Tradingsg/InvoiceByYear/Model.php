<?
class CPL_Admin_Widgets_Tradingsg_InvoiceByYear_Model extends CP_Admin_Widgets_Tradingsg_InvoiceByYear_Model
{
    /**
     *
     */
    function getSQL(){
        $cpCfg = Zend_Registry::get('cpCfg');

	// **** THIS CONDITION HAS BEEN USED ONLY FOR MULTI LOCATION SITE IN BLOSSOMS **** \\
		$appendSql = '' ;
		
		if ($cpCfg['cp.hasMultiUniqueSites']  == 1) {
			$appendSql = ",o.site_id" ;
		}

        $SQL = "
        SELECT CASE WHEN MONTH(i.invoice_date)>=4 THEN
                 concat(YEAR(i.invoice_date), '-',YEAR(i.invoice_date)+1)
               ELSE concat(YEAR(i.invoice_date)-1,'-', YEAR(i.invoice_date)) END AS invoice_year
              ,(SUM(i.invoice_amount)) AS invoice_amount_yearly
              ,YEAR(i.invoice_date) AS start_Year
              ,(YEAR(i.invoice_date)+1) AS end_Year
              {$appendSql}
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

        $start_date  = $fn->getReqParam('start_date');
        $end_date    = $fn->getReqParam('end_date');
        $year        = $fn->getReqParam('year');
        $currentYear = date('Y');
        $nextYear    = date('Y', strtotime('+1 year'));

        /*if ($start_date == '') {
            $start_date = date('Y-m-d', mktime (0,0,0,date("m")-6, date("d"), date("Y")));
        }

        if ($end_date == '') {
            $end_date = date('Y-m-d');
        }*/

        /*if ($year == '') {
            $start_date = $currentYear . '-' . '04' . '-' . '01';
            $end_date   = $nextYear . '-' . '03' . '-' . '31';
        }else{
            $currentYear = $year;
            $nextYear    = $year + 1;
            $start_date  = $currentYear . '-' . '04' . '-' . '01';
            $end_date    = $nextYear . '-' . '03' . '-' . '31';
        }*/

        //$searchVar->sqlSearchVar[] = "i.invoice_date BETWEEN '{$start_date}' AND '{$end_date}'";

        $searchVar->sqlSearchVar[] = "i.status !='Cancelled'";
        $searchVar->groupBy = "invoice_year";

    }

    /**
     *
     */
    function getDataArray() {
        $ln = Zend_Registry::get('ln');

        $modelHelper = Zend_Registry::get('modelHelper');
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_invoiceByYear');

        $this->dataArray = $dataArray ;
        return $dataArray;
    }

    /**
     *
     */
    function getExportToExcelOLD($dataArray = ''){
        $dbUtil = Zend_Registry::get('dbUtil');

        if (!is_array($dataArray)){
            $dataArray = $this->getDataArray();
        }

        $phpExcel = includeCPClass('Lib', 'PhpExcelExportWrapper', 'PhpExcelExportWrapper');
        $fa = array(
              'invoice_year'            => $phpExcel->getFldObj('Year')
             ,'invoice_amount_yearly'   => $phpExcel->getFldObj('Amount')
        );

        $file_name = "InvoiceByYear_" . date("d-m-Y") . ".xls";

        $config = array(
             'filename'  => $file_name
            ,'fldsArr'   => $fa
            ,'dataArray' => $dataArray
        );

        return $phpExcel->exportData($config);
    }

    /**
     *
     */
    function getExportToExcel(){
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "SalesByYear_" . date("d-m-Y") . ".xls";

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
        $actSheet = &$objPHPExcel->getActiveSheet();

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Year');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Amount');
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

        //$year        = $fn->getReqParam('year');
        //$currentYear = $row['start_Year'];
        //$nextYear    = $row['end_Year'];

        //if ($year == '') {
        //$start_date = $currentYear . '-' . '04' . '-' . '01';
        //$end_date   = $nextYear . '-' . '03' . '-' . '31';
        /*}else{
            $currentYear = $year;
            $nextYear    = $year + 1;
            $start_date  = $currentYear . '-' . '04' . '-' . '01';
            $end_date    = $nextYear . '-' . '03' . '-' . '31';
        }*/

         $SQL = "
	     SELECT CASE WHEN MONTH(i.invoice_date)>=4 THEN
                 concat(YEAR(i.invoice_date), '-',YEAR(i.invoice_date)+1)
               ELSE concat(YEAR(i.invoice_date)-1,'-', YEAR(i.invoice_date)) END AS invoice_year
              ,(SUM(i.invoice_amount)) AS invoice_amount_yearly
              ,YEAR(i.invoice_date) AS start_Year
              ,(YEAR(i.invoice_date)+1) AS end_Year
         FROM invoice i
         LEFT JOIN (`order` o) ON (o.order_id = i.order_id)
         WHERE i.status !='Cancelled'
		 GROUP BY invoice_year
		 ";

        $result = $db->sql_query($SQL);

        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;

            $currentYear = $row['start_Year'];
            $nextYear    = $row['end_Year'];

            $start_date = $currentYear . '-' . '04' . '-' . '01';
            $end_date   = $nextYear . '-' . '03' . '-' . '31';

            $SQLinvoice = "
            SELECT  i.invoice_id
                   ,i.p_f
                   ,i.frieght_cost
            FROM invoice i
            LEFT JOIN (`order` o) ON (o.order_id = i.order_id)
            WHERE i.status != 'Cancelled'
            AND i.invoice_date BETWEEN '{$start_date}' AND '{$end_date}'
            ";
            $resultInvoice = $db->sql_query($SQLinvoice);

            $amount = 0;
            $total_Year_Invoice_Amount = 0;
            while ($rowInvoice = $db->sql_fetchrow($resultInvoice)) {
                $sqlInvItem ="
                SELECT SUM(it.qty * it.unit_price) As amount
                FROM invoice_item it
                WHERE it.invoice_id = {$rowInvoice['invoice_id']}
                ";
                $resultInvItem = $db->sql_query($sqlInvItem);
                $rowInvItem = $db->sql_fetchrow($resultInvItem);

                $pfVal = 0;
                if($rowInvoice['p_f'] != ''){
                    $pfVal = $rowInvItem['amount'] * $rowInvoice['p_f'] / 100;
                }

                $frieghtCost = 0;
                if($rowInvoice['frieght_cost'] != ''){
                    $frieghtCost = $rowInvoice['frieght_cost'];
                }

                $amount = $rowInvItem['amount'];

                $total_Year_Invoice_Amount += $amount;
            }

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['invoice_year']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, number_format($total_Year_Invoice_Amount,2));
        }

        $colc = 0;
        $rowc++;

        $actSheet->getStyle("A{$rowc}:B{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

}
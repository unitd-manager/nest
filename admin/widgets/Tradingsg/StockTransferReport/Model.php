<?
class CPL_Admin_Widgets_Tradingsg_StockTransferReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        
        $SQL = "
        SELECT sth.*
              ,st.date
              ,st.to_location
              ,st.from_location
              ,st.status
              ,p.title AS product_title
        FROM stock_transfer_history sth
        LEFT JOIN (`stock_transfer` st) ON (st.stock_transfer_id = sth.stock_transfer_id)
        LEFT JOIN (`product` p) ON (p.product_id = sth.product_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'sth';

        $to_location         = $fn->getReqParam('to_location');
        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');

        if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "st.date >= '{$start_date}' AND st.date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $searchVar->sqlSearchVar[] = "st.date >= '{$start_date}' AND st.date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "st.date >= '{$start_date}' AND st.date <= '{$end_date}'";
        } else {
        }

        if ($to_location != "" ) {
            $searchVar->sqlSearchVar[] = "(st.to_location = '{$to_location}')";
        }

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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_stockTransferReport');

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
        $to_location         = $fn->getReqParam('to_location');
        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $actSheet = &$objPHPExcel->getActiveSheet();

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'S.No');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Date');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'To Location');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Status');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Product');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Qty Requested');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Qty Delivered');

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
            $startDateAppendSql = "AND (st.date >= '{$start_date}' AND st.date <= '{$current_date}')";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $startDateAppendSql = "AND (st.date >= '{$start_date}' AND st.date <= '{$end_date}')";
        } else if ($start_date != '' && $end_date != '') {
            $startDateAppendSql = "AND (st.date >= '{$start_date}' AND st.date <= '{$end_date}')";
        } else {
        }

        $locationAppendSql = '';

        if ($to_location != "" ) {
            $locationAppendSql = "AND (st.to_location = '{$to_location}')";
        }

        $SQL = "
        SELECT sth.*
              ,st.date
              ,st.to_location
              ,st.from_location
              ,st.status
              ,p.title AS product_title
        FROM stock_transfer_history sth
        LEFT JOIN (`stock_transfer` st) ON (st.stock_transfer_id = sth.stock_transfer_id)
        LEFT JOIN (`product` p) ON (p.product_id = sth.product_id)
        WHERE sth.product_id > 0
        {$startDateAppendSql}
        {$locationAppendSql}
        ";

        $result = $db->sql_query($SQL);

        $rows = '';
        $count = 1;
        $TotalAmount = "";
        $PaidAmount = "";
        $BalanceAmount = "";

        while ($row = $db->sql_fetchrow($result)) {
            $colc=0 ;
            $rowc++;
            $date = $fn->getCPDate($row['date'], 'd-m-Y');

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $count);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $date);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['to_location']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['status']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['product_title']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['qty_requested']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['qty']);
            $count++;
        }

        $colc = 0;
        $rowc++;
        $actSheet->getStyle("A{$rowc}:G{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

}
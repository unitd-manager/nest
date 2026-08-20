<?
class CPL_Admin_Widgets_Tradingsg_GSTREPORT_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){

        $SQL = "
        SELECT  o.order_id
               ,DATE_FORMAT(o.order_date, '%d-%m-%Y') AS order_date
               ,SUM((unit_price * qty) - ((unit_price * discount_percentage) /100 * qty) - discount_amount) AS order_amount
               ,oi.gst AS gst
               ,SUM(((unit_price * qty) - ((unit_price * discount_percentage) /100 * qty) - discount_amount) * oi.gst / 100) AS gst_amount
               ,o.cust_company_name AS companyName
               ,o.cust_gst_no
               ,i.invoice_id
               ,i.invoice_code
        FROM `order` o
        LEFT JOIN `order_item` oi ON (oi.order_id = o.order_id)
        LEFT JOIN `invoice` i ON (i.order_id = o.order_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'o';

        $start_date = $fn->getReqParam('start_date');
        $end_date   = $fn->getReqParam('end_date');
        $year       = $fn->getReqParam('year');
        $month      = $fn->getReqParam('month');

        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');

        if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "o.order_date BETWEEN '{$start_date}' AND '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = substr($end_date, 0, 8) . '01';
            $searchVar->sqlSearchVar[] = "o.order_date BETWEEN '{$start_date}' AND '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "o.order_date BETWEEN '{$start_date}' AND '{$end_date}'";
        } else {
            $start_date = $year. '-' .$month. '-01';
            $end_date   = $year. '-' .$month. '-31';

            $searchVar->sqlSearchVar[] = "o.order_date BETWEEN '{$start_date}' AND '{$end_date}'";
        }

        $searchVar->sqlSearchVar[] = "o.order_status != 'Cancelled'";
        $searchVar->sqlSearchVar[] = "i.invoice_id != ''";
        $searchVar->sqlSearchVar[] = "i.status != 'Cancelled'";
        $searchVar->sqlSearchVar[] = "o.gst_status = 'ON'";

        $searchVar->groupBy   = 'oi.order_id, oi.gst';
        $searchVar->sortOrder = "o.order_id DESC";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_gSTREPORT');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

    /**
     *
     */
    function getSqlForCount() {
        $db = Zend_Registry::get('db');

        $total_gst_amount    = 0;
        $total_order_amount  = 0;

        foreach($this->dataArray as $row){
            $total_gst_amount   += $row['gst_amount'];
            $total_order_amount += $row['order_amount'];
        }

        $row = array('total_gst_amount' => $total_gst_amount, 'total_order_amount' => $total_order_amount);

        return $row;
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

        $start_date = $fn->getReqParam('start_date');
        $end_date   = $fn->getReqParam('end_date');
        $year       = $fn->getReqParam('year');
        $month      = $fn->getReqParam('month');
        $order_status = $fn->getReqParam('order_status');

        $rows = '';

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "GSTReport__" . date("d-m-Y") . ".xls";

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

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Date');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Invoice Code');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Order Code');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'GST %');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'GST Amount');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Amount(GST Excluded)');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Company Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'GST No');
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

        $appendSql = '';

        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        
        if ($start_date != '' && $end_date == '') {
            $appendSql .= " o.order_date BETWEEN '{$start_date}' AND '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = substr($end_date, 0, 8) . '01';
            $appendSql .= " o.order_date BETWEEN '{$start_date}' AND '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $appendSql .= " o.order_date BETWEEN '{$start_date}' AND '{$end_date}'";
        } else {
            $start_date = $year . '-' . $month . '-01';
            $end_date   = $year . '-' . $month . '-31';;

            $appendSql .= " o.order_date BETWEEN '{$start_date}' AND '{$end_date}'";
        }

        $SQL = "
        SELECT  o.order_id
               ,DATE_FORMAT(o.order_date, '%d-%m-%Y') AS order_date
               ,SUM((unit_price * qty) - ((unit_price * discount_percentage) /100 * qty) - discount_amount) AS order_amount
               ,oi.gst AS gst
               ,SUM(((unit_price * qty) - ((unit_price * discount_percentage) /100 * qty) - discount_amount) * oi.gst / 100) AS gst_amount
               ,o.cust_company_name AS companyName
               ,o.cust_gst_no
               ,i.invoice_id
               ,i.invoice_code
        FROM `order` o
        LEFT JOIN `order_item` oi ON (oi.order_id = o.order_id)
        LEFT JOIN `invoice` i ON (i.order_id = o.order_id)
        WHERE {$appendSql}
        AND o.order_status != 'Cancelled'
        AND i.invoice_id != ''
        AND i.status != 'Cancelled'
        AND o.gst_status = 'ON'
        GROUP BY oi.order_id, oi.gst
        ORDER BY o.order_id DESC
        ";
        $result = $db->sql_query($SQL);

        $total_order_amount   = 0;
        $total_gst_amount     = 0;
        $overall_order_amount = 0;

        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;

            $totalamount  = $row['gst_amount'] + $row['order_amount'];
            $totalamount  = number_format($totalamount,2);
            $order_amount = number_format($row['order_amount'], 2);
            $gstAmount    = number_format($row['gst_amount'], 2);

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

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['order_date']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $invoice_code);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $orderId);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['gst']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $gstAmount);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $order_amount);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['companyName']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['cust_gst_no']);

            $total_order_amount   += $row['order_amount'];
            $total_gst_amount     += $row['gst_amount'];
            //$overall_order_amount += $row['gst_amount'] + $row['order_amount'];
        }

        $colc = 0;
        $rowc++;

        $total_order_amount   = number_format($total_order_amount, 2);
        $total_gst_amount     = number_format($total_gst_amount, 2);
        //$overall_order_amount = number_format($overall_order_amount, 2);

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, "");
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, "");
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, "");
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, "Total");
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $total_gst_amount);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $total_order_amount);

        $actSheet->getStyle("A{$rowc}:H{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
}
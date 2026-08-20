<?
class CPL_Admin_Widgets_Tradingsg_ProfitByMonth_Model extends CP_Admin_Widgets_Tradingsg_ProfitByMonth_Model
{
    /**
     *
     */
    function getSQL(){
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $price_from_supplier = $fn->getReqParam('price_from_supplier');

        $additional_field = "";
        if ($price_from_supplier == 1) {
            $additional_field .= ",(SUM(oi.price_from_supplier*oi.qty)) AS total_cost_price_monthly";
        }
        else{
            $additional_field .= ",(SUM(oi.cost_price*oi.qty)) AS total_cost_price_monthly";
        }

        $SQL = "
        SELECT DATE_FORMAT(o.order_date, '%M') AS profit_month
               ,o.order_date
        ,(SUM(oi.unit_price*oi.qty)) AS total_selling_price_monthly
        {$additional_field}
        FROM `order_item` oi
        LEFT JOIN `order` o ON (oi.order_id = o.order_id)
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

        $start_date = $fn->getReqParam('start_date');
        $end_date   = $fn->getReqParam('end_date');
        $location_id    = $fn->getReqParam('location_id');
        if ($location_id != '') {
            $searchVar->sqlSearchVar[] = "o .site_id = {$location_id}";
        }

        if ($start_date == '') {
            $start_date = date('Y-m-d', mktime (0,0,0,date("m")-6, date("d"), date("Y")));
        }

        if ($end_date == '') {
            $end_date = date('Y-m-d');
        }

        $searchVar->sqlSearchVar[] = "o.order_status != 'Cancelled'";

        $searchVar->sqlSearchVar[] = "o.order_date BETWEEN '{$start_date}' AND '{$end_date}'";
        $searchVar->groupBy = "DATE_FORMAT(o.order_date, '%Y-%m')";
    }

    /**
     *
     */
    function getDataArray() {
        $modelHelper = Zend_Registry::get('modelHelper');
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_profitByMonth');

        $this->dataArray = $dataArray ;
        return $dataArray;
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

        $file_name = "ProfitByMonth_" . date("d-m-Y") . ".xls";

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
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Month'); 
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

        $start_date = $fn->getReqParam('start_date');
        $end_date   = $fn->getReqParam('end_date');

        $price_from_supplier = $fn->getReqParam('price_from_supplier');

        if ($start_date == '') {
            $start_date = date('Y-m-d', mktime (0,0,0,date("m")-6, date("d"), date("Y")));
        }

        if ($end_date == '') {
            $end_date = date('Y-m-d');
        }

        $orderDate = "o.order_date BETWEEN '{$start_date}' AND '{$end_date}'";

		$SQL = "
  		SELECT DATE_FORMAT(o.order_date, '%M') AS profit_month
        	,(SUM(oi.unit_price*oi.qty)) AS total_selling_price_monthly
        	,(SUM(oi.price_from_supplier*oi.qty)) AS total_cost_price_monthly
        FROM `order_item` oi
        LEFT JOIN `order` o ON (oi.order_id = o.order_id)
        WHERE {$orderDate}
        AND o.order_status != 'Cancelled'
 		GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
 		";

        $result = $db->sql_query($SQL);

        $payment_total = '';
		$total = '';
        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;

            $additional_field = 0;

            if ($price_from_supplier == 1) {
                $additional_field = $row['total_cost_price_monthly'];
            }

            $total_profit = $row['total_selling_price_monthly'] - $additional_field;
            $payment_total += $total_profit;
            $total_profit = number_format($total_profit, 2);


            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['profit_month']);  
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $total_profit);
        }

        $colc = 0;
        $rowc++;

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, number_format($payment_total,2));

        $actSheet->getStyle("A{$rowc}:B{$rowc}")->applyFromArray($headStyle);           

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
}
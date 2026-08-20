<?
class CPL_Admin_Widgets_Tradingsg_SalesSummaryByProduct_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){

        $SQL = "
        SELECT oi.*
              ,o.order_date
              ,p.item_code
              ,SUM(oi.qty) AS quantity
              ,s.company_name
              ,s.supplier_id
        FROM order_item oi
        LEFT JOIN (`order` o) ON (oi.order_id = o.order_id)
        LEFT JOIN (`invoice` i) ON (o.order_id = i.order_id)
        LEFT JOIN product p ON (oi.record_id = p.product_id)
        LEFT JOIN product_company pc ON(pc.product_id = p.product_id)
        LEFT JOIN supplier s ON(s.supplier_id = pc.company_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'oi';

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        $product_id     = $fn->getReqParam('product_id');
        $supplier_id     = $fn->getReqParam('supplier_id');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $location_id    = $fn->getReqParam('location_id');
        if ($location_id != '') {
            $searchVar->sqlSearchVar[] = "o.site_id = {$location_id}";
        }

        if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "o.order_date >= '{$start_date}' AND o.order_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $searchVar->sqlSearchVar[] = "o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else {
            $searchVar->sqlSearchVar[] = "o.order_date = '{$current_date}'" ;
        }

        if ($product_id != '') {
            $searchVar->sqlSearchVar[] = "p.product_id = '{$product_id}'";
        }

        if ($supplier_id != '') {
            $searchVar->sqlSearchVar[] = "p.supplier_id = '{$supplier_id}'";
        }

        $searchVar->sqlSearchVar[] = "o.order_status != 'Cancelled'" ;
        $searchVar->groupBy = "p.item_code";
        $searchVar->sortOrder = 'o.order_date DESC';

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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_salesSummaryByProduct');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

    /**
     */
    function getExportToExcel(){
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "SalesSummaryByProduct_" . date("d-m-Y") . ".xls";

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

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Item Code');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Item Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Supplier');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Qty');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Price');
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

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $product_id     = $fn->getReqParam('product_id');
        $current_date   = date('Y-m-d');
        $supplier_id     = $fn->getReqParam('supplier_id');

        if ($start_date != '' && $end_date == '') {
            $orderDate = "o.order_date >= '{$start_date}' AND o.order_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $orderDate = "o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $orderDate = "o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else {
            $orderDate = "o.order_date = '{$current_date}'" ;
        }


        $productID = '' ;
        if ($product_id != '') {
            $productID = "AND p.product_id = '{$product_id}'";
        }

        $supplierId = '' ;
        if ($supplier_id != '') {
            $supplierId = "AND p.supplier_id = '{$supplier_id}'";
        }

        $SQL = "
        SELECT oi.*
              ,o.order_date
              ,p.item_code
              ,SUM(oi.qty) AS quantity
              ,s.company_name
              ,s.supplier_id
        FROM order_item oi
        LEFT JOIN (`order` o) ON (oi.order_id = o.order_id)
        LEFT JOIN (`invoice` i) ON (o.order_id = i.order_id)
        LEFT JOIN product p ON (oi.record_id = p.product_id)
        LEFT JOIN product_company pc ON(pc.product_id = p.product_id)
        LEFT JOIN supplier s ON(s.supplier_id = pc.company_id)
        WHERE
        {$orderDate}
        {$productID}
        {$supplierId}
        AND o.order_status != 'Cancelled'
        GROUP BY p.item_code
        ORDER BY o.order_date DESC
        ";

        $result = $db->sql_query($SQL);

        $payment_total = '';
        $total = 0;

        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;
            $amount = $row['quantity'] * $row['unit_price'];
            $total += $amount;
            $amount = number_format($amount, 2);

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['item_code']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['item_title']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['company_name']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['quantity']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['unit_price']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $amount);
        }

        $colc = 0;
        $rowc++;

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $total);

        $rowc++;
        $actSheet->getStyle("A{$rowc}:D{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
}
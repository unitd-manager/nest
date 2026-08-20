<?
class CPL_Admin_Widgets_Tradingsg_PurchaseOrderReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        $fn = Zend_Registry::get('fn');
        $appendSQL = '';

        /*$SQL = "
        SELECT po.*
              ,clnt.company_name
        FROM purchase_order po
        LEFT JOIN company clnt ON clnt.company_id = po.company_id_supplier
        ";*/
        $SQL = "
        SELECT pop.*
              ,po.po_code
              ,po.creation_date AS po_date
              ,clnt.company_name
              ,p.part_number
              ,p.title AS item_title
              ,pg.title
        FROM po_product pop
        LEFT JOIN (`purchase_order` po) ON (po.purchase_order_id = pop.purchase_order_id)
        LEFT JOIN company clnt ON clnt.company_id = po.company_id_supplier
        LEFT JOIN product p ON (pop.product_id = p.product_id)
        LEFT JOIN product_group pg ON (pg.product_group_id = p.product_group_id)
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
        $cpCfg = Zend_Registry::get('cpCfg');

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $company_id     = $fn->getReqParam('company_id');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');

        if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(po.creation_date , '%Y-%m-%d')  >= '{$start_date}' AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(po.creation_date , '%Y-%m-%d')  >= '{$start_date}' AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(po.creation_date , '%Y-%m-%d')  >= '{$start_date}' AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  <= '{$end_date}'";
        /*} else if ($monthVal == '' && $yearVal == ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(po.creation_date , '%Y-%m-%d')  >= '{$start_date}' AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  <= '{$end_date}'";*/
        }

        if ($company_id != "") {
            $searchVar->sqlSearchVar[] = "po.company_id_supplier = '{$company_id}'";
        }

        if ($monthVal != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(po.creation_date , '%m') = '{$monthVal}'" ;
        }

        if ($yearVal != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(po.creation_date , '%Y') = '{$yearVal}'" ;
        }

        $searchVar->sqlSearchVar[] = "po.status != 'Cancelled'";
        //$searchVar->groupBy = "po.creation_date";
        $searchVar->sortOrder = 'po.creation_date DESC';
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_purchaseOrderReport');

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


        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "ExpenseReport_" . date("d-m-Y") . ".xls";

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

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'PO Code');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'PO Date');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Part Number');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Item Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Product Group Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Supplier Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Quantity');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Unit Price');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total Amount');

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
        $company_id     = $fn->getReqParam('company_id');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');

        $appendFollowUpDateSQL = '';
        if ($start_date != '' && $end_date == '') {
            $appendFollowUpDateSQL = "AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  >= '{$start_date}' AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $appendFollowUpDateSQL = "AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  >= '{$start_date}' AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $appendFollowUpDateSQL = "AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  >= '{$start_date}' AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  <= '{$end_date}'";
        } else if ($monthVal == '' && $yearVal == ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $appendFollowUpDateSQL = "AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  >= '{$start_date}' AND DATE_FORMAT(po.creation_date , '%Y-%m-%d')  <= '{$end_date}'";
        }

        $monthSql = '';
        if ($monthVal != '') {
            $monthSql = "AND DATE_FORMAT(po.creation_date , '%m') = '{$monthVal}'" ;
        }

        $yearSql = '';
        if ($yearVal != '') {
            $yearSql = "AND DATE_FORMAT(po.creation_date , '%Y') = '{$yearVal}'" ;
        }

        $comSql = '';
        if ($company_id != "") {
            $comSql = "AND po.company_id_supplier = '{$company_id}'";
        }

        $count =1;

        $SQL = "
        SELECT pop.*
              ,po.po_code
              ,po.creation_date AS po_date
              ,clnt.company_name
              ,p.part_number
              ,p.title AS item_title
              ,pg.title
        FROM po_product pop
        LEFT JOIN (`purchase_order` po) ON (po.purchase_order_id = pop.purchase_order_id)
        LEFT JOIN company clnt ON clnt.company_id = po.company_id_supplier
        LEFT JOIN product p ON (pop.product_id = p.product_id)
        LEFT JOIN product_group pg ON (pg.product_group_id = p.product_group_id)
        WHERE po.status != 'Cancelled'
        {$appendFollowUpDateSQL}
        {$monthSql}
        {$yearSql}
        {$comSql}
        ORDER BY po.creation_date DESC
        ";

        $result = $db->sql_query($SQL);
        $amount = 0;

        while ($row = $db->sql_fetchrow($result)) {

            $colc = 0;
            $rowc++;

            //$follow_up_date = $fn->getCPDate($row['follow_up_date'],"d-m-Y");
            $po_date = $fn->getCPDate($row['po_date'],"d-m-Y");
            $price = number_format($row['price'],2);
            $totalAmount = $row['qty'] * $row['price'];
            $amount += $totalAmount;
            $totalAmount = number_format($totalAmount,2);

            $SQLTotal = "
                SELECT SUM(round(
                (pop.qty * pop.price),2)) AS total_cost
                FROM po_product pop
                WHERE pop.purchase_order_id = {$row['purchase_order_id']}
            ";
            $resultTotal = $db->sql_query($SQLTotal);
            $rowTotal = $db->sql_fetchrow($resultTotal);
            $totalCost = number_format($rowTotal['total_cost'], 2);

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['po_code']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $po_date);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['part_number']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['item_title']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['title']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['company_name']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['qty']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $price);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $totalAmount);

            $count++;

        }

        $rowc++;
        $actSheet->getStyle("A{$rowc}:D{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

}
<?
class CPL_Admin_Widgets_Tradingsg_StockReport_Model extends CP_Admin_Widgets_Tradingsg_StockReport_Model
{
    /**
     *
     */
    function getSQL(){
        $cpCfg = Zend_Registry::get('cpCfg');

        $SQL = "
        SELECT i.*
        	  ,p.title AS product_title
              ,p.part_number
              ,p.item_code
        FROM inventory i
        LEFT JOIN (product p) ON (p.product_id = i.product_id)
        LEFT JOIN (product_company pc) ON (pc.product_id = p.product_id)
        ";

        $SQL = "
        SELECT sh.*
              ,p.title AS product_title
              ,p.part_number
              ,p.item_code
              ,SUM(sh.qty) AS purchased_qty
              ,SUM(sh.damage_qty) AS damaged_qty
        FROM stock_history sh
        LEFT JOIN (product p) ON (p.product_id = sh.product_id)
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'sh';

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        $location_id    = $fn->getReqParam('location_id');
        $company_id     = $fn->getReqParam('company_id');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');

        /*if ($location_id != '') {
            $searchVar->sqlSearchVar[] = "i.site_id = {$location_id}";
        }*/

        if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "sh.creation_date >= '{$start_date}' AND sh.creation_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $searchVar->sqlSearchVar[] = "sh.creation_date >= '{$start_date}' AND sh.creation_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "sh.creation_date >= '{$start_date}' AND sh.creation_date <= '{$end_date}'";
        /*} else {
            $searchVar->sqlSearchVar[] = "sh.creation_date    = '{$current_date}'" ;*/
        }

        /*if ($company_id != '') {
            $searchVar->sqlSearchVar[] = "pc.company_id = {$company_id}";
        }*/

        $searchVar->sqlSearchVar[] = "p.title != ''";

        $searchVar->groupBy       = "sh.product_id";
        $searchVar->sortOrder       = "p.product_id ASC";
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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_stockReport');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

    /**
     *
     */
    function getExportToExcel12($dataArray = ''){
        $dbUtil = Zend_Registry::get('dbUtil');


        if (!is_array($dataArray)){
            $dataArray = $this->getDataArray();
        }

        $phpExcel = includeCPClass('Lib', 'PhpExcelExportWrapper', 'PhpExcelExportWrapper');
        $fa = array(
              'contactDate'         => $phpExcel->getFldObj('Date')
             ,'company_name'        => $phpExcel->getFldObj('Client')
             ,'comments'            => $phpExcel->getFldObj('Meeting Notes')
             ,'staff_name'  		=> $phpExcel->getFldObj('Staff')
        );

        $file_name = "LeadByStaff_" . date("d-m-Y") . ".xls";

        $config = array(
             'filename'  => $file_name
            ,'fldsArr'   => $fa
            ,'dataArray' => $dataArray
        );

        return $phpExcel->exportData($config);
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

        $staff_id  = $fn->getReqParam('staff_id');
        $start_date = $fn->getReqParam('start_date');
        $end_date   = $fn->getReqParam('end_date');
        $month      = $fn->getReqParam('month');
        $year       = $fn->getReqParam('year');
        $current_date = date('Y-m-d');

        $rows = '';
        $appendSql = '';


        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "StockReport__" . date("d-m-Y") . ".xls";

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
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Product Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total Stock');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Total Cost');
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


        $SQL = "
        SELECT i.*
              ,p.title AS product_title
              ,p.carton_no
              ,p.item_code
              ,p.model
        FROM inventory i
        LEFT JOIN (product p) ON (p.product_id = i.product_id)
        LEFT JOIN (product_company pc) ON (pc.product_id = p.product_id)
        WHERE p.title != ''
        ORDER BY p.product_id ASC
        ";
        $result = $db->sql_query($SQL);

        $sum_purchase_cp_per_qty = 0;

        $linkToStock = '' ;

        if($cpCfg['cp.excludeStock'] == 1){
            $linkToStock = "AND o.link_stock = 1";
        }

        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;

            $StockSql = "
            SELECT
                (SELECT SUM(qty) FROM po_product
                WHERE product_id = {$row['product_id']}
                ) AS product_qty_purchased
                
                ,(SELECT SUM(oi.qty) FROM order_item oi
                LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
                WHERE oi.record_id = {$row['product_id']}
                  AND o.order_status = 'Paid'
                ) AS product_qty_sold

                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled')
                WHERE ini.record_id = {$row['product_id']}
                  AND srh.status IS NULL
                ) AS sales_return_qty

                ,(SELECT SUM(damage_qty) FROM po_product
                WHERE product_id = {$row['product_id']}
                ) AS damaged_qty

                ,(SELECT pp.cost_price FROM po_product pp
                WHERE pp.product_id = {$row['product_id']}
                ORDER BY pp.po_product_id DESC
                LIMIT 0,1
                ) AS purchase_cp_per_qty
            ";
            $resultStockSql = $db->sql_query($StockSql);
            $rowStockSql    = $db->sql_fetchrow($resultStockSql);

            $stock = $rowStockSql['product_qty_purchased'] - $rowStockSql['product_qty_sold'] + $rowStockSql['sales_return_qty'] - $rowStockSql['damaged_qty'];
            $sum_purchase_cp_per_qty = $stock * $rowStockSql['purchase_cp_per_qty'];
            $sum_purchase_cp_per_qty = number_format($sum_purchase_cp_per_qty, 2);

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['item_code']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['product_title']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $stock);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $sum_purchase_cp_per_qty);
        }

        $rowc++;
        $actSheet->getStyle("A{$rowc}:D{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

}
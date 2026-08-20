<?
class CPL_Admin_Widgets_Tradingsg_PriceTrackReport_Model extends CP_Common_Lib_WidgetModelAbstract
{
    /**
     *
     */
    function getSQL(){
        $cpCfg = Zend_Registry::get('cpCfg');
        $fn = Zend_Registry::get('fn');

        $SQL = "
        SELECT p.title
              ,p.product_id
        FROM `product_price` pp
        LEFT JOIN ( `product` p ) ON ( p.product_id = pp.product_id )
        ";

        return $SQL;
    }

    /**
     *
     */
    function setSearchVar() {
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');
        $tv = Zend_Registry::get('tv');
        $searchVar = $this->searchVar;
        $searchVar->mainTableAlias = 'pp';

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $supplier_id    = $fn->getReqParam('supplier_id');
        $section_id     = $fn->getReqParam('section_id');


        if ($start_date != '' && $end_date == '') {
            $searchVar->sqlSearchVar[] = "pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $searchVar->sqlSearchVar[] = "pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $searchVar->sqlSearchVar[] = "pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
        } else if ($monthVal == '' && $yearVal == ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $searchVar->sqlSearchVar[] = "pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
        }

        if ($supplier_id != '' ) {
                $searchVar->sqlSearchVar[] = "p.supplier_id = '{$supplier_id}'";
        }

        if ($section_id != '' ) {
            $searchVar->sqlSearchVar[] = "p.section_id = '{$section_id}'";
        }

        if ($monthVal != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(pp.creation_date, '%m') = '{$monthVal}'" ;
        }
        if ($yearVal != '') {
            $searchVar->sqlSearchVar[] = "DATE_FORMAT(pp.creation_date, '%Y') = '{$yearVal}'" ;
        }

        $searchVar->groupBy   = "pp.product_id";
        $searchVar->sortOrder = "p.title ASC";

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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_priceTrackReport');

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

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');
        $monthVal       = $fn->getReqParam('month');
        $yearVal        = $fn->getReqParam('year');
        $supplier_id    = $fn->getReqParam('supplier_id');
        $section_id     = $fn->getReqParam('section_id');

        $rows = '';


        set_time_limit(50000);
        ini_set('memory_limit', '512M');

        require_once("PHPExcel.php");
        include 'PHPExcel/IOFactory.php';

        $file_name = "Price_Track_Report__" . date("d-m-Y") . ".xls";

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


        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Product Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Highest Price');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Lowest Price');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Recently Changed Price');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'No of times Changed');
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

        $appendSqlMain = "";
        if ($start_date != '' && $end_date == '') {
            $appendSqlMain = "pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $appendSqlMain = "pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $appendSqlMain = "pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
        } else if ($monthVal == '' && $yearVal == ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $appendSqlMain = "pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
        }

        if ($supplier_id != '' ) {
            $appendSqlMain .= "AND p.supplier_id = '{$supplier_id}'";
        }

        if ($section_id != '' ) {
            $appendSqlMain .= "AND p.section_id = '{$section_id}'";
        }

        if ($monthVal != '') {
            $appendSqlMain .= "DATE_FORMAT(pp.creation_date, '%m') = '{$monthVal}'" ;
        }
        
        if ($yearVal != '') {
            $appendSqlMain .= "DATE_FORMAT(pp.creation_date, '%Y') = '{$yearVal}'" ;
        }

        $SQL = "
        SELECT p.title
              ,p.product_id
        FROM `product_price` pp
        LEFT JOIN ( `product` p ) ON ( p.product_id = pp.product_id )
        WHERE {$appendSqlMain}
        GROUP BY pp.product_id
        ORDER BY p.title ASC
        ";
        $result1 = $db->sql_query($SQL);
        $total = 0;
        $highest_price = 0;
        $lowest_price = 0;
        $recent_price = 0;

        while ($row = $db->sql_fetchrow($result1)) {

            $appendSql = '';
            if ($start_date != '' && $end_date == '') {
                $appendSql .= "AND pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$current_date}'";
            } else if ($start_date == '' && $end_date != ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $appendSql .= "AND pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
            } else if ($start_date != '' && $end_date != '') {
                $appendSql .= "AND pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
            } else if ($monthVal == '' && $yearVal == ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $end_date = $year . '-' . $month . '-' . '31';
                $appendSql .= "AND pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
            }
            
            $SQLPriceOthers = "
            SELECT 
                (SELECT MAX( pp.price ) AS highestPrice
                FROM `product_price` pp
                WHERE pp.product_id = {$row['product_id']}
                {$appendSql}
                ) AS highest_price

                ,(SELECT MIN( pp.price ) AS lowestPrice
                FROM `product_price` pp
                WHERE pp.product_id = {$row['product_id']}
                {$appendSql}
                ) AS lowest_price

                ,(SELECT pp.price  
                FROM `product_price` pp
                WHERE pp.product_id = {$row['product_id']}
                {$appendSql}
                ORDER BY pp.product_price_id DESC
                LIMIT 0,1
                ) AS recent_price
                
                ,(SELECT count( pp.price ) AS timeChangedCount
                FROM `product_price` pp
                WHERE pp.product_id = {$row['product_id']}
                {$appendSql}
                ) AS time_changed_count
            ";
            $resultPriceOthers = $db->sql_query($SQLPriceOthers);
            $rowPriceOthers = $db->sql_fetchrow($resultPriceOthers);
            $highest_price = number_format($rowPriceOthers['highest_price'], 2);
            $lowest_price  = number_format($rowPriceOthers['lowest_price'], 2);
            $recent_price  = number_format($rowPriceOthers['recent_price'], 2);

            $colc = 0;
            $rowc++;

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['title']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $highest_price);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $lowest_price);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $recent_price);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowPriceOthers['time_changed_count']);
        }

        $colc = 0;
        $rowc++;

        $actSheet->getStyle("A{$rowc}:E{$rowc}")->applyFromArray($headStyle);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }




}
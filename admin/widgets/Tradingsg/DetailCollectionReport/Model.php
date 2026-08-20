<?
class CPL_Admin_Widgets_Tradingsg_DetailCollectionReport_Model extends CP_Common_Lib_WidgetModelAbstract
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
        SELECT o.order_date
        	  ,o.order_status
              ,o.order_id
              ,o.record_type
              ,c.company_name
              ,o.discount
              {$appendSql}
        FROM `order` o
        LEFT JOIN company c ON (c.company_id = o.company_id)
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

        $month      	= $fn->getReqParam('month');
        $year       	= $fn->getReqParam('year');
        $order_status   = $fn->getReqParam('order_status');
        $current_date 	= date('Y-m-d');
        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $record_type    = $fn->getReqParam('record_type');
        $location_id    = $fn->getReqParam('location_id');
        if ($location_id != '') {
            $searchVar->sqlSearchVar[] = "o .site_id = {$location_id}";
        }

        if ($month != ''){
            if ($year != '') {
                $startMonth = $year . '-' . $month . '-' . '01';
                $endMonth   = $year . '-' . $month . '-' . '31';
            } else {
                $year = date('Y');
                $startMonth = $year . '-' . $month . '-' . '01';
                $endMonth   = $year . '-' . $month . '-' . '31';
            }
            $searchVar->sqlSearchVar[] = "o.order_date BETWEEN '{$startMonth}' AND '{$endMonth}'";
        }

        if ($year != ''){
            $startYear = $year .'-01-01';
            $endYear   = $year .'-12-31';

            $searchVar->sqlSearchVar[] = "o.order_date BETWEEN '{$startYear}' AND '{$endYear}'";
        }


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
            $searchVar->sqlSearchVar[] = "o.order_date = '{$current_date}'";
        }

        if ($record_type == 'Quote') {
            $searchVar->sqlSearchVar[] = "o.record_type = 'Quote'";
        } else {
            $searchVar->sqlSearchVar[] = "o.record_type = 'POS'";
        }

		if ($order_status != '') {
			$searchVar->sqlSearchVar[] = "o.order_status = '{$order_status}'";
		}

        if($cpCfg['cp.excludeStock'] == 1){
            $searchVar->sqlSearchVar[] = "o.link_stock = 1";
        }

        $searchVar->sortOrder = 'o.order_date DESC';

        $searchVar->sortOrder = 'o.creation_date DESC';

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
        $dataArray = $modelHelper->getWidgetDataArray($this->controller, 'tradingsg_detailCollectionReport');

        $this->dataArray = $dataArray;
        return $this->dataArray;
    }

    /**
     *
     */
    function getSqlForCount() {
        $db = Zend_Registry::get('db');

        $serial_no   = 0;
        $grand_total = 0;
        $profitAmount  = 0;
        $totalProfitAmount = 0;
        $OverallTotalProfitAmount = 0;
        foreach($this->dataArray as $row){
            $modObj       = getCPModuleObj('tradingsg_pos');
            $order_amount = $modObj->view->getTotalAmount($row['order_id']);

            $subSqlForPercentSum = "
            SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2)) as discount_sum
            FROM order_item oi
            WHERE oi.order_id = {$row['order_id']}
              AND oi.discount_type = '%'
            ";
            $resultSubSql = $db->sql_query($subSqlForPercentSum);
            $rowSql       = $db->sql_fetchrow($resultSubSql);
            if($rowSql['discount_sum'] > 0){
                $subSqlForPercentSum = "
                SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2))
                FROM order_item oi
                WHERE oi.order_id = {$row['order_id']}
                  AND oi.discount_type = '%'
                ";
            }
            else{
                $subSqlForPercentSum = 0;
            }


            //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
            $subSqlForValueSum ="
            SELECT SUM(round(oi.discount_amount  * oi.qty,2)) as discount_sum
            FROM order_item oi
            WHERE oi.order_id = {$row['order_id']}
              AND oi.discount_type = 'Value'
            ";
            $resultSubSql = $db->sql_query($subSqlForValueSum);
            $rowSql       = $db->sql_fetchrow($resultSubSql);
            if($rowSql['discount_sum'] > 0){
                $subSqlForValueSum ="
                SELECT SUM(round(oi.discount_amount  * oi.qty,2))
                FROM order_item oi
                WHERE oi.order_id = {$row['order_id']}
                  AND oi.discount_type = 'Value'
                ";
            }
            else{
                $subSqlForValueSum = 0;
            }


            $SQLOrderItem = "
            SELECT oi.*
                  ,o.discount
                  ,o.gst_status
                  ,p.item_code
                  ,p.unit
                  ,p.tag_no
                  ,(SELECT
                  ($subSqlForPercentSum)
                   +
                  ($subSqlForValueSum)) as discount_percentage_amount_sum
            FROM order_item oi
            LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
            LEFT JOIN (product p) ON (p.product_id = oi.record_id)
            WHERE oi.order_id = {$row['order_id']}
            ";
            $resultOrderItem   = $db->sql_query($SQLOrderItem);
            $numRowsOrderItem  = $db->sql_numrows($resultOrderItem);
            $OrderItemRow      = '';
            $count             = 1;
            $subTotal          = 0;
            $gstValue          = 0;
            $discount          = 0;
            $profitAmount      = 0;
            $totalProfitAmount = 0;
            while ($rowOrderItem = $db->sql_fetchrow($resultOrderItem)) {
                $item_code = $rowOrderItem['item_code'];
                $tag_no    = $rowOrderItem['tag_no'];
                $unit      = $rowOrderItem['unit'];

                $discount_value_for_one_qty = '';
                $discountValue = 0;
                $discount_percentage = '';
                $discount_percentage_type =0;
                if($rowOrderItem['discount_percentage'] > 0 || $rowOrderItem['discount_amount'] > 0){
                    if($rowOrderItem['discount_type'] == '%'){
                        $discount_value_for_one_qty  =  $rowOrderItem['unit_price'] * ($rowOrderItem['discount_percentage'] / 100);
                        $discountValue = $discount_value_for_one_qty;
                        $discount_percentage_type = $discountValue;
                        $discount_percentage = '';
                    }
                    else if($rowOrderItem['discount_type']  == 'Value'){
                        $discount_value_for_one_qty  =  $rowOrderItem['discount_amount'];
                        $discountValue = $discount_value_for_one_qty;
                        $discount_percentage = $rowOrderItem['discount_amount'];
                        $discount_percentage_type = $rowOrderItem['discount_amount'];
                    }
                }

                $total = ($rowOrderItem['unit_price'] - $discount_value_for_one_qty) * $rowOrderItem['qty'];

                if($rowOrderItem['unit_price'] == ""){
                    $rowOrderItem['unit_price'] = 0;
                }

                $profitAmount = $total - ($rowOrderItem['qty'] * $rowOrderItem['cost_price']);

                if($rowOrderItem['gst_status'] == "ON"){
                    $gstValue = $total * $rowOrderItem['gst'] / 100;
                    $total    = $total + $gstValue;
                }

                $totalProfitAmount += $profitAmount;
                $discount      = $row['discount'];
            }
            
            if($totalProfitAmount > 0 || $totalProfitAmount < 0){
                $totalProfitAmount = $totalProfitAmount - $discount;
            }

            $OverallTotalProfitAmount += $totalProfitAmount;
            $serial_no   += 1;
            $grand_total += $order_amount;
        }

        $row = array('grand_total' => $grand_total, 'profit_total' => $OverallTotalProfitAmount);

        return $row;
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

        $file_name = "DetailCollectionReport_" . date("d-m-Y") . ".xls";

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
        $record_type    = $fn->getReqParam('record_type');
        $order_status   = $fn->getReqParam('order_status');
        $location_id    = $fn->getReqParam('location_id');
        $actSheet = &$objPHPExcel->getActiveSheet();

        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Order Date');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Order No');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Company Name');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Discount Given');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Amount');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Order Status');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Profit Amount');
        if($cpCfg['cp.hasMultiUniqueSites'] == 1){
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Location');
        }

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

        $appendSqlDate = '';

        if ($start_date != '' && $end_date == '') {
            $appendSqlDate = "o.order_date >= '{$start_date}' AND o.order_date <= '{$current_date}'";
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $appendSqlDate = "o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else if ($start_date != '' && $end_date != '') {
            $appendSqlDate = "o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
        } else {
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $appendSqlDate = "o.order_date = '{$current_date}'";
        }


        $appendSql = '';
        if ($location_id != '') {
            $appendSql = "AND o.site_id = {$location_id}";
        }

        if ($record_type == 'Quote') {
            $recordType = "AND o.record_type = 'Quote'";
        } else {
            $recordType = "AND o.record_type = 'POS'";
        }

        $sumTxt = '';
        if ($cpCfg['m.ecommerce.order.hasDiscount']){
            $sumTxt = "SUM(oi.unit_price * oi.qty) + o.shipping_charge - o.discount";
        } else {
            $sumTxt = "SUM(oi.unit_price * oi.qty) + o.shipping_charge";
        }

        $statusSql = '';
        if ($order_status != '') {
            $statusSql = "AND o.order_status = '{$order_status}'";
        }

        $linkToStock = '' ;

        if($cpCfg['cp.excludeStock'] == 1){
            $linkToStock = "AND o.link_stock = 1";
        }

        $siteTitle = '' ;

        if ($cpCfg['cp.hasMultiUniqueSites']  == 1) {
            $siteTitle = ",o.site_id" ;
        }

        $SQL = "
        SELECT o.order_date
              ,o.order_status
              ,o.order_id
              ,o.record_type
              ,c.company_name
              ,o.discount
              ,o.gst_status
              ,(SELECT SUM(oi.qty * oi.cost_price)
                FROM order_item oi
                WHERE oi.order_id = o.order_id
               )OrderAmountCost
        FROM `order` o
        LEFT JOIN company c ON (c.company_id = o.company_id)
        WHERE {$appendSqlDate}
              {$appendSql}
              {$recordType}
              {$statusSql}
              {$linkToStock}
        ORDER BY o.order_date DESC,o.creation_date DESC
        ";

        $result = $db->sql_query($SQL);

        $grand_total = 0 ;
        $grand_totalfrm = 0;
        $profitAmount  = 0;
        $totalProfitAmount = 0;
        while ($row = $db->sql_fetchrow($result)) {
            $colc = 0;
            $rowc++;

            $subSqlForPercentSum = "
            SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2)) as discount_sum
            FROM order_item oi
            WHERE oi.order_id = {$row['order_id']}
              AND oi.discount_type = '%'
            ";
            $resultSubSql = $db->sql_query($subSqlForPercentSum);
            $rowSql       = $db->sql_fetchrow($resultSubSql);
            if($rowSql['discount_sum'] > 0){
                $subSqlForPercentSum = "
                SELECT SUM(round(((oi.unit_price * oi.discount_percentage )/100)* oi.qty,2))
                FROM order_item oi
                WHERE oi.order_id = {$row['order_id']}
                  AND oi.discount_type = '%'
                ";
            }
            else{
                $subSqlForPercentSum = 0;
            }


            //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
            $subSqlForValueSum ="
            SELECT SUM(round(oi.discount_amount  * oi.qty,2)) as discount_sum
            FROM order_item oi
            WHERE oi.order_id = {$row['order_id']}
              AND oi.discount_type = 'Value'
            ";
            $resultSubSql = $db->sql_query($subSqlForValueSum);
            $rowSql       = $db->sql_fetchrow($resultSubSql);
            if($rowSql['discount_sum'] > 0){
                $subSqlForValueSum ="
                SELECT SUM(round(oi.discount_amount  * oi.qty,2))
                FROM order_item oi
                WHERE oi.order_id = {$row['order_id']}
                  AND oi.discount_type = 'Value'
                ";
            }
            else{
                $subSqlForValueSum = 0;
            }


            $SQLOrderItem = "
            SELECT oi.*
                  ,o.discount
                  ,o.gst_status
                  ,p.item_code
                  ,p.unit
                  ,p.tag_no
                  ,(SELECT
                  ($subSqlForPercentSum)
                   +
                  ($subSqlForValueSum)) as discount_percentage_amount_sum
            FROM order_item oi
            LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
            LEFT JOIN (product p) ON (p.product_id = oi.record_id)
            WHERE oi.order_id = {$row['order_id']}
            ";
            $resultOrderItem   = $db->sql_query($SQLOrderItem);
            $numRowsOrderItem  = $db->sql_numrows($resultOrderItem);

            $modObj       = getCPModuleObj('tradingsg_pos');
            $order_amount = $modObj->view->getTotalAmount($row['order_id']);
            $grand_totalfrm += $order_amount;
            $profitAmountOrder = $modObj->view->getProfitAmount($row['order_id']); 
            $totalProfitAmount += $profitAmountOrder;
            $creationDate = $fn->getCPDate($row['order_date'],"d-m-Y");
            $order_amount = number_format($order_amount, 2);
            $discount     = number_format($row['discount'], 2);
            $gst_status   = $row['gst_status'];

            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $creationDate);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['order_id']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['company_name']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $discount);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $order_amount);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $row['order_status']);
            $actSheet->setCellValueByColumnAndRow($colc++, $rowc, number_format($profitAmountOrder, 2));

            if($numRowsOrderItem > 0){
                $colc = 1;
                $rowc++;

                $actSheet->getStyle($rowc)->applyFromArray($headStyle);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'S.No');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Product Name');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Qty');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Cost Price');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Selling Price');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Discount');
                if($gst_status == "ON"){
                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Gst');
                }
                
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Amount');
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Profit Amount');
            }

            $OrderItemRow = '';
            $count           = 1;
            $subTotal        = 0;
            $gstValue        = 0;
            $discount        = 0;
            $profitAmount      = 0;
            while ($rowOrderItem = $db->sql_fetchrow($resultOrderItem)) {
                $item_code = $rowOrderItem['item_code'];
                $tag_no    = $rowOrderItem['tag_no'];
                $unit      = $rowOrderItem['unit'];

                $discount_value_for_one_qty = '';
                $discountValue = 0;
                $discount_percentage = '';
                $discount_percentage_type =0;
                if($rowOrderItem['discount_percentage'] > 0 || $rowOrderItem['discount_amount'] > 0){
                    if($rowOrderItem['discount_type'] == '%'){
                        $discount_value_for_one_qty  =  $rowOrderItem['unit_price'] * ($rowOrderItem['discount_percentage']/100);
                        $discountValue = $discount_value_for_one_qty;
                        $discount_percentage_type = $discountValue;
                        $discount_percentage = '';
                    }
                    else if($rowOrderItem['discount_type']  == 'Value'){
                        $discount_value_for_one_qty  =  $rowOrderItem['discount_amount'];
                        $discountValue = $discount_value_for_one_qty;
                        $discount_percentage = $rowOrderItem['discount_amount'];
                        $discount_percentage_type = $rowOrderItem['discount_amount'];
                    }
                    $discountValue = number_format($discountValue, 2);
                }

                $total = ($rowOrderItem['unit_price'] - $discount_value_for_one_qty) * $rowOrderItem['qty'];

                if($rowOrderItem['unit_price'] == ""){
                    $rowOrderItem['unit_price'] = 0;
                }

                $profitAmount = $total - ($rowOrderItem['qty'] * $rowOrderItem['cost_price']);

                if($rowOrderItem['gst_status'] == "ON"){
                    $gstValue = $total * $rowOrderItem['gst'] / 100;
                    $total    = $total + $gstValue;
                }


                $subTotal += $total;
                $discount = $rowOrderItem['discount'];
                $netTotal = $subTotal - $discount;
                $discount_percentage_amount_sum = $rowOrderItem['discount_percentage_amount_sum'] + $discount;

                $discount_percentage_type = number_format($discount_percentage_type, 2);
                $total    = number_format($total, 2);
                
                $gstOrderItemColumn = "displayNone";
                if($rowOrderItem['gst_status'] == "ON"){
                    $gstValue = number_format($gstValue, 2);
                    $gstOrderItemColumn = "";
                }

                $unit_price    = number_format($rowOrderItem['unit_price'], 2);
                $cost_price    = number_format($rowOrderItem['cost_price'], 2);
                $discountValue = number_format($discountValue, 2);
                $profitAmountFormatted = number_format($profitAmount, 2);

                $colc = 1;
                $rowc++;
                
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $count);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowOrderItem['item_title']);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $rowOrderItem['qty']);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $cost_price);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $unit_price);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $discountValue);

                if($gst_status == "ON"){
                    $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $gstValue.'('.$rowOrderItem['gst'].'%)');
                }

                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $total);
                $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $profitAmountFormatted);

                $count++;
            }

        }

        $colc = 0;
        $rowc++;

        $grand_totalfrm = number_format($grand_totalfrm, 2);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, '');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, 'Grand Total');
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, $grand_totalfrm);
        $actSheet->setCellValueByColumnAndRow($colc++, $rowc, number_format($totalProfitAmount, 2));

        $actSheet->getStyle("A{$rowc}:J{$rowc}")->applyFromArray($headStyle);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
}
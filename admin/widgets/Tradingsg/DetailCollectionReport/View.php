<?
class CPL_Admin_Widgets_Tradingsg_DetailCollectionReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $dateUtil = Zend_Registry::get('dateUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

	// **** THIS CONDITION HAS BEEN USED ONLY FOR MULTI LOCATION SITE IN BLOSSOMS **** \\
		$siteLocation = '' ;
		if($cpCfg['cp.hasMultiUniqueSites']){
			$siteLocation = "
			<th>Location</th>
			";
		}

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $month          = $fn->getReqParam('month');
        $year           = $fn->getReqParam('year');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');

        if ($start_date != '' && $end_date == '') {
            $start_date = $start_date;
            $end_date   = $current_date;
        } else if ($start_date == '' && $end_date != ''){
            $start_date = $year . '-' . $month . '-' . '01';
            $start_date = $start_date;
            $end_date   = $end_date;
        } else if ($start_date != '' && $end_date != '') {
            $start_date = $start_date;
            $end_date   = $end_date;
        } else {
            $start_date = $year . '-' . $month . '-' . '01';
            $end_date = $year . '-' . $month . '-' . '31';
            $start_date = $current_date;
            $end_date   = $current_date;
        }

        $start_date_formatted = $dateUtil->formatDate($start_date, 'DD/MM/YYYY');
        $end_date_formatted   = $dateUtil->formatDate($end_date, 'DD/MM/YYYY');

        $summaryRec   = $this->model->getSqlForCount();
        $grand_total  = number_format(round($summaryRec['grand_total']), 2);
        $profit_total = number_format($summaryRec['profit_total'], 2);

        $text = "
        <h2>Detail Collection Report</h2>
        <table class='thinlist summaryTable mb20'>
            <thead>
                <th colspan='6'>Summary</th>
            </thead>
            <tr>
                <th>Start Date : {$start_date_formatted}</th>
                <th>End Date : {$end_date_formatted}</th>
                <th>Grand Total : {$grand_total}</th>
                <th>Total Profit : {$profit_total}</th>
            </tr>
        </table>
		<div class = 'tableOuter scroll-pane'>
			<table class='thinlist'>
				<thead>
					<tr>
						<th>Order Date</th>
                        <th>Order No</th>
						<th>Company Name</th>
                        <th class='txtRight'>Discount Given</th>
                        <th class='txtRight'>Amount</th>
                        <th>Order Status</th>
                        <th class='txtRight'>Profit Amount</th>
						{$siteLocation}
					</tr>
				</thead>
				{$this->getRowsHTML()}
			</table>
		</div>
        ";
        return $text;
    }

    function getRowsHTML() {
        $fn 	= Zend_Registry::get('fn');
        $db 	= Zend_Registry::get('db');
        $cpCfg 	= Zend_Registry::get('cpCfg');

        $rows = '';
		$siteTitle = '' ;
        $profitAmount  = 0;
        $totalProfitAmount = 0;
        foreach($this->model->dataArray as $row){
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
                $profitAmountFormatted = number_format($profitAmount, 2);

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
                $discount      = $row['discount'];
                $discountValue = number_format($discountValue, 2);

                $OrderItemRow .= "
                <tr>
                    <td>{$count}</td>
                    <td>{$rowOrderItem['item_title']}</td>
                    <td class='txtCenter'>{$rowOrderItem['qty']}</td>
                    <td class='txtRight'>{$cost_price}</td>
                    <td class='txtRight'>{$unit_price}</td>
                    <td class='txtRight'>{$discountValue}</td>
                    <td class='txtRight {$gstOrderItemColumn}'>{$gstValue}({$rowOrderItem['gst']}%)</td>
                    <td class='txtRight'>{$total}</td>
                    <td class='txtRight'>{$profitAmountFormatted}</td>
                </tr>
                ";

                $count++;

                $rowOrderItem['gst_value'] = $total * $rowOrderItem['gst'] / 100;
            }

            $totalAmount = $subTotal - $discount;

            $OrderItemDetails = "";
            if($numRowsOrderItem > 0){
                $OrderItemDetails = "
                <tr>
                    <td></td>
                    <td colspan='6'>
                        <div class='OrderItemDetails'>
                            <table class='thinlist tableInvRecDetail'>
                                <thead>
                                    <th>S.No</th>
                                    <th>Product Name</th>
                                    <th class='txtCenter'>Qty</th>
                                    <th class='txtRight'>Cost Price</th>
                                    <th class='txtRight'>Selling Price</th>
                                    <th class='txtRight'>Discount</th>
                                    <th class='txtRight {$gstOrderItemColumn}'>Gst</th>
                                    <th class='txtRight'>Amount</th>
                                    <th class='txtRight'>Profit Amount</th>
                                </thead>
                                <tbody>
                                    {$OrderItemRow}
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
                ";
            }

            $creationDate = $fn->getCPDate($row['order_date'],"d-m-Y");
            $totalAmount  = number_format($totalAmount, 2);
            $orderLink    = "index.php?_topRm=order&module=tradingsg_order&_action=edit&order_id={$row['order_id']}";
            
            if($totalProfitAmount > 0 || $totalProfitAmount < 0){
                $totalProfitAmount = $totalProfitAmount - $discount;
            }

            $discount          = number_format($discount, 2);
            $totalProfitAmount = number_format($totalProfitAmount, 2);

            $rows .= "
            <tbody class='detailCollectionOrderItemDetails'>
    			<tr>
    				<td>{$creationDate}</td>
    				<td>
                        <a href='{$orderLink}' target='_blank'>
                            <u>{$row['order_id']}</u>
                        </a>
                    </td>
    				<td>{$row['company_name']}</td>
                    <td class='txtRight'>{$discount}</td>
                    <td class='txtRight OrderItemDetailsToggle'>{$totalAmount}</td>
                    <td>{$row['order_status']}</td>
                    <td align='right'>{$totalProfitAmount}</td>
    			</tr>
                {$OrderItemDetails}
            </tbody>
			";
        }

        $text = "
        {$rows}
        ";

        return $text;
    }

}
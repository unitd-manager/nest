<?
class CPL_Admin_Widgets_Tradingsg_DailyCollectionReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $dateUtil = Zend_Registry::get('dateUtil');
        $cpCfg = Zend_Registry::get('cpCfg');

	// **** THIS CONDITION HAS BEEN USED ONLY FOR MULTI LOCATION SITE IN BLOSSOMS **** \\
		
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

        $summaryRec = $this->model->getSqlForCount();
        $grand_total = number_format(round($summaryRec['grand_total']), 2);

        $text = "
        <h2>Daily Collection Report</h2>
        <table class='thinlist summaryTable mb20'>
            <thead>
                <th colspan='6'>Summary</th>
            </thead>
            <tr>
                <td>Start Date : {$start_date_formatted}</td>
                <td>End Date : {$end_date_formatted}</td>
                <td>Grand Total : {$grand_total}</td>
            </tr>
        </table>
		<div class = 'tableOuter scroll-pane'>
			<table class='thinlist'>
				<thead>
					<tr>
						<th>Date</th>
						<!--<th class='txtRight'>Total Cost Amount</th>-->
                        <th class='txtRight'>Total Selling Amount</th>
                        <!--<th class='txtRight'>Total Profit Amount</th>-->
					</tr>
				</thead>
				<tbody>
					{$this->getRowsHTML()}
				</tbody>
			</table>
		</div>
        ";
        return $text;
    }

    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        $rows = '';
		$siteTitle = '' ;
        $ProfitAmount = 0;
        foreach($this->model->dataArray as $row){
            $discount_sum_percent = 0;
            $discount_sum_value = 0;

            //TO CHECK IF THE SUM OF DISCOUNT TYPE(%) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
            $subSqlForPercentSum = "
            SELECT SUM(((oi.unit_price * oi.discount_percentage )/100)* oi.qty) as discount_sum_percent
            FROM order_item oi
            WHERE oi.order_id = {$row['order_id']}
              AND oi.discount_type = '%'
            ";
            $resultSubSql = $db->sql_query($subSqlForPercentSum);
            $rowSql       = $db->sql_fetchrow($resultSubSql);
            if($rowSql['discount_sum_percent'] > 0){
                $discount_sum_percent = $rowSql['discount_sum_percent'];
            }

            //TO CHECK IF THE SUM OF DISCOUNT TYPE(VALUE) HAS VALUE OR NOT AND ASSIGN THE QUERY IF IT HAS VALUE ELSE ASSIGN ZERO
            $subSqlForValueSum ="
            SELECT SUM(oi.discount_percentage  * oi.qty) as discount_sum_value
            FROM order_item oi
            WHERE oi.order_id = {$row['order_id']}
              AND oi.discount_type = 'Value'
            ";
            $resultSubSql1 = $db->sql_query($subSqlForValueSum);
            $rowSql1       = $db->sql_fetchrow($resultSubSql1);
            if($rowSql1['discount_sum_value'] > 0){
                $discount_sum_value = $rowSql1['discount_sum_value'];
            }

            $discount_percentage_amount_sum = $discount_sum_value + $discount_sum_percent;
            if($row['record_type'] == 'POS'){
                $order_amount = $row['amount'] - $discount_percentage_amount_sum;
            } else {
                $order_amount = $row['amount'];
            }

			if($row['date']){
				$creationDate = $fn->getCPDate($row['date'],"d-m-Y");
                if($row['sales_return_amount'] != ''){

                    if($row['CostPriceAmt'] != ""){
                        $ProfitAmount = $row['receipt_amount'] - $row['sales_return_amount'] - $row['CostPriceAmt'];
                    }else{
                        $ProfitAmount = 0;
                    }

                    $amount = $row['receipt_amount'] - $row['sales_return_amount'];
                }else{
                    if($row['CostPriceAmt'] != ""){
                        $ProfitAmount = $row['receipt_amount'] - $row['CostPriceAmt'];
                    }else{
                        $ProfitAmount = 0;
                    }

				    $amount = $row['receipt_amount'];
                }

                $amount       = number_format(round($amount), 2);
                $ProfitAmount = number_format(round($ProfitAmount), 2);
                $CostPriceAmt = number_format(round($row['CostPriceAmt']), 2);

			    $rows .= "
				<tr>
					<td>{$creationDate}</td>
                    <!--<td class='txtRight'>{$CostPriceAmt}</td>-->
					<td class='txtRight'>{$amount}</td>
                    <!--<td class='txtRight'>{$ProfitAmount}</td>-->
				</tr>
				";
			}
        }

        $text = "
        {$rows}
        ";

        return $text;
    }

}
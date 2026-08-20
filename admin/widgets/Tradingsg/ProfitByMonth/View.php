<?
class CPL_Admin_Widgets_Tradingsg_ProfitByMonth_View extends CP_Admin_Widgets_Tradingsg_ProfitByMonth_View
{
    /**
     *
     */
    function getWidget() {
    	$fn    = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $text = "
        <h2>Profit by Last 12 Months</h2>
		<div class = 'tableOuter scroll-pane'>
		<table class='thinlist'>
			<thead>
				<tr>
					<th>Month</th>
					<th class='txtRight'>Amount</th>
				</tr>
			</thead>
			{$this->getRowsHTML()}
		</table>
		</div>
        ";
        return $text;
    }

    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $price_from_supplier = $fn->getReqParam('price_from_supplier');
        
        $rows = '';
        $class = '';
        $total = 0;
		$siteTitle = '' ;
		$siteLocationTotal = '' ;

        foreach($this->model->dataArray as $row){
            $additional_field = 0;
            if ($price_from_supplier == 1) {
                $additional_field = $row['total_cost_price_monthly'];
            }

            $total_profit = $row['total_selling_price_monthly'] - $additional_field;
            $total += $total_profit;
            $total_profit = number_format($total_profit, 2);
            
            $salesByClient = $this->getSalesByClient($row['order_date']);

            $rows .= "
            <tbody class='profitByMonthSummary'>
				<tr class='monthValDisable'>
					<td class='profitVal'>{$row['profit_month']}</td>
					<td  class='txtRight'>{$total_profit}</td>
				</tr>
				<tr>
	                <td colspan='2'>{$salesByClient}</td>
	            </tr>
	         </tbody>
			";                
        }

        $total = number_format($total, 2);

    	$siteLocationTotal = "
        <tr class=''>
            <td class='lastRowBgColor'>Total</td>
            <td class='txtRight lastRowBgColor'>{$total}</td>
        </tr>
        ";
        
        $text = "
        {$rows}
        {$siteLocationTotal}
        ";

        return $text;
    }

    /**
     *
     */
    function getSalesByClient($order_date) {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');

        $rows = '';
        $errorText = '';
        $price_from_supplier = $fn->getReqParam('price_from_supplier');

        $additional_field = "";
        if ($price_from_supplier == 1) {
            $additional_field .= ",(SUM(oi.price_from_supplier*oi.qty)) AS total_cost_price_monthly";
        }
        else{
            $additional_field .= ",(SUM(oi.cost_price*oi.qty)) AS total_cost_price_monthly";
        }

        $endDateMonth = $fn->getCPDate($order_date, 'Y-m');

        $sqlClient = "
        SELECT c.company_name
        	   ,(SUM(oi.unit_price*oi.qty)) AS total_selling_price_monthly
        	    {$additional_field}
        FROM `order_item` oi
        LEFT JOIN `order` o ON (oi.order_id = o.order_id)
        LEFT JOIN `company` c ON (o.company_id = c.company_id)
        WHERE o.order_status != 'Cancelled'
		AND o.order_date BETWEEN '{$order_date}' AND '{$endDateMonth}-31'
		GROUP BY o.company_id
		ORDER BY c.company_name ASC
        ";

        $result     = $db->sql_query($sqlClient);
        $numRows    = $db->sql_numrows($result);

        while ($row = $db->sql_fetchrow($result)) {

            $additional_field = 0;
            if ($price_from_supplier == 1) {
                $additional_field = $row['total_cost_price_monthly'];
            }

            $total_profit = $row['total_selling_price_monthly'] - $additional_field;
            $total_profit = number_format($total_profit, 2);

            $rows .= "
			<tr>
				<td>{$row['company_name']}</td>
				<td  class='txtRight'>{$total_profit}</td>
			</tr>
			"; 
        }

        $clientRows = "
        <div class = 'profitDetails'>
            <table class='thinlist'>
                <thead class = 'profitDetailsTitle'>
                    <th>Client Name</th>
                    <th class = 'txtRight'>Amount</th>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>
        </div>
        ";

        $text ="
        {$clientRows}
        ";

        return $text;
    }
}
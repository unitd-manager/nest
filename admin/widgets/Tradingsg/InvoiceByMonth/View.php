<?
class CPL_Admin_Widgets_Tradingsg_InvoiceByMonth_View extends CP_Admin_Widgets_Tradingsg_InvoiceByMonth_View
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

	// **** THIS CONDITION HAS BEEN USED ONLY FOR MULTI LOCATION SITE IN BLOSSOMS **** \\
		$siteLocation = '' ;
		if($cpCfg['cp.hasMultiUniqueSites']){
			$siteLocation = "
			<th>Location</th>
			";
		}

        $text = "
        <h2>Sales by Last 12 Months</h2>
		<div class = 'tableOuter scroll-pane'>
		<table class='thinlist'>
			<thead>
				<tr>
					<th>Month</th>
					{$siteLocation}
					<th class='txtRight'>Amount</th>
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

    /**
     *
     */
    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        $rows = '';
        $total = 0;
		$siteTitle = '' ;
		$siteLocationTotal = '' ;
		$start_invoice_date = '';
		$end_invoice_date   = '';

        foreach($this->model->dataArray as $row){
            $start_invoice_date = $row['invoice_month_year']. '-' . '01';
            $end_invoice_date   = $row['invoice_month_year']. '-' . '31';

            $SQLinvoice = "
            SELECT  i.invoice_id
                   ,i.p_f
                   ,i.frieght_cost
            FROM invoice i
            LEFT JOIN (`order` o) ON (o.order_id = i.order_id)
            WHERE i.status != 'Cancelled'
            AND i.invoice_date BETWEEN '{$start_invoice_date}' AND '{$end_invoice_date}'
            ";
            $resultInvoice = $db->sql_query($SQLinvoice);
            $amount = 0;
            $total_Month_Invoice_Amount = 0;
			while ($rowInvoice = $db->sql_fetchrow($resultInvoice)) {
				$sqlInvItem ="
		        SELECT SUM(it.qty * it.unit_price) As amount
		        FROM invoice_item it
		        WHERE it.invoice_id = {$rowInvoice['invoice_id']}
		        ";
		        $resultInvItem = $db->sql_query($sqlInvItem);
		        $rowInvItem = $db->sql_fetchrow($resultInvItem);

		        $amount = $rowInvItem['amount'];

		        $total_Month_Invoice_Amount += $amount;
			}

			$month_Amount = number_format($total_Month_Invoice_Amount,2);

			$rows .= "
			<tr>
				<td>{$row['invoice_month']}</td>
				<td class='txtRight'>{$month_Amount}</td>
			</tr>
			";

            $total += $total_Month_Invoice_Amount;
        }

            $total = number_format($total, 2);

		   // **** THIS CONDITION HAS BEEN ADDED ONLY FOR MULTI LOCATION SITE IN BLOSSOMS **** \\

			if($cpCfg['cp.hasMultiUniqueSites'] == 1){
				$siteLocationTotal = " 
			    <tr class=''>
			        <td class='lastRowBgColor' colspan='2'>Total</td>
			        <td class='txtRight lastRowBgColor'>{$total}</td>
			    </tr>
			    ";
		    } else {

		    	//***** THIS IS A DEFAULT CONDTION FOR TAKING ALL TRADING REPORTS  EXCEPT BLOSSOMS *****/
		    	$siteLocationTotal = "
		        <tr class=''>
		            <td class='lastRowBgColor'>Total</td>
		            <td class='txtRight lastRowBgColor'>{$total}</td>
		        </tr>
		        ";
		    }

        $text = "
        {$rows}
        {$siteLocationTotal}
        ";

        return $text;
    }
}
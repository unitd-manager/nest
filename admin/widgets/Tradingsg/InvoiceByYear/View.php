<?
class CPL_Admin_Widgets_Tradingsg_InvoiceByYear_View extends CP_Admin_Widgets_Tradingsg_InvoiceByYear_View
{
    /**
     *
     */
    function getWidget() {
        $c = &$this->controller;
        $cpCfg = Zend_Registry::get('cpCfg');

	// **** THIS CONDITION HAS BEEN USED ONLY FOR MULTI LOCATION SITE IN BLOSSOMS **** \\
		$siteLocation = '' ;
		if($cpCfg['cp.hasMultiUniqueSites']){
			$siteLocation = "
			<th>Location</th>
			";
		}

        $rowsHTML = $this->getRowsHTML();
        $text = '';

        if ($rowsHTML != ""){
            $text = "
            <h2>Sales by Year</h2>
    		<div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>Year</th>
						{$siteLocation}
                        <th class='txtRight'>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    {$rowsHTML}
                </tbody>
            </table>
            </div>
            ";
        }

        return $text;
    }
    
    /**
     *
     */
    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');
        $location_id    = $fn->getReqParam('location_id');

        $rows = '';
		$siteTitle = '' ;

        foreach($this->model->dataArray as $row){

            $currentYear = $row['start_Year'];
            $nextYear    = $row['end_Year'];

            $start_date = $currentYear . '-' . '04' . '-' . '01';
            $end_date   = $nextYear . '-' . '03' . '-' . '31';

            $SQLinvoice = "
            SELECT  i.invoice_id
                   ,i.p_f
                   ,i.frieght_cost
            FROM invoice i
            LEFT JOIN (`order` o) ON (o.order_id = i.order_id)
            WHERE i.status != 'Cancelled'
            AND i.invoice_date BETWEEN '{$start_date}' AND '{$end_date}'
            ";
            $resultInvoice = $db->sql_query($SQLinvoice);

            $amount = 0;
            $total_Year_Invoice_Amount = 0;
            while ($rowInvoice = $db->sql_fetchrow($resultInvoice)) {
                $sqlInvItem ="
                SELECT SUM(it.qty * it.unit_price) As amount
                FROM invoice_item it
                WHERE it.invoice_id = {$rowInvoice['invoice_id']}
                ";
                $resultInvItem = $db->sql_query($sqlInvItem);
                $rowInvItem = $db->sql_fetchrow($resultInvItem);

                $pfVal = 0;
                if($rowInvoice['p_f'] != ''){
                    $pfVal = $rowInvItem['amount'] * $rowInvoice['p_f'] / 100;
                }

                $frieghtCost = 0;
                if($rowInvoice['frieght_cost'] != ''){
                    $frieghtCost = $rowInvoice['frieght_cost'];
                }

                $amount = $rowInvItem['amount'];

                $total_Year_Invoice_Amount += $amount;
            }

            $year_Amount = number_format($total_Year_Invoice_Amount,2);
              

            $rows .= "
            <tr>
                <td>{$row['invoice_year']}</td>
                {$siteTitle}
                <td class='txtRight'>{$year_Amount}</td>
            </tr>
            ";
        }

        $text = "
        {$rows}
        ";

        return $text;
    }
}
<?
class CPL_Admin_Widgets_Tradingsg_QuoteByMonth_View extends CP_Admin_Widgets_Tradingsg_QuoteByMonth_View
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        $text = "
        <h2>Quote by Month</h2>
		<div class = 'tableOuter scroll-pane'>
		<table class='thinlist'>
			<thead>
				<tr>
					<th>S.No</th>
					<th>Quote Code</th>
					<th>Quote Date</th>
					<th>Title</th>
					<th>Client</th>
					<th>Status</th>
					<th class='txtRight'>Amount</th>
					<th>Staff</th>
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
        $count = 1;
        $total = 0;
        $quote_amount = 0;
		$siteTitle = '' ;
		$siteLocationTotal = '' ;

        foreach($this->model->dataArray as $row){

			$quote_amount = number_format($row['quote_total_amount'], 2);
			$quote_date = $fn->getCpDate($row['quote_date'], 'd-m-Y');

            $rows .= "
			<tr>
				<td>{$count}</td>
				<td>{$row['quote_code']}</td>
				<td>{$quote_date}</td>
				<td>{$row['title']}</td>
				<td>{$row['company_name']}</td>
				<td>{$row['status']}</td>
				<td class='txtRight'>{$quote_amount}</td>
				<td>{$row['staff_name']}</td>
			</tr>
			";

			$count++;
            $total += $row['quote_total_amount'];
        }

        $total = number_format($total, 2);

		$siteLocationTotal = "
	    <tr class=''>
	        <td class='lastRowBgColor txtRight' colspan='6'>Total</td>
	        <td class='txtRight lastRowBgColor'>{$total}</td>
	        <td class='lastRowBgColor'></td>
	    </tr>
	    ";

        $text = "
        {$rows}
        {$siteLocationTotal}
        ";

        return $text;
    }
}
<?
class CPL_Admin_Widgets_Tradingsg_SalesSummaryByProduct_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Sales Summary By Product</h2>
		<div class = 'tableOuter scroll-pane'>
			<table class='thinlist'>
				<thead>
					<tr>
						<th>S.No</th>
						<th>Product Item Code</th>
						<th>Name of the Item</th>
						<th>Supplier</th>
						<th>Quantity</th>
						<th>Price</th>
						<th>Amount</th>
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

        $rows = '';
		$count = 1;
		$total = 0;

        foreach($this->model->dataArray as $row){
        	$amount = $row['quantity'] * $row['unit_price'];
	        $total += $amount;
            $amount = number_format($amount, 2);

		    $rows .= "
			<tr>
				<td>{$count}</td>
				<td>{$row['item_code']}</td>
				<td>{$row['item_title']}</td>
				<td>{$row['company_name']}</td>
				<td>{$row['quantity']}</td>
				<td align='right'>{$row['unit_price']}</td>
				<td align='right'>{$amount}</td>
			</tr>
			";
            $count++;
        }
        $total = number_format($total, 2);

        $text = "
        {$rows}
        <tr class=''>
            <td class='lastRowBgColor txtRight' colspan='6'>Total</td>
    		<td class='lastRowBgColor txtRight'>{$total}</td>
        </tr>
        ";

        return $text;
    }

}
<?
class CPL_Admin_Widgets_Tradingsg_QuoteByStaff_View extends CP_Admin_Widgets_Tradingsg_QuoteByStaff_View
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Quote By Staff</h2>
		<div class = 'tableOuter scroll-pane'>
		<table class='thinlist'>
			<thead>
				<tr>
					<th>S.No</th>
					<th>Quote Code</th>
					<th>Date</th>
					<th>Staff</th>
					<th>Client</th>
					<th>Status</th>
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

    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
       
        $rows = '';
		$count = 1;
		$quote_amount = 0;
		
        foreach($this->model->dataArray as $row){
			if($row['quote_date']){
				$follow_up_date = $fn->getCPDate($row['quote_date'],"d-m-Y");
				$quote_amount = number_format($row['quote_total_amount'], 2);

			    $rows .= "
				<tr>
					<td>{$count}</td>
					<td>{$row['quote_code']}</td>
					<td>{$follow_up_date}</td>
					<td>{$row['staff_name']}</td>
					<td>{$row['company_name']}</td>
					<td>{$row['status']}</td>
					<td class='txtRight'>{$quote_amount}</td>
				</tr>
				";                
			}	
				$count++;
                               
        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }

}
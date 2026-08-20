<?
class CPL_Admin_Widgets_Tradingsg_PurchaseOrderReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $text = "
        <div class = 'tableOuter scroll-pane'>
        <table class='thinlist'>
            <thead>
                <tr>
                    <th>PO Code</th>
                    <th>PO Date</th>
                    <th>Part Number</th>
                    <th>Item Name</th>
                    <th>Product Group Name</th>
                    <th>Supplier Name</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Amount</th>
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
        $amount = 0;
        $total = 0;
        $totalMain = 0;

        foreach($this->model->dataArray as $row){
            $po_date = $fn->getCPDate($row['po_date'],"d-m-Y");
            $price = number_format($row['price'],2);
            $totalAmount = $row['qty'] * $row['price'];
            $amount += $totalAmount;
            $totalAmount = number_format($totalAmount,2);

            $SQLTotal = "
                SELECT SUM(round(
                (pop.qty * pop.price),2)) AS total_cost
                FROM po_product pop
                WHERE pop.purchase_order_id = {$row['purchase_order_id']}
            ";
            $resultTotal = $db->sql_query($SQLTotal);
            $rowTotal = $db->sql_fetchrow($resultTotal);
            $totalCost = number_format($rowTotal['total_cost'], 2);

            $rows .= "
            <tr>
                <td>{$row['po_code']}</td>
                <td>{$po_date}</td>
                <td>{$row['part_number']}</td>
                <td>{$row['item_title']}</td>
                <td>{$row['title']}</td>
                <td>{$row['company_name']}</td>
                <td>{$row['qty']}</td>
                <td class='txtRight'>{$price}</td>
                <td class='txtRight'>{$totalAmount}</td>
            </tr>
            ";
            $count++;

            $total += $row['price'];
        }
        $total = number_format($total, 2);
        $amount = number_format($amount, 2);

        $totalRow = " 
        <tr class=''>
            <td class='lastRowBgColor' colspan='7'>Total</td>
            <td class='txtRight lastRowBgColor'>{$total}</td>
            <td class='txtRight lastRowBgColor'>{$amount}</td>
        </tr>
        ";

        $text = "
        {$rows}
        {$totalRow}
        ";

        return $text;
    }
}
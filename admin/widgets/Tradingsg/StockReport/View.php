<?
class CPL_Admin_Widgets_Tradingsg_StockReport_View extends CP_Admin_Widgets_Tradingsg_StockReport_View
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $text = "
        <h2>Stock Report</h2>
		<div class = 'tableOuter scroll-pane'>
		<table class='thinlist'>
			<thead>
				<tr>
                    <th>Item Code</th>
                    <th>Product Name</th>
                    <th>Stock Added</th>
                    <th>Stock Sold</th>
                    <th>Damage Qty (Amount)</th>
                    <th>Total Available Stock</th>
                    <th class='txtRight'>Total Cost</th>
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
        $fn 	= Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $cpCfg 	= Zend_Registry::get('cpCfg');

        $start_date     = $fn->getReqParam('start_date');
        $end_date       = $fn->getReqParam('end_date');
        $current_date   = date('Y-m-d');
        $month          = date('m');
        $year           = date('Y');

        $rows = '';
		$siteTitle = '' ;
        $count = 1 ;
        $linkToStock = '' ;
        $sum_purchase_cp_per_qty = 0;
        $startDateAppendSql = '';
        $startDateAppendSqlInv = '';

        if($cpCfg['cp.excludeStock'] == 1){
            $linkToStock = "AND o.link_stock = 1";
        }

        foreach($this->model->dataArray as $row){

            if ($start_date != '' && $end_date == '') {
                $startDateAppendSql = "AND o.order_date >= '{$start_date}' AND o.order_date <= '{$current_date}'";
            } else if ($start_date == '' && $end_date != ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $startDateAppendSql = "AND o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
            } else if ($start_date != '' && $end_date != '') {
                $startDateAppendSql = "AND o.order_date >= '{$start_date}' AND o.order_date <= '{$end_date}'";
            /*} else {
                $startDateAppendSql = "AND o.order_date    = '{$current_date}'" ;*/
            }

            if ($start_date != '' && $end_date == '') {
                $startDateAppendSqlInv = "AND inv.invoice_date >= '{$start_date}' AND inv.invoice_date <= '{$current_date}'";
            } else if ($start_date == '' && $end_date != ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $startDateAppendSqlInv = "AND inv.invoice_date >= '{$start_date}' AND inv.invoice_date <= '{$end_date}'";
            } else if ($start_date != '' && $end_date != '') {
                $startDateAppendSqlInv = "AND inv.invoice_date >= '{$start_date}' AND inv.invoice_date <= '{$end_date}'";
            /*} else {
                $startDateAppendSqlInv = "AND inv.invoice_date    = '{$current_date}'" ;*/
            }

            $StockSql = "
            SELECT                
                (SELECT SUM(oi.qty) FROM order_item oi
                LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
                WHERE oi.record_id = {$row['product_id']}
                  AND (o.order_status = 'Paid' OR o.order_status = 'Partial Payment')
                  {$startDateAppendSql} 
                ) AS product_qty_sold

                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled')
                WHERE ini.record_id = {$row['product_id']}
                  AND srh.status IS NULL
                  {$startDateAppendSqlInv}
                ) AS sales_return_qty

                ,(SELECT pp.cost_price FROM po_product pp
                WHERE pp.product_id = {$row['product_id']}
                ORDER BY pp.po_product_id DESC
                LIMIT 0,1
                ) AS purchase_cp_per_qty
            ";
            $resultStockSql = $db->sql_query($StockSql);
            $rowStockSql    = $db->sql_fetchrow($resultStockSql);

            $stock_sold = $rowStockSql['product_qty_sold'];
            $stock = $row['purchased_qty'] - $rowStockSql['product_qty_sold'] + $rowStockSql['sales_return_qty'] - $row['damaged_qty'];
            $sum_purchase_cp_per_qty = $stock * $rowStockSql['purchase_cp_per_qty'];
            $sum_purchase_cp_per_qty = number_format($sum_purchase_cp_per_qty, 2);
            $sum_purchase_per_damageqty = $row['damaged_qty'] * $rowStockSql['purchase_cp_per_qty'];
            $sum_purchase_per_damageqty = number_format($sum_purchase_per_damageqty, 2);

		    $rows .= "
            <tr>
                <td>{$row['item_code']}</td>
                <td>{$row['product_title']}</td>
                <td align='right'>{$row['purchased_qty']}</td>
                <td align='right'>{$stock_sold}</td>
                <td align='right'>{$row['damaged_qty']}({$sum_purchase_per_damageqty})</td>
                <td align='right'>{$stock}</td>
                <td class='txtRight'>{$sum_purchase_cp_per_qty}</td>
            </tr>
			";
        }

        $text = "
        {$rows}
        ";

        return $text;
    }

    function getWidget1() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $text = "
        <h2>Stock Report</h2>
        <div class = 'tableOuter scroll-pane'>
        <table class='thinlist'>
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Product Name</th>
                    <th>Total Stock</th>
                    <th class='txtRight'>Total Cost</th>
                    <th>Damage Qty (Amount)</th>
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

    function getRowsHTML1() {
        $fn     = Zend_Registry::get('fn');
        $db     = Zend_Registry::get('db');
        $cpCfg  = Zend_Registry::get('cpCfg');

        $rows = '';
        $siteTitle = '' ;
        $count = 1 ;
        $linkToStock = '' ;
        $sum_purchase_cp_per_qty = 0;

        if($cpCfg['cp.excludeStock'] == 1){
            $linkToStock = "AND o.link_stock = 1";
        }

        foreach($this->model->dataArray as $row){

            $StockSql = "
            SELECT
                (SELECT SUM(qty) FROM po_product
                WHERE product_id = {$row['product_id']}
                ) AS product_qty_purchased
                
                ,(SELECT SUM(oi.qty) FROM order_item oi
                LEFT JOIN (`order` o) ON (o.order_id = oi.order_id)
                WHERE oi.record_id = {$row['product_id']}
                  AND (o.order_status = 'Paid' OR o.order_status = 'Partial Payment')
                ) AS product_qty_sold

                ,(SELECT SUM(srh.qty_return) FROM sales_return_history srh
                LEFT JOIN (invoice_item ini) ON (ini.invoice_item_id = srh.invoice_item_id)
                LEFT JOIN (invoice inv) ON (inv.invoice_id = srh.invoice_id AND inv.status != 'Cancelled')
                WHERE ini.record_id = {$row['product_id']}
                  AND srh.status IS NULL
                ) AS sales_return_qty

                ,(SELECT SUM(damage_qty) FROM po_product
                WHERE product_id = {$row['product_id']}
                ) AS damaged_qty

                ,(SELECT pp.cost_price FROM po_product pp
                WHERE pp.product_id = {$row['product_id']}
                ORDER BY pp.po_product_id DESC
                LIMIT 0,1
                ) AS purchase_cp_per_qty
            ";
            $resultStockSql = $db->sql_query($StockSql);
            $rowStockSql    = $db->sql_fetchrow($resultStockSql);

            $stock = $rowStockSql['product_qty_purchased'] - $rowStockSql['product_qty_sold'] + $rowStockSql['sales_return_qty'] - $rowStockSql['damaged_qty'];
            $sum_purchase_cp_per_qty = $stock * $rowStockSql['purchase_cp_per_qty'];
            $sum_purchase_cp_per_qty = number_format($sum_purchase_cp_per_qty, 2);
            $sum_purchase_per_damageqty = $rowStockSql['damaged_qty'] * $rowStockSql['purchase_cp_per_qty'];
            $sum_purchase_per_damageqty = number_format($sum_purchase_per_damageqty, 2);

            $rows .= "
            <tr>
                <td>{$row['item_code']}</td>
                <td>{$row['product_title']}</td>
                <td>{$stock}</td>
                <td class='txtRight'>{$sum_purchase_cp_per_qty}</td>
                <td>{$rowStockSql['damaged_qty']}({$sum_purchase_per_damageqty})</td>
            </tr>
            ";
        }

        $text = "
        {$rows}
        ";

        return $text;
    }
}
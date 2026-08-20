<?
class CPL_Admin_Widgets_Tradingsg_PriceTrackReport_View extends CP_Common_Lib_WidgetViewAbstract
{
    //==================================================================//
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');
        $cpCfg = Zend_Registry::get('cpCfg');

        $text = "
        <h2>Price Track Report</h2>
        <div class = 'tableOuter scroll-pane'>
        <table class='thinlist'>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th class='txtRight'>Highest Price</th>
                    <th class='txtRight'>Lowest Price</th>
                    <th class='txtRight'>Recently Changed Price</th>
                    <th class='txtCenter'>No of times Changed</th>
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
        $db      = Zend_Registry::get('db');
        $cpCfg = Zend_Registry::get('cpCfg');

        $rows = '';
        $total = 0;
        $highest_price = 0;
        $lowest_price = 0;
        $recent_price = 0;

        foreach($this->model->dataArray as $row){

            $start_date     = $fn->getReqParam('start_date');
            $end_date       = $fn->getReqParam('end_date');
            $current_date   = date('Y-m-d');
            $month          = date('m');
            $year           = date('Y');
            $monthVal       = $fn->getReqParam('month');
            $yearVal        = $fn->getReqParam('year');
            $supplier_id    = $fn->getReqParam('supplier_id');
            $section_id     = $fn->getReqParam('section_id');

            $appendSql = '';
            if ($start_date != '' && $end_date == '') {
                $appendSql .= "AND pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$current_date}'";
            } else if ($start_date == '' && $end_date != ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $appendSql .= "AND pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
            } else if ($start_date != '' && $end_date != '') {
                $appendSql .= "AND pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
            } else if ($monthVal == '' && $yearVal == ''){
                $start_date = $year . '-' . $month . '-' . '01';
                $end_date = $year . '-' . $month . '-' . '31';
                $appendSql .= "AND pp.creation_date >= '{$start_date}' AND pp.creation_date <= '{$end_date}'";
            }
        
            $SQLProductOthers = "
            SELECT 
                (SELECT MAX( pp.price ) AS highestPrice
                FROM `product_price` pp
                WHERE pp.product_id = {$row['product_id']}
                {$appendSql}
                ) AS highest_price

                ,(SELECT MIN( pp.price ) AS lowestPrice
                FROM `product_price` pp
                WHERE pp.product_id = {$row['product_id']}
                {$appendSql}
                ) AS lowest_price

                ,(SELECT pp.price  
                FROM `product_price` pp
                WHERE pp.product_id = {$row['product_id']}
                {$appendSql}
                ORDER BY pp.product_price_id DESC
                LIMIT 0,1
                ) AS recent_price
                
                ,(SELECT count( pp.price ) AS timeChangedCount
                FROM `product_price` pp
                WHERE pp.product_id = {$row['product_id']}
                {$appendSql}
                ) AS time_changed_count
                ";
            $resultProductOthers = $db->sql_query($SQLProductOthers);
            $rowProductOthers = $db->sql_fetchrow($resultProductOthers);

            $highest_price = number_format($rowProductOthers['highest_price'], 2);
            $lowest_price  = number_format($rowProductOthers['lowest_price'], 2);
            $recent_price  = number_format($rowProductOthers['recent_price'], 2);
            $rows .= "
            <tr>
                <td>{$row['title']}</td>
                <td class='txtRight'>{$highest_price}</td>
                <td class='txtRight'>{$lowest_price}</td>
                <td class='txtRight'>{$recent_price}</td>
                <td class='txtCenter'>{$rowProductOthers['time_changed_count']}</td>
            </tr>
            ";
        }
        $text = "
        {$rows}
        ";

        return $text;
    }

}
<?
class CPL_Admin_Widgets_Tradingsg_SalesByYearChart_View extends CP_Admin_Widgets_Tradingsg_SalesByYearChart_View
{
    /**
     *
     */
    function getWidget1() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Sales by Year</h2>
        <div class='tableOuter' id='sales_by_year_div'>
        </div>

        <script type='text/javascript' src='https://www.google.com/jsapi'></script>
            <script type='text/javascript'>
                // Load the Visualization API and the piechart package.
                google.load('visualization', '1.0', {'packages':['corechart']});
                
                // Set a callback to run when the Google Visualization API is loaded.
                google.setOnLoadCallback(drawChart);
                
                // Callback that creates and populates a data table,
                // instantiates the pie chart, passes in the data and
                // draws it.
                function drawChart() {
                
                // Create the data table.
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Year');
                data.addColumn('number', 'Sales');
                data.addRows([
                  {$this->getRowsHTML()}
                ]);

                var chart = new google.visualization.ColumnChart(document.getElementById('sales_by_year_div'));
                chart.draw(data, {colors: ['#660066'], width: 600, height: 290, title: '',
                        hAxis: {title: 'Year', titleTextStyle: {color: 'black'}}
                });
            }
        </script>
        ";
        return $text;
    }
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        //include("/lib/fusioncharts.php");

        $text = "
        <script type='text/javascript' src='/admin/lib/fusioncharts.js'></script>
        <script type='text/javascript' src='/admin/lib/fusioncharts.charts.js'></script>
        <script type='text/javascript' src='/admin/lib/fusioncharts.theme.fint.js'></script>
        <script type='text/javascript' src='/admin/lib/fusioncharts-jquery-plugin.js'></script>
        <h2>Sales by Year</h2>
        <div class='tableOuter' id='chart-containeryear'></div>

        <script type='text/javascript'>
            jQuery('document').ready(function () {
                $('#chart-containeryear').insertFusionCharts({
                    type: 'column2d',
                    width: '600',
                    height: '290',
                    dataFormat: 'json',
                    dataSource: {
                        'chart': {
                            'xAxisName': 'Year',
                            'palettecolors': 'e44a00',
                            'theme': 'fint'
                        },
                        'data': [{$this->getRowsHTML()}]
                    }
                });
            });     
        </script>
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
        /*foreach($this->model->dataArray as $row){
            $rows .= "['{$row['order_year']}', {$row['order_amount_yearly']}],";
        }*/

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

            $year_Amount = $total_Year_Invoice_Amount;              

            //$rows .= "['{$row['invoice_year']}', {$year_Amount}],";

            $rows .= "{'label':'{$row['invoice_year']}', 'value':'{$year_Amount}'},";

        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }

}
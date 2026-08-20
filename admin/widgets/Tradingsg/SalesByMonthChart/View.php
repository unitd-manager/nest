<?
class CPL_Admin_Widgets_Tradingsg_SalesByMonthChart_View extends CP_Admin_Widgets_Tradingsg_SalesByMonthChart_View
{
    /**
     *
     */
    function getWidget1() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Sales by Last 12 Months</h2>
        <div class='tableOuter' id='sales_by_month_div'>
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
                data.addColumn('string', 'Month');
                data.addColumn('number', 'Sales');
                data.addRows([
                  {$this->getRowsHTML()}
                ]);

                var chart = new google.visualization.ColumnChart(document.getElementById('sales_by_month_div'));
                chart.draw(data, {colors: ['#8B4513'], width: 600, height: 290, title: '',
                        hAxis: {title: 'Month', titleTextStyle: {color: 'black'}}
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
        <h2>Sales by Last 12 Months</h2>
        <div class='tableOuter' id='chart-container'></div>

        <script type='text/javascript'>
            jQuery('document').ready(function () {
                $('#chart-container').insertFusionCharts({
                    type: 'column2d',
                    width: '600',
                    height: '290',
                    dataFormat: 'json',
                    dataSource: {
                        'chart': {
                            'xAxisName': 'Month',
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
        
        $rows = '';
        foreach($this->model->dataArray as $row){
            $rows .= "{'label':'{$row['order_month']}', 'value':'{$row['order_amount_monthly']}'},";
        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }
}
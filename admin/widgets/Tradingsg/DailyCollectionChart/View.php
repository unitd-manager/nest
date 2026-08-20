<?
class CPL_Admin_Widgets_Tradingsg_DailyCollectionChart_View extends CP_Common_Lib_WidgetViewAbstract
{
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
        <h2>Daily Collection Chart</h2>
        <div class='tableOuter' id='chart-containerdaily'></div>

        <script type='text/javascript'>
            jQuery('document').ready(function () {
                $('#chart-containerdaily').insertFusionCharts({
                    type: 'column2d',
                    width: '1200',
                    height: '290',
                    dataFormat: 'json',
                    dataSource: {
                        'chart': {
                            'xAxisName': 'Month',
                            'palettecolors': 'e44a00',
                            'theme': 'fint',
                            'formatNumberScale': '0',
                            'rotateValues': '0',
                            'numberPrefix': 'Rs.',
                            'placeValuesInside': '0',
                            'valueBgAlpha': '50',
                            'valueFontColor': '#000000',
                            'valueBgColor': '#FFFFFF',
                            'thousandSeparatorPosition': '2,3'
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
        
        $rows = '';
        $discount_sum_percent = 0;
        $discount_sum_value = 0;
        foreach($this->model->dataArray as $row){
            $amount = $row['receipt_amount'] - $row['sales_return_amount'];
            $amount = round($amount, 2);
            
            $date = $fn->getCPDate($row['date'], 'd-m-Y');            
            $rows .= "{'label':'{$date}', 'value':'{$amount}'},";
        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }
}
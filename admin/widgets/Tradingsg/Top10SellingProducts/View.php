<?
class CPL_Admin_Widgets_Tradingsg_Top10SellingProducts_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Top Selling Products</h2>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>product Name</th>
                        <th>Sold Qty</th>
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

    /**
     *
     */
    function getRowsHTML() {
        $fn = Zend_Registry::get('fn');
        $db = Zend_Registry::get('db');
        
        $rows = '';
        $count = 1;
        $sold_qty = '';

        foreach($this->model->dataArray as $row){

            $rows .= "
            <tr>
                <td>{$count}</td>
                <td>{$row['item_title']}</td>
                <td class='txtRight'>{$row['sold_qty']}</td>
            </tr>
            ";

            $count++;
        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }
}
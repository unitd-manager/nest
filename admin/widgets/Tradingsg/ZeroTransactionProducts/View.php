<?
class CPL_Admin_Widgets_Tradingsg_ZeroTransactionProducts_View extends CP_Common_Lib_WidgetViewAbstract
{
    /**
     *
     */
    function getWidget() {
        $db = Zend_Registry::get('db');
        $fn = Zend_Registry::get('fn');

        $text = "
        <h2>Zero Transaction Products</h2>
        <div class = 'tableOuter scroll-pane'>
            <table class='thinlist'>
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>product Name</th>
                        <th>Qty In Stock</th>
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

        foreach($this->model->dataArray as $row){

            $rows .= "
            <tr>
                <td>{$row['item_code']}</td>
                <td>{$row['product_name']}</td>
                <td class='txtRight'>{$row['qty_in_stock']}</td>
            </tr>
            ";

        }
        
        $text = "
        {$rows}
        ";

        return $text;
    }
}
<?
class CPL_Admin_Modules_Core_Staff_Model extends CP_Admin_Modules_Core_Staff_Model
{
    
    /**
     *
     */
    function getCoreStaffTradingsgProductGroupLinkSQL($id) {
                
        $SQL = "
        SELECT pgs.product_group_staff_id
              ,pg.title
        FROM product_group_staff pgs
        LEFT JOIN product_group pg ON (pg.product_group_id = pgs.product_group_id)
        WHERE pgs.staff_id = {$id}
        ";        

        return $SQL;
    }

    /**
     *
     */
    function getEcommerceProductEcommerceCarsLinkSQL1($id) {
                
        $SQL = "
        SELECT cp.cars_product_id
              ,CONCAT_WS(' ', c.make, c.year_from, c.model ) AS cars_info 
        FROM cars_product cp
        LEFT JOIN cars c ON (c.cars_id = cp.cars_id)
        WHERE cp.product_id = {$id}
        ";        

        return $SQL;
    }
    
}

Util.createCPObject('cpm.tradingsg.expense');

cpm.tradingsg.expense = {
    init: function(){

    $('.addNewValue').livequery('click', function (e){
    var title = "Add New Value";
    e.preventDefault();

    var valuelist_name = $(this).attr('valuelist_name');

    var expObj = {
        validate: true
       ,callbackOnSuccess: function(){
            Util.closeAllDialogs();

            var url = 'index.php?module=tradingsg_expense&_spAction=valueByValuelistJSON&showHTML=0';
            $.get(url, {valuelist_name: valuelist_name}, function (data) {
                if(valuelist_name == 'Group'){
                    $('#fld_group').cp_loadSelect(data);
                } 
            }, 'json');
        }
    }
    Util.openFormInDialog.call(this, 'portalForm', title, 400, 300, expObj);
    });

    $('select#fld_group').change(function() {
        var expense_group_id = $(this).val();
        var site_name = $("#fld_site_name_hidden").val();

        if(site_name == 'EPPODUM VENDRAN') {
            $('select#fld_source').val('EV Clinic Income');
        }

        //alert(expense_group_id);
        var url = 'index.php?module=tradingsg_expense&_spAction=subgroupByGroupJSON&showHTML=0';
        $.get(url, {expense_group_id: expense_group_id}, function (data) {
            $('#fld_sub_group').cp_loadSelect(data);
        }, 'json');
    });

    },

}

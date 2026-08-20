cpm.tradingsg.quote = $.extend(cpm.tradingsg.quote, {
    quoteProductQty: function(e) {
        e.preventDefault();
        var parent = $(this).closest('tr');
        var qtyObj = $(this).parents('tr').find('input[name=qty]');
        var qty = qtyObj.val();
        var rec_id = $(parent).attr('recid');
        var totalSellingPriceObj = $(this ).closest('tr').find('.total-selling-price');
        var costPriceObj = $(this ).closest('tr').find('.total-cost-price');
        var marginObj    = $(this ).closest('tr').find('.mark-up-amount');
        var discountObj = $(this ).closest('tr').find('.discount-percentage-amount');
        var discountValObj = $(this).parents('tr').find('input[name=discount_percentage_amount]');
        var discount_percentage = discountValObj.val();
        var url = 'index.php?module=tradingsg_quote&_spAction=updateSellingLineItems&showHTML=0';

        $.get(url, {rec_id: rec_id, qty: qty, discount_percentage: discount_percentage}, function(json){
            totalSellingPriceObj.html(json.totalSellingPrice);
            costPriceObj.html(json.totalCostPrice);

            //The margin is uneditable for trading mass hence the condition is given as below
            if (m_tradingsg_quote_hasProductLinkForSplCase == 1){
                marginObj.html(json.mark_up_value);
            }
            $('tr.summary-row .totalCp').html(json.total_cost_price_sum);
            $('tr.summary-row .discountSum').html(json.discount_percentage_amount_sum);
            $('tr.summary-row .serviceCostSum').html(json.mark_up_amount_sum);
            $('tr.summary-row .totalSp').html(json.total_selling_price_sum);
            $("input[name=discount_percentage_amount]", discountObj).val(json.discount_value);
            $("input[name=mark_up_amount]", marginObj).val(json.mark_up_value);
        });
    },

    quoteProductProductTitle: function() {
        var titleObj = this;
    	$(titleObj).autocomplete({
             source : 'index.php?module=tradingsg_quote&_spAction=searchProductTitle&showHTML=0'
            ,minLength : 1
    		,select: function(event, ui) {
                var selectedObj = ui.item;
    			var product_id = selectedObj.id
    			$(this).after("<input type='hidden' name='product_id' value=" + product_id + ">");

                //To Populate the related values in the table
                //--------------------------------------------
                Util.showProgressInd();
                var parent          = $(this).closest('tr');
                var rec_id          = $(parent).attr('recid');
                var productTitleObj = $(this ).closest('tr').find('.product-title');
                var costPriceObj    = $(this ).closest('tr').find('.cost-price');
                var marginObj       = $(this ).closest('tr').find('.mark-up-amount');
                var titleObj        = $(this ).closest('tr').find('.c-title');
                var sellingPriceObj = $(this ).closest('tr').find('.selling-price-amount');
                var itemCodeObj     = $(this ).closest('tr').find('.item-code');
                var unitObj         = $(this ).closest('tr').find('.unit');
                var stockObj         = $(this ).closest('tr').find('.stock');
                var partNumberObj   = $(this ).closest('tr').find('.item-code');
                var discountObj     = $(this ).closest('tr').find('.discount-percentage-amount');
                var clientIdObj     = $(this ).parents('tr').find('.company-name');
                var markUpTypeObj     = $(this ).parents('tr').find('.mark-up-type select[name=mark_up_type]');
                var discountTypeObj     = $(this ).parents('tr').find('.discount-type select[name=discount_type]');
                var qtyObj         = $(this ).closest('tr').find('.qty');
                var totalObj      = $(this ).closest('tr').find('.total-selling-price');

                var url = 'index.php?module=tradingsg_quote&_spAction=updateProductLineItems&showHTML=0';
                $.get(url, {product_id: product_id, rec_id: rec_id}, function(json){
                    if (json.msg != '') {
                        Util.hideProgressInd();
                        Util.alert(json.msg);
                        $('input[name=product_title]', productTitleObj).val('')
                        return;
                    }

                    //For General Trading
                    //$("input[name=cost_price]", costPriceObj).val(json.price);
                    $("input[name=qty]", qtyObj).val(1);
                    costPriceObj.html(json.price);
                    discountTypeObj.val('Value');
                    markUpTypeObj.val('Value');
                    titleObj.html(json.title);
                    sellingPriceObj.html(json.sellingPrice);
                    itemCodeObj.html(json.itemCode);
                    unitObj.html(json.unit);
                    stockObj.html(json.stock);
                    partNumberObj.html(json.partNumber);
                    clientIdObj.html(json.clientId);
                    totalObj.html(json.total);
                    $('tr.summary-row .totalSp').html(json.total_selling_price_sum);

                    /*var url = $('#scopeRootAlias').val() + 'index.php?module=tradingsg_quote&_spAction=supplierJsonByProductId&showHTML=0';

                    $.getJSON(url, {product_id: product_id}, function(data) {
                        clientIdObj.cp_loadSelect(data);
                    });*/

                    Util.hideProgressInd();
                });
    		}
    	});
    },

    quoteProductCostPrice: function(e) {
        e.preventDefault();
        var parent = $(this).closest('tr');
        var costPriceObj = $(this).parents('tr').find('input[name=cost_price]');
        var costPrice    = costPriceObj.val();

        var rec_id       = $(parent).attr('recid');
        //var profitObj = $(this ).closest('tr').find('.profit');
        var totalSellingPriceObj = $(this ).closest('tr').find('.total-selling-price');
        var costPriceObj = $(this ).closest('tr').find('.total-cost-price');
        var markUpObj    = $(this ).closest('tr').find('.mark-up-amount');
        var discountObj  = $(this ).closest('tr').find('.discount-percentage-amount');
        var sellingPriceObj = $(this ).closest('tr').find('.selling-price-amount');

        var url = 'index.php?module=tradingsg_quote&_spAction=updateSellingLineItems&showHTML=0';

        $.get(url, {rec_id: rec_id, costPrice: costPrice}, function(json){
            totalSellingPriceObj.html(json.totalSellingPrice);
            costPriceObj.html(json.totalCostPrice);
            $("input[name=discount_percentage_amount]", discountObj).val(json.discount_value);
            $("input[name=mark_up_amount]", markUpObj).val(json.mark_up_value);
            //when changing cost  for general trading margin is not going to change hence commented below
            sellingPriceObj.html(json.selling_price);
            $('tr.summary-row .totalCp').html(json.total_cost_price_sum);
            $('tr.summary-row .discountSum').html(json.discount_percentage_amount_sum);
            $('tr.summary-row .serviceCostSum').html(json.mark_up_amount_sum);
            $('tr.summary-row .totalSp').html(json.total_selling_price_sum);
        });
    },

    duplicate: function() {
        if (!confirm("Are you sure you want to duplicate the Quote?")){
            return;
        }
        var quote_id = $('#record_id').val();

        if (!confirm("Do you want to duplicate all linked Products?")){
            var url = 'index.php?module=tradingsg_quote&_spAction=duplicateQuote&showHTML=0' +
                      '&quote_id=' + quote_id;
        } else {
            var url = 'index.php?module=tradingsg_quote&_spAction=duplicateQuote&showHTML=0' +
                      '&quote_id=' + quote_id + '&linkedProduct=' + 1;
        }

        $.post(url, function (json) {
            if (json.status == 'error') {
                Util.alert(json.errorMsg);
                return;
            }
            document.location = json.returnUrl;
        }, 'json');

    },

    quoteProductBulkAdd: function(e) {
        e.preventDefault();
        //var quote_id       = $(this).attr('quote_id');
        var title = "Bulk Generate Product Records";
        var expObj = {
            validate: true
           ,callbackOnSuccess: function(){
                var msg = 'Generated successfully';
                Links.reloadPortalRecords('tradingsg_quote#tradingsg_productLink');
                Util.closeAllDialogs();
            }
        }
        Util.openFormInDialog.call(this, 'portalForm', title, 300, 175, expObj);
    },
});

$(function(){
    $('.row-tradingsg_quote__tradingsg_productLink .discount-percentage-amount').livequery('change', function(){
        var parent = $(this).closest('tr');
        var discountPercentageObj = $(this).parents('tr').find('input[name=discount_percentage_amount]');
        var discount_percentage   = discountPercentageObj.val();
        var mark_up_amount        = $(this ).closest('tr').find('input[name=mark_up_amount]');
        var mark_up               = mark_up_amount.val();
        var rec_id                = $(parent).attr('recid');
        var totalSellingPriceObj  = $(this ).closest('tr').find('.total-selling-price');
        var sellingPriceObj       = $(this ).closest('tr').find('.selling-price-amount');

        var url = 'index.php?module=tradingsg_quote&_spAction=updateSellingLineItems&showHTML=0';

        $.get(url, {rec_id: rec_id, discount_percentage: discount_percentage, mark_up:mark_up}, function(json){
            totalSellingPriceObj.html(json.totalSellingPrice);
            sellingPriceObj.html(json.selling_price);
            $('tr.summary-row .totalCp').html(json.total_cost_price_sum);
            $('tr.summary-row .discountSum').html(json.discount_percentage_amount_sum);
            $('tr.summary-row .totalSp').html(json.total_selling_price_sum);
        });

    });

    /*$('.row-tradingsg_quote__tradingsg_productLink .mark-up-amount').livequery('change', function(){
        var parent = $(this).closest('tr');
        var markUpObj = $(this).parents('tr').find('input[name=mark_up_amount]');
        var mark_up   = markUpObj.val();

        var rec_id       = $(parent).attr('recid');
        var totalSellingPriceObj = $(this ).closest('tr').find('.total-selling-price');
        var sellingPriceObj = $(this ).closest('tr').find('.selling-price-amount');

        var url = 'index.php?module=tradingsg_quote&_spAction=updateSellingLineItems&showHTML=0';

        $.get(url, {rec_id: rec_id, mark_up: mark_up}, function(json){
            totalSellingPriceObj.html(json.TotalSellingPrice);
            sellingPriceObj.html(json.selling_price);
            $('tr.summary-row .totalCp').html(json.total_cost_price_sum);
            $('tr.summary-row .markUpSum').html(json.mark_up_amount_sum);
            $('tr.summary-row .totalSp').html(json.total_selling_price_sum);
        });

    });*/

    $("a.quickAdd").livequery('click', function (e){
        var url = 'index.php?module=tradingsg_quote&_spAction=quickAdd'
                + '&showHTML=0';
        var exp = {
            url: url
        };
        Util.openDialogForLink('Quick Add Product',  1000, 500, 0, exp);
    });

    /* Add note in quote product link*/
    $('.m-tradingsg_quote a.addNoteQp').livequery('click', function (e){
            var title = "Add Note";
            e.preventDefault();

            var expObj = {
                validate: true
               ,callbackOnSuccess: function(){
                    var msg = 'Note added Successfully';
                    Util.alert(msg, function(){
                        Util.closeAllDialogs();
                        //window.location.reload(true);
                    });
                }
            }
            Util.openFormInDialog.call(this, 'portalForm', title, 600, 300, expObj);
    });
});
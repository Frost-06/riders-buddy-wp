jQuery(document).ready( function($) {
  var wcfmCheckoutTimmer = false;
  $( document ).on('update_checkout', function(event, args){
    clearTimeout( wcfmCheckoutTimmer );
    wcfmCheckoutTimmer = setTimeout( function(){
      var countrySelector = $('#billing_country');
      if($('#ship-to-different-address-checkbox').prop('checked')) {
        var countrySelector = $('#shipping_country');
      }
      var custCountry = countrySelector.val();
      //console.log(custCountry);
      data = {
        action: 'wcfmmp-remove-cart-vendor-product',
        custCountry : custCountry,
        wcfm_ajax_nonce         : wcfm_params.wcfm_ajax_nonce
      };
      $.post(woocommerce_params.ajax_url, data, function (resp) {
        //console.log(resp);
        if(resp && resp.success && resp.data.items_removed ) {
          alert('Item(s) "' + resp.data.removed_products + '" were removed from the cart as the vendors donot ship to the location selected.' );
          window.location.href = window.location.href;
        }
      });
    }, 
    '20', args );
  });
});
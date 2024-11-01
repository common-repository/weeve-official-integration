jQuery(document).ready(function($) {
	var phone = $('#billing_phone').val();
	var name = $('#billing_first_name').val();

	setTimeout(function () {
		get_points();
	}, 2000);

	$('body').on('blur', '#billing_phone, #billing_first_name', function() {
		phone = $('#billing_phone').val();
		name = $('#billing_first_name').val();
		get_points();
	});

	$('.disabled, .used-voucher').click(function(e){
		e.preventDefault();
	})

	function get_points()
	{
		if(phone.length >= 5 && name.length > 2) {
			$.ajax({
				type: "POST",
				url: admin.ajaxurl,
				data: {
					action: "wpm_get_user_balance",
					name: name,
					phone: phone
				},
				success: function(response) {
					if($(".points-api").length) {
						$('.points-api').remove();
					}
					$('.woocommerce-checkout-review-order-table tfoot').prepend(response);
					$('.get-discount').show();
				}
			});
		}
	}

	$('body').on('click', '.entry-title', function(){
		$('body').trigger("update_checkout");
	});

	$('body').on('click', '.discount-api', function(){
		var discount = $(this).attr('data-price');
		var points = $(this).attr('data-points');
		var sku = $(this).attr('data-sku');

		$('.discount-api').prop('disabled', true);
		$('#place_order').prop('disabled', true);
		$('#place_order').text('Wait for discount please');

		$.ajax({
			type: "POST",
			url: admin.ajaxurl,
			data: {
				action: "wpm_get_discount_api",
				name: name,
				phone: phone,
				discount: discount,
				points: points,
				sku: sku
			},
			success: function() {
				$('body').trigger("update_checkout");
				$('#place_order').prop('disabled', false);
				$('#place_order').text('Place Order');

				$.ajax({
					type: "POST",
					url: admin.ajaxurl,
					data: {
						action: "wpm_get_sku_api",
						name: name,
						phone: phone,
						sku: sku
					},
					success: function() {

					}
				});
			}
		});
	});
});


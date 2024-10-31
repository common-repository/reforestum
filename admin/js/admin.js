(function ($) {
	$(document).ready(function () {
		/**
		 * Preselect forests based on the sales contract on load
		 * only disabled unavailable forests, but not change currently selected
		 */
		if ($('select[name="reforestum_sales_contract"]').length) {
			$('select[name="reforestum_sales_contract"]').trigger('change');
		}
	});

	/**
	 * Test openrouteservice connection
	 */
	$('#openrouteservice-test').on('click', function(e) {
		e.preventDefault();
		$.ajax(reforestum.ajaxurl, {
			data: {
				action: 'openrouteservice_test',
				api_key: $('input[name=reforestum_openrouteservice_api_key]').val()
			},
			method: 'POST',
			success: function(data) {
				if(!data.success) {
					$('#openrouteservice-test-response').removeClass('notice notice-success').addClass('error').addClass().html(data.data);
					return false;
				}
				if(data.data.length == 2) {
					var message = $('#openrouteservice-test-response').data('success');
					$('#openrouteservice-test-response').removeClass('error').addClass('notice notice-success').html(message.replaceAll('LON', data.data[0]).replaceAll('LAT', data.data[1]).replaceAll('[gmaps_link]', '<a href="https://google.com/maps/search/' + data.data[1] + ',' + data.data[0] + '" target="_blank">').replaceAll('[/gmaps_link]', '</a>'));
					return true;
				}
			}
		});	
	});

	/**
	 * On change sales contract
	 * Ignore the saved selected forests value and just update with the new one
	 */
	$('select[name="reforestum_sales_contract"]').on('change', function (e) {
		var selectedValue = $(this).val();
		var selectedContract = selectedValue || reforestum.selected_sales_contract;
		if (selectedContract) {
			var selectedContractDetail = reforestum.sales_contracts[selectedContract];
			var availableForests = selectedContractDetail.forests;
			$('input[name="reforestum_selected_forests[]"]').each(function (i, e) {
				var fieldValue = $(this).val();
				if (availableForests) {
					var matching = $.grep(availableForests, function (n, idx) {
						return (fieldValue == n);
					});
					// If not match, 
					if (!matching.length) {
						$(this).prop({
							disabled: true,
							checked: false
						});
					} else {
						$(this).prop({
							checked: $('#reforestum_project_restricted').is(':checked') ? $(this).is(':checked') : true,
							disabled: $('#reforestum_project_restricted').is(':checked') ? false : true
						});
					}
				} else {
					// If no forests restrictions, enable all
					$(this).prop({
						checked: true,
						disabled: !$('#reforestum_project_restricted').is(':checked')
					});
				}

			});
		}
	});

	/**
	 * At least one forest required
	 */
	$('input[name="reforestum_selected_forests[]"]').change(function () {
		var selectedForests = $(this).parents('form').find('input[name="reforestum_selected_forests[]"]:checked');
		if (!selectedForests.length) {
			alert(reforestum.forest_required);
			$(this).prop('checked', true);
		}
	});

	/**
	 * Show hide forest selection for project constrains
	 */
	$('#reforestum_project_restricted').on('change', function () {
		$('select[name="reforestum_sales_contract"]').trigger('change');
	});


	/**
	 * Project contstrains on product variation
	 */
	$('body').on('woocommerce_variations_loaded', function (e) {
		/**
		 * On change variation sales contract
		 * Ignore the saved selected forests value and just update with the new one
		 */
		$('.reforestum_sales_contract_variation select').on('change', function (e) {
			var selectedValue = $(this).val();
			var selectedContract = selectedValue || reforestum.selected_sales_contract;
			if (selectedContract) {
				var selectedContractDetail = reforestum.sales_contracts[selectedContract];
				var availableForests = selectedContractDetail.forests;

				// Dependent elements
				var variationForests = $(this).parents('.form-row').siblings('.reforestum_selected_forests_variation').find('input[type="checkbox"]');
				var variationRestricted = $(this).parents('.form-row').siblings('.reforestum_project_restricted_variation').find('input[type="checkbox"]');

				// Select forest checkboxes from preview form row
				variationForests.each(function (i, e) {
					var fieldValue = $(this).val();
					if (availableForests) {
						var matching = $.grep(availableForests, function (n, idx) {
							return (fieldValue == n);
						});
						// If not match, 
						if (!matching.length) {
							$(this).prop({
								disabled: true,
								checked: false
							});
						} else {
							$(this).prop({
								checked: variationRestricted.is(':checked') ? $(this).is(':checked') : true,
								disabled: variationRestricted.is(':checked') ? false : true
							});
						}
					} else {
						// If no forests restrictions, enable all
						$(this).prop({
							checked: true,
							disabled: !variationRestricted.is(':checked')
						});
					}

				});
			}
		});

		$('.reforestum_sales_contract_variation select').each(function () {
			$(this).trigger('change');
		});

		$('.reforestum_project_restricted_variation input[type="checkbox"]').on('change', function () {
			$(this).parents('.form-row').siblings('.reforestum_sales_contract_variation').find('select').trigger('change');
		});

		/**
		 * At least one forest is required
		 */
		$('.reforestum_selected_forests_variation input[type="checkbox"]').change(function () {
			var selectedForests = $(this).parents('.reforestum_selected_forests_variation').find('input[type="checkbox"]:checked');
			if (!selectedForests.length) {
				alert(reforestum.forest_required);
				$(this).prop('checked', true);
			}
		});

	});

})(jQuery);
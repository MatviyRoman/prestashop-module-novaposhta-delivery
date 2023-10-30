<script>
	alert(777);
</script>


<div id="mobile_np_select">
	<div class="back_block">
		<i class="menu-left-arrow"></i><span> Назад </span>
	</div>
	<input type="text" id="mobile_np_popup" value="" placeholder="{l s='Выберите отделение' mod='np'}" />
</div>

<script src="/themes/default-bootstrap/js/jquery.richAutocomplete.js" type="text/javascript"></script>

<style type="text/css">
	tr.np_block {
		display: none;
	}
</style>

<script>
	$(document).on('click', '#np_mobile, .np_mobile-cintainer', function() {
		//rich auotocomplete start

		var idAddress_delivery = ($('#opc_id_address_delivery').length == 1 ? $('#opc_id_address_delivery').val() :
			$('#id_address_delivery').val());
		var idAddress_invoice = ($('#opc_id_address_invoice').length == 1 ? $('#opc_id_address_invoice').val() : (
			$('#addressesAreEquals:checked').length == 1 ? idAddress_delivery : ($('#id_address_invoice')
				.length == 1 ? $('#id_address_invoice').val() : idAddress_delivery)));
		var input = $('#mobile_np_popup');
		input.val('');
		var country_list = [];
		//console.log(formatedAddressFieldsValuesList);
		var delivery_city;
		// var delivery_city = '{if !isset($address_delivery_cart->city)}' + (typeof window.formatedAddressFieldsValuesList !== 'undefined' && window.formatedAddressFieldsValuesList.length !== 0 ? window.formatedAddressFieldsValuesList["{$cart->id_address_delivery}"]["formated_fields_values"]["city"] : formatedAddressFieldsValuesList["{$cart->id_address_delivery}"]["formated_fields_values"]["city"]) + '{else}{$address_delivery_cart->city}{/if}';
		// console.log(delivery_city);

		// console.log('id_address_delivery: ' + idAddress_delivery);
		// console.log('id_address_invoice: ' + idAddress_invoice);	

		if (typeof window.formatedAddressFieldsValuesListNP !== 'undefined' && window
			.formatedAddressFieldsValuesListNP.length !== 0) {
			// console.log(window.formatedAddressFieldsValuesListNP);
			delivery_city = window.formatedAddressFieldsValuesListNP[idAddress_delivery]["formated_fields_values"][
				"city"
			];
			window.formatedAddressFieldsValuesListNP = [];
		} else {
			delivery_city = formatedAddressFieldsValuesList[idAddress_delivery]["formated_fields_values"]["city"];
		}
		// console.log(window.formatedAddressFieldsValuesListNP);
		// console.log(formatedAddressFieldsValuesList);
		// console.log($('#customer_delivery_city').html());

		var np_warehouse_selected = npWarehouseSelected;

		country_list = getNpPoints(delivery_city, np_warehouse_selected);

		var loadPage = function(searchTerm, pageNumber, pageSize) {
			if (searchTerm === '') {
				return country_list.slice((pageNumber * pageSize), (pageNumber * pageSize) + pageSize);
			}

			var searchedCountries = country_list.filter(function(item) {
				//console.log(item.description);
				return item.description.toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1;
			});

			return searchedCountries.slice((pageNumber * pageSize), (pageNumber * pageSize) + pageSize);
		};

		$('.rich-autocomplete-list').remove(); // TODO: Придумати інше рішення замість цього
		if (!input.next().hasClass('rich-autocomplete-list') && country_list) {
			$(input).richAutocomplete({
				loadPage: loadPage,
				paging: true,
				pageSize: 20,
				select: function(item) {
					$('#np_block_info_mobile').html(item.reception);
					$('#np_mobile span.np_mobile_text').text(item.description);
					updateCustomerAddress(item.description);
					$('#mobile_np_select .back_block').click();
				},
				extractText: function(item) {
					return item.description;
				},
				render: function(item) {
					// console.log(item);
					//return '<p>' + item.description + '</p><small>' + item.ref + '</small>';
					return '<p>' + item.description + '</p><p class="np_rexeption">' + item.reception +
						'</p>';
				},
			});
		}
		//rich auotocomplete end
		$('#mobile_np_select').show();
		$(input).focus();
		$('body').addClass('hidden-y');
	});

	$(document).on('click', '#mobile_np_select .back_block', function() {
		$('#mobile_np_select').hide();
		$('body').removeClass('hidden-y');

		// checkNPSelected(); // Заблокувати чи розблокувати кнопку оформлення замовлення - order-carrier.tpl
		if ($('span.np_mobile_text').text() == '{l s="Выберите отделение"}') { // TODO: Можливо є інший кращий спосіб це зробити
		$('#submitAccountSummary').attr('disabled', 'disabled');
		$('#submitAccountSummaryText').html('{l s="Выберите отделение"}');
	} else {
		$('#submitAccountSummary').removeAttr('disabled');
		$('#submitAccountSummaryText').html('&nbsp;');
	}
	});

	function getNpPoints(delivery_city, np_warehouse_selected) {
		var country_list = [];
		$.ajax({
			type: 'POST',
			headers: { "cache-control": "no-cache" },
			url: '/modules/np/ajax.php',
			async: false,
			cache: false,
			dataType: "html",
			data: "city=" + delivery_city + "&reorder_warehouse=" + np_warehouse_selected,
			success: function(request) {
				country_list = JSON.parse(request);
				//country_list = request;
				var ref = $('#np_block').val();
				$.ajax({
					type: 'POST',
					headers: { "cache-control": "no-cache" },
					url: 'np2.php',
					async: false,
					cache: false,
					dataType: "html",
					data: "ref=" + ref,
					success: function(request) {
						// console.log(request);

					},
					error: function(request, error) {
						$('.np_mobile-cintainer').html('--');
						$('#np_block_info_mobile').html('--');
					}
				});

			}
		});

		return country_list;

	}


	function updateCustomerAddress(ref) {
		var idAddress_delivery = ($('#opc_id_address_delivery').length == 1 ? $('#opc_id_address_delivery').val() : $(
			'#id_address_delivery').val());
		var idAddress_invoice = ($('#opc_id_address_invoice').length == 1 ? $('#opc_id_address_invoice').val() : ($(
			'#addressesAreEquals:checked').length == 1 ? idAddress_delivery : ($('#id_address_invoice')
			.length == 1 ? $('#id_address_invoice').val() : idAddress_delivery)));

		$.ajax({
			type: 'POST',
			headers: { "cache-control": "no-cache" },
			url: 'quick-order?rand=' + new Date().getTime(),
			async: false,
			cache: false,
			dataType: "html",
			data: 'allow_refresh=1&ajax=true&method=updateExtraAddressEdit&id_address_delivery=' +
				idAddress_delivery + '&id_address_invoice=' + idAddress_invoice + '&token=' + static_token +
				'&np_warehouse=' + ref,
			success: function(request) {

			},
			error: function(request, error) {
				$('.np_mobile-cintainer').html('<option>-</option>');
				$('#np_block_info_mobile').html('');
			}
		});
	}
</script>


{strip}
	{addJsDef npWarehouseSelected=$np_warehouse_selected}
	{addJsDef mobile_np_text=$mobile_np_text}
{/strip}
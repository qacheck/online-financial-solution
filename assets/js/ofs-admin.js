jQuery(function($){
	var maxZ = Math.max.apply(null,$.map($('body *'), function(e,n){
		if($(e).css('position')==='fixed')
			return parseInt($(e).css('z-index'))||1 ;
		})
	);

    $('.ofs-modal').css('z-index', maxZ+2);

    function processing($msg='Đang thực hiện...') {
        var tmp = '<div id="ofs-processing" style="z-index:'+maxZ+';"><span>'+$msg+'</span></div>';
        processed();
        $('body').append(tmp);
    }

    function processed() {
        $('body').find('#ofs-processing').remove();
    }

	$(document).on('keydown', function(e){
		var ct_modal = $(this).find('.ofs-modal');
		if(e.key=='Escape' && ct_modal.length>0) {
			ct_modal.find('.modal-body').html('');
			ct_modal.hide();
		}
	});

    $(document).on('click', '.ofs-modal .close', function(e){
		var md = $(this).parents('.ofs-modal');
		md.find('.modal-body').html('');
		md.hide();
	});

	$('#ofs-add-condition-field').on('click', function(e){
		e.preventDefault();

		$('#ofs-condition-field-config').show(0, function(){
			var md = $(this),
				field_type = $('#ofs-select-condition-field').val(),
				condition_id = $('#post_ID').val();
			
			$.ajax({
				url:ofs_admin.ajax_url+'?action=ofs_condition_add_field',
				type: 'POST',
				data: {field_type:field_type, condition_id:condition_id, nonce:ofs_admin.nonce},
				beforeSend:function(){
					processing();
				},
				success: function(response) {
					processed();
					md.find('.modal-body').html(response);
				}
			});
			
		});

		return false;
	});

	$(document).on('submit', '#ofs-condition-field-config-form', function(e){
		e.preventDefault();
		var frm = $(this),
			list = $('#ofs-condition-fields'),
			field_id = frm.find('input[name="field_id"]').val();
		$.ajax({
			url:frm.attr('action'),
			type: 'POST',
			dataType: 'json',
			data: frm.serialize(),
			beforeSend:function(){
				processing();
			},
			success: function(response) {
				processed();
				//console.log(response);
				if(response.status) {
					switch(response.act) {
						case 'add':
							list.append(response.html);
							break;
						case 'edit':
							list.find('#'+field_id).replaceWith(response.html);
							break;
					}
					condition_fields_sortable(true);
				}
				$('#ofs-condition-field-config .close').trigger('click');
			}
		});
		return false;
	});

	$(document).on('click', '.ofs-edit-field', function(e){
		e.preventDefault();
		var $this = $(this),
			field_id = $this.data('field'),
			field_type = $this.data('type'),
			condition_id = $('#post_ID').val();

		$('#ofs-condition-field-config').show(0, function(){
			var md = $(this);
			
			$.ajax({
				url:ofs_admin.ajax_url+'?action=ofs_condition_add_field',
				type: 'POST',
				data: {field_id:field_id, field_type:field_type, condition_id:condition_id, nonce:ofs_admin.nonce},
				beforeSend:function(){
					processing();
				},
				success: function(response) {
					processed();
					md.find('.modal-body').html(response);
				}
			});
			
		});

		return false;
	});

	$(document).on('click', '.ofs-remove-field', function(e){
		e.preventDefault();
		var $this = $(this),
			field_id = $this.data('field'),
			list = $('#ofs-condition-fields'),
			condition_id = $('#post_ID').val();
		$.ajax({
			url:ofs_admin.ajax_url+'?action=ofs_condition_remove_field',
			type: 'POST',
			data: {condition_id:condition_id, field_id:field_id, nonce:ofs_admin.nonce},
			beforeSend:function(){
				processing();
			},
			success: function(response) {
				processed();
				if(response) {
					list.find('#'+field_id).remove();
				}
				condition_fields_sortable(true);
			}
		});
	});

	function condition_fields_sortable(refresh=false) {
		if(!refresh) {
			$( "#ofs-condition-fields" ).sortable({
				placeholder: "ui-state-highlight"
			});

			$( "#ofs-condition-fields" ).disableSelection();

			$( "#ofs-condition-fields" ).on('sortupdate', function(e){
				var list = $(this),
					fields = list.find('.ui-sortable-handle'),
					condition_id = $('#post_ID').val(),
					sort = [];
					$.each(fields, function(index, el) {
						sort.push(el.id);
					});

				$.ajax({
					url:ofs_admin.ajax_url+'?action=ofs_condition_sort_fields',
					type: 'POST',
					data: {condition_id:condition_id, sort:sort, nonce:ofs_admin.nonce}
				});
			});
		} else {
			$( "#ofs-condition-fields" ).sortable('refresh');
			$( "#ofs-condition-fields" ).trigger('sortupdate');
		}

	}

	condition_fields_sortable();

	/* requirement management */
	var $oecr = $('#ofs-edit-condition-requirement');
	if($oecr.length>0) {
		$oecr.find('.ofs-manage-condition-field-require').on('click', function(e){
			var uid = parseInt($oecr.find('[name="uid"]').val());
			var cid = parseInt($oecr.find('[name="cid"]').val());
			var field_id = $(this).data('field');
			var field_type = $(this).data('type');
			$('#ofs-condition-field-require-config').show(0, function(){
				var md = $(this);
				$.ajax({
					url:ofs_admin.ajax_url+'?action=ofs_edit_condition_field_require',
					type: 'POST',
					data: {uid:uid, cid:cid, field_id:field_id, field_type:field_type, nonce:ofs_admin.nonce},
					beforeSend:function(){
						processing();
					},
					success: function(response) {
						processed();
						md.find('.modal-body').html(response);
					}
				});
			});
			return false;
		});

		$(document).on('submit', '#ofs-condition-field-require-config-form', function(e){
			e.preventDefault();
			var $frm = $(this);
			$.ajax({
				url:$frm.attr('action'),
				type: 'POST',
				dataType: 'json',
				data: $frm.serialize(),
				beforeSend:function(){
					processing();
				},
				success: function(response) {
					processed();
					//console.log(response);
					$frm.closest('.ofs-modal').find('.close').trigger('click');
				}
			});

			return false;
		});

	}
	
	$('#user-profile-photo-upload').on('click', function(e){
		var fd = new FormData();
        var files = $('#user-profile-photo')[0].files[0];
        //console.log(files);
        fd.append('profile_photo',files);
        
        $.ajax({
            url: ofs_admin.ajax_url+'?action=profile_photo_upload',
            type: 'post',
            data: fd,
            contentType: false,
            processData: false,
            //dataType:'json',
            success: function(res){
            	$('#user-profile-picture-select').html(res);
                //console.log(res);
            },
        });
        
	});

	$('body').on('click', '.ofs-buy-borrower', function(e){
		var $this = $(this),
			borrower_id = parseInt($this.data('borrower-id')),
			lender_id = parseInt($this.data('lender-id'));
		var cf = confirm('Bạn có chắc muốn mua?');
		if(cf==true) {
			$.ajax({
				url:ofs_admin.ajax_url+'?action=ofs_buy_borrower',
				type: 'POST',
				dataType: 'json',
				data: {borrower_id:borrower_id, lender_id:lender_id, nonce:ofs_admin.nonce},
				beforeSend:function(){
					processing();
				},
				success: function(response) {
					processed();
					// switch(response['status']) {
					// 	case 0:
					// 		alert(response['message']);
					// 		break;
					// 	case 1:

					// }
					if(response['status']==1) {
						$('#wp-admin-bar-user-coin .ab-item strong').text(response['coin']);
						$('body').find('#conn-'+borrower_id+'-'+lender_id).html(response['html']);
					} else {
						alert(response['message']);
					}
					//$('body').find('#conn-'+borrower_id+'-'+lender_id).html(response);
				}
			});
		}
	});

	$('body').on('click', '.ofs-view-borrower', function(e){
		var $this = $(this),
			borrower_id = parseInt($this.data('borrower-id')),
			lender_id = parseInt($this.data('lender-id'));
		$.ajax({
			url:ofs_admin.ajax_url+'?action=ofs_view_borrower',
			type: 'POST',
			dataType: 'json',
			data: {borrower_id:borrower_id, lender_id:lender_id, nonce:ofs_admin.nonce},
			beforeSend:function(){
				//processing();
				$('body').append('<div id="borrower-info-popup" style="display:none;z-index:'+maxZ+';"><div class="dialog"><div class="dialog-header"><button type="button" class="close">x</button></div><div class="content-body"></div></div></div>')
			},
			success: function(res) {
				//processed();
				$('body').find('#borrower-info-popup').find('.dialog-header').prepend('<span class="title">'+res['title']+'</span>');
				$('body').find('#borrower-info-popup').show().find('.content-body').html(res['body']);
			}
		});
	});

	$('body').on('click', '#borrower-info-popup .close', function(e){
		$(this).closest('#borrower-info-popup').remove();
	});

	$('body').on('change', '#select-condition', function(e){
		var condition_id = $(this).val();
		$('body').find('.borrower-data #condition-'+condition_id).addClass('active').siblings().removeClass('active');
	});

	$('#user_coin').inputNumber({
		decimals: 0,
        thousandsSep: ',',
        decPoint: '.',
        integer: true,
        negative: false
	});

	var ajax_user = null;
	$('.ofs-user-toggle-status').on('click', function(e){
		if(ajax_user!=null) {
			ajax_user.abort();
		}
		var _this = $(this),
			uid = parseInt(_this.data('user'));
		ajax_user = $.ajax({
						url:ofs_admin['ajax_url']+'?action=ofs_change_user_status',
						type:'post',
						dataType:'html',
						data:{uid:uid,nonce:ofs_admin['nonce']},
						success:function(res) {
							if(res!='') {
								_this.html(res);
							}
						}
					});

		return false;
	});
});
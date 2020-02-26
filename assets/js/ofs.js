function checkPhoneNumber(textbox) {
	if (textbox.value === '') { 
        textbox.setCustomValidity('Vui lòng nhập số điện thoại!'); 
    } else if (textbox.validity.patternMismatch) { 
        textbox.setCustomValidity('Số điện thoại không đúng!'); 
    } else { 
        textbox.setCustomValidity(''); 
    } 

    return true; 
}

function checkFullName(textbox) {
	if (textbox.value === '') { 
        textbox.setCustomValidity('Vui lòng nhập họ tên!'); 
    } else if (textbox.validity.patternMismatch) { 
        textbox.setCustomValidity('Họ tên không đúng!'); 
    } else { 
        textbox.setCustomValidity(''); 
    } 

    return true; 
}

function url_get(param) {
    var url = new URL(window.location.href);
    return url.searchParams.get(param);
}

jQuery(function($){
    $('.ofs-button-borrower-login').on('click', function(e){
        $('.ofs-borrower-login').toggleClass('open');
    });
    //console.log(url_get('verify'));
    if(url_get('verify')) {
        $('.ofs-borrower-login').addClass('open');
    }
    var borrowers_list = $('#ofs-borrowers-list');
    function ofs_get_borrowers() {
        if(borrowers_list.length>0) {
            var cid = parseInt($('select[name=cid]').val()),
                odb = borrowers_list.find('[name=odb]').val(),
                od = borrowers_list.find('[name=od]').val(),
                page = parseInt(borrowers_list.find('[name=page]').val()),
                maxpage = parseInt(borrowers_list.find('[name=maxpage]').val());

            $.ajax({
                url:ofs.ajax_url,
                type:'GET',
                data:{action:'ofs_get_public_borrowers',cid:cid,odb:odb,od:od,page:page},
                success:function(response) {
                    borrowers_list.html(response);
                    borrowers_list.find('#ofs-borrowers-list-page .prev').on('click', function(e){
                        e.preventDefault();
                        var page = parseInt(borrowers_list.find('[name=page]').val());
                        if(page>1) {
                            borrowers_list.find('[name=page]').val(page-1);
                            ofs_get_borrowers();
                        }
                        return false;
                    });

                    borrowers_list.find('#ofs-borrowers-list-page .next').on('click', function(e){
                        e.preventDefault();
                        var page = parseInt(borrowers_list.find('[name=page]').val());
                        var maxpage = parseInt(borrowers_list.find('[name=maxpage]').val());
                        if(page<maxpage) {
                            borrowers_list.find('[name=page]').val(page+1);
                            ofs_get_borrowers();
                        }
                        return false;
                    });
                }
            });
        }
    }
    
    ofs_get_borrowers();

    $('#ofs-condition-filter-form').on('submit', function(e){
        var frm = $(this);
        $.ajax({
            url:ofs.ajax_url,
            type:'POST',
            data:frm.serialize(),
            beforeSend:function(xhr) {
                processing();
            },
            success:function(response) {
                processed();
                $('#ofs-condition-filter-results').html(response);
            }
        });

        return false;
    });

    var maxZ = 0;
    setTimeout(function(){
        maxZ = Math.max.apply(null,$.map($('body *'), function(e,n){
            if($(e).css('position')==='fixed') {
                return parseInt($(e).css('z-index'))||1 ;
            }
        }));
    },1000);

    function processing($msg='Đang thực hiện...') {
        var tmp = '<div id="ofs-processing" style="z-index:'+maxZ+';"><span>'+$msg+'</span></div>';
        processed();
        $('body').append(tmp);
    }

    function processed() {
        $('body').find('#ofs-processing').remove();
    }

    $('.ofs-modal-close').on('click', function(e){
        $(this).closest('#ofs-modal').fadeOut();
    });

    $('body').on('click', '#ofs-condition-filter-results .register', function(e){
        var $this = $(this),
            lender_id = parseInt($this.data('lender-id')),
            condition_id = parseInt($this.data('condition-id'));
        if(lender_id>0) {
            $.ajax({
                url:ofs.ajax_url+'?action=borrower_register',
                type:'POST',
                dataType:'json',
                data:{lender_id:lender_id,condition_id:condition_id,nonce:ofs.nonce},
                beforeSend:function(xhr) {
                    processing('Đang gửi đăng ký...');
                },
                success:function(res) {
                    processed();
                    if(res['status']) {
                        $this.html(res['message']);
                        $this.prop('disabled', true);
                    } else {
                        alert(res['message']);
                    }
                },
                error(xhr,status,err) {
                    processed();
                    alert(err);
                }
            });
        }
    });

    $(document).on('click', '.toggle-lender-bio-mobile', function(e){
        $(this).closest('.osf-lender-profile').find('.lender-bio-mobile').toggle();
    });
});
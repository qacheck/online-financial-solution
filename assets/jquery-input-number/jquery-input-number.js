;(function($, window, document, undefined) {

    $.fn.inputNumber = function( options ) {

        var settings = $.extend({
            // These are the defaults.
            decimals: 0,
            thousandsSep: ',',
            decPoint: '.',
            integer: true,
            negative: true
        }, options );

        return this.each(function(){
            var $this = $(this);

            $this.on('keyup change', function(e){
                var opts = settings;
                var number = $this.val();

                number = (number + '').replace(/[^0-9+\-\.]/g, '');

                /* fix dấu */
                var sigFix = function(number) {
                    number = ''+number;
                    if (number.length > 1) {
                        return number.slice(0, 1) + number.slice(1).replace(/\-|\+/g, '');
                    }
                    return number;
                }

                if(opts.negative) {
                    number = sigFix(number);
                } else {
                    number = number.replace(/\-|\+/g,'');
                }

                var prec = (!isFinite(+opts.decimals)) ? -1 : Math.abs(opts.decimals);
                var sep = (typeof opts.thousandsSep === 'undefined') ? ',' : opts.thousandsSep;

                if(opts.integer || prec<0) {
                    number = (number!='') ? parseInt(number.replace(/\./g, '')) : '';
                    number = (''+number).replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                } else {
                    var dec = (typeof opts.decPoint === 'undefined') ? '.' : opts.decPoint;
                    var s = '';
                    var sn = [];

                    s = number.split('.');
                    sn[0] = ''+( (s[0]!='')?parseInt(s[0]):'');

                    if(s.length>1) {
                        s.shift();
                        var decimals = s.join().replace(/\D/g, '');
                        if(prec>0) {
                            decimals = decimals.slice(0,prec);
                        }
                        sn[1] = decimals;
                    }

                    if (sn[0].length > 3) {
                        sn[0] = sn[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                    }

                    if(sn.length>1) {
                        if (sn[1].length > 3) {
                            sn[1]=sn[1].replace(/\B(?=(?:\d{3})+(?!\d))/g,sep);
                        }
                    }
                    number = sn.join(dec);
                }

                $this.val(number);

            }).trigger('change');
        });

    };

})(window.Zepto || window.jQuery, window, document);
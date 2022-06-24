jQuery(document).ready(function() {
    console.log('TAO Super 1.0.0');
	window.tao_parseQuery = function(qstr) {
		var query = {};
		var a = qstr.substr(1).split('&');
		for (var i = 0; i < a.length; i++) {
			var b = a[i].split('=');
			query[decodeURIComponent(b[0])] = decodeURIComponent(b[1] || '');
		}
		return query;
	}
	window.tao_setCookie = function(cname, cvalue, exdays, domain) {
		if (exdays != 0) {
			var d = new Date();
			d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
			var expires = "expires=" + d.toUTCString();
			document.cookie = cname + "=" + cvalue + "; domain=" + domain + "; " + expires + "; path=/";
		} else {
			//Session cookie
			document.cookie = cname + "=" + cvalue + "; domain=" + domain + "; path=/";
		}
	}
	window.tao_getCookie = function(cname) {
		var name = cname + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) == ' ') c = c.substring(1);
			if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
		}
		return "";
	}
    window.cqs_progress = false;
    window.tao_ajaxHandler = function(data, callback) {
        cqs_progress = true;
        data.data.n = toaglobal.n;
        jQuery.ajax(toaglobal.u, {
            timeout: 60000,
            data: data.data,
            type: "POST",
            error: function() {               
            },
            beforeSend: function() {},
            success: function(response) {
                var r = JSON.parse(response);
                if (r.error == true) {
                    console.log(response);
                } else {
                    data.res = r;
                    callback(data);
                }
            },
            complete: function() { cqs_progress = false; }
        }); 
    }    
    function check_affiliate() {
        var search = tao_parseQuery(window.location.search);
        if (search.aff != undefined) {
            var tac = tao_getCookie('tao_aff');
            //Test cookie's expiration time
            var ck = false;
            if (tac != '') {
                cd = JSON.parse(atob(tac));
                if (cd != '' && cd.a != undefined && cd.e != undefined) {
                    if (search.aff.trim() != cd.a) {
                        overwrite = false;
                        var n = new Date(Date.now());
                        n = Math.floor(n.getTime() / 1000);                        
                        if (n > cd.e) {
                            ck = true;
                        }
                    }
                }
            } else {
                ck = true;
            }
            if (ck == true) {
                //Validate the new affiliate
                tao_ajaxHandler({
                    data: {
                        action: 'affcheck',
                        affilate: search.aff.trim()
                    }
                }, function(data){
                    if (data.res.error == false && data.res.result != false) {
                        set_affiliate(data.res.result);
                    }
                });
            } else {
                //Update expiration
                set_affiliate('');
            }
        }
    }
    function set_affiliate(affilate) {
        //7 Days limit
        var search = tao_parseQuery(window.location.search);
        var e = new Date(Date.now() + 1000 * 60 * 60 * 24 * 14);
        e = Math.floor(e.getTime() / 1000);
        if (affilate == '') {
            affilate = search.aff.trim();
        }
        var cd = {
            a: affilate,
            e: e
        };
        cd = btoa(JSON.stringify(cd)); 
        tao_setCookie('tao_aff', cd, 30, 'thriveasone.ca');     
    }
    check_affiliate();
    jQuery('.tao_aff').on('click', function(e){
        var u = jQuery(this).prop('href');
        var force = false;
        if (u == undefined || u == '') {
            force = true;
            u = jQuery(this).data('url');
        }
        if (u != '') {
            var tac = tao_getCookie('tao_aff');
            if (tac != '') {
                cd = JSON.parse(atob(tac));
                if (cd.a != undefined) {
                    var a = 'https://' + cd.a.toLowerCase() + '--thriveasone.thrivecart.com';
                    var u = u.replace('https://checkout.thriveasone.ca', a);
                    window.location.href = u;
                    return false;
                }
            }
        }
        if (force) {
            window.location.href = u;
            return false;
        } else {
            return true;
        }    
    });
});
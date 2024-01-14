console.log('TAO Super 2.0.0');
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
    let cqs_progress = true;
    data.data.n = toaglobal.n;
    fetch(toaglobal.u, {
        method: 'POST',
        body: JSON.stringify(data.data),
        headers: {
            'Content-Type': 'application/json'
        },
        timeout: 60000
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(r => {
        if (r.error === true) {
            console.log(JSON.stringify(r));
        } else {
            data.res = r;
            callback(data);
        }
    })
    .catch(error => {
        console.error('There has been a problem with your fetch operation:', error);
    })
    .finally(() => {
        cqs_progress = false;
    });
}
//Modal helper functions
window.sm_bg_close = true; 
document.querySelectorAll('.x-modal-bg').forEach(function(element) {
    element.addEventListener('click', function() {
        return window.sm_bg_close;
    });
});
document.querySelectorAll('.x-off-canvas-bg').forEach(function(element) {
    element.addEventListener('click', function() {
        return window.sm_bg_close;
    });
});
window.sm_toggle = function(status, model_class, cb = '', bgclose = true) {
    setTimeout(function() {
        var element = document.querySelector('.' + model_class);
        var id = element ? element.getAttribute('data-x-toggleable') : null;
        var isModalOpen = window.xToggleGetState(id);
        if (status === true) {
            window.sm_bg_close = bgclose;
            if (!isModalOpen) {
                window.xToggleUpdate(id, status);
            }
        } else {
            window.sm_bg_close = true;
            window.xToggleUpdate(id, status);
        }
        if (cb !== "") cb();
    }, 100);
}    
//Cookies
function init_banner() {
    sm_toggle(true, 'tao_cookie_panel', '', false); 
}
document.querySelectorAll('.cookie_law_btn').forEach(function(element) {
    element.addEventListener('click', function() {
        init_banner();
    });
});
document.querySelectorAll('.tao_cookie_btn').forEach(function(element) {
    element.addEventListener('click', function() {
        var c = this.getAttribute('data-policy');
        tao_setCookie('tao_gdpr', c, 365, 'thriveas.one');
        tao_gtag_status(c);
        sm_toggle(false, 'tao_cookie_panel');
    });
});
//Determine the laws before we continue
function init_cookie() {
    var choice = tao_getCookie('tao_gdpr');
    var law = tao_getCookie('tao_laws');
    if (choice == '') {
        if (law == '') {
            tao_ajaxHandler({
                data: {
                    action: 'tao_country_code'
                }
            }, function(data){
                law = data.res.countryCode;
                tao_setCookie('tao_laws', law, 30, 'thriveas.one');
                if (cookie_law(law)) {
                    init_banner();
                } else {
                    tao_setCookie('tao_auto', 'true', 365, 'thriveas.one');
                    tao_setCookie('tao_gdpr', 'allow', 365, 'thriveas.one');
                    tao_gtag_status('allow');
                }
            });
        } else {
            if (cookie_law(law)) {
                init_banner();
            }
        }
    } else {
        tao_gtag_status(choice);
    }
    cookie_law(law);
}
init_cookie();
//Ensure GTAG obeys their decision   
function tao_gtag_status(status) {
    var v = false;
    if (status == "allow") {
        v = true;
    }
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        "tao_consent": v,
        "event": "consent"
    });
}
function cookie_law(law) {
    //Turn cookie features on/off
    var apply = false;
    var enable = ["CA","AT","BE","BG","HR","CZ","CY","DK","EE","FI","FR","DE","EL","HU","IE","IT","LV","LT","LU","MT","NL","PL","PT","SK","ES","SE","GB","UK","GR","EU"]
    if (enable.includes(law)) {
        apply = true;
        document.querySelectorAll('.cookie_law_btn').forEach(function(element) {
            element.style.display = '';
        });
    } else {
        document.querySelectorAll('.cookie_law_btn').forEach(function(element) {
            element.style.display = 'none';
        });
    }        
    return apply;
}

//Affiliates
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
    tao_setCookie('tao_aff', cd, 30, 'thriveas.one');     
}
check_affiliate();
document.querySelectorAll('.tao_aff').forEach(function(element) {
    element.addEventListener('click', function(e) {
        var u = this.href;
        var force = false;
        if (!u) {
            force = true;
            u = this.getAttribute('data-url');
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
document.querySelectorAll('.tao_sbtn').forEach(function(element) {
    element.addEventListener('click', function(e) {
        if (this.classList.contains('tao_in_modal')) {
            if (vimeoPlayer != null && vimeoPlayer !== false) {
                vimeoPlayer.pause();
                sm_toggle(false, 'tao_sample_modal');               
            }
        }
        var t = this.getAttribute('data-scroll');
        var s = parseInt(this.getAttribute('data-speed'), 10);
        var targetElement = document.getElementById(t);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop,
                behavior: 'smooth' // or 'auto' depending on desired scroll behavior
            });
        }
    });
});
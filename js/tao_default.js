jQuery(document).ready(function() {
  console.log('TAO Global 1.0.0');

  //Testimonials
	jQuery('.tao_slider_test').each(function() {
		//Power first slider
		jQuery('.tao_slider_test').slick({
            dots: true,
            appendDots: '#tao_slider_test_nav',
            appendArrowsNext: '#tao_slider_test_nav_next',
            appendArrowsPrev: '#tao_slider_test_nav_prev',
            infinite: true,
            speed: 1000,
            slidesToShow: 1,
            slidesToScroll: 1,
            adaptiveHeight: false,
            autoplay: true,
            autoplaySpeed: 5000,
            prevArrow: '<button type="button" class="tao_slider_btn slick-prev"><i class="x-icon" aria-hidden="true" data-x-icon-l=""></i></button>',
            nextArrow: '<button type="button" class="tao_slider_btn slick-next"><i class="x-icon" aria-hidden="true" data-x-icon-l=""></i></button>',
            fade: true,
            cssEase: 'linear' 
		});
	});

    /* Program Homepage Slider  */
	jQuery('.tao_program_slider').each(function() {
		//Power first slider
		jQuery('.tao_program_slider').slick({
            dots: true,
            appendDots: '#tao_slider_prog_nav',
            appendArrowsNext: '#tao_slider_prog_nav_next',
            appendArrowsPrev: '#tao_slider_prog_nav_prev',
            infinite: true,
            speed: 1000,
            slidesToShow: 1,
            slidesToScroll: 1,
            adaptiveHeight: false,
            autoplay: true,
            autoplaySpeed: 5000,
            prevArrow: '<button type="button" class="tao_slider_btn slick-prev"><i class="x-icon" aria-hidden="true" data-x-icon-l=""></i></button>',
            nextArrow: '<button type="button" class="tao_slider_btn slick-next"><i class="x-icon" aria-hidden="true" data-x-icon-l=""></i></button>',            
            responsive: [
                {
                  breakpoint: 10000,
                  settings: "unslick"
                },
                {
                  breakpoint: 979,
                  settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1
                  }
                },
                {
                  breakpoint: 767,
                  settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                  }
                }
              ]
		});
	});    

  jQuery('.tao_docs_slider').each(function() {
		//Power first slider
		jQuery('.tao_docs_slider').slick({
            dots: true,
            appendDots: '#tao_slider_doc_nav',
            appendArrowsNext: '#tao_slider_doc_nav_next',
            appendArrowsPrev: '#tao_slider_doc_nav_prev',
            infinite: true,
            speed: 1000,
            slidesToShow: 1,
            slidesToScroll: 1,
            adaptiveHeight: false,
            autoplay: true,
            autoplaySpeed: 5000,
            prevArrow: '<button type="button" class="tao_slider_btn slick-prev"><i class="x-icon" aria-hidden="true" data-x-icon-l=""></i></button>',
            nextArrow: '<button type="button" class="tao_slider_btn slick-next"><i class="x-icon" aria-hidden="true" data-x-icon-l=""></i></button>',            
            responsive: [
                {
                  breakpoint: 10000,
                  settings: "unslick"
                },
                {
                  breakpoint: 979,
                  settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1
                  }
                },
                {
                  breakpoint: 767,
                  settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                  }
                }
              ]
		});
	});

    /* Program Event's Component */
    //Dynamically labal the components
    var sc = 0;
    jQuery('.tao_esn').each(function() {
        sc++;
        var id = 'tao_ems_nav_' + sc;
        jQuery(this).attr('id', id);        
    });
    var sc = 0;
    jQuery('.tao_esn_prev').each(function() {
        sc++;
        var id = 'tao_ems_prev_' + sc;
        jQuery(this).attr('id', id);        
    });
    var sc = 0;
    jQuery('.tao_esn_next').each(function() {
        sc++;
        var id = 'tao_ems_next_' + sc;
        jQuery(this).attr('id', id);        
    });    
    //Build sliderrs
    var sc = 0;
    jQuery('.tao_event_modal_slider').each(function() {
        //Get and assign the slider an ID
        sc++;
        var id = 'tao_ems_' + sc;
        jQuery(this).attr('id', id); 
        //Dynmaic Slick
		jQuery('#' + id).slick({
            dots: true,
            appendDots: '#tao_ems_nav_' + sc,
            appendArrowsNext: '#tao_ems_next_' + sc,
            appendArrowsPrev: '#tao_ems_prev_' + sc,
            infinite: true,
            speed: 1000,
            slidesToShow: 1,
            slidesToScroll: 1,
            adaptiveHeight: false,
            autoplay: true,
            autoplaySpeed: 5000,
            prevArrow: '<button type="button" class="tao_mini_slider_btn slick-prev"><i class="x-icon" aria-hidden="true" data-x-icon-l=""></i></button>',
            nextArrow: '<button type="button" class="tao_mini_slider_btn slick-next"><i class="x-icon" aria-hidden="true" data-x-icon-l=""></i></button>'
		});        
     
    });

});



jQuery(document).ready(function($) {
  
  console.log('>>> ui actions ready ...');
  console.log("working");
  
  $(window).on("load",function(){
    $(".mobile-sidebar .product-categories").mCustomScrollbar({
      axis: "x",
      theme: "rounded-dark",
    });
  });
  
  $('#myModal .close').on('click', function(){    
    $('#myModal').hide();
  })
  
  $('.html.header-button-2 .button, .call_req_bed').on('click', function(e){    
    e.preventDefault();    
    $('.register-badrift-popup').addClass('show');    
    $('html').addClass('no-scroll');
  })
  
  $('.register-badrift-popup span.close').on('click', function(e){    
    $('.register-badrift-popup').removeClass('show');    
    $('html').removeClass('no-scroll');
  })
  
  $('.is_showcase .items-wrapper').slick({
    infinite: true,
    slidesToShow: 3,
    slidesToScroll: 1,
    prevArrow: '<button type="button" class="slick-prev"><img src="https://staging.internshop.no/wp-content/themes/flatsome-child/images/arrow-right.png" class="arrow"/></button>',
    nextArrow: '<button type="button" class="slick-next"><img src="https://staging.internshop.no/wp-content/themes/flatsome-child/images/arrow-left.png" class="arrow"/> </button>',
    arrows: false,
    autoplay: true,
    responsive: [
      {
        breakpoint: 480,
        settings: {
          centerMode: false,
          slidesToShow: 1,
          arrows: true,
        }
      }
    ]      
  });

  $('.is_sequence_images .sequence-container').slick({
    infinite: true,
    slidesToShow: 5,
    slidesToScroll: 3,
    prevArrow: '<button type="button" class="slick-prev"><img src="https://staging.internshop.no/wp-content/themes/flatsome-child/images/arrow-right.png" class="arrow"/></button>',
    nextArrow: '<button type="button" class="slick-next"><img src="https://staging.internshop.no/wp-content/themes/flatsome-child/images/arrow-left.png" class="arrow"/> </button>',
    arrows: false,
    autoplay: true,
    responsive: [
      {
        breakpoint: 480,
        settings: {
          //centerMode: true,
          slidesToShow: 3,
          arrows: true,
        }
      }
    ]
  });
  
  $('.is_testimonials .testimonial-wrapper').slick({
    infinite: true,
    slidesToShow: 3,
    slidesToScroll: 1,
    prevArrow: '<button type="button" class="slick-prev"><img src="https://staging.internshop.no/wp-content/themes/flatsome-child/images/arrow-right.png" class="arrow"/></button>',
    nextArrow: '<button type="button" class="slick-next"><img src="https://staging.internshop.no/wp-content/themes/flatsome-child/images/arrow-left.png" class="arrow"/> </button>',
    arrows: false,
    autoplay: true,
    responsive: [
      {
        breakpoint: 480,
        settings: {
          slidesToShow: 1,
          arrows: true,            
        }
      }
    ]
  });        
  
  if( window.innerWidth <= 992 ){  }
  
  /* $('.custom_var_wrap label span').on('click', function(){

    $(`select[name="attribute_pa_size"] option[value="${jQuery(this).html().toLowerCase()}"]`).prop("selected",true).trigger("change")

  }) */

  jQuery('.product-gallery').append(jQuery('.product-footer').clone());




  // storeguide popup
  $(function() {
    $('.popup-modal').magnificPopup({
        closeOnBgClick: true,
        callbacks: {
            beforeOpen: function() {
                $.magnificPopup.instance.close = function() {
                    $.magnificPopup.proto.close.call(this);
                };
            }
        }
    });
  });

  
  

  // toggle tabs
  $(document).ready(function(){
	
    $('ul.tabs li').click(function(){
      var tab_id = $(this).attr('data-tab');
  
      $('ul.tabs li').removeClass('current');
      $('.tab-content').removeClass('current');
  
      $(this).addClass('current');
      $("#"+tab_id).addClass('current');
    })
  
  })

  $(".close-modal").click(function(){
    $.magnificPopup.close();
});

});

//footer popup storrelguide

jQuery(document).ready(function($) {

    $(".footer-popup-modal").click(function(){
        $("#footer-store-modal").addClass("visible");
        $('#wrapper, #main').css({"opacity": "0.5", "background-color": "black"});
        $("html").css("overflow", "hidden");
      });
      
      $(".close-modal").click(function(){
        $("#footer-store-modal").removeClass("visible");
        $('#wrapper, #main').css({"opacity": "1", "background-color": "white"});
        $("html").css("overflow", "initial");
      });
      
      $(document).click(function(event) {
        //if you click on anything except the modal itself or the "open modal" link, close the modal
        if (!$(event.target).closest("#footer-store-modal,.footer-popup-modal").length) {
          $("body").find("#footer-store-modal").removeClass("visible");
          $('#wrapper, #main').css({"opacity": "1", "background-color": "white"});
          $("html").css("overflow", "initial");
        }
      });
      

      $(function() {
        $('#footer-store-modal .tabs-nav a').click(function() {
      
          // Check for active
          $('#footer-store-modal .tabs-nav li').removeClass('active');
          $(this).parent().addClass('active');
      
          // Display active tab
          let currentTab = $(this).attr('href');
          $('.tabs-content div').hide();
          $(currentTab).show();
      
          return false;
        });
      });
});


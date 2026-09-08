jQuery(document).ready(function ($) {

    var coursesOwl = $('#courses-slider');
    coursesOwl.owlCarousel({
        rtl: true,
        loop: true,
        margin: 20, 
        nav: false, 
        autoplay: true,
        autoplayTimeout: 3000,
        autoplaySpeed: 800,
        autoplayHoverPause: true,
        dots: false,
        responsive: {
            0: {
                items: 1.5
            },
            768: {
                items: 2
            },
            1024: {
                items: 3
            },
            1200: {
                items: 4
            }
        }
    });
    $('.custom-next-btn').click(function () {
        coursesOwl.trigger('next.owl.carousel');
    });
    $('.custom-prev-btn').click(function () {
        coursesOwl.trigger('prev.owl.carousel');
    });




    var reviewsOwl = $('#reviews-carousel');
    reviewsOwl.owlCarousel({
        rtl: true,
        loop: true,
        margin: 20,
        nav: false,
        dots: false,
        responsive: {
            0: { items: 1.2 },
            768: { items: 2 }
        }
    });
    $('.custom-review-next').click(function () {
        reviewsOwl.trigger('next.owl.carousel');
    });

    $('.custom-review-prev').click(function () {
        reviewsOwl.trigger('prev.owl.carousel');
    });



    var articlescarousel = $('#articles-carousel');
    articlescarousel.owlCarousel({
        rtl: true,
        loop: true,
        margin: 14,
        nav: false,
        dots: false, 
        autoplay: true,
        autoplayTimeout: 3000,
        autoplaySpeed: 800,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 1.5
            },
            768: {
                items: 4
            },
            1024: {
                items: 5
            }
        }
    });
    $('.articles-carousel-next').click(function () {
        articlescarousel.trigger('next.owl.carousel');
    });
    $('.articles-carousel-prev').click(function () {
        articlescarousel.trigger('prev.owl.carousel');
    });



    $('.btn-outline-white').click(function (e) {
        e.preventDefault(); 
        var $target = $('.courses-wrapper');
        if ($target.length) {
            var targetTop = $target.offset().top;
            var targetHeight = $target.outerHeight();
            var windowHeight = $(window).height();
            var scrollPosition = targetTop - (windowHeight / 2) + (targetHeight / 2);
            $('html, body').animate({
                scrollTop: scrollPosition
            }, 800);
        }
    });
});

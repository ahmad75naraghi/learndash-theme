jQuery(document).ready(function ($) {
    var coursescarousel = $('.author-courses-carousel');
    var articlescarousel = $('#articles-carousel');
    
    coursescarousel.owlCarousel({
        rtl: true,
        loop: false,
        margin: 14, // فاصله بین کارت‌ها (همان gap در grid)
        nav: false,
        dots: false, // اگر می‌خواهید دات‌های پایین باشند، این را true کنید
        autoplay: false,
        responsive: {
            0: {
                items: 1.5
            },
            768: {
                items: 2.5
            },
            1024: {
                items: 5
            }
        }
    });
    articlescarousel.owlCarousel({
        rtl: true,
        loop: false,
        margin: 14, // فاصله بین کارت‌ها (همان gap در grid)
        nav: false,
        dots: false, // اگر می‌خواهید دات‌های پایین باشند، این را true کنید
        autoplay: false,
        responsive: {
            0: {
                items: 1.5
            },
            768: {
                items: 2.5
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

    $('.rating-stars .stars svg').on('click', function () {
        selectedRating = $(this).data('val');

        $('.rating-stars .stars svg').removeClass('active');

        $('.rating-stars .stars svg').each(function (index) {
            if (index < selectedRating) {
                $(this).addClass('active');
            }
        });
    });
});
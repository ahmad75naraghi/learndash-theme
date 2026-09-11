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

    // امتیازدهی به استاد (فعلاً فقط UI؛ ارسال به سرور در TODO ثبت شده)
    var selectedRating = 0;

    $('.rating-stars .stars svg').on('click', function () {
        selectedRating = parseInt($(this).data('val'), 10) || 0;

        // در چیدمان RTL ترتیب DOM با ترتیب دید یکی نیست؛ پس به‌جای شمارهٔ index
        // همهٔ ستاره‌هایی که مقدارشان کوچک‌تر/مساوی امتیاز انتخاب‌شده است فعال می‌شوند.
        $('.rating-stars .stars svg').each(function () {
            var starVal = parseInt($(this).data('val'), 10) || 0;
            $(this).toggleClass('active', starVal <= selectedRating && starVal > 0);
        });
    });
});
var SCM = SCM || {};

SCM.slider = (function(SCM, $){
    var $slideContainer = '.section__tabs--tabtrack',
        $slides = '.tabpanel',
        $slideLabel = $('.tabnav-label');

    function init() {
        setTimeout(function() {
            setSlideHeight($slides, $slideContainer);
        }, 200);

        bindEvents();
    }

    function setSlideHeight(slide, wrap) {
        var maxHeight = 0;

        $(slide).each(function() {
            if ($(this).height() > maxHeight) {
                maxHeight = $(this).height();
            }
        });

        $(wrap).css('height', maxHeight);
    }

    function bindEvents() {
        // tabbed content sections
        $slideLabel.on('click', function() {
            var $el = $(this);

            $el.siblings('label').removeClass('active');
            $el.addClass('active');
        });
    }

    return {
        init: init
    };

})(SCM, jQuery);

SCM.router = (function(SCM, $){
    function init() {
        if ($('.section__tabs').length) {
            SCM.slider.init();
        }
    }

    return {
        init: init
    };

})(SCM, jQuery);

SCM.router.init();
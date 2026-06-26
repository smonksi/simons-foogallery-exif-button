jQuery(function ($) {


    function closeExifWindow()
    {

        $("#fg-exif-window").remove();

    }



    /*
     * Close when FooGallery lightbox closes
     */
    $(document).on(
        "click",
        ".fg-lightbox-close, .fg-panel-close, .fg-close",
        function(){

            closeExifWindow();

        }
    );



    /*
     * Close when user advances image
     *
     * FooGallery navigation buttons
     */
    $(document).on(
        "click",
        ".fg-next, .fg-prev, .fg-lightbox-next, .fg-lightbox-prev",
        function(){

            closeExifWindow();

        }
    );



    /*
     * Observe FooGallery lightbox changes
     */
    const observer = new MutationObserver(function(mutations){


        mutations.forEach(function(mutation){


            /*
             * Lightbox removed
             */
            if (
                !document.querySelector(
                    ".fg-panel, .foogallery-lightbox"
                )
            ) {

                closeExifWindow();

            }



            /*
             * Image changed
             */
            if (
                mutation.type === "childList"
            ) {

                closeExifWindow();

            }


        });


    });



    observer.observe(

        document.body,

        {

            childList:true,

            subtree:true

        }

    );



});
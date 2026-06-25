jQuery(function ($) {


    $(document).on(
        "click",
        ".fg-show-exif-button",
        function(e){


            e.preventDefault();


            const attachmentId =
                this.id.replace(
                    "attachment-",
                    ""
                );



            const metadata =
                $(".fg-exif-data[data-attachment=\"" 
                + attachmentId +
                "\"]").html();



            openExifWindow(
                attachmentId,
                metadata
            );


        }
    );





    function openExifWindow(id, metadata)
    {


        $("#fg-exif-window").remove();



        const win = $(

        `
        <div id="fg-exif-window">


            <div class="fg-exif-header">


                Metadata for attachment ${id}



                <button class="fg-exif-close">

                    ×

                </button>


            </div>




            <div class="fg-exif-content">


                ${metadata}


            </div>



        </div>
        `

        );



        $("body").append(win);



        makeDraggable(win);


    }





    function makeDraggable(win)
    {


        let dragging = false;

        let offsetX = 0;

        let offsetY = 0;



        $(".fg-exif-header", win)

        .on(
            "mousedown",
            function(e){


                dragging = true;


                offsetX =
                    e.clientX -
                    win.offset().left;



                offsetY =
                    e.clientY -
                    win.offset().top;


            }
        );





        $(document)

        .on(
            "mousemove.fgExif",
            function(e){


                if(!dragging)
                    return;



                win.css({

                    left:
                    e.clientX - offsetX,


                    top:
                    e.clientY - offsetY


                });


            }
        );





        $(document)

        .on(
            "mouseup.fgExif",
            function(){

                dragging = false;

            }
        );





        $(".fg-exif-close", win)

        .on(
            "click",
            function(){

                win.remove();

            }
        );


    }


});
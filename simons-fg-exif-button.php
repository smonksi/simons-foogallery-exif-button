<?php
/**
 * Plugin Name: Simon's FG Exif Button
 * Plugin URI: https://www.simonatherley.com
 * Description: FooGallery EXIF Metadata Button + Hidden Metadata Injection
 * Version: 1.0.0
 * Author: Simon Atherley
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Simons_FG_Exif_Button {

	public function __construct() {

        add_action(
            'wp_enqueue_scripts',
            [ $this,'enqueue_fg_exif_window' ]
        );

        add_filter(
			'foogallery_attachment_html_link_attributes',
			[ $this, 'foogallery_attachment_html_link_attributes' ],
			11,
			3
		);
	}


    /**
	 * --------------------------------------------------
	 * Filter the data-caption-desc attribute
	 * --------------------------------------------------
	 */
    public function foogallery_attachment_html_link_attributes($data, $args, $attachment) {

        if (!current_user_can('edit_posts')) {
            return $data;
        }

		// if ( array_key_exists( 'data-caption-desc', $data ) ) {
		// 		$data['data-caption-desc'] = '';
		// }

		$data['data-caption-desc'] = $this->add_fg_exif_button_and_data( $attachment );
		
        return $data;

    }

    /**
	 * --------------------------------------------------
	 * Gather EXIF from meta data
	 * --------------------------------------------------
	 */
    private function add_fg_exif_button_and_data($attachment) {

            $attachment_id = $attachment->ID;

            $metadata = wp_get_attachment_metadata($attachment_id);

            if (!$metadata || empty($metadata['image_meta'])) {
                return $html;
            }


            $exif_groups = $metadata['image_meta']['extended_meta_data'];

            unset( $exif_groups['extended_meta_data'] ); 

            $rows = '';

            foreach ( $exif_groups as $group_key => $group_data ) {

                $rows .= '
                <tr class="exif-table-head">
                    <td>
                        <strong>' .
                            esc_html(ucwords(str_replace('_',' ',$group_key))) .
                        '</strong>
                    </td>
                    <td></td>
                </tr>';

                foreach ($group_data as $key => $value) {

                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }

                    if ($value !== '') {

                        $rows .= '

                        <tr>
                            <td>
                                <strong>' .
                                    esc_html(ucwords(str_replace('_',' ',$key))) .
                                '</strong>
                            </td>

                            <td>' .
                                esc_html($value) .
                            '</td>
                        </tr>';

                    }

                }

            }

           

            if (!$rows) {
                return $html;
            }


            $button = '

            <div class="fg-media-caption-title">

                <button
                    class="fg-show-exif-button"
                    id="attachment-' . esc_attr($attachment_id) . '">

                    + show metadata for ' . esc_html($attachment_id) . '

                </button>


                <div
                    class="fg-exif-data"
                    data-attachment="' . esc_attr($attachment_id) . '"
                    style="display:none;">

                    <table>
                        <tbody>
                            ' . $rows . '
                        </tbody>
                    </table>

                </div>

            </div>

            ';


            $html .= $button;


            return $html;

    }

        /**
	 * --------------------------------------------------
	 * JS for window ot display the EXIF data
	 * --------------------------------------------------
	 */
    public function enqueue_fg_exif_window() {

        wp_add_inline_script(
            'jquery',
            '

            jQuery(function($){


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



                    win.css({

                        position:"fixed",

                        top:"120px",

                        left:"120px",

                        width:"500px",

                        height:"550px",

                        background:"#333",

                        border:"1px solid #888",

                        zIndex:999999,


                        resize:"both",

                        overflow:"hidden",


                        boxShadow:
                        "0 4px 15px rgba(0,0,0,.35)"

                    });



                    $(".fg-exif-header",win)
                    .css({

                        height:"40px",

                        lineHeight:"40px",

                        padding:"0 10px",

                        background:"#333",

                        color:"#fff",

                        cursor:"move",

                        userSelect:"none"

                    });



                    $(".exif-table-head",win)
                    .css({
                        color:"#9e6020",

                    });


                    $(".fg-exif-content",win)
                    .css({

                        height:
                        "calc(100% - 40px)",

                        overflow:"auto",

                        padding:"10px",

                        fontSize:"14px"

                    });



                    $(".fg-exif-content table",win)
                    .css({

                        width:"100%",

                        borderCollapse:"collapse"

                    });



                    $(".fg-exif-content td",win)
                    .css({

                        padding:"5px",

                        borderBottom:
                        "1px solid #666"

                    });



                    $(".fg-exif-close",win)
                    .css({

                        float:"right",

                        marginTop:"8px",

                        cursor:"pointer"

                    });



                    $(".fg-exif-close",win)
                    .on(
                        "click",
                        function(){

                            win.remove();

                        }
                    );



                    makeDraggable(win);


                }




                function makeDraggable(win)
                {

                    let dragging=false;

                    let offsetX=0;

                    let offsetY=0;



                    $(".fg-exif-header",win)

                    .on(
                        "mousedown",
                        function(e){

                            dragging=true;


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
                                e.clientX-offsetX,


                                top:
                                e.clientY-offsetY

                            });


                        }
                    );



                    $(document)

                    .on(
                        "mouseup.fgExif",
                        function(){

                            dragging=false;

                        }
                    );


                }


            });

            '
        );

    }

}

new Simons_FG_Exif_Button();













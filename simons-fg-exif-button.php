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
            [ $this,'enqueue_fg_exif_assets' ]
        );


        add_filter(
			'foogallery_attachment_html_link_attributes',
			[ $this, 'foogallery_attachment_html_link_attributes' ],
			11,
			3
		);

        add_filter(
            'simons_fg_exif_data',
            [ $this, 'filter_exif_data' ],
            5,
            1
        );


	}

    /**
	 * Initial Filtering of metadata
	 */
    public function filter_exif_data($exif_groups) {



        /** 
         * Convert 'SRC' filepath fields 
         * (created in bash file) to 'openable' file paths
         */

        if (
            empty($exif_groups['SRC'])
            || !is_array($exif_groups['SRC'])
        ) {
            return $exif_groups;
        }

        

        foreach ($exif_groups['SRC'] as $key => &$value) {

            if (
                !in_array(
                    $key,
                    [
                        'source_jpg',
                        'converted_webp'
                    ],
                    true
                )
                || empty($value)
            ) {
                continue;
            }

            $value =
                $this->convert_path_to_os_editor_link(
                    $value
                );

        }


        $edit_path = $exif_groups['SRC']['edit_path'];


        if ( !empty( $edit_path )) {

            $ifdo_filename =$exif_groups["IFD0"]['Make'];
            $ifdo_fileExtension =$exif_groups["EXIF"]['Lens'];

            if (!empty($ifdo_filename) ) {

                $affinity_edit_path =   $edit_path . $ifdo_filename . '.afphoto';
                
                $exif_groups['SRC']['affinity_edit_path'] = $this->convert_path_to_os_editor_link( $affinity_edit_path );

                // $exif_groups["IFD0"]['Make'] = $exif_groups["EXIF"]["UndefinedTag:0xA433"];
            }
        }

        return $exif_groups;

    }

    private function convert_path_to_os_editor_link($file_path) {


        if (empty($file_path)) {
            return '';
        }

        
        // encode file path
        $encoded = rawurlencode($file_path);
        $uri = 'affinityedit://open?file=' . $encoded;
  

        /*
        * Display stays human-readable (original form)
        */
        return sprintf(
            '<a href="%s" class="fg-exif-file-link" target="_blank" rel="noopener">Open in Affinity: %s</a>',
            esc_attr($uri),
            esc_html($file_path)
        );

        // /*
        // * Normalise slashes only (no structural changes)
        // */
        // $normalized = str_replace('\\', '/', $path);


        // /*
        // * Fix Windows drive format for file:///
        // */
        // if (preg_match('#^[A-Za-z]:/#', $normalized)) {
        //     $normalized = '/' . $normalized;
        // }


        // /*
        // * Encode only unsafe URL characters
        // * (DO NOT touch ":" or "/")
        // */
        // $encoded = str_replace(
        //     [' ', '#'],
        //     ['%20', '%23'],
        //     $normalized
        // );


        // /*
        // * Build file URL
        // */
        // $file_url = 'file://' . $encoded;


        // /*
        // * Display stays human-readable (original form)
        // */
        // return sprintf(
        //     '<a href="%s" class="fg-exif-file-link" target="_blank" rel="noopener">%s</a>',
        //     esc_attr($file_url),
        //     esc_html($path)
        // );

    }



    /**
	 * Add EXIF data to FooGallery caption
	 */
    public function foogallery_attachment_html_link_attributes(
        $data,
        $args,
        $attachment
    ) {


        if (!current_user_can('edit_posts')) {
            return $data;
        }


		$data['data-caption-desc'] =
            $this->add_fg_exif_button_and_data($attachment);


        return $data;

    }



    /**
	 * Build EXIF HTML
	 */
    private function add_fg_exif_button_and_data($attachment) {


        $attachment_id = $attachment->ID;


        $metadata =
            wp_get_attachment_metadata($attachment_id);



        if (
            !$metadata ||
            empty($metadata['image_meta'])
        ) {

            return '';

        }



        if (
            empty(
                $metadata['image_meta']['extended_meta_data']
            )
        ) {

            return '';

        }

        $exif_groups = apply_filters( 'simons_fg_exif_data', $metadata['image_meta']['extended_meta_data'] );

        // $exif_groups = $metadata['image_meta']['extended_meta_data'];

        if ( array_key_exists('extended_meta_data',  $exif_groups ) ) {
            
            unset( $exif_groups['extended_meta_data'] );
        }

        if ( !array_key_exists('EXIF',  $exif_groups ) ) {

            $exif_groups = [];
            $exif_groups['EXIF'] = $metadata['image_meta']['extended_meta_data'];
        }


        $rows = '';

        foreach ($exif_groups as $group_key => $group_data) {


            $rows .= '

            <tr class="exif-table-head">

                <td>

                    <strong>' .
                        esc_html(
                            ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $group_key
                                )
                            )
                        )
                    . '</strong>

                </td>


                <td></td>


            </tr>';



            foreach ($group_data as $key => $value) {


                if (is_array($value)) {

                    $value =
                        implode(
                            ', ',
                            $value
                        );

                }



                if ($value !== '') {


                    $rows .= '

                    <tr>

                        <td>

                            <strong>' .
                                esc_html(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $key
                                        )
                                    )
                                )
                            . '</strong>

                        </td>


                        <td>' . $value . '</td>


                    </tr>';

                }

            }


        }



        if (!$rows) {

            return '';

        }



        return '

        <div class="fg-media-caption-title">


            <button

                class="fg-show-exif-button"

                id="attachment-' .
                    esc_attr($attachment_id)
                . '">


                + show metadata for ' .
                    esc_html($attachment_id)
                . '


            </button>




            <div

                class="fg-exif-data"

                data-attachment="' .
                    esc_attr($attachment_id)
                . '"

                style="display:none;">



                <table>

                    <tbody>

                        ' .
                        $rows
                        . '


                    </tbody>


                </table>



            </div>



        </div>';

    }





    /**
	 * Enqueue external JS and CSS
	 */
    public function enqueue_fg_exif_assets() {


        wp_enqueue_script(

            'simons-fg-exif',

            plugin_dir_url(__FILE__) .
            'assets/js/simons-fg-exif.js',

            [
                'jquery'
            ],

            '1.0.0',

            true

        );



        wp_enqueue_style(

            'simons-fg-exif',

            plugin_dir_url(__FILE__) .
            'assets/css/simons-fg-exif.css',

            [],

            '1.0.0'

        );


    }


}


new Simons_FG_Exif_Button();
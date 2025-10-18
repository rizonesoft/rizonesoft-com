<?php

class gdmBlocks {

	function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		wp_enqueue_style( 'gdm-styles', WP_GLUON_DL_MANAGER_URL . '/css/gdm_wp_styles.css' );

		wp_register_script(
			'gdm-blocks-script',
			WP_GLUON_DL_MANAGER_URL . '/js/gdm_blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ),
			WP_GLUON_DL_MANAGER_VERSION
		);

		$fancyArr   = array();
		$fancyArr[] = array(
			'label' => 'Fancy 0',
			'value' => 0,
		);
		$fancyArr[] = array(
			'label' => 'Fancy 1',
			'value' => 1,
		);
		$fancyArr[] = array(
			'label' => 'Fancy 2',
			'value' => 2,
		);

		$defColorArr = GDM_get_download_button_colors();

		$colorArr   = array();
		$colorArr[] = array(
			'label' => __( 'Default', 'gluon-download-manager' ),
			'value' => '',
		);
		foreach ( $defColorArr as $value => $label ) {
			$colorArr[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		wp_localize_script( 'gdm-blocks-script', 'sdmDownloadBlockItems', $this->get_items_array() );
		wp_localize_script( 'gdm-blocks-script', 'sdmDownloadBlockFancy', $fancyArr );
		wp_localize_script( 'gdm-blocks-script', 'sdmDownloadBlockColor', $colorArr );
		wp_localize_script(
			'gdm-blocks-script',
			'sdmBlockDownloadItemStr',
			array(
				'title'          => __( 'SDM Download', 'gluon-download-manager' ),
				'download'       => __( 'Download Item', 'gluon-download-manager' ),
				'downloadHelp'   => __( 'Select download item.', 'gluon-download-manager' ),
				'buttonText'     => __( 'Button Text', 'gluon-download-manager' ),
				'buttonTextHelp' => __( 'Customized text for the download button. Leave it blank to use default text.', 'gluon-download-manager' ),
				'fancy'          => __( 'Template', 'gluon-download-manager' ),
				'fancyHelp'      => __( 'Select download item template.', 'gluon-download-manager' ),
				'newWindow'      => __( 'Open Download in a New Window', 'gluon-download-manager' ),
				'color'          => __( 'Button Color', 'gluon-download-manager' ),
				'colorHelp'      => __( 'Select button color. Note that this option may not work for some templates.', 'gluon-download-manager' ),
			)
		);

		register_block_type(
			'gluon-download-manager/download-item',
			array(
				'attributes'      => array(
					'itemId'     => array(
						'type'    => 'string',
						'default' => 0,
					),
					'fancyId'    => array(
						'type'    => 'string',
						'default' => 0,
					),
					'color'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'buttonText' => array(
						'type'    => 'string',
						'default' => '',
					),
					'newWindow'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'editor_script'   => 'gdm-blocks-script',
				'render_callback' => array( $this, 'render_item_block' ),
			)
		);
	}

	function render_item_block( $atts ) {

		$itemId     = ! empty( $atts['itemId'] ) ? intval( $atts['itemId'] ) : 0;
		$fancyId    = ! empty( $atts['fancyId'] ) ? intval( $atts['fancyId'] ) : 0;
		$color      = ! empty( $atts['color'] ) ? $atts['color'] : '';
		$buttonText = ! empty( $atts['buttonText'] ) ? esc_attr( $atts['buttonText'] ) : GDM_get_download_form_with_termsncond( $itemId );
		$newWindow  = ! empty( $atts['newWindow'] ) ? 1 : 0;

		if ( empty( $itemId ) ) {
			return '<p>' . __( 'Select item to view', 'gluon-download-manager' ) . '</p>';
		}

		$sc_str = 'GDM_download id="%d" fancy="%d" button_text="%s" new_window="%d" color="%s"';
		$sc_str = sprintf( $sc_str, $itemId, $fancyId, $buttonText, $newWindow, $color );

		if ( ! empty( $atts['btnOnly'] ) ) {
			$sc_str .= ' button_only="1"';
		}

		return do_shortcode( '[' . $sc_str . ']' );
	}

	private function get_items_array() {
		$q        = get_posts(
			array(
				'post_type'      => 'GDM_downloads',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$itemsArr = array(
			array(
				'label' => __( '(Select item)', 'gluon-download-manager' ),
				'value' => 0,
			),
		);
		foreach ( $q as $post ) {
			$title      = html_entity_decode( $post->post_title );
			$itemsArr[] = array(
				'label' => esc_attr( $title ),
				'value' => $post->ID,
			);
		}
		wp_reset_postdata();
		return $itemsArr;
	}

}

new gdmBlocks();

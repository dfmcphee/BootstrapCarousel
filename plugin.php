<?php
	/*
	Plugin Name: WPBootstrapCarousel
	Plugin URI: http://dominicmcphee.com/wp-bootstrap-carousel
	Description: WPBootstrapCarousel is a plugin to display WordPress galleries using the Bootstrap Carousel.
	Version: 0.0.1
	Author: Dominic McPhee
	Author URI: Dominic McPhee
	License: GPL2
	*/
	
	$wp_responsive_slides_height = 500;
	$wp_responsive_slides_width = 1200;
	
	class WPBootstrapCarousel {
		/**
		 * Returns markup for carousel
		 */
		public static function get_gallery($id){
			$gallery = '';
			
			$gallery_post = get_post($id);
			
			if ($gallery_post) {
				$post_content = $gallery_post->post_content;
				
				preg_match('/\[gallery.*ids=.(.*).\]/', $post_content, $ids);
				$attachment_ids = explode(",", $ids[1]);
				
				$gallery .= '<div id="carousel-' . $id . '" class="carousel slide">';
				
				$gallery .= '<ol class="carousel-indicators">';

				$indicator_index = 0;
				foreach ($attachment_ids as $attachment_id) {
					$class = '';
					if ($indicator_index === 0) {
						$class = ' class="active"';
					}
					$gallery .= '<li data-target="#carousel-' . $id . '" data-slide-to=slide"';
					$gallery .= $indicator_index . '"' . $class . '></li>';
					$indicator_index++;
				}

				$gallery .= '</ol>';
				
				$gallery .= '<div class="carousel-inner">';

				$indicator_index = 0;
				foreach ($attachment_ids as $attachment_id) {
					$class = '';
					if ($indicator_index === 0) {
						$class = ' active';
					}
					$attachment = wp_get_attachment_image_src($attachment_id, 'wp-resp-slide');
					$attachment_info = get_post($attachment_id);
					$gallery .= '<div class="item' . $class . '">';
					$gallery .= '<img src="' . $attachment[0] . '" />';
					if ($attachment_info->post_excerpt) {
						$post_callout = get_post_meta($attachment_id, 'post_callout_link');

						if ($post_callout) {
							$link = get_permalink($post_callout);
							$excerpt = '<a href="' . $link . '">' . $attachment_info->post_excerpt . '</a>';
						} else {
							$excerpt = $attachment_info->post_excerpt;
						}
    					$gallery .= '<div class="carousel-caption">' . $excerpt . '</div>';
					}
					$gallery .= '</div>';
					$indicator_index++;
				}
				
				$gallery .= '</div>';

  				$gallery .= '<a class="left carousel-control" href="#carousel-' . $id . '" data-slide="prev">';
				$gallery .= '<span class="icon-prev"></span>';
				$gallery .= '</a>';
  				$gallery .= '<a class="right carousel-control" href="#carousel-' . $id . '" data-slide="next">';
    			$gallery .= '<span class="icon-next"></span>';
  				$gallery .= '</a>';
				
				$gallery .= '</div>';
			}
			
			return $gallery;
		}
		
		/**
		 * Shortcode to create Carousel
		 */
		public static function carousel_shortcode($atts, $content = null) {  
		    extract(shortcode_atts(array(
		    	'id' => null,
		        'width' => '1200',
		        'height' => '500'
		    ), $atts));

		    return WPBootstrapCarousel::get_gallery($id, $atts[""]);  
		}
		
		/**
		 * Creates the carousel post type
		 */
		public static function create_post_type(){
			register_post_type( 'carousel',
				array(
					'labels' => array(
						'name' => __( 'Carousels' ),
						'singular_name' => __( 'Carousel' )
					),
					'public' => true,
					'has_archive' => true,
				)
			);
		}
		
		/**
		 * Enqueue carousel js file
		 */
		public static function add_carousel_script() {
			wp_enqueue_script(
				'bootstrap-transition',
				plugins_url('/js/transition.js', __FILE__),
				array( 'jquery' )
			);
			wp_enqueue_script(
				'bootstrap-carousel',
				plugins_url('/js/carousel.js', __FILE__),
				array( 'jquery' )
			);
		}
		
		/**
		 * Add meta box to display carousel id
		 */
		public static function carousel_admin() {
		    add_meta_box( 'carousel_meta_box',
		        'Carousel ID',
		        array('WPBootstrapCarousel', 'display_carousel_meta_box'),
		        'carousel', 'normal', 'high'
		    );
		}

		/**
		 * Display carousel id meta box
		 */
		public static function display_carousel_meta_box( $movie_review ) {
			echo '<h3>' . get_the_ID() . '</h3>';	
		}

		/**
		 * Add Post Callout ID to media item
		 *
		 * @param $form_fields array, fields to include in attachment form
		 * @param $post object, attachment record in database
		 * @return $form_fields, modified form fields
		 */
		public static function post_callout_attachment_field($form_fields, $post) {
			$form_fields['post_callout_link'] = array(
				'label' => 'Post Callout ID',
				'input' => 'text',
				'value' => get_post_meta($post->ID, 'post_callout_link', true),
				'helps' => 'If provided, slide will link to post',
			);

			return $form_fields;
		}

		/**
		 * Save values of Post Callout ID in media uploader
		 *
		 * @param $post array, the post data for database
		 * @param $attachment array, attachment fields from $_POST form
		 * @return $post array, modified post data
		 */
		function save_post_callout_attachment_field($post, $attachment) {
			if (isset($attachment['post_callout_link'])) {
				update_post_meta($post['ID'], 'post_callout_link', $attachment['post_callout_link']);
			}

			return $post;
		}

	}

	// Add field for post callout id
	add_filter('attachment_fields_to_edit', array('WPBootstrapCarousel', 'post_callout_attachment_field'), 10, 2);
	
	// Save field for post callout id
	add_filter('attachment_fields_to_save', array('WPBootstrapCarousel', 'save_post_callout_attachment_field'), 10, 2);
	
	// Add image size for slides
	add_image_size( 'wp-resp-slide', $wp_responsive_slides_width, $wp_responsive_slides_height, true);
	
	// Register custom post type
	add_action( 'init', array('WPBootstrapCarousel', 'create_post_type') );
	
	// Register carousel javascript
	add_action('wp_enqueue_scripts', array('WPBootstrapCarousel', 'add_carousel_script'));
	
	// Register shortcode
	add_shortcode('carousel',  array('WPBootstrapCarousel', 'carousel_shortcode'));  
	
	// Register widget to display ID when editing
	add_action( 'admin_init', array('WPBootstrapCarousel','carousel_admin') );
?>
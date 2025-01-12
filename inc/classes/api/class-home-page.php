<?php

/**
 * Home_Page class.
 *
 * @package headless-cms
 */

namespace Headless_CMS\Features\Inc\Api;

use Headless_CMS\Features\Inc\Traits\Singleton;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Home_Page
 */
class Home_Page
{

	use Singleton;

	/**
	 * Plugin options
	 *
	 * @var object
	 */
	private $plugin_options;

	/**
	 * Route name
	 *
	 * @var string
	 */
	private $route = '/home';

	/**
	 * Construct method.
	 */
	protected function __construct()
	{

		$this->plugin_options = get_option('hcms_plugin_options');
		$this->setup_hooks();
	}

	/**
	 * To setup action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks()
	{
		/**
		 * Action
		 */
		add_action('rest_api_init', [$this, 'rest_posts_endpoints']);
	}

	/**
	 * Register posts endpoints.
	 */
	public function rest_posts_endpoints()
	{

		/**
		 * Handle Posts Request: GET Request
		 *
		 * This api gets the custom home page data for the site.
		 * The data will include:
		 * 1. Hero section data ( Title, description, button name )
		 * 2. Search section data ( Search placeholder text, three lates taxonomies, with given taxonomy name passed in query params of URL, defaults to 'category' )
		 * 3. Featured post data ( heading, 3 featured posts selected from plugin settings page )
		 * 4. Latest posts ( Heading and 3 latest posts, with given post type passed in query params of URL, defaults to 'post' )
		 *
		 * The 'post_type' here is a string e.g. 'post', The 'taxonomy' here is a string e.g. 'category'
		 *
		 * Example: http://example.com/wp-json/rae/v2/home?post_type=post&taxonomy=category
		 */
		register_rest_route(
			'rae/v1',
			$this->route,
			[
				'methods'  => 'GET',
				'callback' => [$this, 'rest_endpoint_handler'],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Get posts call back.
	 *
	 * Returns the menu items array of object on success
	 *
	 * @param WP_REST_Request $request request object.
	 *
	 * @return WP_Error|WP_REST_Response response object.
	 */
	public function rest_endpoint_handler(WP_REST_Request $request)
	{

		$response   = [];
		$parameters = $request->get_params();
		$post_type  = !empty($parameters['post_type']) ? sanitize_text_field($parameters['post_type']) : 'post';
		$taxonomy   = !empty($parameters['taxonomy']) ? sanitize_text_field($parameters['taxonomy']) : 'category';

		// Error Handling.
		$error = new WP_Error();

		$hero_section_data   = $this->get_hero_section();
		$search_section_data = $this->get_search_section($taxonomy);
		$featured_posts      = $this->get_featured_posts();
		$latest_posts        = $this->get_latest_posts($post_type);

		// If any menus found.
		if (!empty($hero_section_data) || !empty($search_section_data) || !empty($featured_posts) || !empty($latest_posts)) {

			$data = array(
				'wordpress_id'         => 220, // Use an id required for the GraphQL query.
				'heroSection'          => $hero_section_data,
				'searchSection'        => $search_section_data,
				'featuredPostsSection' => $featured_posts,
				'latestPostsSection'   => $latest_posts,
			);
			return new WP_REST_Response($data, 200);
		} else {

			// If the posts not found.
			$error->add(406, __('Data not found', 'rest-api-endpoints'));

			return $error;
		}
	}

	/**
	 * Get Hero Section data.
	 *
	 * @return array $hero_section_data Hero Section data
	 */
	public function get_hero_section()
	{

		if (empty($this->plugin_options)) {
			return [];
		}

		$hero_section_data = [
			'heroTitle'       => $this->plugin_options['hero_title'],
			'heroDescription' => $this->plugin_options['hero_description'],
			'heroBtnTxt'      => $this->plugin_options['hero_btn_text'],
			'heroImgURL'      => $this->plugin_options['hero_back_img'],
		];

		return $hero_section_data;
	}

	/**
	 * Get Search Section data.
	 *
	 * @param string $taxonomy Taxonomy.
	 *
	 * @return array $search_section_data Hero Section data.
	 */
	public function get_search_section($taxonomy)
	{

		// Get latest three categories.
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 3,
				'parent'     => '0',
			]
		);

		$terms_with_attach = $this->get_terms_with_attach($terms);

		$search_section_data = [
			'searchPlaceholderTxt' => $this->plugin_options['search_placeholder_text'],
			'searchBackURL'        => $this->plugin_options['search_back_img'],
			'terms'                => $terms_with_attach,
		];

		return $search_section_data;
	}

	/**
	 * Get terms with attachment image
	 *
	 * @param array $terms Terms.
	 */
	public function get_terms_with_attach($terms)
	{

		$terms_with_attach = [];

		if (!empty($terms)) {
			foreach ($terms as $term) {

				$attachment_id_data = get_term_meta($term->term_id, 'category-image-id');
				$attachment_id      = $attachment_id_data[0];

				$term_data = [
					'termId'   => $term->term_id,
					'name'     => $term->name,
					'slug'     => $term->slug,
					'taxonomy' => $term->taxonomy,
					'image'    => [
						'img_sizes'  => wp_get_attachment_image_sizes($attachment_id),
						'img_src'    => wp_get_attachment_image_src($attachment_id, 'full'),
						'img_srcset' => wp_get_attachment_image_srcset($attachment_id),
					],
				];

				array_push($terms_with_attach, $term_data);
			}
		}

		return $terms_with_attach;
	}

	/**
	 * Get featured Posts.
	 *
	 * @return array $featured_posts Featured Posts.
	 */
	public function get_featured_posts()
	{

		if (empty($this->plugin_options)) {
			return [];
		}

		$featured_post_ids = [
			intval($this->plugin_options['first_featured_post_id']),
			intval($this->plugin_options['second_featured_post_id']),
			intval($this->plugin_options['third_featured_post_id']),
		];

		$featured_posts = [];

		if (!empty($featured_post_ids) && is_array($featured_post_ids)) {
			foreach ($featured_post_ids as $post_ID) {

				$author_id     = get_post_field('post_author', $post_ID);
				$attachment_id = get_post_thumbnail_id($post_ID);

				$post_data                     = [];
				$post_data['id']               = $post_ID;
				$post_data['title']            = get_the_title($post_ID);
				$post_data['excerpt']          = get_the_excerpt($post_ID);
				$post_data['slug']             = get_post_field('post_name', $post_ID);
				$post_data['date']             = get_the_date('', $post_ID);
				$post_data['attachment_image'] = [
					'img_sizes'  => wp_get_attachment_image_sizes($attachment_id),
					'img_src'    => wp_get_attachment_image_src($attachment_id, 'full'),
					'img_srcset' => wp_get_attachment_image_srcset($attachment_id),
				];
				$post_data['meta']             = [
					'author_id'   => $author_id,
					'author_name' => get_the_author_meta('display_name', $author_id),
				];

				array_push($featured_posts, $post_data);
			}
		}

		return [
			'featuredPostHeading' => $this->plugin_options['featured_post_heading'],
			'featuredPosts'       => $featured_posts,
		];
	}

	/**
	 * Get latest posts
	 *
	 * @param string $post_type Post Type.
	 *
	 * @return array latest posts
	 */
	public function get_latest_posts($post_type)
	{

		$args = [
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => 3, // Get three posts.
			'fields'                 => 'ids',
			'orderby'                => 'date',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,

		];

		$result = new WP_Query($args);

		$latest_post_ids = $result->get_posts();

		$latest_posts = [];

		if (!empty($latest_post_ids) && is_array($latest_post_ids)) {
			foreach ($latest_post_ids as $post_ID) {

				$attachment_id = get_post_thumbnail_id($post_ID);

				$post_data                     = [];
				$post_data['id']               = $post_ID;
				$post_data['title']            = get_the_title($post_ID);
				$post_data['excerpt']          = get_the_excerpt($post_ID);
				$post_data['attachment_image'] = [
					'img_sizes'  => wp_get_attachment_image_sizes($attachment_id),
					'img_src'    => wp_get_attachment_image_src($attachment_id, 'full'),
					'img_srcset' => wp_get_attachment_image_srcset($attachment_id),
				];

				array_push($latest_posts, $post_data);
			}
		}

		return [
			'latestPostHeading' => !empty($this->plugin_options['latest_post_heading']) ? $this->plugin_options['latest_post_heading'] : '',
			'latestPosts'       => $latest_posts,
		];
	}
}

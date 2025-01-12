<?php

/**
 * Get_Post class.
 *
 * @package headless-cms
 */

namespace Headless_CMS\Features\Inc\Api;

use Headless_CMS\Features\Inc\Traits\Singleton;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_Post;

/**
 * Class Get_Post
 */
class Get_Post
{

	use Singleton;

	/**
	 * Route name
	 *
	 * @var string
	 */
	private $route = '/post';

	/**
	 * Post type
	 *
	 * @var string
	 */
	private $post_type = 'post';

	/**
	 * Construct method.
	 */
	protected function __construct()
	{
		$this->setup_hooks();
	}

	/**
	 * To setup action/filter.
	 *
	 * @return void
	 */
	protected function setup_hooks()
	{
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
		 * This endpoint takes 'post_id' or 'post_slug' in query params of the request.
		 * Returns the posts data object on success
		 * Also handles error by returning the relevant error.
		 *
		 * Example: http://example.com/wp-json/rae/v1/post?post_id=1
		 * http://example.com/wp-json/rae/v1/post?post_slug=hello-world
		 */
		register_rest_route(
			'rae/v1',
			$this->route,
			[
				'method'   => 'GET',
				'callback' => [$this, 'rest_endpoint_handler'],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Get posts call back.
	 *
	 * Returns the posts data object on success
	 *
	 * @param WP_REST_Request $request request object.
	 *
	 * @return WP_Error|WP_REST_Response response object.
	 */
	public function rest_endpoint_handler(WP_REST_Request $request)
	{
		$response   = [];
		$parameters = $request->get_params();
		$post_id    = !empty($parameters['post_id']) ? intval(sanitize_text_field($parameters['post_id'])) : '';
		$post_slug  = !empty($parameters['post_slug']) ? sanitize_text_field($parameters['post_slug']) : '';

		// Error Handling.
		$error = new WP_Error();

		// Get id from slug
		if (!empty($post_slug)) {
			$the_post = get_page_by_path($post_slug, OBJECT, 'post');
			$post_id = $the_post instanceof WP_Post ? $the_post->ID : $post_id;
		}

		$post_data = $this->get_required_post_data($post_id);

		// If posts found.
		if (!empty($post_data)) {

			$response['status']    = 200;
			$response['post_data'] = $post_data;
		} else {

			// If the posts not found.
			$error->add(406, __('Post not found', 'rest-api-endpoints'));

			return $error;
		}

		return new WP_REST_Response($response);
	}

	/**
	 * Construct a post data that contains, title, excerpt and featured image.
	 *
	 * @param {array} $post_ID post id.
	 *
	 * @return array
	 */
	public function get_required_post_data($post_ID)
	{

		$post_data = [];

		if (empty($post_ID) && !is_array($post_ID)) {
			return $post_data;
		}

		$author_id     = get_post_field('post_author', $post_ID);
		$attachment_id = get_post_thumbnail_id($post_ID);

		$post_data                     = [];
		$post_data['id']               = $post_ID;
		$post_data['title']            = get_the_title($post_ID);
		$post_data['excerpt']          = get_the_excerpt($post_ID);
		$post_data['date']             = get_the_date('', $post_ID);
		$post_data['slug']             = get_post_field('post_name', $post_ID);
		$post_data['permalink']        = get_the_permalink($post_ID);
		$post_data['content']          = get_post_field('post_content', $post_ID);
		$post_data['attachment_image'] = [
			'img_sizes'  => wp_get_attachment_image_sizes($attachment_id),
			'img_src'    => wp_get_attachment_image_src($attachment_id, 'full'),
			'img_srcset' => wp_get_attachment_image_srcset($attachment_id),
		];
		$post_data['categories']       = get_the_category($post_ID);
		$post_data['meta']             = [
			'author_id'   => $author_id,
			'author_name' => get_the_author_meta('display_name', $author_id),
			'author_url'  => get_author_posts_url($author_id),
		];


		return $post_data;
	}
}

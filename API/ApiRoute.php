<?php

namespace API;

use API\NewsFeed;
use API\Admin\AdminPanel;

class ApiRoute {

    function __construct() {
        $this->namespace = 'newsfeed/v1';
        $this->rest_base = 'posts';
        add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
    }

    /*
    * Register custom routes
    * @return
    */
    function registerRoutes() {
        register_rest_route( $this->namespace, $this->rest_base,
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'newsFeedPosts' ),
                'show_in_index'       => true,
        ) );
        register_rest_route( $this->namespace . '/' . $this->rest_base, '/(?P<id>[\d]+)',
            array(
                'methods'       => \WP_REST_Server::READABLE,
                'callback'      => array( $this, 'newsFeedPost' ),
                'show_in_index' => true,
                'args'          => array(
                    'context' => array(
                        'default' => 'view',
                    ),
                    'per_page' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ) );
    }

    /**
     * Get a collection of items from newsfeed posts
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    function newsFeedPosts( $request ) {
        $args = array(
            'post_type'      => NewsFeed::$postType,
            'posts_per_page' => -1,
            'post_status'    => 'publish' // change to "any" to view all posts
        );
        $postArray = array();
        $posts = get_posts( $args );
        
        foreach ( $posts as $key => $post ) {
            array_push( $postArray, array( htmlspecialchars( $post->post_title, ENT_QUOTES ), mysql_to_rfc3339( $post->post_date ) ) );
        }

        return new \WP_REST_Response( $postArray, 200 );
    }

    /**
     * Get a post from the request ID
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    function newsFeedPost( $request ) {
        $controller = new \WP_REST_Posts_Controller( NewsFeed::$postType );
        $post = $controller->get_item( $request );

        return new \WP_REST_Response( $post, 200 );
    }


}
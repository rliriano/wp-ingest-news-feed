<?php

namespace API;

use API\NewsFeed;
use API\XMLParcer;
use API\Admin\AdminPanel;

class FeedPostType {

    function __construct() {
        $this->XMLParcer = new XMLParcer();
        add_action( 'init', array( $this, 'registerPostType' ), 0 );

        add_filter( 'manage_edit-'. NewsFeed::$postType .'_columns', array( $this, 'registerCustomColumns' ) );
        add_action( 'manage_'. NewsFeed::$postType .'_posts_custom_column', array( $this, 'setCustomColumnContent' ) );
    }

    /**
	 * Register our custom feed Post Type
	 * @action init
	 */
    function registerPostType() {
        $postLabels = array(
            'name'               => 'News Feed',
            'singular_name'      => 'News Feed',
            'add_new'            => 'Add Feed',
            'add_new-item'       => 'Add Feed',
            'edit_item'          => 'Edit Feed',
            'new_item'           => 'New Feed',
            'view_item'          => 'View Feeds',
            'search_items'       => 'Search Feeds',
            'not_found'          => 'No Feed Found',
            'not_found_in_trash' => 'No Feeds Found in Trash',
            'parent_item_colon'  => ''
        );

        $postArgs = array(
            'labels'              => $postLabels,
            'public'              => true,
            'exclude_from_search' => true,
            'show_in_rest'        => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'capability_type'     => 'post',
            'hierarchical'        => true,
            'menu_icon'           => 'dashicons-rss',
            'menu_position'       => 5,
            'taxonomies'          => [ 'category', 'post_tag' ],
            'query_var'           => true,
            'has_archive'         => true,
            'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author' ],
            //'rewrite'             => [ 'slug' => '%feeds%' ],
        );
        register_post_type( NewsFeed::$postType, $postArgs );
    }


    /**
     * Output custom columns for Genre Post Type
     * @filter manage_edit-{post-type}_columns
     * @return columns
     */
    function registerCustomColumns( $columns ) {
        $columns = array(
            'cb'       => '<input type=\"checkbox\" />',
            'title'    => 'Title',
            'linkurl'  => 'Permalink',
            'ID'       => 'ID',
            'date'     => 'Date',
        );
        return $columns;
    }

    /**
     * Custom column content for Genre Post Type
     * @action manage_{post-type}_posts_custom_column
     */
    function setCustomColumnContent( $column ) {
        global $post;

        if ( 'ID' == $column ){
            echo $post->ID;
        } elseif ( 'linkurl' == $column ) {
            echo '<a href="'.get_the_permalink( $post->ID ).'"/ target="_blank">'.get_the_permalink( $post->ID ).'</a>';
        }
    }


    /*
    * Create post from data
    * @return results
    */
    public function createFeedPost() {
        $postData = array();
        $item = $this->XMLParcer->getXMLItems();
        $guidId = get_option( NewsFeed::$guidOption );
        $c = 0;

        XMLParcer::log( 'Running Hook - ' . NewsFeed::$cronHook );
        XMLParcer::log( 'Found ' . count( $item ) . ' total items' );

        if ( $item ) {
            XMLParcer::log( "Uploading Posts..." );

            for ( $i = 0; $i < count( $item ); $i++ ) {
                $postData['post_title']   = wp_strip_all_tags( $item[$i]->title );
                $postData['post_name']    = preg_replace('/[^a-zA-Z0-9\-]/', '-', strtolower( $item[$i]->title ) );
                $postData['post_content'] = !empty( $item[$i]->description ) ? wp_strip_all_tags( $item[$i]->description ) : '';
                $postData['post_date']    = date( 'Y-m-d H:i:s', strtotime( (string)$item[$i]->pubDate ) );
                $postData['post_type']    = NewsFeed::$postType;
                $postData['post_status']  = 'draft';

                $wpPost = get_page_by_title( wp_strip_all_tags( $item[$i]->title ), 'OBJECT', NewsFeed::$postType );
                
                if ( $wpPost ) {
                    XMLParcer::log( 'Post already exist, Skipping: ' . $wpPost->ID );
                    continue;
                }

                // Create the new post
                require_once ABSPATH . WPINC . '/pluggable.php';
                $newPostId = wp_insert_post( $postData, true );
                XMLParcer::log( "Post Uploaded - [$newPostId]" );
                
                if ( is_wp_error( $newPostId ) ) {
                    XMLParcer::log( 'ERROR: Could not insert post ' . $newPostId );
                    continue;
                }

                // Upload and set thumbnail to post
                $file = $this->XMLParcer->getXMLMediaContent( $item[$i] );
                self::thumbnailUpload( $file, $newPostId );

                $c++;
            }
            XMLParcer::log( "Finished...Uploaded $c post(s)" );
        } else {
            XMLParcer::log( "No Items..." );
        }
    }

    /**
    * Handles uploading a file and attaching it to a WordPress post
    * @param  int $post_id Post ID to upload the photo to
    * @param  object $file XML media object
    */
    protected function thumbnailUpload( $file, $postId ) {

        $imageurl = $file->thumbnailUrl;
        preg_match( '/[^\/]+.(jpg|jpeg|gif|png)/i', $imageurl, $matches );

        // remove extension from name
        $filename = preg_replace('/.(jpg|jpeg|gif|png)/', '', strtolower( sanitize_file_name( $matches[0] ) ) );
        // XMLParcer::log( 'IMAGE NAME: ' . $filename );

        //prepare upload image to WordPress Media Library
        $upload = wp_upload_bits( $matches[0] , null, file_get_contents( $imageurl, FILE_USE_INCLUDE_PATH ) );

        if ( empty( $upload['error'] ) ) {
            // check and return file type
            $imageFile = $upload['file'];
            $wpFileType = wp_check_filetype( $imageFile, null );
            $wpUploadDir = wp_upload_dir();

            // XMLParcer::log( 'UPLOADED IMAGE: ' . $upload['file'] );
            // XMLParcer::log( 'UPLOADED IMAGE URL: ' . $upload['url'] );
            // XMLParcer::log( 'UPLOADED IMAGE TYPE: ' . $upload['type'] );
            // XMLParcer::log( 'UPLOADED IMAGE ERROR: ' . $upload['error'] );
            // XMLParcer::log( 'UPLOADED IMAGE GUID: ' . $wpUploadDir['url'] . '/' . basename( strtolower( $matches[0] ) ) );

            // Attachment attributes for file
            $attachmentArgs = array(
                'guid'           => $wpUploadDir['url'] . '/' . basename( strtolower( $matches[0] ) ),
                'post_mime_type' => $wpFileType['type'],
                'post_title'     => sanitize_text_field( $file->title ),
                'post_excerpt'   => sanitize_text_field( $file->title ),
                'post_content'   => sanitize_text_field( $file->description ),
                'post_status'    => 'inherit'
            );
            
            // insert and return attachment id
            $attachmentId = wp_insert_attachment( $attachmentArgs, $imageFile, $postId );
            // XMLParcer::log( 'IMAGE ID: '.$attachmentId );

            // insert and return attachment metadata
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attachmentData = wp_generate_attachment_metadata( $attachmentId, $filename );
            wp_update_attachment_metadata( $attachmentId, $attachmentData );
            
            // finally, associate attachment id to post id
            $uploaded = set_post_thumbnail( $postId, $attachmentId );

            if ( $uploaded ) {
                XMLParcer::log( 'Uploaded image "'.$filename.'" to post "' . $postId . '"' );
            }
        } else {
            XMLParcer::log( 'ERROR: Could not upload image to Media Library ' . $upload['error'] );
        }
       
    }

}
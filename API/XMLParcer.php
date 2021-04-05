<?php

namespace API;

use API\NewsFeed;
use API\Admin\AdminPanel;

class XMLParcer {

    /*
    * Feed logger
    * @return
    */
    public static function log( $msg ) {
        date_default_timezone_set( 'America/New_York' );

        $path = NF_API_DIR . '/Log';
        $filename = $path . '/feed-log-'. date('m-d-Y') . '.log';

        if ( !file_exists( $path ) ) {
            mkdir( $path, 0777, true );
        }
        $msgData = $_SERVER['REMOTE_ADDR'] . ' - [' . date( 'm-d-Y h:i:s A', time() ) . '] ' . $msg . "\n";
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents( $filename, $msgData, FILE_APPEND );
    }

    /*
    * Make the API call
    * @return results
    */
    function getXML() {
        self::log( "\n" );
        self::log( "Getting XML..." );

        libxml_use_internal_errors( true );

        $url = get_option( AdminPanel::$tabs['setup'] );
        $xml = simplexml_load_file( $url['news-feed_feed_url'], 'SimpleXMLElement', LIBXML_NOCDATA );

        if ( $xml === false ) {
            $errors = libxml_get_errors();

            foreach ( $errors as $error ) {
                self::displayXMLError( $error, $xml );
            }
            libxml_clear_errors();
        }
        return $xml;
    }

    /*
    * Get all XML items convert to array for post data
    * @return postData
    */
    function getXMLItems() {
        $xml = self::getXML();

        if ( $xml->channel ) {
            $item = $xml->channel->item;
            $guidId = array();

            // Save guid ID
            for ( $i = 0; $i < count( $item ); $i++ ) {
                array_push( $guidId, (string)$item[$i]->guid );
            }

            if ( get_option( NewsFeed::$guidOption ) !== false ) {
                // The option already exists, so update it.
                update_option( NewsFeed::$guidOption, $guidId );
            } else {
                // The option hasn't been created yet, so add it with $autoload set to 'no'.
                add_option( NewsFeed::$guidOption, $guidId, null, 'no' );
            }
            
            return $item;
        }
    }

    /*
    * Get the contents from media:content
    * @return results
    */
    function getXMLMediaContent( $item ) {
        $content = $item->children('media', true)->content;

        $media = new \stdClass();
        $media->title = preg_replace('/[^a-zA-Z0-9]/', ' ', $content->title );
        $media->credit = (string)$content->credit;
        $media->description = htmlentities( $content->description );
        $media->thumbnailUrl = (string)$content->thumbnail->attributes()->url;

        return $media;
    }

    /*
    * Catch errors
    * @return results
    */
    function displayXMLError( $error, $xml ) {
        $return  = $xml[$error->line - 1] . "\n";
        $return .= str_repeat( '-', $error->column ) . "^\n";

        switch ( $error->level ) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }
        $return .= trim( $error->message ) .
                "\n  Line: $error->line" .
                "\n  Column: $error->column" .
                "\n";

        self::log( $return );
    }

}
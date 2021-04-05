<?php

namespace API;

use API\FeedPostType;
use API\ApiRoute;
use API\XMLParcer;
use API\Admin\AdminPanel;

class NewsFeed {

    static $prefix = 'news-feed_';
    static $settingsPage = 'news-feed-settings';
    static $setupSettingsOptions = 'feed_setup_options';
    static $postType = 'news-feed';

    static $cronHook = 'nbc_news_feed_cron_hook';
    static $guidOption = 'xml-guid-id';

    static $timeSchedules = array(
        array(
            'label' => 'Five Minutes',
            'value' => 'five_minutes',
            'interval' => '300',
        ),
        array(
            'label' => 'Ten Minutes',
            'value' => 'ten_minutes',
            'interval' => '600',
        ),
        array(
            'label' => 'Fifteen Minutes',
            'value' => 'fifteen_minutes',
            'interval' => '900',
        ),
        array(
            'label' => 'Twenty Minutes',
            'value' => 'twenty_minutes',
            'interval' => '1200',
        ),
    );

    function __construct() {
        $this->feedPostType = new FeedPostType();
        $this->apiRoute = new ApiRoute();
        $this->adminPanel = new AdminPanel();

        $this->setupOptions = get_option( AdminPanel::$tabs['setup'] );

        register_activation_hook( __FILE__, array( $this, 'nfActivate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'nfDeactivate' ) );

        add_filter( 'cron_schedules', array( $this, 'addCronInterval' ) );
        self::initScheduleEvent();

        add_action( self::$cronHook, array( $this, 'runScheduledFeed' ) );
        add_action( 'updated_option', array( $this, 'onOptionUpdate' ), 10, 3 );
        
    }

    /**
     * Clear scheduled hook on resaving settings
	 * @hook action - updated_option
    */
    function onOptionUpdate( $option_name, $old_value, $value ) {
        if ( $option_name === self::$setupSettingsOptions ) {
            XMLParcer::log( 'Clearing scheduled hook - ' . self::$cronHook );
            wp_clear_scheduled_hook( self::$cronHook );
        }
    }

    /**
     * Plugin Activation Hook
	 * @hook action - register_activation_hook
    */
	function nfActivate() {
        if ( ! current_user_can( 'activate_plugins' ) )
			return;
    }
  
    /**
     * Plugin Deactivation Hook
     * Remove options on deactivate
     * @hook action - register_deactivation_hook
    */
    function nfDeactivate() {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        
        wp_clear_scheduled_hook( self::$cronHook );
        delete_option( AdminPanel::$tabs['setup'] );
    }

    /**
	 * Custom plugin admin links
	 * @param  array $links Links to add to the plugin
	 * @return links
	 */
	function nfActionLinks( $links ) {
		$links[] = '<a href="'. esc_url( get_admin_url( null, 'admin.php?page=' . self::$settingsPage ) ) .'">Settings</a>';
		return $links;
    }

    
    /**
	 * Cron Intervals
	 * @param  array $scheduless
     * @hook action - cron_schedules
	 * @return schedules
	 */
    function addCronInterval( $schedules ) {    
        if ( !empty( $this->setupOptions['news-feed_time_delay'] ) ) {
            foreach( self::$timeSchedules as $sch ) {
                $schedules[$sch['value']] = array(
                    'interval' => $sch['interval'],
                    'display'  => esc_html__( 'Every ' . $sch['label'] ),
                );
            }
        }
        return $schedules;
    }

    /**
	 * Schedule Event
	 */
    function initScheduleEvent() {
        if ( !empty( $this->setupOptions['news-feed_time_delay'] ) ) {
            if ( !wp_next_scheduled( self::$cronHook ) ) {
                XMLParcer::log( 'Setting up event - ' . self::$cronHook );
                wp_schedule_event( time(), $this->setupOptions['news-feed_time_delay'], self::$cronHook );
            }
        }
    }

    /**
	 * Run scheduled event
	 * @return
	 */
    public function runScheduledFeed() {
        $this->feedPostType->createFeedPost();
    }

}
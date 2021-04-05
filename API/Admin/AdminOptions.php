<?php
namespace API\Admin;

use API\NewsFeed;
use API\Admin\AdminPanel;

class AdminOptions {

    public $nfOptionFields = array();

	public function __construct() {
		add_action( 'init', array( $this, 'nfAdminOptions' ) );
	}
	
	public function nfAdminOptions() {
		
		if ( ! ( current_user_can( 'administrator' ) || current_user_can( 'developer' )) )
			return;
		
		$this->nfOptionFields['setup_options'] = array(
			array(
				'label' => 'XML/RSS Feed URL',
				'desc'  => '',
				'id'    => NewsFeed::$prefix.'feed_url',
				'type'  => 'text',
				'placeholder' => 'http://'
			),
			array(
				'label' => 'Schedule Time Delay',
				'desc'  => 'Setup schedule time delay between each run <br/><i>(Will run immediately if time delay is changed and saved)</i>',
				'id'    => NewsFeed::$prefix.'time_delay',
				'type'  => 'select',
				'options' => NewsFeed::$timeSchedules
			),
			
		);
		
	}
	
}
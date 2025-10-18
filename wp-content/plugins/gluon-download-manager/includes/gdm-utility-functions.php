<?php

/**
 * Get (filtered) list of all download button colors.
 *
 * @return array Array of colors: color key => color name.
 */
function gdm_get_download_button_colors() {
	return apply_filters(
		'gdm_download_button_color_options',
		array(
			'green'    => __( 'Green', 'gluon-download-manager' ),
			'blue'     => __( 'Blue', 'gluon-download-manager' ),
			'purple'   => __( 'Purple', 'gluon-download-manager' ),
			'teal'     => __( 'Teal', 'gluon-download-manager' ),
			'darkblue' => __( 'Dark Blue', 'gluon-download-manager' ),
			'black'    => __( 'Black', 'gluon-download-manager' ),
			'grey'     => __( 'Grey', 'gluon-download-manager' ),
			'pink'     => __( 'Pink', 'gluon-download-manager' ),
			'orange'   => __( 'Orange', 'gluon-download-manager' ),
			'white'    => __( 'White', 'gluon-download-manager' ),
		)
	);
}

function gdm_get_download_count_for_post( $id ) {
	// First try to get cached count from post meta
	$cached_count = get_post_meta( $id, 'gdm_download_count', true );
	
	if ( $cached_count !== '' && $cached_count !== false ) {
		// Return cached count (already includes offset)
		return intval( $cached_count );
	}
	
	// Fallback: Count from database (for backward compatibility)
	global $wpdb;
	$table = $wpdb->prefix . 'gdm_downloads';
	$db_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table . ' WHERE post_id=%d', $id ) );
	
	// Check post meta to see if we need to offset the count before displaying to viewers
	$get_offset = get_post_meta( $id, 'gdm_count_offset', true );
	if ( $get_offset && $get_offset != '' ) {
		$db_count = $db_count + intval( $get_offset );
	}
	
	// Cache the count for next time
	update_post_meta( $id, 'gdm_download_count', $db_count );
	
	return intval( $db_count );
}

/**
 * Counts all total downloads including offset count.
 *
 * @return number
 */
function gdm_get_download_count_for_all_posts() {
    global $wpdb;

	// Get total count from logs table using COUNT(*)
    $table = $wpdb->prefix . 'gdm_downloads';
	$db_count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $table );

	// Get all download post IDs efficiently
    $post_ids = $wpdb->get_col( 
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_status='publish'",
			'gdm_downloads'
		)
	);

    // Add offset counts from post meta
	if ( ! empty( $post_ids ) ) {
		foreach ( $post_ids as $post_id ) {
			$get_offset = get_post_meta( $post_id, 'gdm_count_offset', true );
			if ( $get_offset && $get_offset != '' ) {
				$db_count = $db_count + intval( $get_offset );
			}
		}
	}
	
    return intval( $db_count );
}

function gdm_get_password_entry_form( $id, $args = array(), $class = '' ) {
	$action_url = WP_GLUON_DL_MANAGER_SITE_HOME_URL . '/?gdm_process_download=1&download_id=' . $id;

	//Get the download button text
	$button_text = isset( $args['button_text'] ) ? $args['button_text'] : '';
	if ( empty( $button_text ) ) {//Use the default text for the button
		$button_text_string = gdm_get_default_download_button_text( $id );
	} else { //Use the custom text
		$button_text_string = $button_text;
	}

	$uuid = uniqid( 'gdm-pass-' );

	$data = '';

	//Enter password label
	$enter_password_label = __( 'Enter Password to Download:', 'gluon-download-manager' );
	$enter_password_label = apply_filters( 'gdm_enter_password_to_download_label', $enter_password_label );
	$data                .= '<span class="gdm_enter_password_label_text">' . $enter_password_label . '</span>';

	//Check if new window is enabled
	$new_window    = get_post_meta( $id, 'gdm_item_new_window', true );
	$window_target = empty( $new_window ) ? '' : ' target="_blank"';
	$window_target = apply_filters('gdm_download_window_target', $window_target);

	//Form code
	$data .= '<form action="' . $action_url . '" method="post" id="' . $uuid . '" class="gdm-download-form"' . $window_target . '>';
	$data .= '<input type="password" name="pass_text" class="gdm_pass_text" value="" /> ';

	$data .= gdm_get_download_with_recaptcha();

	//Check if Terms & Condition enabled
	$data .= gdm_get_checkbox_for_termsncond();

	$data .= '<span class="gdm-download-button">';
	$data .= '<a href="#" name="gdm_dl_pass_submit" class="pass_sumbit gdm_pass_protected_download gdm_download_with_condition ' . esc_attr($class) . '">' . $button_text_string . '</a>';
	$data .= '</span>';
	$data .= '<input type="hidden" name="download_id" value="' . $id . '" />';
	$data .= '</form>';
	return $data;
}

/**
 * Get remote IP address.
 *
 * @link http://stackoverflow.com/questions/1634782/what-is-the-most-accurate-way-to-retrieve-a-users-correct-ip-address-in-php
 *
 * @param bool $ignore_private_and_reserved Ignore IPs that fall into private or reserved IP ranges.
 * @return mixed IP address as a string or null, if remote IP address cannot be determined (or is ignored).
 */
function gdm_get_ip_address( $ignore_private_and_reserved = false ) {
	$flags = $ignore_private_and_reserved ? ( FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) : 0;
	$header_order = array(
		'HTTP_X_REAL_IP', //Nginx/FastCGI
		'HTTP_X_FORWARDED_FOR', //Most proxies
		'HTTP_CLIENT_IP',
		'HTTP_X_FORWARDED', 
		'HTTP_X_CLUSTER_CLIENT_IP', 
		'HTTP_FORWARDED_FOR', 
		'HTTP_FORWARDED', 
		'REMOTE_ADDR' //Fallback (might be proxy IP)
	);
	//Trigger the filter hook to allow other plugins to modify the header order.
	$header_order = apply_filters('gdm_ip_address_header_order', $header_order);

	//Loop through the headers and check for a valid IP address
	foreach ( $header_order as $key ) {
		if ( array_key_exists( $key, $_SERVER ) === true ) {
			//X-Forwarded-For can contain multiple IPs, take the first one.
			foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
				$ip = trim( $ip ); // just to be safe

				if ( filter_var( $ip, FILTER_VALIDATE_IP, $flags ) !== false ) {
					//Filter hook to allow modification of the detected IP address.
					$ip = apply_filters('gdm_get_ip_address', $ip);
					return $ip;
				}
			}
		}
	}
	//No valid IP found. Filter hook.
	$ip = apply_filters('gdm_get_ip_address', '');
	return $ip;
}

/**
 * Get location information (country or other info) for given IP address.
 *
 * @param string $ip
 * @param string $purpose
 * @return mixed
 */
function gdm_ip_info( $ip, $purpose = 'location' ) {

	$continents = array(
		'AF' => 'Africa',
		'AN' => 'Antarctica',
		'AS' => 'Asia',
		'EU' => 'Europe',
		'OC' => 'Australia (Oceania)',
		'NA' => 'North America',
		'SA' => 'South America',
	);

	return gdm_get_ip_info_by_ipwhois($ip, $purpose, $continents );
//	return gdm_get_ip_info_by_geoplugin($ip, $purpose, $continents );

}

/**
 * @param String $ip The visitors IP address.
 * @param String $purpose Name of the data to receive.
 * @param Array  $continents Available continents.
 *
 * @return array|string|null*
 */
function gdm_get_ip_info_by_ipwhois( $ip, $purpose, $continents ) {
	$ipdat = @json_decode( wp_remote_retrieve_body( wp_remote_get( 'http://ipwhois.app/json/' . $ip ) ) );

	if ( @strlen( trim( $ipdat->country_code ) ) === 2 ) {
		switch ( $purpose ) {
			case 'location':
				return array(
					'city'           => @$ipdat->city,
					'state'          => @$ipdat->region,
					'country'        => @$ipdat->country,
					'country_code'   => @$ipdat->country_code,
					'continent'      => @$continents[ strtoupper( $ipdat->continent_code ) ],
					'continent_code' => @$ipdat->continent_code,
				);
			case 'address':
				$address = array( $ipdat->country );
				if ( @strlen( $ipdat->region ) >= 1 ) {
					$address[] = $ipdat->region;
				}
				if ( @strlen( $ipdat->city ) >= 1 ) {
					$address[] = $ipdat->city;
				}

				return implode( ', ', array_reverse( $address ) );
			case 'city':
				return @$ipdat->city;
			case 'state':
				return @$ipdat->region;
			case 'region':
				return @$ipdat->region;
			case 'country':
				return @$ipdat->country;
			case 'countrycode':
				return @$ipdat->country_code;
		}
	}

	// Either no info found or invalid $purpose.
	return null;
}

/**
 * @param String $ip The visitors IP address.
 * @param String $purpose Name of the data to receive.
 * @param Array  $continents Available continents.
 *
 * @return array|string|null*
 */
function gdm_get_ip_info_by_geoplugin( $ip, $purpose, $continents ) {
	$ipdat = @json_decode( wp_remote_retrieve_body( wp_remote_get( 'http://www.geoplugin.net/json.gp?ip=' . $ip ) ) );

	if ( @strlen( trim( $ipdat->geoplugin_countryCode ) ) === 2 ) {
		switch ( $purpose ) {
			case 'location':
				return array(
					'city'           => @$ipdat->geoplugin_city,
					'state'          => @$ipdat->geoplugin_regionName,
					'country'        => @$ipdat->geoplugin_countryName,
					'country_code'   => @$ipdat->geoplugin_countryCode,
					'continent'      => @$continents[ strtoupper( $ipdat->geoplugin_continentCode ) ],
					'continent_code' => @$ipdat->geoplugin_continentCode,
				);
			case 'address':
				$address = array( $ipdat->geoplugin_countryName );
				if ( @strlen( $ipdat->geoplugin_regionName ) >= 1 ) {
					$address[] = $ipdat->geoplugin_regionName;
				}
				if ( @strlen( $ipdat->geoplugin_city ) >= 1 ) {
					$address[] = $ipdat->geoplugin_city;
				}
				return implode( ', ', array_reverse( $address ) );
			case 'city':
				return @$ipdat->geoplugin_city;
			case 'state':
				return @$ipdat->geoplugin_regionName;
			case 'region':
				return @$ipdat->geoplugin_regionName;
			case 'country':
				return @$ipdat->geoplugin_countryName;
			case 'countrycode':
				return @$ipdat->geoplugin_countryCode;
		}
	}

	// Either no info found or invalid $purpose.
	return null;
}

/*
 * Checks if the string exists in the array key value of the provided array. If it doesn't exist, it returns the first key element from the valid values.
 */

function gdm_sanitize_value_by_array( $to_check, $valid_values ) {
	$keys = array_keys( $valid_values );
	$keys = array_map( 'strtolower', $keys );
	if ( in_array( $to_check, $keys ) ) {
		return $to_check;
	}
	return reset( $keys ); //Return the first element from the valid values
}

function gdm_get_logged_in_user() {
	$visitor_name = false;

	if ( is_user_logged_in() ) {  // Get WP user name (if logged in)
		$current_user = wp_get_current_user();
		$visitor_name = $current_user->user_login;
	}

	//WP eMember plugin integration
	if ( class_exists( 'Emember_Auth' ) ) {
		//WP eMember plugin is installed.
		$emember_auth = Emember_Auth::getInstance();
		$username     = $emember_auth->getUserInfo( 'user_name' );
		if ( ! empty( $username ) ) {//Member is logged in.
			$visitor_name = $username; //Override the visitor name to emember username.
		}
	}

	$visitor_name = apply_filters( 'gdm_get_logged_in_user_name', $visitor_name );

	return $visitor_name;
}

// Checks if current visitor is a bot
function gdm_visitor_is_bot() {
	$bots = array( 'archiver', 'baiduspider', 'bingbot', 'binlar', 'casper', 'checkprivacy', 'clshttp', 'cmsworldmap', 'comodo', 'curl', 'diavol', 'dotbot', 'DuckDuckBot', 'Exabot', 'email', 'extract', 'facebookexternalhit', 'feedfinder', 'flicky', 'googlebot', 'grab', 'harvest', 'httrack', 'ia_archiver', 'jakarta', 'kmccrew', 'libwww', 'loader', 'MJ12bot', 'miner', 'msnbot', 'nikto', 'nutch', 'planetwork', 'purebot', 'pycurl', 'python', 'scan', 'skygrid', 'slurp', 'sucker', 'turnit', 'vikspider', 'wget', 'winhttp', 'yandex', 'yandexbot', 'yahoo', 'youda', 'zmeu', 'zune', 'Sidetrade', 'AhrefsBot', 'Amazonbot' );

	$isBot = false;

	$user_agent = wp_kses_data( $_SERVER['HTTP_USER_AGENT'] );

	foreach ( $bots as $bot ) {
		if ( stripos( $user_agent, $bot ) !== false ) {
			$isBot = true;
		}
	}

	if ( empty( $user_agent ) || $user_agent == ' ' ) {
		$isBot = true;
	}

	//This filter can be used to override what you consider bot via your own custom function. You can read the user-agent value from the server var.
	$isBot = apply_filters( 'gdm_visitor_is_bot', $isBot );

	return $isBot;
}

function gdm_get_download_form_with_recaptcha( $id, $args = array(), $class = '' ) {
	$action_url = WP_GLUON_DL_MANAGER_SITE_HOME_URL . '/?gdm_process_download=1&download_id=' . $id;

	//Get the download button text
	$button_text = isset( $args['button_text'] ) ? $args['button_text'] : '';
	if ( empty( $button_text ) ) {//Use the default text for the button
		$button_text_string = gdm_get_default_download_button_text( $id );
	} else { //Use the custom text
		$button_text_string = $button_text;
	}

	$main_advanced_opts = get_option( 'gdm_advanced_options' );

	$new_window    = get_post_meta( $id, 'gdm_item_new_window', true );
	$window_target = empty( $new_window ) ? '' : ' target="_blank"';
	$window_target = apply_filters('gdm_download_window_target', $window_target);

	$data = '<form action="' . $action_url . '" method="post" class="gdm-g-recaptcha-form gdm-download-form"' . esc_attr($window_target) . '>';

	$data .= '<div class="gdm-recaptcha-button">';

    if (gdm_is_recaptcha_v3_enabled()){
	    $data .= gdm_get_recaptcha_v3_html();
    } else {
		$data .= '<div class="g-recaptcha gdm-g-recaptcha"></div>';
    }

	//Check if Terms & Condition enabled
	$data .= gdm_get_checkbox_for_termsncond();

	$data .= '<a href="#" class="' . esc_attr($class) . ' gdm_download_with_condition">' . $button_text_string . '</a>';
	$data .= '</div>';
	$data .= '<input type="hidden" name="download_id" value="' . $id . '" />';
	$data .= '</form>';
	return $data;
}

function gdm_get_download_with_recaptcha() {
	if ( gdm_is_recaptcha_v3_enabled() ) {
        return gdm_get_recaptcha_v3_html();
	} else if (gdm_is_recaptcha_v2_enabled()) {
		return '<div class="g-recaptcha gdm-g-recaptcha"></div>';
    }
	return '';
}

function gdm_get_checkbox_for_termsncond() {
	$main_advanced_opts = get_option( 'gdm_advanced_options' );
	$termscond_enable   = isset( $main_advanced_opts['termscond_enable'] ) ? true : false;
	if ( $termscond_enable ) {
		$data  = '<div class="gdm-termscond-checkbox">';
		$data .= '<input type="checkbox" class="agree_termscond" value="1"/> ' . __( 'I agree to the ', 'gluon-download-manager' ) . '<a href="' . $main_advanced_opts['termscond_url'] . '" target="_blank">' . __( 'terms and conditions', 'gluon-download-manager' ) . '</a>';
		$data .= '</div>';
		return $data;
	}
	return '';
}

function gdm_get_download_form_with_termsncond( $id, $args = array(), $class = '' ) {
	$action_url = WP_GLUON_DL_MANAGER_SITE_HOME_URL . '/?gdm_process_download=1&download_id=' . $id;

	//Get the download button text
	$button_text = isset( $args['button_text'] ) ? $args['button_text'] : '';
	if ( empty( $button_text ) ) {//Use the default text for the button
		$button_text_string = gdm_get_default_download_button_text( $id );
	} else { //Use the custom text
		$button_text_string = $button_text;
	}

	$main_advanced_opts = get_option( 'gdm_advanced_options' );
	$termscond_enable   = isset( $main_advanced_opts['termscond_enable'] ) ? true : false;

	$new_window    = get_post_meta( $id, 'gdm_item_new_window', true );
	$window_target = empty( $new_window ) ? '' : ' target="_blank"';
	$window_target = apply_filters('gdm_download_window_target', $window_target);

	$data  = '<form action="' . $action_url . '" method="post" class="gdm-download-form"' . $window_target . '>';
	$data .= gdm_get_checkbox_for_termsncond();
	$data .= '<div class="gdm-termscond-button">';
	$data .= '<a href="#" class="' . esc_attr($class) . ' gdm_download_with_condition">' . $button_text_string . '</a>';
	$data .= '</div>';
	$data .= '<input type="hidden" name="download_id" value="' . $id . '" />';
	$data .= '</form>';
	return $data;
}

function gdm_get_default_download_button_text( $download_id ) {
	$default_text = __( 'Download Now!', 'gluon-download-manager' );
	$meta_text    = get_post_meta( $download_id, 'gdm_download_button_text', true );

	$button_text = ! empty( $meta_text ) ? $meta_text : $default_text;

	//Allow other plugins to filter the button text
	$button_text = apply_filters( 'gdm_download_button_text_filter', $button_text, $download_id );
	return $button_text;
}

/*
 * Use this function to read the current page's URL
 */
function gdm_get_current_page_url() {
	$page_url = 'http';

	if ( isset( $_SERVER['SCRIPT_URI'] ) && ! empty( $_SERVER['SCRIPT_URI'] ) ) {
		$page_url = $_SERVER['SCRIPT_URI'];
		$page_url = apply_filters( 'gdm_get_current_page_url', $page_url );
		return $page_url;
	}

	if ( isset( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTPS'] == 'on' ) ) {
		$page_url .= 's';
	}
	$page_url .= '://';
	if ( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) ) {
		$page_url .= ltrim( $_SERVER['SERVER_NAME'], '.*' ) . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
	} else {
		$page_url .= ltrim( $_SERVER['SERVER_NAME'], '.*' ) . $_SERVER['REQUEST_URI'];
	}

	$page_url = apply_filters( 'gdm_get_current_page_url', $page_url );
	return $page_url;
}

/*
 * Use this function to redirect to a URL
 */
function gdm_redirect_to_url( $url, $delay = '0', $exit = '1' ) {
	$url = apply_filters( 'gdm_before_redirect_to_url', $url );
	if ( empty( $url ) ) {
		echo '<strong>';
		_e( 'Error! The URL value is empty. Please specify a correct URL value to redirect to!', 'gluon-download-manager' );
		echo '</strong>';
		exit;
	}
	if ( ! headers_sent() ) {
		header( 'Location: ' . $url );
	} else {
		echo '<meta http-equiv="refresh" content="' . esc_attr( $delay ) . ';url=' . esc_url( $url ) . '" />';
	}
	if ( $exit == '1' ) {//exit
		exit;
	}
}

/*
 * Utility function to insert a download record into the logs DB table. Used by addons sometimes.
 */
function gdm_insert_download_to_logs_table( $download_id ) {
	global $wpdb;

	if ( ! $download_id ) {
		gdm_Debug::log( 'Error! insert to logs function called with incorrect download item id.', false );
		return;
	}

	$main_option = get_option( 'gdm_downloads_options' );

	$download_title = get_the_title( $download_id );
	$download_link  = get_post_meta( $download_id, 'gdm_upload', true );

	$ipaddress = '';
	//Check if do not capture IP is enabled.
	if ( ! isset( $main_option['admin_do_not_capture_ip'] ) ) {
			$ipaddress = gdm_get_ip_address();
	}

	$user_agent = '';
	//Check if do not capture User Agent is enabled.
	if ( ! isset( $main_option['admin_do_not_capture_user_agent'] ) ) {
			//Get the user agent data. The get_browser() function doesn't work on many servers. So use the HTTP var.
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
				$user_agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] );
		}
	}

	$referrer_url = '';
	//Check if do not capture Referer URL is enabled.
	if ( ! isset( $main_option['admin_do_not_capture_referrer_url'] ) ) {
			//Get the user agent data. The get_browser() function doesn't work on many servers. So use the HTTP var.
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$referrer_url = sanitize_text_field( $_SERVER['HTTP_REFERER'] );
		}
	}

	$date_time       = current_time( 'mysql' );
	$visitor_country = ! empty( $ipaddress ) ? gdm_ip_info( $ipaddress, 'country' ) : '';

	$visitor_name = gdm_get_logged_in_user();
	$visitor_name = ( $visitor_name === false ) ? __( 'Not Logged In', 'gluon-download-manager' ) : $visitor_name;

	// Get option for global disabling of download logging
	$no_logs = isset( $main_option['admin_no_logs'] );

	// Get optoin for logging only unique IPs
	$unique_ips = isset( $main_option['admin_log_unique'] );

	// Get post meta for individual disabling of download logging
	$get_meta             = get_post_meta( $download_id, 'gdm_item_no_log', true );
	$item_logging_checked = isset( $get_meta ) && $get_meta === 'on' ? 'on' : 'off';

	$dl_logging_needed = true;

	// Check if download logs have been disabled (globally or per download item)
	if ( $no_logs === true || $item_logging_checked === 'on' ) {
			$dl_logging_needed = false;
	}

	// Check if we are only logging unique ips
	if ( $unique_ips === true ) {
			$check_ip = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'gdm_downloads WHERE post_id="' . $download_id . '" AND visitor_ip = "' . $ipaddress . '"' );

			//This IP is already logged for this download item. No need to log it again.
		if ( $check_ip ) {
				$dl_logging_needed = false;
		}
	}

	// Check if "Do Not Count Downloads from Bots" setting is enabled
	if ( isset( $main_option['admin_dont_log_bots'] ) ) {
			//it is. Now let's check if visitor is a bot
		if ( gdm_visitor_is_bot() ) {
				//visitor is a bot. We neither log nor count this download
				$dl_logging_needed = false;
		}
	}

	// Determine if we should count this download (separate from detailed logging)
	$should_count_download = true;
	
	// Don't count if it's a bot and bot counting is disabled
	if ( isset( $main_option['admin_dont_log_bots'] ) && gdm_visitor_is_bot() ) {
		$should_count_download = false;
	}
	
	// Don't count if logging unique IPs and this IP has already downloaded
	if ( $unique_ips === true ) {
		$check_ip = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'gdm_downloads WHERE post_id="' . $download_id . '" AND visitor_ip = "' . $ipaddress . '"' );
		if ( $check_ip ) {
			// This IP already downloaded this item
			$should_count_download = false;
		}
	}

	// Increment cached download count (even if detailed logging is disabled)
	if ( $should_count_download ) {
		$current_count = get_post_meta( $download_id, 'gdm_download_count', true );
		$table = $wpdb->prefix . 'gdm_downloads';
		if ( $current_count === '' || $current_count === false ) {
			// No cached count exists, initialize it
			if ( $dl_logging_needed ) {
				// If logging is enabled, count from DB
				$db_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table . ' WHERE post_id=%d', $download_id ) );
			} else {
				// If logging is disabled, start from 0
				$db_count = 0;
			}
			$get_offset = get_post_meta( $download_id, 'gdm_count_offset', true );
			if ( $get_offset && $get_offset != '' ) {
				$db_count = $db_count + intval( $get_offset );
			}
			// Add 1 for this download
			update_post_meta( $download_id, 'gdm_download_count', $db_count + 1 );
		} else {
			// Increment existing cached count
			update_post_meta( $download_id, 'gdm_download_count', intval( $current_count ) + 1 );
		}
	}

	if ( $dl_logging_needed ) {
			// We need to log this download.
			$table = $wpdb->prefix . 'gdm_downloads';
			$data  = array(
				'post_id'         => $download_id,
				'post_title'      => $download_title,
				'file_url'        => $download_link,
				'visitor_ip'      => $ipaddress,
				'date_time'       => $date_time,
				'visitor_country' => $visitor_country,
				'visitor_name'    => $visitor_name,
				'user_agent'      => $user_agent,
				'referrer_url'    => $referrer_url,
			);

			$data         = array_filter( $data ); //Remove any null values.
			$insert_table = $wpdb->insert( $table, $data );

			if ( $insert_table ) {
				//Download request was logged successfully
				gdm_Debug::log( 'Download has been logged in the logs table for download ID: ' . $download_id );
			} else {
				//Failed to log the download request
				gdm_Debug::log( 'Error! Failed to log the download request in the database table.', false );
			}
	}
}

function gdm_sanitize_text( $text ) {
	$text = htmlspecialchars( $text );
	$text = strip_tags( $text );
	$text = sanitize_text_field( $text );
	$text = esc_attr( $text );
	return $text;
}

/*
* Useful for using with wp_kses() function.
*/
function gdm_sanitize_allowed_tags() {
	$my_allowed = wp_kses_allowed_html( 'post' );

	// form fields - input
	$my_allowed['input'] = array(
			'class' => array(),
			'id'    => array(),
			'name'  => array(),
			'value' => array(),
			'type'  => array(),
			'step' => array(),
			'min' => array(),
			'checked' => array(),
			'size' => array(),
			'readonly' => array(),
			'style' => array(),
			'placeholder' => array(),
			'required' => array(),
	);
	// select
	$my_allowed['select'] = array(
			'class'  => array(),
			'id'     => array(),
			'name'   => array(),
			'value'  => array(),
			'type'   => array(),
			'placeholder' => array(),
			'required' => array(),
	);
	// select options
	$my_allowed['option'] = array(
			'selected' => array(),
			'value' => array(),
	);
	// button
	$my_allowed['button'] = array(
			'type' => array(),
			'class' => array(),
			'id' => array(),
			'style' => array(),
	);
	// style
	$my_allowed['style'] = array(
			'types' => array(),
	);

	return $my_allowed;
}

/*
* Useful for using with wp_kses() function.
*/
function gdm_sanitize_allowed_tags_expanded() {
	$my_allowed = gdm_sanitize_allowed_tags();

	//Expanded allowed button tags
	if( isset( $my_allowed['input'] ) && is_array( $my_allowed['input'] ) ){
		$input_extra = array(
			'onclick' => array(),
		);
		$my_allowed['input'] = array_merge( $my_allowed['input'] , $input_extra);
	}

	// iframe
	$my_allowed['iframe'] = array(
			'src'             => array(),
			'height'          => array(),
			'width'           => array(),
			'frameborder'     => array(),
			'allowfullscreen' => array(),
	);

	// allow for some inline jquery
	$my_allowed['script'] = array();

	return $my_allowed;
}

/**
 * Retrieves the download button text for a download item.
 * If download is provided, get the custom download button text if available. Else return default text.
 * 
 * @param int|null $download_id The download id to fetch the custom button text if have any.
 * 
 * @return string Download button text.
 */
function gdm_get_dl_button_text($download_id = null){
	$default_button_text = __( 'Download Now!', 'gluon-download-manager' );
	if (empty($download_id)) {
		return $default_button_text;
	}

	$custom_button_text = sanitize_text_field(get_post_meta($download_id, 'gdm_download_button_text', true));
	
	return !empty($custom_button_text) ? $custom_button_text : $default_button_text;
}

function gdm_get_standard_download_url_from_id( $download_id ) {
	$homepage = rtrim( get_bloginfo( 'url' ), '/' ); // Remove the trailing slash (if there is one)
	$download_url = $homepage . '/?gdm_process_download=1&download_id=' . $download_id;
	return $download_url;
}

/**
 * Get the capability settings for SDM admin sections.
 * Default 'manage_options', which is a admin capability. 
 * 
 * @return string User capability to get access to SDM admin. 
 */
function get_gdm_admin_access_permission(){
	$main_opts = get_option( 'gdm_downloads_options' );
	$admin_dashboard_access_permission = isset($main_opts['admin-dashboard-access-permission']) && !empty($main_opts['admin-dashboard-access-permission']) ? sanitize_text_field($main_opts['admin-dashboard-access-permission']) : 'manage_options';
    $admin_dashboard_access_permission = apply_filters("gdm_dashboard_access_role", $admin_dashboard_access_permission);
	return $admin_dashboard_access_permission;
}

/*
 * Check if the current page is an admin page of the SDM plugin.
 */
function is_gdm_admin_page() {
	if( !is_admin() ){
		//Not an admin page
		return false;
	}

	//Check if this is an admin page of the SDM plugin
	if ( isset( $_GET['post_type'] ) && ( stripos( $_GET['post_type'], 'gdm_downloads' ) !== false ) ) {
		//This is an admin page (admin menu page) of the SDM plugin
		return true;
	}
	return false;
}

function gdm_is_any_recaptcha_enabled(){
	return gdm_is_recaptcha_v2_enabled() || gdm_is_recaptcha_v3_enabled();
}

function gdm_is_recaptcha_v2_enabled(){
	$advanced_settings = get_option( 'gdm_advanced_options' );

	return isset( $advanced_settings[ 'recaptcha_enable' ] ) && $advanced_settings[ 'recaptcha_enable' ] == 'on';
}

function gdm_is_recaptcha_v3_enabled(){
	$advanced_settings = get_option( 'gdm_advanced_options' );

	return isset( $advanced_settings[ 'recaptcha_v3_enable' ] ) && $advanced_settings[ 'recaptcha_v3_enable' ] == 'on';
}

function gdm_get_recaptcha_v3_html(){
	wp_enqueue_script('gdm-recaptcha-v3-scripts-lib');

	// This input field programmatically stores captcha token using js to send the token to the server with form submission.
    return '<input type="hidden" class="gdm-g-recaptcha-v3-response" name="g-recaptcha-response"/>';
}

function gdm_dl_request_intermediate_page($content) {
	wp_enqueue_script( 'gdm-intermediate-page-scripts', WP_GLUON_DL_MANAGER_URL . '/js/gdm_intermediate_page.js' , array(), WP_GLUON_DL_MANAGER_VERSION);

    // The redirect url when leaving this intermediate page.
    $download_id = isset($_REQUEST['download_id']) ? sanitize_text_field($_REQUEST['download_id']) : '';
	$redirect_url = apply_filters('gdm_redirect_url_from_intermediate_page', '', $download_id);
	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>

	<main class="gdm_dl_request_intermediate_page_content">
		<?php echo wp_kses_post($content) ?>

        <?php // The following renders after captcha verification successful and download has started. ?>
        <div id="gdm_after_captcha_verification_content" class="hidden">
            <p><?php _e('CAPTCHA verification successful. Once the download is complete, click the button below to return.', 'gluon-download-manager') ?></p>
            <button id="gdm_intermediate_page_manual_redirection_btn" class="gdm_download white"><?php _e('Go Back', 'gluon-download-manager') ?></button>
        </div>

		<input type="hidden" id="gdm_redirect_form_intermediate_page_url" value="<?php echo esc_url_raw($redirect_url) ?>">
	</main>

	<?php wp_footer(); ?>
	</body>
	</html>
	<?php
	exit;
}

/**
 * Map old fancy numeric values to new template names (backward compatibility)
 */
function gdm_map_template_name( $template ) {
	// Mapping: fancy number => template name
	$template_map = array(
		'0' => 'simple',
		'1' => 'box',
		'2' => 'card',
		'3' => 'compact',
	);
	
	// If numeric, convert to name
	if ( isset( $template_map[ $template ] ) ) {
		return $template_map[ $template ];
	}
	
	// If already a name, return as-is
	$valid_templates = array( 'simple', 'box', 'card', 'compact' );
	if ( in_array( $template, $valid_templates ) ) {
		return $template;
	}
	
	// Default to simple
	return 'simple';
}

function gdm_load_template( $template, $args = array(), $load_once = true ) {
	// Convert old fancy parameter to new template name
	$template_name = gdm_map_template_name( strval( $template ) );
	
	// Try to locate template in theme first (for theme overrides)
	$theme_template_path = 'simple-download-monitor/template-' . $template_name . '/gdm-template-' . $template_name . '.php';
	$legacy_theme_path = 'simple-download-monitor/fancy' . $template . '/gdm-fancy-' . $template . '.php';
	
	$template_files = array(
		$theme_template_path,
		$legacy_theme_path, // Backward compatibility with old theme overrides
	);

	//Filter hook to allow overriding of the template file path
	$template_files = apply_filters( 'gdm_load_template_files', $template_files, $template_name);
	
	$located = locate_template($template_files);
	
	$output = '';

	if ( ! empty( $located ) ) {
		// Template file found in theme. Load it.
		ob_start();
		
		if ($load_once) {
			include_once $located;
		} else {
			include $located;
		}

		$output .= ob_get_clean();
		return $output;
	}

	// Template file not found in theme. Load from plugin folder.
	$template_file = WP_GLUON_DL_MANAGER_TEMPLATE_DIR . 'template-' . $template_name . '/gdm-template-' . $template_name . '.php';
	
	if ( file_exists( $template_file ) ) {
		include_once $template_file;
		
		// Call the appropriate function based on template name
		$function_name = 'gdm_generate_' . $template_name . '_template_display_output';
		if ( function_exists( $function_name ) ) {
			$output .= call_user_func( $function_name, $args );
			
			// Enqueue card template CSS if needed
			if ( $template_name === 'card' ) {
				wp_enqueue_style( 'gdm_template_card_styles', WP_GLUON_DL_MANAGER_URL . '/includes/templates/template-card/gdm-template-card-styles.css', array(), WP_GLUON_DL_MANAGER_VERSION );
			}
		}
	} else {
		// Fallback to legacy fancy templates if new ones don't exist
		switch ( $template_name ) {
			case 'box':
				include_once WP_GLUON_DL_MANAGER_TEMPLATE_DIR . 'fancy1/gdm-fancy-1.php';
				$output .= gdm_generate_fancy1_display_output( $args );
				break;
			case 'card':
				include_once WP_GLUON_DL_MANAGER_TEMPLATE_DIR . 'fancy2/gdm-fancy-2.php';
				wp_enqueue_style( 'gdm_fancy2_styles', WP_GLUON_DL_MANAGER_URL . '/includes/templates/fancy2/gdm-fancy-2-styles.css', array(), WP_GLUON_DL_MANAGER_VERSION );
				$output .= gdm_generate_fancy2_display_output( $args );
				break;
			case 'compact':
				include_once WP_GLUON_DL_MANAGER_TEMPLATE_DIR . 'fancy3/gdm-fancy-3.php';
				$output .= gdm_generate_fancy3_display_output( $args );
				break;
			case 'simple':
			default:
				include_once WP_GLUON_DL_MANAGER_TEMPLATE_DIR . 'fancy0/gdm-fancy-0.php';
				$output .= gdm_generate_fancy0_display_output( $args );
				break;
		}
	}

	return $output;
}
<?php

// Modify Matomo configuration settings to Ensure Matomo redirects correctly when behind a proxy server.
// It uses the X-Forwarded-Uri header (the proxy server should pass it)
// See MBSD-296 for details
$GLOBALS['MATOMO_MODIFY_CONFIG_SETTINGS'] = function ( $settings ) {
	$settings['General']['proxy_uri_header'] = 1;
	return $settings;
};

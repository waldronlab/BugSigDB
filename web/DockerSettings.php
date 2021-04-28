<?php

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

const DOCKER_SKINS = [
	'MonoBook', # bundled
	'Timeless', # bundled
	'Vector', # bundled
	'chameleon',
];

const DOCKER_EXTENSIONS = [
	'Arrays',
	'Bootstrap',
	'CategoryThree', # bundled
	'Cite', # bundled
	'CiteThisPage', # bundled
	'CodeEditor', # bundled
	'CodeMirror',
//	'ConfirmAccount', no extension.json
	'ConfirmEdit', # bundled
	'DataTransfer',
	'DisplayTitle',
	'Gadgets', # bundled
	'ImageMap', # bundled
	'InputBox', # bundled
	'Interwiki', # bundled
	'LocalisationUpdate', # bundled
	'Loops',
	'MultimediaViewer', # bundled
	'MyVariables',
	'NCBITaxonomyLookup',
	'Nuke', # bundled
	'OATHAuth', # bundled
	'PageImages', # bundled
//	'PageForms',   must be enabled manually after enableSemantics()
	'ParserFunctions', # bundled
	'PdfHandler', # bundled
	'Poem', # bundled
	'PubmedParser',
	'Renameuser', # bundled
	'ReplaceText', # bundled
	'Scribunto', # bundled
	'SecureLinkFixer', # bundled
	'SemanticExtraSpecialProperties',
	'SemanticResultFormats',
	'SpamBlacklist', # bundled
	'SyntaxHighlight_GeSHi', # bundled
	'TemplateData', # bundled
	'TextExtracts', # bundled
	'TitleBlacklist', # bundled
	'Variables',
	'VisualEditor', # bundled
	'WikiEditor', # bundled
];

$DOCKER_MW_VOLUME = getenv( 'MW_VOLUME' );

########################### Core Settings ##########################

# The name of the site. This is the name of the site as displayed throughout the site.
$wgSitename  = getenv( 'MW_SITE_NAME' );

$wgMetaNamespace = "Project";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs
## (like /w/index.php/Page_title to /wiki/Page_title) please see:
## https://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath = "/w";
$wgScriptExtension = ".php";

## The protocol and server name to use in fully-qualified URLs
if ( getenv( 'MW_SITE_SERVER' ) ) {
	$wgServer = getenv( 'MW_SITE_SERVER' );
}

# Internal server name as known to Squid, if different than $wgServer.
#$wgInternalServer = false;

## The URL path to static resources (images, scripts, etc.)
$wgResourceBasePath = $wgScriptPath;

## The URL path to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
$wgLogo = "$wgResourceBasePath/resources/assets/wiki.png";

## UPO means: this is also a user preference option

$wgEnableEmail = false;
$wgEnableUserEmail = false; # UPO

$wgEmergencyContact = "apache@🌻.invalid";
$wgPasswordSender = "apache@🌻.invalid";

$wgEnotifUserTalk = false; # UPO
$wgEnotifWatchlist = false; # UPO
$wgEmailAuthentication = true;

## Database settings
$wgDBtype = "mysql";
$wgDBserver = getenv( 'MW_DB_SERVER' );
$wgDBname = getenv( 'MW_DB_NAME' );
$wgDBuser = getenv( 'MW_DB_USER' );
$wgDBpassword = getenv( 'MW_DB_PASS' );

# MySQL specific settings
$wgDBprefix = "";

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

# Periodically send a pingback to https://www.mediawiki.org/ with basic data
# about this MediaWiki instance. The Wikimedia Foundation shares this data
# with MediaWiki developers to help guide future development efforts.
$wgPingback = false;

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale
$wgShellLocale = "en_US.utf8";

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publicly accessible from the web.
$wgCacheDirectory = getenv( 'MW_USE_CACHE_DIRECTORY' ) ? "$IP/cache" : false;

$wgSecretKey = getenv( 'MW_SECRET_KEY' );

# Changing this will log out all existing sessions.
$wgAuthenticationTokenVersion = "1";

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

# see https://www.mediawiki.org/wiki/Manual:$wgCdnServersNoPurge
$wgUseCdn = true;
$wgCdnServersNoPurge = [];
$wgCdnServersNoPurge[] = '172.16.0.0/12';

if ( getenv( 'MW_SHOW_EXCEPTION_DETAILS' ) === 'true' ) {
	$wgShowExceptionDetails = true;
}

# Site language code, should be one of the list in ./languages/Names.php
$wgLanguageCode = getenv( 'MW_SITE_LANG' ) ?: 'en';

# Allow images and other files to be uploaded through the wiki.
$wgEnableUploads  = getenv( 'MW_ENABLE_UPLOADS' );

####################### Skin Settings #######################
# Default skin: you can change the default skin. Use the internal symbolic
# names, ie 'standard', 'nostalgia', 'cologneblue', 'monobook', 'vector':
$wgDefaultSkin = getenv( 'MW_DEFAULT_SKIN' );
$dockerLoadSkins = null;
$dockerLoadSkins = getenv( 'MW_LOAD_SKINS' );
if ( $dockerLoadSkins ) {
	$dockerLoadSkins = explode( ',', $dockerLoadSkins );
	$dockerLoadSkins = array_intersect( DOCKER_SKINS, $dockerLoadSkins );
	if ( $dockerLoadSkins ) {
		wfLoadSkins( $dockerLoadSkins );
	}
}
if ( !$dockerLoadSkins ) {
	wfLoadSkin( 'Vector' );
	$wgDefaultSkin = 'Vector';
} else{
	if ( !$wgDefaultSkin ) {
		$wgDefaultSkin = reset( $dockerLoadSkins );
	}
	$dockerLoadSkins = array_combine( $dockerLoadSkins, $dockerLoadSkins );
}

if ( isset( $dockerLoadSkins['chameleon'] ) ) {
	wfLoadExtension( 'Bootstrap' );
}

####################### Extension Settings #######################
$dockerLoadExtensions = getenv( 'MW_LOAD_EXTENSIONS' );
if ( $dockerLoadExtensions ) {
	$dockerLoadExtensions = explode( ',', $dockerLoadExtensions );
	$dockerLoadExtensions = array_intersect( DOCKER_EXTENSIONS, $dockerLoadExtensions );
	if ( $dockerLoadExtensions ) {
		wfLoadExtensions( $dockerLoadExtensions );
		$dockerLoadExtensions = array_combine( $dockerLoadExtensions, $dockerLoadExtensions );
	}
}

# SyntaxHighlight_GeSHi
$wgPygmentizePath = '/usr/bin/pygmentize';

# SemanticMediaWiki
$smwgConfigFileDir = "$DOCKER_MW_VOLUME/extensions/SemanticMediaWiki/config";

# Flow https://www.mediawiki.org/wiki/Extension:Flow
if ( isset( $dockerLoadExtensions['Flow'] ) ) {
	$flowNamespaces = getenv( 'MW_FLOW_NAMESPACES' );
	if ( $flowNamespaces ) {
		$wgFlowContentFormat = 'html';
		foreach ( explode( ',', $flowNamespaces ) as $ns ) {
			$wgNamespaceContentModels[ constant( $ns ) ] = 'flow-board';
		}
	}
}

// Scribunto https://www.mediawiki.org/wiki/Extension:Scribunto
if ( isset( $dockerLoadExtensions['Scribunto'] ) ) {
	$wgScribuntoDefaultEngine = 'luastandalone';
	$wgScribuntoUseGeSHi = boolval( $dockerLoadExtensions['SyntaxHighlight_GeSHi'] ?? false );
	$wgScribuntoUseCodeEditor = boolval( $dockerLoadExtensions['CodeEditor'] ?? false );
	$wgScribuntoEngineConf['luastandalone']['errorFile'] = "/var/log/httpd/lua.log";
	$wgScribuntoEngineConf['luastandalone']['luaPath'] = "$IP/extensions/Scribunto/includes/engines/LuaStandalone/binaries/lua5_1_5_linux_64_generic/lua";
}

# Interwiki
$wgGroupPermissions['sysop']['interwiki'] = true;

# InstantCommons allows wiki to use images from http://commons.wikimedia.org
$wgUseInstantCommons  = getenv( 'MW_USE_INSTANT_COMMONS' ) ? true : false;

# Name used for the project namespace. The name of the meta namespace (also known as the project namespace), used for pages regarding the wiki itself.
#$wgMetaNamespace = 'Project';
#$wgMetaNamespaceTalk = 'Project_talk';

# The relative URL path to the logo.  Make sure you change this from the default,
# or else you'll overwrite your logo when you upgrade!
$wgLogo = "$wgScriptPath/logo.png";

# The URL of the site favicon (the small icon displayed next to a URL in the address bar of a browser)
$wgFavicon = "$wgScriptPath/favicon.ico";

##### Short URLs
## https://www.mediawiki.org/wiki/Manual:Short_URL
$wgArticlePath = '/wiki/$1';
## Also see mediawiki.conf

##### Jobs
# Number of jobs to perform per request. see https://www.mediawiki.org/wiki/Manual:$wgJobRunRate
$wgJobRunRate = 0;

##### Improve performance
# https://www.mediawiki.org/wiki/Manual:$wgMainCacheType
switch ( getenv( 'MW_MAIN_CACHE_TYPE' ) ) {
	case 'CACHE_ACCEL':
		# APC has several problems in latest versions of WediaWiki and extensions, for example:
		# https://www.mediawiki.org/wiki/Extension:Flow#.22Exception_Caught:_CAS_is_not_implemented_in_Xyz.22
		$wgMainCacheType = CACHE_ACCEL;
		$wgSessionCacheType = CACHE_DB; #This may cause problems when CACHE_ACCEL is used
		break;
	case 'CACHE_DB':
		$wgMainCacheType = CACHE_DB;
		break;
	case 'CACHE_ANYTHING':
		$wgMainCacheType = CACHE_ANYTHING;
		break;
	case 'CACHE_MEMCACHED':
		# Use Memcached, see https://www.mediawiki.org/wiki/Memcached
		$wgMainCacheType = CACHE_MEMCACHED;
		$wgParserCacheType = CACHE_MEMCACHED; # optional
		$wgMessageCacheType = CACHE_MEMCACHED; # optional
		$wgMemCachedServers = explode( ',', getenv( 'MW_MEMCACHED_SERVERS' ) );
		$wgSessionsInObjectCache = true; # optional
		$wgSessionCacheType = CACHE_MEMCACHED; # optional
		break;
	case 'CACHE_REDIS':
		$wgObjectCaches['redis'] = [
			'class' => 'RedisBagOStuff',
			'servers' => ['redis:6379']
		];
		$wgMainCacheType = 'redis';
		$wgSessionCacheType = CACHE_DB;
		break;
	default:
		$wgMainCacheType = CACHE_NONE;
}

# Use Varnish accelerator
$tmpProxy = getenv( 'MW_PROXY_SERVERS' );
if ( $tmpProxy ) {
	# https://www.mediawiki.org/wiki/Manual:Varnish_caching
	$wgUseSquid = true;
	$wgSquidServers = explode( ',', $tmpProxy );
	$wgUsePrivateIPs = true;
	$wgHooks['IsTrustedProxy'][] = function( $ip, &$trusted ) {
		// Proxy can be set as a name of proxy container
		if ( !$trusted ) {
			global $wgSquidServers;
			foreach ( $wgSquidServers as $proxy ) {
				if ( !ip2long( $proxy ) ) { // It is name of proxy
					if ( gethostbyname( $proxy ) === $ip ) {
						$trusted = true;
						return;
					}
				}
			}
		}
	};
}
//Use $wgSquidServersNoPurge if you don't want MediaWiki to purge modified pages
//$wgSquidServersNoPurge = array('127.0.0.1');

########################### VisualEditor ###########################
//$tmpRestDomain = getenv( 'MW_REST_DOMAIN' );
//$tmpRestParsoidUrl = getenv( 'MW_REST_PARSOID_URL' );
//if ( $tmpRestDomain && $tmpRestParsoidUrl ) {
//	wfLoadExtension( 'VisualEditor' );
//
//	// Enable by default for everybody
//	$wgDefaultUserOptions['visualeditor-enable'] = 1;
//
//	// Optional: Set VisualEditor as the default for anonymous users
//	// otherwise they will have to switch to VE
//	// $wgDefaultUserOptions['visualeditor-editor'] = "visualeditor";
//
//	// Don't allow users to disable it
//	$wgHiddenPrefs[] = 'visualeditor-enable';
//
//	// OPTIONAL: Enable VisualEditor's experimental code features
//	#$wgDefaultUserOptions['visualeditor-enable-experimental'] = 1;
//
//	$wgVirtualRestConfig['modules']['parsoid'] = [
//		// URL to the Parsoid instance
//		'url' => $tmpRestParsoidUrl,
//		// Parsoid "domain", see below (optional)
//		'domain' => $tmpRestDomain,
//		// Parsoid "prefix", see below (optional)
//		'prefix' => $tmpRestDomain,
//	];
//
//	$tmpRestRestbaseUrl = getenv( 'MW_REST_RESTBASE_URL' );
//	if ( $tmpRestRestbaseUrl ) {
//		$wgVirtualRestConfig['modules']['restbase'] = [
//			'url' => $tmpRestRestbaseUrl,
//			'domain' => $tmpRestDomain,
//			'parsoidCompat' => false
//		];
//
//		$tmpRestProxyPath = getenv( 'MW_REST_RESTBASE_PROXY_PATH' );
//		if ( $tmpProxy && $tmpRestProxyPath ) {
//			$wgVisualEditorFullRestbaseURL = $wgServer . $tmpRestProxyPath;
//		} else {
//			$wgVisualEditorFullRestbaseURL = $wgServer . ':' . getenv( 'MW_REST_RESTBASE_PORT' ) . "/$tmpRestDomain/";
//		}
//		$wgVisualEditorRestbaseURL = $wgVisualEditorFullRestbaseURL . 'v1/page/html/';
//	}
//}

########################### Search Type ############################
//switch( getenv( 'MW_SEARCH_TYPE' ) ) {
//	case 'CirrusSearch':
//		# https://www.mediawiki.org/wiki/Extension:CirrusSearch
//		wfLoadExtension( 'Elastica' );
//		require_once "$IP/extensions/CirrusSearch/CirrusSearch.php";
//		$wgCirrusSearchServers =  explode( ',', getenv( 'MW_CIRRUS_SEARCH_SERVERS' ) );
//		if ( $flowNamespaces ) {
//			$wgFlowSearchServers = $wgCirrusSearchServers;
//		}
//		$wgSearchType = 'CirrusSearch';
//		break;
//	default:
//		$wgSearchType = null;
//}

######################### Custom Settings ##########################
@include( 'CustomSettings.php' );

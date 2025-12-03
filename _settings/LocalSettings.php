<?php

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}


## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

//$wgSitename = "A Comprehensive Database of Microbial Signatures";
//$wgMetaNamespace = "Project";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs
## (like /w/index.php/Page_title to /wiki/Page_title) please see:
## https://www.mediawiki.org/wiki/Manual:Short_URL
//$wgScriptPath = "/w";
//$wgScriptExtension = ".php";
$wgArticlePath = "/$1";

//## The protocol and server name to use in fully-qualified URLs
//$wgServer = "https://bugsigdb.org";

## The URL path to static resources (images, scripts, etc.)
//$wgResourceBasePath = $wgScriptPath;

## The URL path to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
$wgLogo = "$wgScriptPath/logo.png";
$wgLogos = [
	'1x' => "$wgScriptPath/logo.png"
];

## UPO means: this is also a user preference option

$wgEnableEmail = true;
$wgEnableUserEmail = true; # UPO

$wgEmergencyContact = "apache@bugsigdb.org";
$wgPasswordSender = "apache@bugsigdb.org";

$wgEnotifUserTalk = true; # UPO
$wgEnotifWatchlist = true; # UPO
$wgEmailAuthentication = true;


# Periodically send a pingback to https://www.mediawiki.org/ with basic data
# about this MediaWiki instance. The Wikimedia Foundation shares this data
# with MediaWiki developers to help guide future development efforts.
$wgPingback = true;

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale
$wgShellLocale = "en_US.utf8";

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publicly accessible from the web.
#$wgCacheDirectory = "$IP/cache";

//# Site language code, should be one of the list in ./languages/data/Names.php
//$wgLanguageCode = "en";

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

# The following permissions were set based on your choice in the installer
$wgGroupPermissions['*']['createaccount'] = false;
$wgGroupPermissions['*']['edit'] = false;

//## Default skin: you can change the default skin. Use the internal symbolic
//## names, ie 'vector', 'monobook':
//$wgDefaultSkin = "chameleon";
$wgCategoryCollation = 'numeric';

//# Enabled skins.
//# The following skins were automatically enabled:
//wfLoadSkin( 'Vector' );
//wfLoadSkin( 'chameleon' );
//
//# Enabled extensions. Most of the extensions are enabled by adding
//# wfLoadExtensions('ExtensionName');
//# to LocalSettings.php. Check specific extension documentation for more details.
//# The following extensions were automatically enabled:
//wfLoadExtension( 'CodeEditor' );
//wfLoadExtension( 'Nuke' );
//wfLoadExtension( 'ParserFunctions' );
//wfLoadExtension( 'ReplaceText' );
//wfLoadExtension( 'WikiEditor' );
//
//wfLoadExtension( 'Interwiki' );
//# End of automatically generated settings.
//# Add more configuration options below.

############# Custom core settings #############
$wgFavicon = "$wgScriptPath/favicon.ico";

$wgRestrictDisplayTitle = false;

$wgNamespacesWithSubpages[NS_MAIN] = true;

$wgRunJobsAsync = true;

/*
#$wgExpensiveParserFunctionLimit = 9999;

# Core hook - used to be sure that Studies get updated when Experiments or Signatures added
/*$wgHooks['ArticleUpdateBeforeRedirect'][] = function( Article $article, &$sectionanchor, &$extraQuery ) {
	# based loosely on how PageForms gets returnto and WikiPage in PFAutoeditAPI
	$returnto = Title::newFromText( $article->getContext()->getRequest()->getText( 'returnto' ) );
	if ( $returnto !== null ) {
		WikiPage::factory( $returnto )->doPurge();
	}
};*/

############# Skin settings #############
# chameleon
$egChameleonExternalStyleModules = [
	"$IP/skins/chameleon/custom/Cosmo/_variables.scss" => 'afterFunctions',
	"$IP/skins/chameleon/custom/_variables.scss" => 'afterVariables',
	"$IP/skins/chameleon/custom/Cosmo/_bootswatch.scss" => 'afterMain',
	"$IP/skins/chameleon/custom/custom.scss" => 'afterMain',
];
$egChameleonLayoutFile = "$IP/skins/chameleon/custom/layouts/navhead.xml";

############# Extension settings #############
# Semantic Mediawiki - keep this early
wfLoadExtension( 'SemanticMediaWiki' );
enableSemantics( 'bugsigdb.org' ); # Keep this first
$smwgEntityCollation = $wgCategoryCollation;
$wgNamespacesWithSubpages[102] = true; // SMW_NS_PROPERTY
$smwgQMaxInlineLimit = 220000;
# Enable embedded query updates, see MBSD-142
$smwgEnabledQueryDependencyLinksStore = false;

$smwgCacheType = 'redis';
$smwgQueryResultCacheType = 'redis';
$smwgValueLookupCacheType = 'redis';

//wfLoadExtension( 'DataTransfer' );

//wfLoadExtension( 'NCBITaxonomyLookup' );
$wgNCBITaxonomyLookupCacheTTL = 1296000; # 15 days
$wgNCBITaxonomyApiKey = getenv( 'MW_NCBI_TAXONOMY_API_KEY' );
$wgNCBITaxonomyApiTimeout = 30;
$wgNCBITaxonomyLookupCacheRandomizeTTL = true;
$wgNCBITaxonomyApiTimeoutFallbackToCache = true;

//wfLoadExtension( 'Variables' );
wfLoadExtension( 'PageForms' );
#$wgPageFormsCacheFormDefinitions = true;
$wgPageFormsMaxAutocompleteValues = $wgPageFormsMaxLocalAutocompleteValues = 10000;

//wfLoadExtension( 'Loops' );
$egLoopsCounterLimit = 2000;

require_once "$IP/extensions/SimpleTooltip/SimpleTooltip.php";
$wgShowExceptionDetails = true;

# ParserFunctions
$wgPFEnableStringFunctions = true;

wfLoadExtension('PubmedParser');
#wfLoadExtension('PFEditorInput');

# Documents states and reviewers
$wgGroupPermissions['reviewer']['review'] = true;
#$wgGroupPermissions['user']['userrights'] = true;
#$wgGroupPermissions['sysop']['interface-admin'] = true;
define("NS_REVIEW", 3100);
define("NS_REVIEW_TALK", 3101);
$wgExtraNamespaces[NS_REVIEW] = "Review";
$wgExtraNamespaces[NS_REVIEW_TALK] = "Review_talk";

// WLDR-356
# $wgNamespaceProtection[NS_REVIEW] = [ 'review' ]; # removed in favor of CategoryLockdown
wfLoadExtension( 'CategoryLockdown' );
$wgCategoryLockdown['Reviews']['edit'] = 'reviewer';

$smwgNamespacesWithSemanticLinks[NS_REVIEW] = true;
//wfLoadExtension( 'CodeMirror' );
$wgDefaultUserOptions['usecodemirror'] = 1;
//wfLoadExtension( 'MyVariables' );
$wgPageFormsAutoeditNamespaces[] = NS_REVIEW;
//wfLoadExtension( 'SemanticExtraSpecialProperties' );
$sespgEnabledPropertyList = [ '_EUSER', '_CUSER' ];
//wfLoadExtension('Arrays');
$smwgPageSpecialProperties[] = '_CDAT';

//wfLoadExtension( 'Scribunto' );
//$wgScribuntoDefaultEngine = 'luastandalone';
//$wgScribuntoUseGeSHi = true;
//$wgScribuntoUseCodeEditor = true;
//$wgScribuntoEngineConf['luastandalone']['errorFile'] = "$IP/lua.log";
//$wgScribuntoEngineConf['luastandalone']['luaPath'] = "$IP/extensions/Scribunto/includes/engines/LuaStandalone/binaries/lua5_1_5_linux_64_generic/lua";
//wfLoadExtension( 'CodeEditor' );
//wfLoadExtension( 'DisplayTitle' );

$wgDisplayTitleHideSubtitle = true;

wfLoadExtensions([ 'ConfirmEdit', 'ConfirmEdit/ReCaptchaNoCaptcha' ]);
$wgCaptchaClass = 'ReCaptchaNoCaptcha';
$wgReCaptchaSiteKey = getenv( 'MW_RECAPTCHA_SITE_KEY' );
$wgReCaptchaSecretKey = getenv( 'MW_RECAPTCHA_SECRET_KEY' );
$wgCaptchaTriggers['edit']          = false;
$wgCaptchaTriggers['create']        = false;
$wgCaptchaTriggers['createtalk']    = false;
$wgCaptchaTriggers['addurl']        = false;
$wgCaptchaTriggers['createaccount'] = true;
$wgCaptchaTriggers['badlogin']      = true;

//wfLoadExtension( 'SemanticResultFormats' );

wfLoadExtension( 'ConfirmAccount' );
$wgGroupPermissions['*']['createaccount'] = false;
$wgConfirmAccountContact = "waldronlab@gmail.com";

$wgGroupPermissions['sysop']['smw-pageedit'] = true;
$wgPageFormsCacheFormDefinitions = false;
$wgAllowSiteCSSOnRestrictedPages = true;
$wgGroupPermissions['sysop']['confirmaccount'] = true;

// Fixme in extensions/SemanticExtraSpecialProperties/SemanticExtraSpecialProperties.php
$wgDisableCounters = false;

// Bump the limits
$wgMaxArticleSize = 2048*80;
$wgMaxPPExpandDepth = 40*2;
$wgMaxPPNodeCount = 1000000*2;
$wgMaxTemplateDepth = 40*2;
$wgMaxGeneratedPPNodeCount = 1000000*2;
# WLDR-371, MBSD-262
$wgExpensiveParserFunctionLimit = 99*6;

$egLoopsCounterLimit = 3000;

$wgFooterIcons = [];

$wgFooterIcons['poweredby']['cuny'] = [
	'src' => $wgScriptPath . '/cuny.png',
	'url' => 'https://sph.cuny.edu/about/people/faculty/levi-waldron/',
	'alt' => 'The City University of New York',
	'width' => 'auto',
	'height' => '48',
];

$wgFooterIcons['poweredby']['bioconductor'] = [
	'src' => $wgScriptPath . '/bioc.png',
	'url' => 'https://bioconductor.org/',
	'alt' => 'Bioconductor - Open Source Software for Bioinformatics',
	'width' => 'auto',
	'height' => '48',
	'style' => 'padding-right: 15px; border-right: 1px solid lightgray;'
];

$wgFooterIcons['poweredby']['wikiworks'] = [
	'src' => $wgScriptPath . '/ww.png',
	'url' => 'https://wikiworks.com/',
	'alt' => 'WikiWorks',
	'width' => 'auto',
	'height' => '48'
];

$wgFooterIcons['poweredby']['mediawiki'] = [
	'src' => $wgScriptPath . '/mw.png',
	'url' => 'https://www.mediawiki.org/',
	'alt' => 'Powered by MediaWiki',
	'width' => 'auto',
	'height' => '48'
];

$wgFooterIcons['poweredby']['semanticmediawiki'] = [
	'src' => $wgScriptPath . '/smw.png',
	'url' => 'https://www.semantic-mediawiki.org/',
	'alt' => 'Powered by Semantic MediaWiki',
	'width' => 'auto',
	'height' => '48'
];

$wgGTagAnalyticsId = 'G-YKH03F3F5K';
$wgGroupPermissions['bot']['gtag-exempt'] = true;
$wgGTagAnonymizeIP = true;

$wgHooks['SkinAddFooterLinks'][] = function ( $skin, string $key, array &$footerlinks  ) {
	if ( $key === 'info' ) {
		$footerlinks['funded'] = 'Funded by NIH 5R01CA230551 to the City University of New York';
	}
};

// Disables cache for Special:Random
$wgHooks['SpecialPageBeforeExecute'][] = function( SpecialPage $special, $subPage ) {
	if ( $special->getName() === 'Randompage' ) {
		$special->getOutput()->enableClientCache( false );
	}
};

// LinkTarget
wfLoadExtension( 'LinkTarget' );
$wgLinkTargetParentClasses = [ 'newtab' ];
$wgExternalLinkTarget = '_blank';
$wgPageFormsMaxLocalAutocompleteValues = 20;

wfLoadExtension( 'WikiSEO' );

// WLDR-194
wfLoadExtension( 'ContributionScores' );
$wgContribScoreIgnoreBots = true;
$wgContribScoreIgnoreBlockedUsers = false;
$wgContribScoreIgnoreUsernames = [
	'Wikiteq',
	'WikiWorks',
	'WikiWorks753',
	'WikiWorks743',
	'WikiWorks017',
	'Admin'
];
$wgContribScoreDisableCache = false;

$wgPubmedParserApiKey = '';

wfLoadExtension( 'Echo' );
$wgEchoWatchlistNotifications = true;
$wgPageFormsEmbedQueryCacheTTL = 60*60;
$wgContribScoreCacheTTL = 60*60;

$smwgQMaxLimit = 100000;

wfLoadExtension( 'GoogleLogin' );
$wgGLSecret = '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';
$wgGLAppId = '766842223289-f249tnqkmcvq06oq88joriq4a2mi97ak.apps.googleusercontent.com';
/**
$wgAuthManagerConfig = [
        'primaryauth' => [
                GoogleLogin\Auth\GooglePrimaryAuthenticationProvider::class => [
                        'class' => GoogleLogin\Auth\GooglePrimaryAuthenticationProvider::class,
                        'sort' => 0
                ]
        ],
        'preauth' => [],
        'secondaryauth' => []
];
$wgInvalidUsernameCharacters = ':~';
$wgUserrightsInterwikiDelimiter = '~';
$wgGroupPermissions['*']['autocreateaccount'] = true;
$wgGLAuthoritativeMode = true;
**/

// WLDR-258
wfLoadExtension( 'DynamicPageList3' );
$wgDplSettings['functionalRichness'] = 3;

wfLoadExtension( 'VariablesLua' );
wfLoadExtension( 'SubpageWatchlist' );
wfLoadExtension( 'SemanticScribunto' );
if ( !isset( $wgScribuntoEngineConf ) ) {
	$wgScribuntoEngineConf = [ 'luasandbox' => [] ];
}
// WLDR-312, WLDR-362
$wgScribuntoEngineConf['luasandbox']['cpuLimit'] = 200;

wfLoadExtension( 'SemanticDependencyUpdater' );
// MBSD-256
$wgSDUUseJobQueue = true;

// MBSD-192
wfLoadExtension( 'Gadgets' );

// WLDR-351
wfLoadExtension( 'TextExtracts' );
$wgWikiSeoEnableAutoDescription = true;
$wgWikiSeoTryCleanAutoDescription = true;
$wgPFStringLengthLimit = 10000;

// MBSD-247
wfLoadExtension( 'EmbedVideo' );

wfLoadExtension( 'SimpleTippy' );

// Disable options to change skins, MBSD-188
$wgHiddenPrefs[] = 'skin';
$wgHiddenPrefs[] = 'skin-responsive';
$wgHiddenPrefs[] = 'skin-prefs';
$wgHiddenPrefs[] = 'VectorSkinVersion'; // 1.35 Vector

// WLDR-301 Install DiscussionTools
wfLoadExtensions([
    'VisualEditor',
    'Linter',
    'DiscussionTools'
]);

// Search based on display titles, WLDR-370, GitHub issue #219
$wgCirrusSearchCustomPageFields[ 'display_title' ] = \SearchIndexField::INDEX_TYPE_TEXT;
// Match the default weight of the real title
$wgCirrusSearchWeights[ 'display_title' ] = 20;

// WLDR-388
wfLoadExtension( 'DismissableSiteNotice' );

// MBSD-355
wfLoadExtension( 'SemanticReports' );

// WLDR-406
wfLoadExtension( 'Widgets' );

// MBSD-369
wfLoadExtension( 'ExternalData' );

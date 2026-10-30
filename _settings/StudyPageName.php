<?php
/**
 * Reject a new study page when its name fails the curator rules.
 *
 * The PMID box (#forminput for form Study) may be empty or a PMID of 1-8
 * digits with no leading zero. A manual name may contain only letters,
 * digits, ".", "-" and "_". MediaWiki stores "_" as a space, so a space in
 * the name is checked as "_".
 */
class BugSigDBStudyPageName {

	/**
	 * @param string $raw Name as entered, or the title text (spaces for "_").
	 * @param string $mode "pmid" or "manual".
	 * @return string|null "pmid", "manual", or null when the name is allowed.
	 */
	public static function failure( $raw, $mode ) {
		$raw = trim( $raw );
		if ( $mode === 'pmid' ) {
			if ( $raw === '' || preg_match( '/^[1-9][0-9]{0,7}$/', $raw ) ) {
				return null;
			}
			return 'pmid';
		}
		if ( $raw === '' ) {
			return null;
		}
		// Title text uses spaces where the curator typed "_".
		$normalized = str_replace( ' ', '_', $raw );
		if ( preg_match( '/^[A-Za-z0-9._-]+$/', $normalized ) ) {
			return null;
		}
		return 'manual';
	}

	/**
	 * @param string $raw
	 * @param string $mode "pmid" or "manual".
	 * @param bool $pageExists An existing page is opened for edit, whatever its name.
	 * @return string|null "pmid", "manual", or null when the request may proceed.
	 */
	public static function blockCode( $raw, $mode, $pageExists ) {
		if ( $pageExists ) {
			return null;
		}
		return self::failure( $raw, $mode );
	}

	/**
	 * @param string $code "pmid" or "manual".
	 * @return string Wikitext shown when creation is refused.
	 */
	public static function message( $code ) {
		if ( $code === 'pmid' ) {
			return 'A PMID must be 1-8 digits and cannot start with 0.'
				. ' Leave the field blank to enter a manual name.'
				. "\n\nSee [[Project:Add or edit studies]].";
		}
		return 'Study names may contain only letters, digits, <code>.</code>,'
			. ' <code>-</code> and <code>_</code>.'
			. ' For a DOI, replace every other character with <code>_</code>,'
			. ' for example <code>10.1038_s41522-026-01144-y</code>.'
			. "\n\nSee [[Project:Add or edit studies]].";
	}

	public static function register() {
		global $wgHooks;
		$wgHooks['PageForms::FormInputEnd'][] = [ self::class, 'onFormInputEnd' ];
		$wgHooks['SpecialPageBeforeExecute'][] = [ self::class, 'onSpecialPageBeforeExecute' ];
		$wgHooks['EditFilter'][] = [ self::class, 'onEditFilter' ];
	}

	/**
	 * Mark the Study #forminput so FormStart can tell it from the manual name.
	 *
	 * @param array $params
	 * @param string &$formContents
	 * @return bool
	 */
	public static function onFormInputEnd( $params, &$formContents ) {
		if ( !is_array( $params ) ) {
			return true;
		}
		$form = '';
		foreach ( $params as $param ) {
			if ( !is_string( $param ) ) {
				continue;
			}
			$parts = explode( '=', $param, 2 );
			if ( count( $parts ) === 2 && trim( $parts[0] ) === 'form' ) {
				$form = trim( $parts[1] );
			}
		}
		if ( !self::isStudyForm( $form ) ) {
			return true;
		}
		$formContents .= '<input type="hidden" name="bsdb_study_name" value="pmid" />';
		return true;
	}

	/**
	 * @param SpecialPage $special
	 * @param string|null $subPage
	 * @return bool
	 */
	public static function onSpecialPageBeforeExecute( $special, $subPage ) {
		$name = $special->getName();
		if ( $name === 'FormStart' ) {
			return self::checkFormStart( $special, $subPage );
		}
		if ( $name === 'FormEdit' ) {
			return self::checkFormEdit( $special, $subPage );
		}
		return true;
	}

	/**
	 * @param SpecialPage $special
	 * @param string|null $subPage
	 * @return bool
	 */
	private static function checkFormStart( $special, $subPage ) {
		$req = $special->getRequest();
		$form = $req->getVal( 'form', '' );
		$target = null;

		if ( $req->getCheck( 'page_name' ) ) {
			$target = $req->getVal( 'page_name', '' );
			$super = $req->getVal( 'super_page' );
			if ( $super !== null && $super !== '' ) {
				$target = $super . '/' . $target;
			}
			$namespace = $req->getVal( 'namespace' );
			if ( $namespace !== null && $namespace !== ''
				&& strpos( $target, $namespace . ':' ) === false
			) {
				$target = $namespace . ':' . $target;
			}
		} elseif ( is_string( $subPage ) && $subPage !== '' ) {
			$parts = explode( '/', $subPage, 2 );
			if ( $form === '' ) {
				$form = $parts[0];
			}
			if ( isset( $parts[1] ) ) {
				$target = str_replace( '_', ' ', $parts[1] );
			}
		}

		if ( !self::isStudyForm( $form ) || $target === null || trim( $target ) === '' ) {
			return true;
		}
		$target = trim( $target );

		// #forminput is GET. The name box on FormStart itself is POST, and the
		// PMID flag can still be in that POST's query string.
		$mode = ( $req->getMethod() === 'GET' && $req->getVal( 'bsdb_study_name' ) === 'pmid' )
			? 'pmid'
			: 'manual';
		$code = self::blockCode( $target, $mode, self::pageExists( $target ) );
		if ( $code === null ) {
			return true;
		}
		return self::reject( $special, $code );
	}

	/**
	 * @param SpecialPage $special
	 * @param string|null $subPage
	 * @return bool
	 */
	private static function checkFormEdit( $special, $subPage ) {
		$req = $special->getRequest();
		$form = $req->getText( 'form' );
		$target = $req->getText( 'target' );
		if ( $form === '' && $target === '' && is_string( $subPage ) && $subPage !== '' ) {
			$parts = explode( '/', $subPage, 2 );
			$form = $parts[0];
			$target = isset( $parts[1] ) ? str_replace( '_', ' ', $parts[1] ) : '';
		}
		if ( !self::isStudyForm( $form ) || trim( $target ) === '' ) {
			return true;
		}
		$target = trim( $target );
		$code = self::blockCode( $target, 'manual', self::pageExists( $target ) );
		if ( $code === null ) {
			return true;
		}
		return self::reject( $special, $code );
	}

	/**
	 * Backstop for saves that do not go through FormStart, including action=edit.
	 *
	 * @param EditPage $editor
	 * @param string $text
	 * @param string $section
	 * @param string &$error
	 * @param string $summary
	 * @return bool
	 */
	public static function onEditFilter( $editor, $text, $section, &$error, $summary ) {
		if ( $section !== '' && $section !== 'new' ) {
			return true;
		}
		$title = $editor->getTitle();
		if ( !$title instanceof Title || $title->exists() ) {
			return true;
		}
		if ( !is_string( $text ) || !preg_match( '/^\s*\{\{\s*[Ss]tudy\b/', $text ) ) {
			return true;
		}
		$code = self::failure( $title->getText(), 'manual' );
		if ( $code === null ) {
			return true;
		}
		$error = self::message( $code );
		return true;
	}

	/**
	 * @param string|null $name
	 * @return bool
	 */
	private static function isStudyForm( $name ) {
		return strcasecmp( trim( (string)$name ), 'Study' ) === 0;
	}

	/**
	 * @param string $target
	 * @return bool
	 */
	private static function pageExists( $target ) {
		$title = Title::newFromText( $target );
		return $title instanceof Title && $title->exists();
	}

	/**
	 * SpecialPage::setHeaders() is protected in MediaWiki 1.43, so it cannot
	 * be called from this hook. The message is written with public OutputPage
	 * methods, then the special page is skipped.
	 *
	 * @param SpecialPage $special
	 * @param string $code
	 * @return bool
	 */
	private static function reject( $special, $code ) {
		$out = $special->getOutput();
		$out->setArticleRelated( false );
		$out->setPageTitle( 'Invalid study name' );
		$out->addWikiTextAsInterface( self::message( $code ) );
		return false;
	}
}

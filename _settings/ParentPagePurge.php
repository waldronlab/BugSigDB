<?php
/**
 * Purge parent pages after a signature or experiment is saved or deleted.
 *
 * A signature save does not edit the experiment, and an experiment save does
 * not edit the study. Embedded queries on those parents otherwise keep the
 * previous result. The next view renders the current list.
 *
 * doPurge() drops the parser cache. It does not drop the query-result entry
 * the embedded ask reads. That entry hangs off the page itself; ArticlePurge
 * only deletes _ASK ids. Invalidate the page first, then doPurge().
 *
 * On a web request both steps are queued POSTSEND. Semantic MediaWiki queues
 * its store write earlier in the same request, also POSTSEND, so the write
 * runs first. Clearing the cache in the hook itself would let the next view
 * store an empty list for a week.
 */
class BugSigDBParentPagePurge {

	/**
	 * Title texts to purge, experiment before study when both apply.
	 *
	 * @param int $namespace NS_MAIN is 0.
	 * @param string $titleText Title::getText(), with spaces and "/" subpages.
	 * @return string[]
	 */
	public static function purgeTargets( $namespace, $titleText ) {
		if ( (int)$namespace !== 0 ) {
			return [];
		}
		$parts = explode( '/', (string)$titleText );
		if ( count( $parts ) < 2 ) {
			return [];
		}
		$leaf = str_replace( '_', ' ', array_pop( $parts ) );
		$parent = implode( '/', $parts );
		if ( $parent === '' ) {
			return [];
		}
		if ( strpos( $leaf, 'Signature ' ) === 0 ) {
			$targets = [ $parent ];
			$parentParts = explode( '/', $parent );
			array_pop( $parentParts );
			if ( $parentParts ) {
				$targets[] = implode( '/', $parentParts );
			}
			return $targets;
		}
		if ( strpos( $leaf, 'Experiment ' ) === 0 ) {
			return [ $parent ];
		}
		return [];
	}

	public static function register() {
		global $wgHooks;
		$wgHooks['PageSaveComplete'][] = [ self::class, 'onPageSaveComplete' ];
		$wgHooks['PageDeleteComplete'][] = [ self::class, 'onPageDeleteComplete' ];
	}

	/**
	 * @param WikiPage $wikiPage
	 * @return bool
	 */
	public static function onPageSaveComplete( $wikiPage ) {
		$title = $wikiPage->getTitle();
		self::purgeParents( $title->getNamespace(), $title->getText() );
		return true;
	}

	/**
	 * @param MediaWiki\Page\ProperPageIdentity $page
	 * @return bool
	 */
	public static function onPageDeleteComplete( $page ) {
		$title = Title::newFromPageIdentity( $page );
		self::purgeParents( $title->getNamespace(), $title->getText() );
		return true;
	}

	/**
	 * @param int $namespace
	 * @param string $titleText
	 */
	private static function purgeParents( $namespace, $titleText ) {
		$targets = self::purgeTargets( $namespace, $titleText );
		if ( $targets === [] ) {
			return;
		}
		\MediaWiki\Deferred\DeferredUpdates::addCallableUpdate(
			static function () use ( $targets ) {
				foreach ( $targets as $target ) {
					self::purgeTitleText( $target );
				}
			},
			\MediaWiki\Deferred\DeferredUpdates::POSTSEND
		);
	}

	/**
	 * @param string $text
	 */
	private static function purgeTitleText( $text ) {
		$title = Title::newFromText( $text );
		if ( !$title || !$title->exists() ) {
			return;
		}
		self::invalidateQueryResultCache( $title );
		WikiPage::factory( $title )->doPurge();
	}

	/**
	 * Drop cached ask results stored against this page.
	 *
	 * @param Title $title
	 */
	private static function invalidateQueryResultCache( Title $title ) {
		$subject = \SMW\DataItems\WikiPage::newFromTitle( $title );
		\SMW\Services\ServicesFactory::getInstance()
			->singleton( 'ResultCache' )
			->invalidateCache( $subject, 'ParentPagePurge' );
	}

}

<?php
/**
 * Run: php tests/StudyPageNameTest.php
 */
require_once __DIR__ . '/../_settings/StudyPageName.php';

$failures = 0;

function expect( $label, $actual, $expected ) {
	global $failures;
	if ( $actual !== $expected ) {
		fwrite( STDERR, "FAIL $label: expected " . var_export( $expected, true )
			. ', got ' . var_export( $actual, true ) . "\n" );
		$failures++;
	}
}

expect( 'blank pmid', BugSigDBStudyPageName::failure( '', 'pmid' ), null );
expect( 'blank pmid spaces', BugSigDBStudyPageName::failure( '  ', 'pmid' ), null );
expect( 'pmid 1', BugSigDBStudyPageName::failure( '1', 'pmid' ), null );
expect( 'pmid 8 digits', BugSigDBStudyPageName::failure( '12345678', 'pmid' ), null );
expect( 'pmid 9 digits', BugSigDBStudyPageName::failure( '123456789', 'pmid' ), 'pmid' );
expect( 'pmid leading zero', BugSigDBStudyPageName::failure( '0123', 'pmid' ), 'pmid' );
expect( 'pmid zero', BugSigDBStudyPageName::failure( '0', 'pmid' ), 'pmid' );
expect( 'pmid NA', BugSigDBStudyPageName::failure( 'NA', 'pmid' ), 'pmid' );
expect( 'pmid doi slash', BugSigDBStudyPageName::failure( '10.1038/s41522-026-01144-y', 'pmid' ), 'pmid' );
expect( 'pmid doi underscore', BugSigDBStudyPageName::failure( '10.1038_s41522-026-01144-y', 'pmid' ), 'pmid' );

expect( 'manual blank', BugSigDBStudyPageName::failure( '', 'manual' ), null );
expect( 'manual doi underscore', BugSigDBStudyPageName::failure( '10.1038_s41522-026-01144-y', 'manual' ), null );
expect( 'manual doi space', BugSigDBStudyPageName::failure( '10.1038 s41522-026-01144-y', 'manual' ), null );
expect( 'manual Study_608', BugSigDBStudyPageName::failure( 'Study_608', 'manual' ), null );
expect( 'manual NA', BugSigDBStudyPageName::failure( 'NA', 'manual' ), null );
expect( 'manual pmid digits', BugSigDBStudyPageName::failure( '30778155', 'manual' ), null );
expect( 'manual slash', BugSigDBStudyPageName::failure( '10.1038/s41598-026-75131-w', 'manual' ), 'manual' );
expect( 'manual url', BugSigDBStudyPageName::failure( 'https://doi.org/10.1038/s41522-026-01144-y', 'manual' ), 'manual' );
expect( 'manual colon', BugSigDBStudyPageName::failure( 'Study:Foo', 'manual' ), 'manual' );
expect( 'manual hash', BugSigDBStudyPageName::failure( '10.1038#foo', 'manual' ), 'manual' );

$pmidMessage = BugSigDBStudyPageName::message( 'pmid' );
$manualMessage = BugSigDBStudyPageName::message( 'manual' );
expect( 'pmid message link', strpos( $pmidMessage, '[[Project:Add or edit studies]]' ) !== false, true );
expect( 'manual message link', strpos( $manualMessage, '[[Project:Add or edit studies]]' ) !== false, true );
expect( 'manual message example', strpos( $manualMessage, '10.1038_s41522-026-01144-y' ) !== false, true );

expect( 'existing NA in pmid field', BugSigDBStudyPageName::blockCode( 'NA', 'pmid', true ), null );
expect( 'new NA in pmid field', BugSigDBStudyPageName::blockCode( 'NA', 'pmid', false ), 'pmid' );
expect( 'existing DOI in pmid field', BugSigDBStudyPageName::blockCode( '10.1038_s41522-026-01144-y', 'pmid', true ), null );
expect( 'new DOI in pmid field', BugSigDBStudyPageName::blockCode( '10.1038_s41522-026-01144-y', 'pmid', false ), 'pmid' );
expect( 'existing Study_608', BugSigDBStudyPageName::blockCode( 'Study_608', 'pmid', true ), null );
expect( 'new pmid digits', BugSigDBStudyPageName::blockCode( '30778155', 'pmid', false ), null );
expect( 'existing slash page', BugSigDBStudyPageName::blockCode( '10.1038/s41598-026-75131-w', 'manual', true ), null );
expect( 'new slash name', BugSigDBStudyPageName::blockCode( '10.1038/s41598-026-75131-w', 'manual', false ), 'manual' );

if ( $failures > 0 ) {
	fwrite( STDERR, "$failures failed\n" );
	exit( 1 );
}
echo "ok\n";

<?php
/**
 * Run: php tests/ParentPagePurgeTest.php
 */
require_once __DIR__ . '/../_settings/ParentPagePurge.php';

$failures = 0;

function expect( $label, $actual, $expected ) {
	global $failures;
	if ( $actual !== $expected ) {
		fwrite( STDERR, "FAIL $label: expected " . var_export( $expected, true )
			. ', got ' . var_export( $actual, true ) . "\n" );
		$failures++;
	}
}

expect(
	'signature purges experiment and study',
	BugSigDBParentPagePurge::purgeTargets( 0, 'Study WLDR387CLI/Experiment 1/Signature 1' ),
	[ 'Study WLDR387CLI/Experiment 1', 'Study WLDR387CLI' ]
);
expect(
	'pmid signature purges experiment and study',
	BugSigDBParentPagePurge::purgeTargets( 0, '38111925/Experiment 1/Signature 2' ),
	[ '38111925/Experiment 1', '38111925' ]
);
expect(
	'experiment purges study only',
	BugSigDBParentPagePurge::purgeTargets( 0, 'Study 1103/Experiment 1' ),
	[ 'Study 1103' ]
);
expect(
	'pmid experiment purges the pmid page',
	BugSigDBParentPagePurge::purgeTargets( 0, '38111925/Experiment 1' ),
	[ '38111925' ]
);
expect(
	'study itself has no parent purge',
	BugSigDBParentPagePurge::purgeTargets( 0, 'Study 1103' ),
	[]
);
expect(
	'glossary page is left alone',
	BugSigDBParentPagePurge::purgeTargets( 0, 'Feces' ),
	[]
);
expect(
	'non-main namespace is left alone',
	BugSigDBParentPagePurge::purgeTargets( 10, 'Template:Experiment' ),
	[]
);
expect(
	'root signature has no parent',
	BugSigDBParentPagePurge::purgeTargets( 0, 'Signature 1' ),
	[]
);
expect(
	'underscore leaf still matches',
	BugSigDBParentPagePurge::purgeTargets( 0, 'Study 1103/Experiment_1/Signature_1' ),
	[ 'Study 1103/Experiment_1', 'Study 1103' ]
);

if ( $failures > 0 ) {
	fwrite( STDERR, "$failures failed\n" );
	exit( 1 );
}
echo "ok\n";

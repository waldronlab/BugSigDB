<?php
/**
 * Copy Review state and Quality control tag onto a studies CSV.
 *
 * Join key: studies "Study page name" = reviews "Subject".
 * Usage: php merge_study_review_columns.php <studies.csv> <reviews.csv>
 */

if ( $argc !== 3 ) {
	fwrite( STDERR, "Usage: php merge_study_review_columns.php <studies.csv> <reviews.csv>\n" );
	exit( 1 );
}

$studiesPath = $argv[1];
$reviewsPath = $argv[2];

/**
 * @return string
 */
function titleKey( string $title ): string {
	return str_replace( '_', ' ', trim( $title ) );
}

/**
 * @param resource $handle
 * @return list<string>|null
 */
function readCsvRow( $handle ) {
	$row = fgetcsv( $handle );
	if ( $row === false || $row === null ) {
		return null;
	}
	if ( $row === [ null ] ) {
		return null;
	}
	return $row;
}

$reviewsHandle = fopen( $reviewsPath, 'r' );
if ( $reviewsHandle === false ) {
	fwrite( STDERR, "Cannot read $reviewsPath\n" );
	exit( 1 );
}

$reviewHeader = readCsvRow( $reviewsHandle );
if ( $reviewHeader === null ) {
	fwrite( STDERR, "Reviews CSV has no header\n" );
	fclose( $reviewsHandle );
	exit( 1 );
}

$subjectIdx = array_search( 'Subject', $reviewHeader, true );
$stateIdx = array_search( 'Review state', $reviewHeader, true );
$qcIdx = array_search( 'Quality control tag', $reviewHeader, true );
if ( $subjectIdx === false || $stateIdx === false || $qcIdx === false ) {
	fwrite( STDERR, "Reviews CSV must have Subject, Review state, Quality control tag columns\n" );
	fclose( $reviewsHandle );
	exit( 1 );
}

$bySubject = [];
while ( ( $row = readCsvRow( $reviewsHandle ) ) !== null ) {
	$subject = titleKey( $row[$subjectIdx] ?? '' );
	if ( $subject === '' ) {
		continue;
	}
	$bySubject[$subject] = [
		$row[$stateIdx] ?? '',
		$row[$qcIdx] ?? '',
	];
}
fclose( $reviewsHandle );

$studiesHandle = fopen( $studiesPath, 'r' );
if ( $studiesHandle === false ) {
	fwrite( STDERR, "Cannot read $studiesPath\n" );
	exit( 1 );
}

$studyHeader = readCsvRow( $studiesHandle );
if ( $studyHeader === null ) {
	fwrite( STDERR, "Studies CSV has no header\n" );
	fclose( $studiesHandle );
	exit( 1 );
}

$nameIdx = array_search( 'Study page name', $studyHeader, true );
if ( $nameIdx === false ) {
	fwrite( STDERR, "Studies CSV must have a Study page name column\n" );
	fclose( $studiesHandle );
	exit( 1 );
}

$outPath = $studiesPath . '.tmp';
$outHandle = fopen( $outPath, 'w' );
if ( $outHandle === false ) {
	fwrite( STDERR, "Cannot write $outPath\n" );
	fclose( $studiesHandle );
	exit( 1 );
}

$studyHeader[] = 'Review state';
$studyHeader[] = 'Quality control tag';
fputcsv( $outHandle, $studyHeader );

while ( ( $row = readCsvRow( $studiesHandle ) ) !== null ) {
	$key = titleKey( $row[$nameIdx] ?? '' );
	$extra = $bySubject[$key] ?? [ '', '' ];
	$row[] = $extra[0];
	$row[] = $extra[1];
	fputcsv( $outHandle, $row );
}

fclose( $studiesHandle );
fclose( $outHandle );

if ( !rename( $outPath, $studiesPath ) ) {
	fwrite( STDERR, "Cannot replace $studiesPath\n" );
	exit( 1 );
}

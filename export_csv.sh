#!/bin/bash

CURDATE=$(date +%Y-%m-%d_%H)

cd /var/www/mediawiki/w/ || exit 1

SCRIPT_DIR=$(cd "$(dirname "$(readlink -f "$0")")" && pwd)
STUDIES_CSV="/var/www/mediawiki/w/images/csv_reports/studies.$CURDATE.csv"
REVIEWS_CSV="/tmp/bugsigdb-reviews.$CURDATE.csv"

echo "[$CURDATE] Exporting studies.."
php extensions/SemanticReports/maintenance/GenerateReport.php \
  -q "[[Category:Studies]] |?Study design |?PMID |?DOI |?URI |?Authors list |?Title |?Journal |?Year |?Abstract |?Keyword list=Keywords |?State |?Reviewer |?Curation method |?Curation protocol" \
  -m 'Study page name' \
  -v ',' \
  -s ',' \
  -f "csv" \
  -o "$STUDIES_CSV"
echo "Done"

echo "Exporting study reviews.."
php extensions/SemanticReports/maintenance/GenerateReport.php \
  -q "[[Category:Reviews]] |?Subject |?Review state |?Quality control tag" \
  -m '-' \
  -v ',' \
  -s ',' \
  -f "csv" \
  -o "$REVIEWS_CSV"
echo "Done"

echo "Merging Review state and Quality control tag into studies CSV.."
php "$SCRIPT_DIR/merge_study_review_columns.php" "$STUDIES_CSV" "$REVIEWS_CSV" || exit 1
rm -f "$REVIEWS_CSV"
echo "Done"

echo "Exporting experiments.."
php extensions/SemanticReports/maintenance/GenerateReport.php \
  -q "[[Category:Experiments]] |?Original page name=Experiment page name |?Related study=Study |?Location of subjects |?Host species |?Body site |?UBERON ID |?Condition |?EFO ID |?Group 0 name |?Group 1 name |?Group 1 definition |?Group 0 sample size |?Group 1 sample size |?Antibiotics exclusion |?Sequencing type |?16S variable region |?Sequencing platform |?Data transformation |?Statistical test |?Significance threshold |?MHT correction |?LDA Score above |?Matched on |?Confounders controlled for |?Pielou |?Shannon |?Chao1 |?Simpson |?Inverse Simpson |?Richness |?State |?Reviewer" \
  -m '-' \
  -v ',' \
  -s ',' \
  -f "csv" \
  -o "/var/www/mediawiki/w/images/csv_reports/experiments.$CURDATE.csv"
echo "Done"

echo "Exporting signatures.."
php extensions/SemanticReports/maintenance/GenerateReport.php \
  -q "[[Category:Signatures]] |?Original page name=Signature page name |?Related experiment=Experiment |?Related study=Study |?Source data=Source |?Curated date |?Curator |?Revision editor |?Description |?Abundance in Group 1 |?NCBI_export=MetaPhlAn taxon names |?NCBI_export_ids_sc=NCBI Taxonomy IDs |?State |?Reviewer" \
  -m '-' \
  -v ',' \
  -s ',' \
  -f "csv" \
  -o "/var/www/mediawiki/w/images/csv_reports/signatures.$CURDATE.csv"
echo "Done"

echo "Updating symlinks.."
ln -sf /var/www/mediawiki/w/images/csv_reports/studies.$CURDATE.csv /var/www/mediawiki/w/images/csv_reports/studies.csv
ln -sf /var/www/mediawiki/w/images/csv_reports/experiments.$CURDATE.csv /var/www/mediawiki/w/images/csv_reports/experiments.csv
ln -sf /var/www/mediawiki/w/images/csv_reports/signatures.$CURDATE.csv /var/www/mediawiki/w/images/csv_reports/signatures.csv
echo "Done"

echo "Cleaning up files older than 24 hours.."
find /var/www/mediawiki/w/images/csv_reports/ -maxdepth 1 -name '*.csv' -type f -mtime +0 -delete
echo "Done"

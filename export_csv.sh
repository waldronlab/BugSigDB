#!/bin/bash

CURDATE=$(date +%Y-%m-%d_%H)

cd /var/www/mediawiki/w/ || exit 1

echo "[$CURDATE] Exporting studies.."
php extensions/SemanticReports/maintenance/GenerateReport.php \
  -q "[[Category:Studies]] |?Study design |?PMID |?DOI |?URI |?Authors list |?Title |?Journal |?Year |?Abstract |?Keyword list=Keywords |?State |?Reviewer" \
  -f "csv" \
  -o "/var/www/mediawiki/w/images/csv_reports/studies.$CURDATE.csv"
echo "Done"

echo "Exporting experiments.."
php extensions/SemanticReports/maintenance/GenerateReport.php \
  -q "[[Category:Experiments]] |?Original page name=Experiment page name |?Related study=Study |?Location of subjects |?Host species |?Body site |?UBERON ID |?Condition |?EFO ID |?Group 0 name |?Group 1 name |?Group 1 definition |?Group 0 sample size |?Group 1 sample size |?Antibiotics exclusion |?Sequencing type |?16S variable region |?Sequencing platform |?Data transformation |?Statistical test |?Significance threshold |?MHT correction |?LDA Score above |?Matched on |?Confounders controlled for |?Pielou |?Shannon |?Chao1 |?Simpson |?Inverse Simpson |?Richness |?State |?Reviewer" \
  -f "csv" \
  -o "/var/www/mediawiki/w/images/csv_reports/experiments.$CURDATE.csv"
echo "Done"

echo "Exporting signatures.."
php extensions/SemanticReports/maintenance/GenerateReport.php \
  -q "[[Category:Signatures]] |?Original page name=Signature page name |?Related experiment=Experiment |?Related study=Study |?Source data=Source |?Curated date |?Curator |?Revision editor |?Description |?Abundance in Group 1 |?NCBI_export=MetaPhlAn taxon names |?NCBI_export_ids_sc=NCBI Taxonomy IDs |?State |?Reviewer" \
  -f "csv" \
  -o "/var/www/mediawiki/w/images/csv_reports/signatures.$CURDATE.csv"
echo "Done"

echo "Updating symlinks.."
ln -sf /var/www/mediawiki/w/images/csv_reports/studies.$CURDATE.csv /var/www/mediawiki/w/images/csv_reports/studies.csv
ln -sf /var/www/mediawiki/w/images/csv_reports/experiments.$CURDATE.csv /var/www/mediawiki/w/images/csv_reports/experiments.csv
ln -sf /var/www/mediawiki/w/images/csv_reports/signatures.$CURDATE.csv /var/www/mediawiki/w/images/csv_reports/signatures.csv
echo "Done"

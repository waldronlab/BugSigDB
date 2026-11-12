import mwclient
from os import walk
import urllib.parse
import click
from progress.spinner import Spinner
import sys
import time
import mwparserfromhell
import requests
import json
import validators

isSilent = True
logfileName = False

OLS_API_MAX_ATTEMPTS = 5
OLS_API_BACKOFF_INITIAL_SEC = 5
OLS_API_BACKOFF_MAX_SEC = 30


def ols_api_backoff_seconds(attempt):
    return min(OLS_API_BACKOFF_INITIAL_SEC * (2 ** attempt), OLS_API_BACKOFF_MAX_SEC)


def fetch_ols_json(url):
    last_error = None
    for attempt in range(OLS_API_MAX_ATTEMPTS):
        try:
            response = requests.get(url, timeout=300)
            response.raise_for_status()
            return response.json()
        except (requests.RequestException, ValueError) as exc:
            last_error = exc
            if attempt + 1 < OLS_API_MAX_ATTEMPTS:
                time.sleep(ols_api_backoff_seconds(attempt))
    echo("\t! OLS API request failed after %s attempts: %s" % (OLS_API_MAX_ATTEMPTS, url))
    echo("\t  %s" % last_error)
    return None


@click.command()
@click.option("--wikisite", "-s", help="The wiki site url, domain part only, eg: mywiki.com", required=True)
@click.option("--wikipath", "-w", help="Relative path to the wiki api.php, eg: /w/", required=False, default='/w/', show_default=True)
@click.option("--user", "-u", help="The wiki username", required=True)
@click.option("--password", "-p", help="The wiki password", required=True)
@click.option("--dry", "-d", help="Dry-run, will not save the page", required=False, is_flag=True)
@click.option("--verbose", "-v", help="Verbose", required=False, is_flag=True)
@click.option("--logfile", "-l", help="Log file to use with silent flag", required=False, is_flag=False)
@click.option("--protocol", "-t", help="Connection protocol: http, https", required=False, default='https', show_default=True)
@click.option("--sleep", "-z", help="Time to wait between edits", required=False, default=1, show_default=True)
@click.option("--start", "-r", help="Page name to start with", required=False)
def entry(wikisite, wikipath, user, password, dry, verbose, logfile, protocol, sleep, start):
    global isSilent, logfileName

    startMatched = False
    processed = 0
    updated = 0
    skipped = 0

    if verbose:
        isSilent = False
    else:
        if logfile:
            logfileName = logfile

    site = mwclient.Site(wikisite, wikipath, scheme=protocol)
    site.requests['timeout'] = 1000

    echo("Connecting to %s.." % wikisite)

    site.login(user, password)

    if dry:
        echo("Doing a dry-run! No writes will be performed")

    if isSilent:
        spinner = Spinner('Loading ')

    for page in site.Categories['Glossary']:

        if start and not startMatched:
            if page.page_title != start:
                continue
            else:
                startMatched = True

        processed = processed + 1

        if page.namespace != 0:
            echo("\tNot a regular page!")
            continue

        echo(page.page_title)
        text = page.text()

        if '|Link=' not in text:
            echo("\tLink param not found!")
            continue

        wikicode = mwparserfromhell.parse(text)
        templates = wikicode.filter_templates(matches='Glossary')
        if not len(templates):
            echo("\tTemplate not found!")
            continue

        template = templates[0]
        if not template:
            echo("\tTemplate not found!")
            continue

        if template.has_param('Link'):
            link = template.get('Link')
            linkValue = link.value.rstrip()
            echo("\tLink: %s" % linkValue)

            if not validators.url(linkValue):
                echo("\t! The param value (%s) is not an URL!" % linkValue)
                continue

            ols_url = 'https://www.ebi.ac.uk/ols/api/terms?iri=%s' % linkValue.replace('https://', 'http://')
            ols_data = fetch_ols_json(ols_url)
            if ols_data is None:
                skipped = skipped + 1
                continue

            if '_embedded' not in ols_data:
                echo("\t! The API response does not contain a _embedded list:")
                echo("\t\t%s" % ols_data)
                continue

            if 'terms' not in ols_data['_embedded']:
                echo("\t! The API response does not contain a terms list:")
                echo("\t\t%s" % ols_data)
                continue

            terms = ols_data['_embedded']['terms']
            if not len(terms):
                echo('\tTerms not found!')
                continue
            for term in terms:
                label = term['label']
                echo("\t\t%s" % label)
                is_obsolete = term['is_obsolete']
                replaced = term['term_replaced_by']

                if replaced and not validators.url(replaced):
                    echo("\t! The replacement is not an URL but a term ID: %s" % replaced)
                    json2 = fetch_ols_json('https://www.ebi.ac.uk/ols/api/terms?id=%s' % replaced)

                    if not json2:
                        echo("Unable to fetch URI for term replcement %s" % replaced)
                        continue
                    if '_embedded' not in json2:
                        echo("Unable to fetch _embedded for term replcement %s" % replaced)
                        continue
                    if 'terms' not in json2['_embedded']:
                        echo("Unable to fetch terms for term replcement %s" % replaced)
                        continue
                    if not len(json2['_embedded']['terms']):
                        echo("Unable to fetch terms for term replcement %s" % replaced)
                        continue

                    v = json2['_embedded']['terms'][0]['iri']
                    if not v:
                        echo("Unable to fetch URI for term replcement %s" % replaced)
                        continue
                    replaced = v

                if replaced:
                    echo("\tTerm '%s' (Link: %s) is replaced by: %s" % (label, linkValue, replaced))
                    link.value = '%s\n' % replaced
                    if not dry:
                        page.edit(text=str(wikicode), summary='Updating EFO links: %s -> %s' % (linkValue, replaced))
                        updated = updated + 1
                    echo("----------------")
                    echo(str(wikicode))
                    echo("----------------")
                    # all is done for the term, go next
                    continue

        else:
            echo("\t! Link param not found")
            continue

        if isSilent:
            spinner.next()
        if sleep:
            time.sleep(sleep)

    if isSilent:
        spinner.finish()

    echo("Done!")
    echo("\nProcessed %s pages, Updated %s pages, Skipped %s pages due to OLS API errors" % (processed, updated, skipped))


def echo(text):
    if isSilent:
        if logfileName:
            with open(logfileName, "a") as f:
                f.write(text)
                f.write("\n")
        return
    print(text)


if __name__ == '__main__':
    entry()

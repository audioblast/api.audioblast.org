# References and relationship RDF

The references, links and taxa modules support `output=JSON-LD` and
`output=Turtle`. When `output` is absent, `Accept: application/ld+json` or
`Accept: text/turtle` selects RDF. JSON remains the default. Existing filters
and pagination apply; RDF pages advertise the next page in a Link header.

Records resolve at `/reference/{source}/{id}`, `/link/{source}/{id}` and
`/taxon/{source}/{id}`. These reuse the existing prepared record lookup,
canonical-case redirect and 404 handling. Taxa now expose the existing source
and id columns, so they can be filtered and identified across sources.
No schema migration is needed.

## Reference mapping

| Data | RDF |
| --- | --- |
| Publication | dcterms:BibliographicResource |
| Canonical audioBLAST URI | @id and dwc:referenceID |
| BibTeX type | dwc:referenceType (article -> journalArticle; inbook/incollection -> bookSection; conference/inproceedings -> proceedingsPaper; theses -> thesis; other types retained) |
| Source type label | dc:type |
| Title, abstract, year | dcterms:title, dcterms:abstract, dcterms:issued |
| Notes | dwc:referenceRemarks |
| Authors | dc:creator verbatim; dcterms:creator agents and ordered bibo:authorList |
| Editors | bibo:editor agents and ordered bibo:editorList |
| Publisher | dc:publisher literal |
| DOI, PubMed ID, ISBN | bibo:doi, bibo:pmid, bibo:isbn |
| Volume, issue, pages, chapter, edition | corresponding BIBO properties |
| Journal, containing book, series | dcterms:isPartOf descriptions |
| Journal ISSN and abbreviation | bibo:issn and bibo:shortTitle on the journal |
| Keywords | separate dc:subject literals |
| Attachments | separate dcterms:relation IRIs |
| Source page and publication URL | rdfs:seeAlso (publication URL also bibo:uri) |

Year-only dates remain xsd:gYear. Other supplied date strings are preserved;
normalisation belongs in the ingest. Names are retained verbatim, including
corporate-name braces. Agent and container identifiers are local fragments;
they do not assert that equal names in different references identify the same
entity. Author/editor order is represented with RDF sequences. The source
page is not asserted to be identical to the publication. DOI and PubMed
resolver links are exposed without fetching external metadata.

Month, howpublished, organization, institution, school, address and
type_of_work remain available in JSON; they have no RDF mapping in this version.
This avoids assigning uncertain publication roles from those free-text fields.

## Relationships

Each reference advertises two rdfs:seeAlso links to `/data/links/`, filtered by
its subject identity and object identity respectively. Follow both to find
outgoing relationships and incoming citations. No per-pair views or joins are
introduced and no relationship query is added to each reference lookup.

Each link has its own URI and dwc:ResourceRelationship description. Its
asserting source, predicate, endpoint identifiers and remarks use the existing
Darwin Core fields. Its qualifier is an IRI-valued dcterms:type of the
relationship. Where both endpoints resolve to RDF identities, the link is
also an rdf:Statement, and the direct subject-predicate-object triple is
included. Assertions differing only in remarks stay distinct.

Endpoint module types use the registered record URI paths. `term` and `iri`
use their supplied HTTP(S) IRI directly. Unsupported module types preserve
endpoint type/source/id as a JSON tuple literal in the corresponding Darwin
Core ID field, without inventing a resolvable URI or emitting a direct triple.
No existence checks are made: missing records remain dangling targets, as in
the links table, and return 404 when looked up.

Vocabulary lookup on 2026-09-19 used `https://vocab.audioblast.org/api/mcp`:
list_vocabularies and search_terms. The server did not yet list referenceContent
or topic, nor the planned Oscillogram and AcousticBehaviour terms. Existing
qualifier and topic IRIs are preserved; their definitions still need publishing
in the vocabulary; the exact pending IRIs are tracked in
[vocabulary-backlog.md](vocabulary-backlog.md). RDF generation does not call the vocabulary server.

## Local validation

From the repository root, with PHP and Python rdflib installed:

```sh
php tests/linked-data.php /tmp
python tests/linked-data.py /tmp
```

Use the same output directory for both runtimes (Cygwin /tmp is C:/cygwin64/tmp
for Windows Python). The fixtures use a mock database, never settings/db.php.
They cover Unicode and escaping, sparse records, date precision, contributors,
multiple attachments, cross-source links, qualifiers, distinct assertions,
URI encoding, negotiation, JSON compatibility, 301/404 handling and graph
equivalence. Production data and dangling-target counts have not been queried.

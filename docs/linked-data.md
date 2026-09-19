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
| Canonical audioBLAST URI | @id |
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

Recording, reference, trait and taxon RDF responses include their one-hop incoming and outgoing
relationships from the central links table. This applies both to canonical record
URIs and to paginated `/data/recordings/`, `/data/references/`, `/data/traits/` and `/data/taxa/` responses.
Outgoing triples are merged onto the record node using compact predicate names
where a known prefix exists. Incoming links appear under @reverse on the requested
record in JSON-LD; Turtle writes the equivalent forward triples. If both endpoints
are requested, both records expose the relationship without changing its direction. Each assertion also retains its
own link URI, source, qualifier and remarks. Linked records are identified by URI;
their full descriptions are not recursively fetched.

For example, a recording isReferencedBy a reference appears in the RDF returned
for either endpoint. The reference response does not invent an inverse predicate.
References still advertise the two filtered `/data/links/` discovery URLs.
Traits include their source publications via dcterms:source and their taxa via
IAO is about, using the stored predicates. Measurement values and ontology links
remain on the trait node. Relationships come exclusively from the links table;
no reference or taxon links are inferred from free-text trait fields.
Taxa include incoming recordings, traits and references under @reverse, plus
outgoing links from the same table. Taxonomic names and ranks remain intact.
Relationship predicates are emitted exactly as stored, including
dwc:namePublishedInID in the existing name-publication links. Any correction to
that predicate or its meaning will be made at source later, not in API output.
Prefix compaction changes only the spelling of an IRI, not its identity.

Lookups use prepared parameters for the returned records' exact type/source/id,
in batches of at most 100 records, once for each direction. The asserting source
is not restricted to the records' source. Duplicate assertions found from both
sides or across batches are included once by their source/id. Pagination continues
to count records, not the additional graph nodes. A failed relationship query
returns HTTP 500 and an empty RDF graph rather than silently returning incomplete
data. Empty pages and JSON responses do not query relationships. No schema changes
or per-pair views are required.

Each link has its own URI and rdf:Statement description. rdf:subject,
rdf:predicate and rdf:object identify the relationship using IRIs. The asserting
source and remarks retain dwc:relationshipAccordingTo and dwc:relationshipRemarks;
the qualifier is an IRI-valued dcterms:type of the assertion. The direct triple
is included too: describing a statement alone does not assert it. Assertions
differing only in source, qualifier or remarks remain distinct.

Redundant Darwin Core ID fields are retained in tabular JSON but are not copied
into RDF. This does not rewrite predicates supplied by the links table.
RDF uses @id to identify references, taxa and link assertions, and its subject,
predicate and object terms for links. Traits retain their source-local ID under
dcterms:identifier. This follows the Darwin Core RDF guide, section 2.6:
https://dwc.tdwg.org/rdf/#26-darwin-core-id-terms-and-rdf-normative

Endpoint module types use the registered record URI paths. `term` and `iri`
use their supplied HTTP(S) IRI directly. Unsupported module types preserve
endpoint type/source/id as a JSON tuple in an rdfs:comment labelled with the
unresolved endpoint. Such incomplete assertion descriptions omit that RDF
endpoint and do not emit a direct triple or invent a resolvable URI.
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
URI encoding, negotiation, JSON compatibility, 301/404 handling, embedded
incoming/outgoing links, batching, query failures, reverse framing, multiple
assertions and graph equivalence before and after framing. Production data and dangling-target counts have not been queried.

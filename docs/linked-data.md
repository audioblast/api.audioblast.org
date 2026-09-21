# References and relationship RDF

The references, links, taxa, specimens, locations, descriptions,
vernacularnames and onomatopoeia modules support `output=JSON-LD` and
`output=Turtle`. When `output` is absent, `Accept: application/ld+json` or
`Accept: text/turtle` selects RDF. JSON remains the default. Existing filters
and pagination apply; RDF pages advertise the next page in a Link header.

Records resolve at `/reference/{source}/{id}`, `/link/{source}/{id}`,
`/taxon/{source}/{id}`, `/specimen/{source}/{id}`, `/location/{source}/{id}`,
`/description/{source}/{id}`, `/vernacular-name/{source}/{id}` and
`/onomatopoeia/{source}/{id}`. These reuse
the existing prepared record lookup,
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
Relationship predicates are emitted exactly as stored. BioAcoustica's links from
a classification term to a work treating the taxon were corrected at source in
September 2026: they now use IAO is about with a
`referenceContent#TaxonomicTreatment` qualifier and the page in remarks, rather
than dwc:namePublishedInID, which that field did not support.
dwc:namePublishedInID remains available to a source that can assert it.
Prefix compaction changes only the spelling of an IRI, not its identity.

Lookups use prepared parameters for the returned records' exact type/source/id,
in batches of at most 100 records, once for each direction. The asserting source
is not restricted to the records' source. Duplicate assertions found from both
sides or across batches are included once by their source/id. Pagination continues
to count records, not the additional graph nodes. A failed relationship query
returns HTTP 500 and an empty RDF graph rather than silently returning incomplete
data. Empty pages and JSON responses do not query relationships. No schema changes
or per-pair views are required.

A link is a record with an id of its own, so it can be the subject of another
link: a link that a reference established is the subject of a `dcterms:source`
link to that reference, which is the predicate a trait already uses to cite the
paper its value came from. A source gives that reference as a column of its
links and the ingest makes the link of it, so nothing has to know how a link's
id is made. The citation is a link like any other and is found and served the
same way.

Links carry the links about them, as every other record does, so a relationship
that a reference established **says so on the statement itself**:
`dcterms:source` beside its `rdf:subject`, `rdf:predicate` and `rdf:object`.
`dwc:relationshipAccordingTo` still names the source that asserts the link,
which is the dataset; the reference is what established the relationship, and a
reader of the statement alone should not have to go looking for it.

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

## Specimens

`/data/specimens/?output=JSON-LD` (or `output=Turtle`) describes the specimens
and observations that recordings are of as Darwin Core `dwc:Occurrence`
resources, and the individual route is `/specimen/{source}/{id}`. The table's
columns are already named after the Darwin Core terms they hold, so each is
emitted as its own `dwc:` property, and `dwc:occurrenceID` is the record's own
URI: it is the identifier this API can be asked for the occurrence by, and no
source-local ID is copied into the RDF.

Coordinates and the individual count are typed as decimals, and a record with
both coordinates gets `dwc:geodeticDatum` EPSG:4326, which is what decimal
latitude and longitude mean here. Whole and partial ISO dates are typed;
`eventDate` and `dateIdentified` strings that are neither are kept as given, as
normalisation belongs in the ingest. What a source calls a sex, a life stage or
a type status is left as it is: these are not mapped to a controlled vocabulary.
Empty values are omitted rather than asserted as blank.

The recording a specimen was recorded in, and the taxon it is identified as,
are relationships in the links table, not columns:
`ac:associatedSpecimenReference` from the recording and `dwciri:toTaxon` from
the specimen. They appear in specimen responses in the usual way, incoming ones
under @reverse, along with the two filtered `/data/links/` discovery URLs and
the specimen's page at its source under `rdfs:seeAlso`.

## Ecological interactions

One taxon eating, parasitising or listening for another is a relationship, so
an interaction is a **link**, not a record of its own: the interaction is the
predicate and the two taxa are the subject and object. bio.acousti.ca's nine
use three terms of audioBLAST's own —
`cv/interaction#AcousticallyOrientatingPredatorOf`,
`#AcousticallyOrientatingParasiteOf` and `#RespondsToAlarmCallOf` — because no
other standard names them. The rest of that vocabulary is the Relation
Ontology's biotic interactions (`eats`, `pollinates`, `is parasite of`), which
should take their RO IRIs when something uses them.

Any term of `https://vocab.audioblast.org/cv/interaction#` is accepted as a
predicate, by namespace rather than one at a time, since an interaction is a
relationship by definition. Other namespaces of the vocabulary are not: a
`referenceContent` term says what a link is *of*, not what it *is*.

The `/data/ecoint/` endpoint is a view of those links, flattened to a taxon,
what it interacts with, the interaction and the reference. It serves no RDF of
its own — querying `/data/links/` by an interaction predicate gives the same
relationships, with their assertions, and serves them as RDF.

## Descriptions

`/data/descriptions/?output=JSON-LD` (or `output=Turtle`) describes what a
source says about something in prose as `dcmitype:Text` resources, and the
individual route is `/description/{source}/{id}`. The prose is `dc:description`
and the topic `dc:type`, as GBIF's Taxon Description extension gives them.

A topic is its source's own word, kept as `dc:type`, and `dcterms:type` is the
Species Profile Model info item that word names: `behaviour` is
`SPMInfoItems#Behaviour`, `diagnostic` and `general` its `DiagnosticDescription`
and `GeneralDescription`. That is not a mapping invented here — a Scratchpads
species profile implements the Species Profile Model, so its fields are that
model's info items, and the ingest recovers the identity the data already had.
A topic the model has no item for gets no IRI rather than an invented one.

**What a description is about, and the references it rests on, are links**, so
they reach the graph the way every other relationship does: IAO is about for
the taxon, `dcterms:source` for a reference. That is the reason a description
is a record rather than a detail. bio.acousti.ca writes its citations into the
prose as `[bib]12290[/bib]`, and the export reads each one out as a link and
takes it out of the sentence, so the text reads as written and the paper behind
it is still there to follow.

TDWG marks the Species Profile Model "no longer under development" and no
longer recommends it. Nothing has replaced it, its concepts still carry their
definitions, and GBIF's Taxon Description extension and the Encyclopedia of
Life both name a description's topic with them, so the choice was between a
resolvable IRI and an unmatchable English word. See
[vocabulary-backlog.md](vocabulary-backlog.md) for what would have to happen to
replace it.

## Locations

`/data/locations/?output=JSON-LD` (or `output=Turtle`) describes the places
records were made or collected at as Darwin Core `dwc:Location` resources, and
the individual route is `/location/{source}/{id}`, which is also the place's
`dwc:locationID`. A place is described once however many records share it, and
**which records are of it is a link**, `dwciri:inDescribedPlace`, not a column:
the recordings and specimens made there appear under `@reverse` as usual, and
the two filtered `/data/links/` discovery URLs are in `rdfs:seeAlso` beside the
place's page at its source.

The administrative units a place sits in are its own `dwc:` properties
(`continent`, `countryCode`, `stateProvince`, `county`, `island`,
`islandGroup`, `locality`), and its name is `rdfs:label`, since Darwin Core has
no term for what a source calls a place. Those names are kept as the source
gives them and are not matched to a gazetteer: `Asia-Temperate` and
`Australasia` are the TDWG geographical scheme's own names, not ours.

Coordinates, uncertainty and the two elevations are typed decimals, and an
elevation below sea level is negative. A place with coordinates and no datum
gets `dwc:geodeticDatum` EPSG:4326, which is what decimal latitude and
longitude mean; a source that gives its own datum keeps it.

A recording still carries its own `dwc:countryCode` and `dwc:locality`, because
most sources have no place records at all. Where a recording has both, they
agree: the export fills them from the same place.
## Vernacular names

`/data/vernacularnames/?output=JSON-LD` (or `output=Turtle`) describes the
names a taxon is known by in a language as
`http://rs.gbif.org/terms/1.0/VernacularName` resources, and the individual
route is `/vernacular-name/{source}/{id}`. The name is `dwc:vernacularName`,
its locality `dwc:locality` and its remarks `dwc:taxonRemarks`.

The name is a literal in the language it is in, e.g. `"le Criquet des pins"@fr`,
so that a client can take the names it reads as it takes any other labelled
text, and the tag is given as `dcterms:language` as well for a client that
wants it on its own, typed `xsd:language`, whose lexical space is BCP 47
syntax, as onomatopoeia give theirs. The ingest normalises a source's language
to an IETF BCP
47 tag; the API leaves the tag off a literal whose language it cannot read,
rather than writing RDF that isn't well formed. A name whose source never
recorded a language is a plain literal with no `dcterms:language`: none is
inferred from the name, so the English-looking names that bio.acousti.ca holds
without one stay untagged until the source says.

A name is as its source gives it, with the article a reference wrote it with
(`le Criquet des pins`, `The Long-winged Conehead`). No name at bio.acousti.ca
is held both with an article and without, so stripping one would change the
data rather than make it consistent.

The taxon a name names, and the reference it was taken from, are relationships
in the links table, not columns. Darwin Core has no property that takes a taxon
for a vernacular name — `dwc:vernacularName` takes the name itself — and
`dwc:relationshipOfResourceID` asks for an OBO relation, so the taxon predicate
is `IAO:0000219` (denotes): a name is made to pick out the thing it names,
which is what denotation is. It is a subproperty of `IAO:0000136` (is about),
so a name is still about its taxon under entailment, though a `/data/links/`
query by predicate is a literal match and will not find it under is-about. The
reference predicate is `dcterms:source`, as for trait values taken from
references.

Both appear in vernacular name responses in the usual way. Links are looked up
by the record at either end rather than by predicate, so the names of a taxon
are on the taxon's own response as incoming `IAO:0000219` assertions under
@reverse, next to the recordings and trait values that are about it.

### Names on the taxon

A taxon also carries the names themselves, as `dwc:vernacularName`, which is
what Darwin Core defines on `dwc:Taxon`, each a literal in the language it is
in:

```turtle
<https://api.audioblast.org/taxon/bio.acousti.ca/72>
    a dwc:Taxon ;
    dwc:scientificName "Decticus verrucivorus" ;
    dwc:vernacularName "Warzenbeißer"@de, "Wrattenbijter"@nl, "The Wartbiter"@en .
```

so a client reading a taxon has the names without following a link for each.
The name records stay linked as well, since they hold what a name alone does
not: where it is used, its remarks and the reference it was taken from.

Only a name that **denotes** the taxon is read onto it. A link that merely says
a name is about a taxon stays a link, as it does not say the taxon is called
that.

This is the `rdf.embed` callback, which any module may declare: it is given the
links already found for a page of records and returns nodes to merge onto them,
so it needs no lookup to know what to read. `rdfRecordsByID()` then fetches
those records in batches of 100, binding every value, so a page costs a bounded
number of queries rather than one per name. A failed lookup propagates as a
failure rather than as a taxon with no names, and a page with nothing linked
makes no extra query at all.

## Taxa that are the same taxon

audioBLAST! holds a taxon once for every source that knows it, each row with the
classification its own source gives it. Two such rows share a name and nothing
else, and a name is not an identity: bio.acousti.ca and iNaturalist both hold
*Aepyceros*, one putting it in Aepycerotinae and the other in Antilopinae, and
until now nothing said they were about the same animal. Querying by name was the
only join there was, which has already broken a client — audioblast.org's search
assumed `/data/taxa/?taxon=X` returned one row, and stopped working when
iNaturalist's taxa were added.

The Catalogue of Life is held as a source of its own, and each row of every other
source is matched to the row of it that is the same taxon. The match is a link
like any other: subject the taxa row, predicate
`http://www.w3.org/2004/02/skos/core#exactMatch`, object the catalogue's row,
with the name that was matched, any caveat, and the release it was matched
against in the remarks. Rows matched to the same row are the same taxon.

The catalogue's rows and the links are written by one import, `colR()` in
[audioBlastIngest](https://github.com/audioblast/audioBlastIngest), because they
are one fact: taxonBot was a set of Catalogue of Life rows that nothing
refreshed, and its ids have been quietly becoming synonyms ever since. Rows and
links written together, against the same release, cannot drift apart like that.
Each of the catalogue's own rows carries its address in the catalogue as a
further `skos:exactMatch` to an `iri`, so what audioBLAST! holds still joins to
anything else citing the same taxon.

The rows the catalogue holds are the taxa matched and every taxon above them, so
the classification is a tree that can be walked by `parent_id` rather than the
nine ranks the taxa table has columns for. *Acridoidea* is a superfamily and has
no column, and neither iNaturalist nor bio.acousti.ca can place it; its parent
can.

A taxon's RDF says which rows those are, rather than leaving a client to gather
every match of a taxonomy it may not hold:

```turtle
<https://api.audioblast.org/taxon/bio.acousti.ca/7785>
    a dwc:Taxon ;
    dwc:scientificName "Aepyceros" ;
    dwc:subfamily "Aepycerotinae" ;
    skos:exactMatch <https://api.audioblast.org/taxon/CoL/PQQ>,
        <https://api.audioblast.org/taxon/iNaturalist/42277> .

<https://api.audioblast.org/taxon/CoL/PQQ>
    a dwc:Taxon ;
    dwc:scientificName "Aepyceros" ;
    skos:exactMatch <https://api.checklistbank.org/dataset/3LR/taxon/PQQ> .
```

The catalogue's row stays on the row as well as the equivalent row: it is what
makes the two equivalent, and it is the row a client follows to read the
classification that placed them there. `skos:exactMatch` is transitive and
symmetric, so saying the two source rows match each other adds nothing that the
two matches did not already entail; it only saves the client the join.

Nothing a source gives is changed or chosen between. Each row keeps its own
classification, so a client reading both *Aepyceros* rows sees the disagreement
rather than a table that has settled it. Whether to show one of them by default
is a question for each client, and a reversible one.

This shares the `rdf.embed` callback with the vernacular names above. The links
already found for the page give the taxa that the requested rows are matched to,
and one further query finds every row matched to those same taxa, in batches of
100 with every value bound. A page whose rows are matched to nothing — an
undescribed species no catalogue has a name for, say — makes no extra query, and
a row matched to a taxon no other row reaches is not said to be equivalent to
itself. A failed lookup propagates as a failure rather than as a taxon with no
equivalents.

Names that are not determinations are deliberately left unmatched: `Albanycada
"sp. 01"`, `Ancylecha sp.`, `Ephippiger ?ephippiger`, `Anaxyrus americanus ×
fowleri`. These are undescribed taxa, determinations their author doubted, and
hybrids, and forcing them onto the nearest described species would state
something false. They stay source-local, which is the right outcome for them.

## The taxa a taxon is inside

A taxa row holds the taxon directly above it in `parent_id` and no more, so a
breadcrumb — the query every page of a taxon browser makes — costs a request for
each step up to the root, each one waiting on the one before it. The rank
columns do not answer it: a source's tree can put a superfamily or an infraorder
between the ranks they hold, and those are the ranks the Catalogue of Life spine
was worth having for.

A taxon asked for at its own address carries the whole walk:

```turtle
<https://api.audioblast.org/taxon/bio.acousti.ca/9959>
    a dwc:Taxon ;
    dwc:scientificName "Gryllotalpa vineae" ;
    skos:broader <https://api.audioblast.org/taxon/bio.acousti.ca/9958> ;
    dwc:higherClassification "Eukaryota|Animalia|Arthropoda|Hexapoda|Insecta|Orthoptera|Ensifera|Gryllidea|Gryllotalpoidea|Gryllotalpidae|Gryllotalpinae|Gryllotalpini|Gryllotalpa" .

<https://api.audioblast.org/taxon/bio.acousti.ca/9958>
    a dwc:Taxon ;
    dwc:scientificName "Gryllotalpa" ;
    dwc:taxonRank "Genus" ;
    skos:broader <https://api.audioblast.org/taxon/bio.acousti.ca/9957> .
```

Each taxon above is described as it is on its own page, so nothing further has
to be fetched to draw the chain, and each is `skos:broader` of the one below it.
A taxa row is a source's own taxon concept, which is how these responses already
speak of it in saying that two rows are the same taxon, so what a row is inside
is broader in that source's tree, said of the row rather than of the name.
Nothing is claimed to be transitive: a source is free to put whatever it likes
between two ranks. `dwc:higherClassification` says the same walk the way Darwin
Core says it, the names above the taxon with the highest first.

It is only on the record page, `/taxon/{source}/{id}`. A page of fifty taxa
would walk fifty trees, so `/data/taxa/?output=Turtle` gives each row's
`parent_id` and no more; `/data/taxa/classification/?source=…&id=…` gives the
same walk as JSON.

A walk that cannot reach the root — a taxon inside itself, a parent the source
has lost — still gives what it reached, since each `skos:broader` is true on its
own, but `dwc:higherClassification` is then left out: it would read as the whole
classification, and there is nothing in RDF to note that it is not. The JSON
endpoint says so in its notes instead.

## Onomatopoeia

`/data/onomatopoeia/?output=JSON-LD` (or `output=Turtle`) describes the words a
source renders an animal's sound with — `bow-wow`, `kikeriki`, `bark`, and the
mnemonic a birdwatcher remembers a song by — and the individual route is
`/onomatopoeia/{source}/{id}`. The word is `rdfs:label` in the language it is
in, its kind `dc:type`, and `dwc:sex`, `dwc:lifeStage`, `dwc:locality` and
`dwc:taxonRemarks` hold what else the source says about it.

**These are not vernacular names, though they have a name's shape.** A
vernacular name denotes its taxon, and denoting is what reads a name onto a
taxon as one of the names it is known by. A rendering denotes the sound
instead: `bark` picks out what a dog does, not the dog, and `Get the beer
check` is a way of remembering a vireo's song rather than anything the bird is
called. So the link to the taxon is `IAO:0000136` (is about) and never
`IAO:0000219` (denotes). That one choice is what keeps `bark` out of the
`dwc:vernacularName` of *Canis lupus familiaris*: the taxon's `rdf.embed`
callback asks for what denotes the taxon, and being about it is not that. The
reference a rendering was taken from is `dcterms:source`, as for vernacular
names and trait values. Both appear in the usual way, and the two filtered
`/data/links/` discovery URLs are in `rdfs:seeAlso` beside the rendering's page
at its source.

Every rendering is a `dcmitype:Text`. One whose kind names a class is also an
`ontolex:LexicalEntry`, since the classes a kind can name are word classes, so
a line of musical notation is never published as a word of some language. The
word is `rdfs:label` rather than `ontolex:writtenRep`, whose domain is
`ontolex:Form` rather than an entry and whose range is `rdf:langString`, which
a rendering with no language could not satisfy. No standard has a property for
the word a sound is rendered with, and `dwc:vernacularName` takes a name for
the taxon, which this is not.

A kind is its source's own word, kept as `dc:type`, and `dcterms:type` is the
class that word names: `imitation` and `onomatopoeia verb` are both
`http://purl.org/olia/olia.owl#OnomatopoeticWord`, differing in their part of
speech rather than in being onomatopoeic, and the source's own word keeps the
difference. OLiA is the only vocabulary that names these at all — Darwin Core,
the GBIF vernacular name extension, Audiovisual Core, OntoLex-Lemon, lexinfo,
Plinian Core and every ontology the EBI's lookup service indexes have no term
for onomatopoeia, and no Darwin Core issue has ever proposed one (checked
2026-09-20). Nothing names a mnemonic or musical notation, so those get no IRI
rather than an invented one; see [vocabulary-backlog.md](vocabulary-backlog.md).

The language is `dcterms:language`, typed `xsd:language`, whose lexical space
is BCP 47 syntax. A rendering whose source never recorded a language is a plain
literal with none: the two that the Lokele of the Congo use name a people
rather than a language, which neither ISO 639-3 nor Glottolog registers under
that name, and musical notation is in no language at all. None is inferred from
the word. The ingest normalises a tag to the case BCP 47 writes it in, so
`en-gb` is `en-GB`, which keeps the column consistent without claiming that
British and unmarked English are the same.

## Annotation regions of interest

`/data/annomate/?output=JSON-LD` (or `output=Turtle`) describes annotations as
Audiovisual Core `ac:RegionOfInterest` resources. The individual route is
`/annotation/{source}/{annotation_id}` and also negotiates RDF through Accept.
Ordinary JSON retains `annotation_id` and `source_id` with their existing meanings.
Modules may set `rdf.id` to select an existing ID parameter; the default is `id`.

Each ROI uses `ac:startTime` and `ac:endTime` for offsets in seconds, and
`ac:isROIOf` to identify `/recording/{source}/{source_id}`. The annotation graph
also includes the recording's inverse `ac:hasROI` relationship and, when supplied,
a service access point holding its `ac:accessURI`. This does not fetch or expand all annotations in recording
responses. Missing recording identifiers do not produce a fabricated recording URI.

Annotator, annotation date, information URL, taxon name and annotation category
use `dcterms:creator`, `dcterms:created`, `rdfs:seeAlso`, `dwc:scientificName` and
`dc:type`. Latitude and longitude use the corresponding Darwin Core decimal
properties. Numeric bounds and coordinates are typed as decimals; zero values
are retained. Date-only ISO dates are typed; other source date strings and
nondecimal source values are retained as literals without correction.
`contact` remains available in JSON but is not mapped to RDF. There are no
frequency bounds in this table. No new vocabulary terms are needed.

Incoming and outgoing links use the existing module type `annomate` and
`annotation_id` as their source-local identity. Link predicates remain unchanged.
See the [Audiovisual Core ROI terms](https://ac.tdwg.org/termlist/#ac_RegionOfInterest).

## Recording representations and service access points

Recording RDF links through `ac:hasServiceAccessPoint` to a separate
`ac:ServiceAccessPoint` node. `ac:accessURI` and the literal MIME type (`dc:format`)
are on that representation node, not on the recording or annotation ROI. Ordinary
JSON and the existing flat `format=ac` output are unchanged.

The current source schema supplies one recording file. Future audio variants,
spectrograms and thumbnails can each have their own access point, including
several representations with the same MIME type. No future representations or
quality labels are fabricated. Recording-level title, creator, taxon, capture
date, duration, rights and information page stay on the recording: the current
schema does not provide separate representation-specific values for these.

The recording's sample rate, rights holder and place use the terms Audiovisual
Core borrows for them: `mo:sample_rate` from the Music Ontology, `xmpRights:Owner`
for who holds the rights, and `dwc:countryCode` and `dwc:locality` for where the
recording was made, which is not always where a specimen was collected.
Audiovisual Core has no term for the number of channels, so `channels` stays in
JSON only rather than being given an invented property.

Until sources supply stable representation IDs, access-point fragment IDs use a
SHA-256 digest of the exact URL, scoped to the recording URI. Recording and
annotation responses share an access-point identity when both recording identity
and URL match. Different URLs stay distinct; there is no URL normalization or
assumption that their encodings or timelines match. A URL change changes this
fallback identity. Source representation IDs should replace this fallback when
a representation inventory is available; that will require an identity migration.

A MIME-only record retains a partial access-point description without inventing
an access URL. Its fallback identity is scoped to the recording and MIME value;
this does not identify multiple same-format representations without URLs. An
annotation with no valid recording URL adds no access-point description. MIME
values are never inferred from filename extensions. No new vocabulary terms are
needed. See the [Audiovisual Core service access point vocabulary](https://ac.tdwg.org/termlist/#7-11-service-access-point-vocabulary).

Annotations still describe regions of the recording. Any future spectrogram
pixel coordinates must identify the particular image representation. Recording
responses still do not query annotations directly; discovery awaits links-table
relationships.

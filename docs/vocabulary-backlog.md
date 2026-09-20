# Vocabulary terms to add later

Keep existing IRIs stable. Publish definitions in vocab.audioblast.org when ready;
missing terms do not block reference or relationship RDF. Do not substitute
glossary terms. No vocabulary changes have been made by this implementation.

The vocabulary MCP was checked on 2026-09-19. Its list_vocabularies response
did not include referenceContent or topic; searches for Oscillogram and
Acoustic Behaviour found no matching definitions. The exact IRIs below were
collected from the local links-export/links.csv snapshot (19595 rows).
This is an export inventory, not a check of the current production database.

| Pending IRI | Used as |
| --- | --- |
| https://vocab.audioblast.org/cv/referenceContent#AcousticBehaviour | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#AcousticallyOrientatingPredatorDescription | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#BurrowEntrancePhotograph | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#FemaleBehaviourDescription | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#FluidExpulsionDescription | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#LaryngealMorphologyDescription | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#LaryngealMorphologyImage | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#Oscillogram | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#PlectrumPhotograph | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#Sonagram | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#SongDescription | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#Spectrogram | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#StridulatoryApparatusDescription | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#StridulatoryFilePhotograph | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#StridulatoryFileSEMImage | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#StridulatoryPositionPhotograph | relationship qualifier |
| https://vocab.audioblast.org/cv/referenceContent#TaxonomicTreatment | relationship qualifier |
| https://vocab.audioblast.org/cv/topic#AnthropogenicNoise | relationship target |
| https://vocab.audioblast.org/cv/topic#AudioCollections | relationship target |
| https://vocab.audioblast.org/cv/topic#AutomatedIdentification | relationship target |
| https://vocab.audioblast.org/cv/topic#ClimateChange | relationship target |
| https://vocab.audioblast.org/cv/topic#Dipteran | relationship target |
| https://vocab.audioblast.org/cv/topic#Ecoacoustics | relationship target |
| https://vocab.audioblast.org/cv/topic#EducationalResources | relationship target |
| https://vocab.audioblast.org/cv/topic#Ethnobioacoustics | relationship target |
| https://vocab.audioblast.org/cv/topic#FreshwaterSoundscapes | relationship target |
| https://vocab.audioblast.org/cv/topic#HostBehaviour | relationship target |
| https://vocab.audioblast.org/cv/topic#KraussOrgan | relationship target |
| https://vocab.audioblast.org/cv/topic#MachineLearning | relationship target |
| https://vocab.audioblast.org/cv/topic#MarineSoundscapes | relationship target |
| https://vocab.audioblast.org/cv/topic#Metadata | relationship target |
| https://vocab.audioblast.org/cv/topic#Methods | relationship target |
| https://vocab.audioblast.org/cv/topic#NoisePollution | relationship target |
| https://vocab.audioblast.org/cv/topic#NoiseRemoval | relationship target |
| https://vocab.audioblast.org/cv/topic#Orthopteran | relationship target |
| https://vocab.audioblast.org/cv/topic#Parasitoid | relationship target |
| https://vocab.audioblast.org/cv/topic#PassiveAcousticMonitoring | relationship target |
| https://vocab.audioblast.org/cv/topic#RainfallDetection | relationship target |
| https://vocab.audioblast.org/cv/topic#Reproduction | relationship target |
| https://vocab.audioblast.org/cv/topic#SoilSoundscapes | relationship target |
| https://vocab.audioblast.org/cv/topic#SongEvolution | relationship target |
| https://vocab.audioblast.org/cv/topic#SoundPropagation | relationship target |
| https://vocab.audioblast.org/cv/topic#SoundscapeVisualisation | relationship target |
| https://vocab.audioblast.org/cv/topic#Soundscapes | relationship target |
| https://vocab.audioblast.org/cv/topic#Terminology | relationship target |
| https://vocab.audioblast.org/cv/renderingType#Mnemonic | kind of a rendering |
| https://vocab.audioblast.org/cv/renderingType#MusicalNotation | kind of a rendering |

## Curation notes

- referenceContent terms describe what a reference contains about its target
  taxon; AcousticBehaviour is used for the general acoustic-behaviour tags.
- topic terms are the non-biological topics attached to publications.
- These are planned concepts. Confirm labels, definitions, hierarchy and
  supporting references when publishing them. The table records identifiers
  already used in the export, rather than new definitions.
- renderingType terms are the kinds of rendering that no vocabulary names, so
  onomatopoeia rows of those kinds carry no `dcterms:type` until these exist.
  A mnemonic is the one that will grow: birdwatchers have many, and only one is
  in the data so far. When minted, each should be `skos:exactMatch` to the
  Wikidata item it names (`Q191062` mnemonic, `Q233861` musical notation), as
  description topics would be to their info item.
- Call-type and page detail remains in relationship remarks where the export
  puts it; do not create a compound term solely for those remarks.
- No new audioBLAST property is introduced by the reference and taxon RDF
  implementation. Link predicates are preserved as supplied.
- Extend this list whenever another missing vocabulary term is encountered.

## Source correction made

The links export used http://rs.tdwg.org/dwc/terms/namePublishedInID for the
Reference on a classification term. That field holds any work treating the
taxon, from a revision to a checklist, so most of those 266 links did not record
a name publication. It was corrected at source in September 2026: the links now
say that the reference is about the taxon (IAO:0000136), qualified
referenceContent#TaxonomicTreatment, with the page in remarks. The API still
emits whatever predicate a source supplies, and namePublishedInID remains
available to a source that can assert it, such as the Orthoptera Species File.
No NamePublishedIn property is requested for the audioBLAST vocabulary.

## Deprecated vocabulary in use

A description's topic is served as the Species Profile Model info item it
names, e.g. `http://rs.tdwg.org/ontology/voc/SPMInfoItems#Behaviour`. TDWG
marks that ontology "no longer under development" and no longer recommends it.

It is used anyway because nothing has replaced it: there is no successor to
migrate to, the concepts still carry their definitions, and GBIF's Taxon
Description extension and the Encyclopedia of Life both name a description's
topic with them. A Scratchpads species profile implements the model, so its
fields are these info items; the alternative was an English word that nothing
could match.

If audioBLAST ever mints description-topic terms of its own, each should be
`skos:exactMatch` to the info item it replaces, and the ingest's
`spmInfoItem()` is the one place that decides which IRI a topic names.

## Terms not needed

Specimens and the recordings columns added with them are covered by existing
standards: Darwin Core occurrence terms, ac:associatedSpecimenReference,
dwciri:toTaxon, mo:sample_rate and xmpRights:Owner. The number of channels of a
recording has no Audiovisual Core term and is left in JSON rather than given an
audioBLAST property. The names of details (tape, cd_track, temperature_start and
the rest) are each source's own and are to be matched to vocabulary terms later;
until then details are not published as RDF, so they need no IRIs yet.

Vernacular names are covered as well. A name is a
http://rs.gbif.org/terms/1.0/VernacularName holding dwc:vernacularName,
dcterms:language, dwc:locality and dwc:taxonRemarks.

Darwin Core's vernacularName takes the name, not the taxon, so no Darwin Core
property joins the two, and dwc:relationshipOfResourceID asks for an OBO
relation (checked against dwc.tdwg.org/terms on 2026-09-20). The link to the
taxon is therefore IAO:0000219 (denotes), which is defined as the relation
holding when an information content entity is made to pick something out. It
is a subproperty of IAO:0000136 (is about), so a name is still about its taxon
under entailment; links queries match a predicate literally, so a query by
is-about does not return vernacular names. No new audioBLAST property is
requested for this.

The name itself is a literal in the language it is in (an IETF BCP 47 language
tag, which the ingest normalises and the API leaves off a literal it cannot
read) rather than a new property. A language is never inferred from a name in
the API; where a source gives none, the name has none.

Onomatopoeia are covered too, apart from the two renderingType terms above. A
rendering is a `http://purl.org/dc/dcmitype/Text`, and one whose kind names a
word class also a `http://www.w3.org/ns/lemon/ontolex#LexicalEntry`, holding
`rdfs:label`, `dc:type`, `dcterms:type`, `dcterms:language`, `dwc:sex`,
`dwc:lifeStage`, `dwc:locality` and `dwc:taxonRemarks`.

No standard has a property for the word a sound is rendered with, so the word
is `rdfs:label`: `dwc:vernacularName` takes a name for the taxon, which a
rendering is not, and `ontolex:writtenRep` has `ontolex:Form` for its domain
and `rdf:langString` for its range, so it needs a second node per record and
cannot carry a rendering that has no language. No audioBLAST property is
requested for this.

Of the vocabularies searched on 2026-09-20 — Darwin Core and its extensions,
the GBIF VernacularName extension, ColDP, Audiovisual Core, the Species Profile
Model, OntoLex-Lemon, lexinfo 2.0 and 3.0, SKOS, Dublin Core, Plinian Core,
Wikidata and every ontology in the EBI's lookup service — only OLiA names
onomatopoeia at all, as `olia:OnomatopoeticWord` and `olia:Ideophone`. Neither
is ideal: OnomatopoeticWord carries "no definition given" and sits under
`olia:Residual`, and Ideophone's `rdfs:isDefinedBy` points at ISOcat, which is
gone. They are used because they are `owl:Class`, as the info items
`dcterms:type` points at elsewhere are, and because the alternative was an
English word nothing could match. Wikidata `Q170239` is the better-defined
concept and is the `lexicalCategory` of 127 lexemes; it is the `skos:exactMatch`
to record if OLiA is ever replaced.

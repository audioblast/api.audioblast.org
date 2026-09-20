# Vocabulary terms to add later

Keep existing IRIs stable. Publish definitions in vocab.audioblast.org when ready;
missing terms do not block reference or relationship RDF. Do not substitute
glossary terms. No vocabulary changes have been made by this implementation.

The vocabulary MCP was checked on 2026-09-20. Its list_vocabularies response
did not include referenceContent or topic; searches for Oscillogram, Acoustic
Behaviour and taxonomic found no matching definitions. The exact IRIs below were
collected from links.csv on audioblast_ingest main (242af5e, 21172 rows).
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

## Curation notes

- referenceContent terms describe what a reference contains about its target
  taxon; AcousticBehaviour is used for the general acoustic-behaviour tags.
- topic terms are the non-biological topics attached to publications.
- TaxonomicTreatment is what a reference holds about a taxon when BioAcoustica's
  classification cites it: a revision, a checklist, a description or any other
  treatment of that taxon. It does not say the name was published there, and it
  is not a nomenclatural act. The page the site gives stays in the relationship
  remarks.
- These are planned concepts. Confirm labels, definitions, hierarchy and
  supporting references when publishing them. The table records identifiers
  already used in the export, rather than new definitions.
- Call-type and page detail remains in relationship remarks where the export
  puts it; do not create a compound term solely for those remarks.
- No new audioBLAST property is introduced by the reference and taxon RDF
  implementation. Link predicates are preserved as supplied.
- Extend this list whenever another missing vocabulary term is encountered.

## Source correction made

The links export used http://rs.tdwg.org/dwc/terms/namePublishedInID for the
reference a taxon's BioAcoustica classification cites. That field holds whatever
work treats the taxon, so the name was often not published there, and the export
(audioblast_ingest 242af5e) now gives those 266 links as the reference being
http://purl.obolibrary.org/obo/IAO_0000136 (is about) the taxon, qualified as a
TaxonomicTreatment, with the page in the relationship remarks. The API was not
changed: predicates are still emitted as supplied. No NamePublishedIn property
is requested for the audioBLAST vocabulary; namePublishedInID remains available
to any source that can assert it correctly.

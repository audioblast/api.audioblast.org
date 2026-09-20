"""Compare locally generated RDF outputs; requires rdflib. No network access."""
import sys
from pathlib import Path
from rdflib import Graph, Namespace, URIRef, Literal
from rdflib.compare import isomorphic

base = Path(sys.argv[1])
a = Graph().parse(base / "linked-data.jsonld", format="json-ld")
b = Graph().parse(base / "linked-data.ttl", format="turtle")
assert isomorphic(a, b), "JSON-LD and Turtle differ"
RDF = Namespace("http://www.w3.org/1999/02/22-rdf-syntax-ns#")
DWC = Namespace("http://rs.tdwg.org/dwc/terms/")
FOAF = Namespace("http://xmlns.com/foaf/0.1/")
reference = "https://api.audioblast.org/reference/fixture/book/a%20%231"
assert (URIRef(reference + "#authors"), RDF._2, URIRef(reference + "#author-2")) in a
assert (URIRef(reference + "#author-2"), FOAF.name, Literal("{Natural History Museum}")) in a
assert (URIRef("https://api.audioblast.org/link/curator/link1"), DWC.relationshipRemarks, Literal("p. 12")) in a
assert (URIRef("https://api.audioblast.org/link/curator/link2"), DWC.relationshipRemarks, Literal("p. 14")) in a
assert (URIRef(reference), URIRef("http://purl.obolibrary.org/obo/IAO_0000136"), URIRef("https://api.audioblast.org/taxon/other-source/42")) in a
print(f"Equivalent RDF graphs: {len(a)} triples; author order and distinct assertions verified")

assert (URIRef("https://api.audioblast.org/recording/fixture/rec1"), URIRef("http://purl.org/dc/terms/isReferencedBy"), URIRef(reference)) in a
assert (URIRef("https://api.audioblast.org/link/curator/citation"), DWC.relationshipRemarks, Literal("p. 7")) in a
print("Embedded recording-reference relationship and assertion metadata verified")

before = Graph().parse(base / "framing-before.ttl", format="turtle")
after_json = Graph().parse(base / "framing-after.jsonld", format="json-ld")
after_turtle = Graph().parse(base / "framing-after.ttl", format="turtle")
assert isomorphic(before, after_json) and isomorphic(before, after_turtle), "Framing changed the graph"
for local in ("referenceID", "resourceRelationshipID", "resourceID", "relatedResourceID", "relationshipOfResourceID", "taxonID", "measurementID"):
    assert not list(a.triples((None, DWC[local], None))), f"Tabular ID leaked into RDF: {local}"
print("Reverse framing preserves all triples; no redundant Darwin Core ID fields remain")

trait = URIRef("https://api.audioblast.org/trait/fixture/book/a%20%231")
assert (trait, URIRef("http://purl.org/dc/terms/source"), URIRef(reference)) in a
assert (trait, URIRef("http://purl.obolibrary.org/obo/IAO_0000136"), URIRef("https://api.audioblast.org/taxon/other-source/42")) in a
assert (URIRef("https://api.audioblast.org/recording/fixture/rec1"), URIRef("http://purl.org/dc/terms/relation"), trait) in a
assert (trait, DWC.measurementValue, Literal("12.5")) in a
print("Trait-reference, trait-taxon and incoming relationships verified")

taxon = URIRef("https://api.audioblast.org/taxon/fixture/book/a%20%231")
for kind in ("recording", "trait", "reference"):
    assert (URIRef(f"https://api.audioblast.org/{kind}/fixture/incoming"), URIRef("http://purl.obolibrary.org/obo/IAO_0000136"), taxon) in a
assert (taxon, URIRef("http://rs.tdwg.org/dwc/terms/namePublishedInID"), URIRef(reference)) in a
assert (URIRef("https://api.audioblast.org/link/curator/name-publication"), RDF.predicate, URIRef("http://rs.tdwg.org/dwc/terms/namePublishedInID")) in a
assert not list(a.triples((None, URIRef("https://vocab.audioblast.org/NamePublishedIn"), None)))
print("Taxon incoming links and unchanged source publication predicate verified")

AC = Namespace("http://rs.tdwg.org/ac/terms/")
roi = URIRef("https://api.audioblast.org/annotation/fixture/book/a%20%231")
recording = URIRef("https://api.audioblast.org/recording/fixture/rec1")
assert (roi, RDF.type, AC.RegionOfInterest) in a
assert (roi, AC.isROIOf, recording) in a
assert (recording, AC.hasROI, roi) in a
assert (roi, AC.startTime, Literal("0", datatype=URIRef("http://www.w3.org/2001/XMLSchema#decimal"))) in a
assert (roi, AC.endTime, Literal("1.25", datatype=URIRef("http://www.w3.org/2001/XMLSchema#decimal"))) in a
services = list(a.objects(recording, AC.hasServiceAccessPoint))
assert len(services) == 1
assert (services[0], RDF.type, AC.ServiceAccessPoint) in a
assert (services[0], AC.accessURI, URIRef("https://example.org/audio.wav")) in a
assert (services[0], URIRef("http://purl.org/dc/elements/1.1/format"), Literal("audio/wav")) in a
assert not list(a.objects(recording, AC.accessURI))
assert not list(a.objects(recording, URIRef("http://purl.org/dc/elements/1.1/format")))
assert not list(a.objects(roi, AC.accessURI))
print("Annotation ROI bounds, recording relationships and access metadata verified")

XSD = Namespace("http://www.w3.org/2001/XMLSchema#")
specimen = URIRef("https://api.audioblast.org/specimen/fixture/book/a%20%231")
assert (specimen, RDF.type, DWC.Occurrence) in a
assert (specimen, DWC.occurrenceID, Literal(str(specimen))) in a
assert (specimen, DWC.eventDate, Literal("1962-08", datatype=XSD.gYearMonth)) in a
assert (specimen, DWC.decimalLatitude, Literal("50.6", datatype=XSD.decimal)) in a
assert (specimen, URIRef("http://rs.tdwg.org/dwc/iri/toTaxon"), URIRef("https://api.audioblast.org/taxon/other-source/42")) in a
assert (recording, AC.associatedSpecimenReference, specimen) in a
print("Specimen occurrence, the taxon it is identified as and the recording of it verified")

MO = Namespace("http://purl.org/ontology/mo/")
XMP_RIGHTS = Namespace("http://ns.adobe.com/xap/1.0/rights/")
assert (recording, MO.sample_rate, Literal("44100", datatype=XSD.decimal)) in a
assert (recording, XMP_RIGHTS.Owner, Literal("Natural History Museum, London")) in a
assert (recording, DWC.countryCode, Literal("GB")) in a
assert (recording, DWC.locality, Literal("A place")) in a
print("Recording sample rate, rights holder and place verified")

place = URIRef("https://api.audioblast.org/location/fixture/p1")
assert (place, RDF.type, DWC.Location) in a
assert (place, DWC.locationID, Literal(str(place))) in a
assert (place, DWC.countryCode, Literal("GB")) in a
assert (place, DWC.minimumElevationInMeters, Literal("-5", datatype=XSD.decimal)) in a
assert (place, DWC.geodeticDatum, Literal("EPSG:4326")) in a
assert (recording, URIRef("http://rs.tdwg.org/dwc/iri/inDescribedPlace"), place) in a
print("Place, its units and the recording made there verified")

DC = Namespace("http://purl.org/dc/elements/1.1/")
description = URIRef("https://api.audioblast.org/description/fixture/12289")
assert (description, RDF.type, URIRef("http://purl.org/dc/dcmitype/Text")) in a
assert (description, DC.type, Literal("behaviour")) in a
assert (description, URIRef("http://purl.obolibrary.org/obo/IAO_0000136"), URIRef("https://api.audioblast.org/taxon/other-source/42")) in a
assert (description, URIRef("http://purl.org/dc/terms/source"), URIRef(reference)) in a
print("Description, its topic, its taxon and the reference it rests on verified")

services_json = Graph().parse(base / "service-access.jsonld", format="json-ld")
services_turtle = Graph().parse(base / "service-access.ttl", format="turtle")
assert isomorphic(services_json, services_turtle)
assert len(list(services_json.objects(recording, AC.hasServiceAccessPoint))) == 3
assert len(list(services_json.subjects(RDF.type, AC.ServiceAccessPoint))) == 3
print("Multiple audio/image service access points serialize equivalently")

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
assert (taxon, URIRef("http://rs.tdwg.org/dwc/terms/nameAccordingToID"), URIRef(reference)) in a
assert (URIRef("https://api.audioblast.org/link/curator/taxon-concept"), RDF.predicate, URIRef("http://rs.tdwg.org/dwc/terms/nameAccordingToID")) in a
assert not list(a.triples((None, URIRef("https://vocab.audioblast.org/NameAccordingTo"), None)))
print("Taxon incoming links and unchanged source predicate verified")

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

services_json = Graph().parse(base / "service-access.jsonld", format="json-ld")
services_turtle = Graph().parse(base / "service-access.ttl", format="turtle")
assert isomorphic(services_json, services_turtle)
assert len(list(services_json.objects(recording, AC.hasServiceAccessPoint))) == 3
assert len(list(services_json.subjects(RDF.type, AC.ServiceAccessPoint))) == 3
print("Multiple audio/image service access points serialize equivalently")

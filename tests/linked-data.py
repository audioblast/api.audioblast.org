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

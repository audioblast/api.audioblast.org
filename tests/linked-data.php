<?php
// No settings or real database are loaded. Run from the repository root.
require 'core/rdf.php';
require 'core/modules.php';
require 'core/record.php';
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
  if (!$condition) {throw new Exception($message);}
}
$module = loadModule('references');
$ref = array('source' => 'fixture', 'id' => 'book/a #1', 'type' => 'article',
  'title' => "A \"quoted\" title\nwith Unicode: Ã©", 'year' => '1922', 'month' => '',
  'author' => 'Darwin, Charles; {Natural History Museum}', 'editor' => 'Editor, One; Editor, Two',
  'journal' => 'Test journal', 'issn' => '1234-5678', 'journal_abbreviation' => 'Test J',
  'doi' => '10.1234/test#part', 'pmid' => '12345', 'keywords' => 'song; call',
  'attachments' => 'https://example.org/a.pdf; https://example.org/b.pdf',
  'info_url' => 'https://example.org/catalogue/1', 'url' => 'https://example.org/paper');
$uri = rdfRecordURI($module, $ref['source'], $ref['id']);
check($uri === 'https://api.audioblast.org/reference/fixture/book/a%20%231', 'URI encoding');
$nodes = rdfNodes($module, array($ref));
check($nodes[0]['dwc:referenceType'] === 'journalArticle', 'Darwin Core type');
check($nodes[0]['dcterms:issued']['@type'] === 'xsd:gYear', 'Year precision');
check(count($nodes[0]['dcterms:relation']) === 2, 'Attachments');
check(count($nodes[0]['dcterms:creator']) === 2, 'Distinct authors');
check(strpos(rdfJSONLD($nodes), '{Natural History Museum}') !== FALSE, 'Corporate author retained');
check(count($nodes[0]['rdfs:seeAlso']) === 6, 'Catalogue, incoming/outgoing links, identifiers');
$sparse = array('source' => 'fixture', 'id' => 'sparse', 'type' => 'unusual', 'year' => 'in press',
  'editor' => NULL, 'journal' => NULL);
$sparseNodes = rdfNodes($module, array($sparse));
check($sparseNodes[0]['dcterms:issued'] === 'in press', 'Unusual year retained');
check(count($sparseNodes) === 1, 'No empty related descriptions');
$nodes = array_merge($nodes, $sparseNodes);
$link = array('source' => 'curator', 'id' => 'link1',
  'subject_type' => 'references', 'subject_source' => 'fixture', 'subject_id' => $ref['id'],
  'predicate' => 'http://purl.obolibrary.org/obo/IAO_0000136',
  'object_type' => 'taxa', 'object_source' => 'other-source', 'object_id' => '42',
  'qualifier' => 'https://vocab.audioblast.org/cv/referenceContent#Oscillogram', 'remarks' => 'p. 12');
// Existing module callbacks remain compatible with the expanded graph writer.
foreach (array('recordings', 'traits') as $existing) {
  $definition = loadModule($existing);
  $row = array_fill_keys(array_keys($definition['params']), NULL);
  $row['source'] = 'fixture'; $row['id'] = 'existing';
  $nodes = array_merge($nodes, rdfNodes($definition, array($row)));
}
$links = loadModule('links');
$linkNodes = rdfNodes($links, array($link));
check($linkNodes[0]['rdf:subject']['@id'] === $uri, 'Shared reference identity');
check($linkNodes[0]['rdf:object']['@id'] === 'https://api.audioblast.org/taxon/other-source/42', 'Cross-source target');
check($linkNodes[0]['dwc:relationshipAccordingTo'] === 'curator', 'Asserting source');
check($linkNodes[0]['dwc:relationshipRemarks'] === 'p. 12', 'Relationship remarks');
check($linkNodes[0]['dcterms:type']['@id'] === $link['qualifier'], 'Qualifier IRI');
check(isset($linkNodes[1][rdfCompactIRI($link['predicate'])]), 'Direct assertion');
check($linkNodes[0]['@type'] === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Statement', 'RDF-native assertion');
foreach (array('resourceID', 'relatedResourceID', 'relationshipOfResourceID', 'resourceRelationshipID') as $field) {
  check(!isset($linkNodes[0]['dwc:'.$field]), 'No duplicated Darwin Core ID fields');
}
check(!isset($nodes[0]['dwc:referenceID']), 'Reference identity is its IRI');
$nodes = array_merge($nodes, $linkNodes);
$link['id'] = 'link2'; $link['remarks'] = 'p. 14';
$nodes = array_merge($nodes, rdfNodes($links, array($link)));
$link['object_type'] = 'iri'; $link['object_id'] = 'https://example.org/topic';
check(links_rdf_endpoint($link, 'object')['@id'] === $link['object_id'], 'External target');
$link['object_type'] = '../unsupported';
check(count(rdfNodes($links, array($link))) === 1, 'Unknown type retains metadata');
$taxa = loadModule('taxa');
$nodes = array_merge($nodes, rdfNodes($taxa, array(array('source' => 'other-source', 'id' => '42',
  'taxon' => 'Example species', 'rank' => 'species', 'genus' => 'Example'))));
check(recordModule('/reference/fixture/book/a%20%231')['mname'] === 'references', 'Reference route');
check(recordModule('/taxon/other-source/42')['mname'] === 'taxa', 'Taxon route');
check(recordModule('/link/curator/link1')['mname'] === 'links', 'Link route');
check(rdfNegotiate('application/ld+json') === 'JSON-LD', 'JSON-LD negotiation');
check(rdfNegotiate('text/turtle') === 'Turtle', 'Turtle negotiation');
check(rdfNegotiate('text/turtle;q=0, application/json') === NULL, 'JSON preference');
check(pageURI('/data/references/?source=fixture&page=1', 2) === '/data/references/?source=fixture&page=2', 'Pagination');

// Test the real record handler with a mock prepared lookup.
function SELECTclause($module, $field, $mode, $format) {return('SELECT * FROM '.$module['table']);}
class FixtureResult {
  private $row;
  function __construct($row) {$this->row = $row;}
  function fetch_assoc() {return($this->row);}
}
class FixtureStatement {
  private $row;
  function __construct($row) {$this->row = $row;}
  function bind_param($types, &$source, &$id) {
    check($types === 'ss' && $source === 'fixture' && $id === 'book/a #1', 'Bound decoded IDs');
  }
  function execute() {return(TRUE);}
  function get_result() {return(new FixtureResult($this->row));}
}
class LinkFixtureResult {
  private $rows;
  function __construct($rows) {$this->rows = $rows;}
  function fetch_assoc() {return(array_shift($this->rows));}
  function close() {}
}
class LinkFixtureStatement {
  private $db;
  function __construct($db) {$this->db = $db;}
  function bind_param($types, &...$values) {
    check(strlen($types) === count($values), 'All lookup values are bound');
    $this->db->bound[] = $values;
    return(TRUE);
  }
  function execute() {return(!$this->db->fail);}
  function get_result() {return(new LinkFixtureResult($this->db->links));}
  function close() {}
}
// Records read onto a response by an embed callback, looked up by (source, id)
// rather than one at a time.
class EmbedFixtureStatement {
  private $db;
  function __construct($db) {$this->db = $db;}
  function bind_param($types, &...$values) {
    check(strlen($types) === count($values), 'All embedded values are bound');
    $this->db->bound[] = $values;
    return(TRUE);
  }
  function execute() {return(!$this->db->fail);}
  function get_result() {return(new LinkFixtureResult($this->db->embedded));}
  function close() {}
}
class FixtureDB {
  private $row;
  public $links = array();
  public $embedded = array();
  public $bound = array();
  public $queries = array();
  public $fail = FALSE;
  function __construct($row) {$this->row = $row;}
  function prepare($sql) {
    $this->queries[] = $sql;
    if (strpos($sql, 'FROM links WHERE') !== FALSE) {return(new LinkFixtureStatement($this));}
    //An embed reads many records by (source, id); a record route reads one
    if (strpos($sql, '(`source`, `id`) IN (') !== FALSE) {return(new EmbedFixtureStatement($this));}
    check(preg_match('/WHERE `source` = \? AND `(id|traitID|annotation_id)` = \? LIMIT 1/', $sql) === 1, 'Exact prepared lookup using module ID column');
    return(new FixtureStatement($this->row));
  }
}
$_SERVER['REQUEST_URI'] = '/reference/fixture/book/a%20%231';
$_SERVER['HTTP_ACCEPT'] = 'text/turtle';
$_GET = array('output' => 'JSON');
ob_start(); recordAPI(new FixtureDB($ref)); $json = json_decode(ob_get_clean(), TRUE);
check($json['data'][0] === $ref, 'Explicit JSON precedence and compatibility');
$_GET = array();
ob_start(); recordAPI(new FixtureDB($ref)); $turtle = ob_get_clean();
check(strpos($turtle, '@prefix') === 0, 'Accept negotiation in handler');
ob_start(); recordAPI(new FixtureDB(NULL)); ob_end_clean();
check(http_response_code() === 404, 'Unknown record');
http_response_code(200);
$canonical = $ref; $canonical['source'] = 'Fixture';
ob_start(); recordAPI(new FixtureDB($canonical)); $body = ob_get_clean();
check(http_response_code() === 301 && $body === '', 'Canonical case redirect');
http_response_code(200);
// A recording-reference assertion appears from either endpoint, only once.
$citation = array('source' => 'curator', 'id' => 'citation',
  'subject_type' => 'recordings', 'subject_source' => 'fixture', 'subject_id' => 'rec1',
  'predicate' => 'http://purl.org/dc/terms/isReferencedBy',
  'object_type' => 'references', 'object_source' => 'fixture', 'object_id' => $ref['id'],
  'qualifier' => 'https://vocab.audioblast.org/cv/referenceContent#Oscillogram',
  'remarks' => 'p. 7');
$db = new FixtureDB($ref);
$db->links = array($citation);
$embedded = rdfResponseNodes($db, $module, array($ref));
check(count($db->queries) === 2, 'Two directional lookups per batch');
check($db->bound[0] === array('references', 'fixture', $ref['id']), 'Exact source and ID, not asserting source');
check(strpos($db->queries[0], $ref['id']) === FALSE, 'Record ID is not interpolated into SQL');
$byID = array_column($embedded, NULL, '@id');
check($byID[$uri]['@reverse']['dcterms:isReferencedBy'][0]['@id'] === 'https://api.audioblast.org/recording/fixture/rec1', 'Incoming triple is on reference via reverse');
check($byID['https://api.audioblast.org/link/curator/citation']['dwc:relationshipRemarks'] === 'p. 7', 'Assertion provenance retained');
check(count($embedded) === count(rdfNodes($module, array($ref))) + 1, 'Link found from both sides is deduplicated');
$recordingModule = loadModule('recordings');
$recording = array_fill_keys(array_keys($recordingModule['params']), NULL);
$recording['source'] = 'fixture'; $recording['id'] = 'rec1';
$outgoing = rdfResponseNodes($db, $recordingModule, array($recording));
check(isset($outgoing[0][rdfCompactIRI($citation['predicate'])]), 'Outgoing triple is on the recording node');
$nodes = array_merge($nodes, $embedded, $outgoing);
// No relationships means an unchanged record graph; empty pages make no queries.
$empty = new FixtureDB(NULL);
check(rdfResponseNodes($empty, $module, array($ref)) === rdfNodes($module, array($ref)), 'Unlinked reference unchanged');
$empty->queries = array();
check(rdfResponseNodes($empty, $module, array()) === array() && !$empty->queries, 'Empty page skips links');
check(rdfResponseNodes($empty, $links, array()) === array() && !$empty->queries, 'No recursive traversal');
$batch = array();
for ($i = 0; $i < 101; $i++) {$batch[] = array('source' => 'fixture', 'id' => (string)$i);}
rdfResponseNodes($empty, $module, $batch);
check(count($empty->queries) === 4, '101 records use four bounded queries, not 202');
// Failure is distinguishable from having no links and yields no partial graph.
$db->fail = TRUE;
check(rdfResponseNodes($db, $module, array($ref)) === FALSE, 'Failed lookup propagates');
ob_start(); printRecordRDF($db, $module, array($ref), 'JSON-LD'); $failure = json_decode(ob_get_clean(), TRUE);
check(http_response_code() === 500 && $failure['@graph'] === array(), 'Failure yields HTTP 500 and empty graph');
http_response_code(200);
$db->fail = FALSE;
$_GET = array('output' => 'JSON');
$db->queries = array();
ob_start(); recordAPI($db); $plain = json_decode(ob_get_clean(), TRUE);
check(count($db->queries) === 1 && $plain['data'][0] === $ref, 'JSON does not query links');
$_GET = array('output' => 'JSON-LD');
ob_start(); recordAPI($db); $withLinks = json_decode(ob_get_clean(), TRUE);
check(count($withLinks['@graph']) === count($embedded), 'Record handler includes assertions');
// Trait responses embed source references and taxa, retaining measurement metadata.
$traitModule = loadModule('traits');
$trait = array_fill_keys(array_keys($traitModule['params']), NULL);
$trait['source'] = 'fixture'; $trait['id'] = $ref['id'];
$trait['trait'] = 'Duration'; $trait['value'] = '12.5';
$trait['trait_ontology'] = 'https://vocab.audioblast.org/Duration';
$traitURI = rdfRecordURI($traitModule, $trait['source'], $trait['id']);
$traitReference = $citation;
$traitReference['id'] = 'trait-reference';
$traitReference['subject_type'] = 'traits';
$traitReference['subject_id'] = $trait['id'];
$traitReference['predicate'] = 'http://purl.org/dc/terms/source';
$traitTaxon = $traitReference;
$traitTaxon['id'] = 'trait-taxon';
$traitTaxon['predicate'] = 'http://purl.obolibrary.org/obo/IAO_0000136';
$traitTaxon['object_type'] = 'taxa'; $traitTaxon['object_source'] = 'other-source'; $traitTaxon['object_id'] = '42';
$traitTaxon['qualifier'] = NULL; $traitTaxon['remarks'] = NULL;
// Synthetic incoming relation verifies the generic reverse handling for traits.
$traitIncoming = $citation;
$traitIncoming['id'] = 'trait-incoming';
$traitIncoming['predicate'] = 'http://purl.org/dc/terms/relation';
$traitIncoming['object_type'] = 'traits'; $traitIncoming['object_id'] = $trait['id'];
$traitDB = new FixtureDB($trait);
$traitDB->links = array($traitReference, $traitTaxon, $traitIncoming);
$traitNodes = rdfResponseNodes($traitDB, $traitModule, array($trait));
$traitByID = array_column($traitNodes, NULL, '@id');
check($traitDB->bound[0] === array('traits', 'fixture', $trait['id']), 'Trait identity used in link lookup');
check($traitByID[$traitURI]['dcterms:source']['@id'] === $uri, 'Trait links to reference');
check($traitByID[$traitURI]['http://purl.obolibrary.org/obo/IAO_0000136']['@id'] === 'https://api.audioblast.org/taxon/other-source/42', 'Trait links across sources to taxon');
check($traitByID[$traitURI]['dwc:measurementValue'] === '12.5', 'Trait measurement retained');
check($traitByID[$traitURI]['@reverse']['dcterms:relation'][0]['@id'] === 'https://api.audioblast.org/recording/fixture/rec1', 'Incoming trait relation');
check($traitByID['https://api.audioblast.org/link/curator/trait-reference']['dwc:relationshipRemarks'] === 'p. 7', 'Trait link assertion metadata');
$traitEmpty = new FixtureDB($trait);
check(rdfResponseNodes($traitEmpty, $traitModule, array($trait)) === rdfNodes($traitModule, array($trait)), 'Unlinked trait still returned');
$_SERVER['REQUEST_URI'] = '/trait/fixture/book/a%20%231';
$_GET = array('output' => 'JSON');
$traitDB->queries = array();
ob_start(); recordAPI($traitDB); $traitJSON = json_decode(ob_get_clean(), TRUE);
check($traitJSON['data'][0] === $trait && count($traitDB->queries) === 1, 'Trait JSON unchanged and skips links');
$_GET = array();
$_SERVER['HTTP_ACCEPT'] = 'application/ld+json';
ob_start(); recordAPI($traitDB); $traitLD = json_decode(ob_get_clean(), TRUE);
check($traitLD['@graph'] === $traitNodes, 'Trait route negotiates embedded JSON-LD');
$_SERVER['HTTP_ACCEPT'] = 'text/turtle';
ob_start(); recordAPI($traitDB); $traitTTL = ob_get_clean();
check($traitTTL === rdfTurtle($traitNodes), 'Trait route negotiates equivalent Turtle');
$nodes = array_merge($nodes, $traitNodes);

// Taxa expose incoming recordings, traits and references and outgoing publications.
$taxonModule = loadModule('taxa');
$taxon = array('source' => 'fixture', 'id' => $ref['id'],
  'taxon' => 'Example species', 'rank' => 'species', 'genus' => 'Example');
$taxonURI = rdfRecordURI($taxonModule, $taxon['source'], $taxon['id']);
$taxonLinks = array();
foreach (array('recordings', 'traits', 'references') as $type) {
  $entry = $traitTaxon;
  $entry['id'] = 'taxon-incoming-'.$type;
  $entry['subject_type'] = $type;
  $entry['subject_id'] = 'incoming';
  $entry['object_source'] = $taxon['source']; $entry['object_id'] = $taxon['id'];
  $taxonLinks[] = $entry;
}
$publication = $traitReference;
$publication['id'] = 'name-publication'; $publication['subject_type'] = 'taxa';
$publication['subject_id'] = $taxon['id'];
$publication['predicate'] = 'http://rs.tdwg.org/dwc/terms/namePublishedInID';
$publication['qualifier'] = NULL;
$taxonLinks[] = $publication;
$taxonDB = new FixtureDB($taxon); $taxonDB->links = $taxonLinks;
$taxonNodes = rdfResponseNodes($taxonDB, $taxonModule, array($taxon));
$taxonByID = array_column($taxonNodes, NULL, '@id');
check($taxonDB->bound[0] === array('taxa', 'fixture', $taxon['id']), 'Taxon-specific lookup');
check(count($taxonDB->queries) === 2, 'Taxon incoming and outgoing queries');
check(count($taxonByID[$taxonURI]['@reverse']['http://purl.obolibrary.org/obo/IAO_0000136']) === 3, 'Recordings, traits and references all appear');
check($taxonByID[$taxonURI]['dwc:scientificName'] === 'Example species' && $taxonByID[$taxonURI]['dwc:taxonRank'] === 'species', 'Taxonomy retained');
check($taxonByID[$taxonURI]['dwc:namePublishedInID']['@id'] === $uri, 'Name publication preserves the source predicate');
check($taxonByID['https://api.audioblast.org/link/curator/name-publication']['rdf:predicate']['@id'] === 'http://rs.tdwg.org/dwc/terms/namePublishedInID', 'Assertion predicate matches direct triple');
check($publication['predicate'] === 'http://rs.tdwg.org/dwc/terms/namePublishedInID', 'Stored predicate unchanged');
$publicationDB = new FixtureDB($ref); $publicationDB->links = array($publication);
$publicationNodes = rdfResponseNodes($publicationDB, $module, array($ref));
$publicationByID = array_column($publicationNodes, NULL, '@id');
check($publicationByID[$uri]['@reverse']['dwc:namePublishedInID'][0]['@id'] === $taxonURI, 'Reference uses matching reverse publication property');
check(rdfResponseNodes(new FixtureDB($taxon), $taxonModule, array($taxon)) === rdfNodes($taxonModule, array($taxon)), 'Taxon without links remains unchanged');
$_SERVER['REQUEST_URI'] = '/taxon/fixture/book/a%20%231';
$_GET = array('output' => 'JSON'); $taxonDB->queries = array();
ob_start(); recordAPI($taxonDB); $taxonJSON = json_decode(ob_get_clean(), TRUE);
check($taxonJSON['data'][0] === $taxon && count($taxonDB->queries) === 1, 'Taxon JSON unchanged, no link query');
$_GET = array(); $_SERVER['HTTP_ACCEPT'] = 'application/ld+json';
ob_start(); recordAPI($taxonDB); $taxonLD = json_decode(ob_get_clean(), TRUE);
check($taxonLD['@graph'] === $taxonNodes, 'Taxon URI serves embedded JSON-LD');
$_SERVER['HTTP_ACCEPT'] = 'text/turtle';
ob_start(); recordAPI($taxonDB); $taxonTTL = ob_get_clean();
check($taxonTTL === rdfTurtle($taxonNodes), 'Taxon URI serves embedded Turtle');
$nodes = array_merge($nodes, $taxonNodes, $publicationNodes);

// Specimens are Darwin Core occurrences, with the recordings of them and the
// taxa they are identified as coming from links.
$specimenModule = loadModule('specimens');
$specimen = array('source' => 'fixture', 'id' => $ref['id'],
  'scientificName' => 'Example species', 'basisOfRecord' => 'PreservedSpecimen',
  'institutionCode' => 'NHMUK', 'collectionCode' => 'BMNH(E)', 'catalogNumber' => '15',
  'otherCatalogNumbers' => '', 'typeStatus' => 'Paratype', 'sex' => 'Male',
  'lifeStage' => 'Adult', 'individualCount' => '1', 'recordedBy' => 'A. Collector',
  'eventDate' => '1962-08', 'identifiedBy' => 'An Expert', 'dateIdentified' => '1963-01-02',
  'identificationQualifier' => 'cf.', 'associatedSequences' => '', 'locality' => 'A place',
  'countryCode' => 'GB', 'decimalLatitude' => '50.6', 'decimalLongitude' => '-1.95',
  'occurrenceRemarks' => 'On reeds.', 'info_url' => 'https://example.org/specimen/1');
$specimenURI = rdfRecordURI($specimenModule, $specimen['source'], $specimen['id']);
check(recordModule('/specimen/fixture/sp1')['mname'] === 'specimens', 'Specimen route');
$recordingOf = $citation;
$recordingOf['id'] = 'recording-of'; $recordingOf['qualifier'] = NULL; $recordingOf['remarks'] = NULL;
$recordingOf['subject_type'] = 'recordings'; $recordingOf['subject_id'] = 'rec1';
$recordingOf['predicate'] = 'http://rs.tdwg.org/ac/terms/associatedSpecimenReference';
$recordingOf['object_type'] = 'specimens'; $recordingOf['object_id'] = $specimen['id'];
$identifiedAs = $recordingOf;
$identifiedAs['id'] = 'identified-as';
$identifiedAs['subject_type'] = 'specimens'; $identifiedAs['subject_id'] = $specimen['id'];
$identifiedAs['predicate'] = 'http://rs.tdwg.org/dwc/iri/toTaxon';
$identifiedAs['object_type'] = 'taxa'; $identifiedAs['object_source'] = 'other-source';
$identifiedAs['object_id'] = '42';
$specimenDB = new FixtureDB($specimen);
$specimenDB->links = array($recordingOf, $identifiedAs);
$specimenNodes = rdfResponseNodes($specimenDB, $specimenModule, array($specimen));
$specimenByID = array_column($specimenNodes, NULL, '@id');
check($specimenDB->bound[0] === array('specimens', 'fixture', $specimen['id']), 'Specimen identity used in link lookup');
check($specimenByID[$specimenURI]['@type'] === 'http://rs.tdwg.org/dwc/terms/Occurrence', 'Specimen is an occurrence');
check($specimenByID[$specimenURI]['dwc:occurrenceID'] === $specimenURI, 'Occurrence identified by its own URI');
check($specimenByID[$specimenURI]['dwc:typeStatus'] === 'Paratype', 'Type status retained');
check(!isset($specimenByID[$specimenURI]['dwc:otherCatalogNumbers']), 'Empty values omitted');
check($specimenByID[$specimenURI]['dwc:eventDate']['@type'] === 'xsd:gYearMonth', 'Collecting month precision kept');
check($specimenByID[$specimenURI]['dwc:decimalLatitude'] === rdfTyped('50.6', 'xsd:decimal'), 'Coordinates typed');
check($specimenByID[$specimenURI]['dwc:geodeticDatum'] === 'EPSG:4326', 'Coordinates have a datum');
check($specimenByID[$specimenURI]['dwciri:toTaxon']['@id'] === 'https://api.audioblast.org/taxon/other-source/42', 'Specimen identified as a taxon');
check($specimenByID[$specimenURI]['@reverse']['ac:associatedSpecimenReference'][0]['@id'] === 'https://api.audioblast.org/recording/fixture/rec1', 'Recording of the specimen');
check(count($specimenByID[$specimenURI]['rdfs:seeAlso']) === 3, 'Source page and incoming and outgoing links');
$undated = $specimen; $undated['id'] = 'sp2'; $undated['eventDate'] = 'summer 1962';
$undated['decimalLatitude'] = ''; $undated['decimalLongitude'] = ''; $undated['info_url'] = '';
$undatedNodes = rdfNodes($specimenModule, array($undated));
check($undatedNodes[0]['dwc:eventDate'] === 'summer 1962', 'Unusual date retained as given');
check(!isset($undatedNodes[0]['dwc:geodeticDatum']), 'No datum without coordinates');
check(count($undatedNodes[0]['rdfs:seeAlso']) === 2, 'Only the link queries without a source page');
$_SERVER['REQUEST_URI'] = '/specimen/fixture/book/a%20%231';
$_GET = array('output' => 'JSON'); $specimenDB->queries = array();
ob_start(); recordAPI($specimenDB); $specimenJSON = json_decode(ob_get_clean(), TRUE);
check($specimenJSON['data'][0] === $specimen && count($specimenDB->queries) === 1, 'Specimen JSON unchanged, no link query');
$_GET = array(); $_SERVER['HTTP_ACCEPT'] = 'text/turtle';
ob_start(); recordAPI($specimenDB); $specimenTTL = ob_get_clean();
check($specimenTTL === rdfTurtle($specimenNodes), 'Specimen URI serves embedded Turtle');
$nodes = array_merge($nodes, $specimenNodes);

// A link that a reference established carries it, as the statement and as a
// triple of its own, so what a relationship rests on is read off the relationship.
$interaction = array('source' => 'bio.acousti.ca', 'id' => 'interaction1',
  'subject_type' => 'taxa', 'subject_source' => 'bio.acousti.ca', 'subject_id' => '6138',
  'predicate' => 'https://vocab.audioblast.org/cv/interaction#AcousticallyOrientatingParasiteOf',
  'object_type' => 'taxa', 'object_source' => 'bio.acousti.ca', 'object_id' => '4841',
  'qualifier' => NULL, 'remarks' => NULL);
$establishedBy = array('source' => 'bio.acousti.ca', 'id' => 'cites1',
  'subject_type' => 'links', 'subject_source' => 'bio.acousti.ca',
  'subject_id' => $interaction['id'],
  'predicate' => 'http://purl.org/dc/terms/source',
  'object_type' => 'references', 'object_source' => 'fixture', 'object_id' => $ref['id'],
  'qualifier' => NULL, 'remarks' => NULL);
$interactionURI = rdfRecordURI($links, $interaction['source'], $interaction['id']);
$citingDB = new FixtureDB($interaction);
$citingDB->links = array($establishedBy);
$citedNodes = rdfResponseNodes($citingDB, $links, array($interaction));
$citedByID = array_column($citedNodes, NULL, '@id');
check($citedByID[$interactionURI]['dcterms:source']['@id'] === $uri, 'A link carries the reference that established it');
check($citedByID[$interactionURI]['rdf:subject']['@id'] === 'https://api.audioblast.org/taxon/bio.acousti.ca/6138', 'The statement still says what it relates');
check($citedByID['https://api.audioblast.org/link/bio.acousti.ca/cites1']['rdf:object']['@id'] === $uri, 'The citation is a statement of its own');
//A link with nothing said about it is unchanged
check(rdfResponseNodes(new FixtureDB($interaction), $links, array($interaction)) ===
      rdfNodes($links, array($interaction)), 'An uncited link is unchanged');
$nodes = array_merge($nodes, $citedNodes);

// A description says what it is about and what it cites through links.
$descriptionModule = loadModule('descriptions');
$description = array('source' => 'fixture', 'id' => '12289', 'topic' => 'behaviour',
  'value' => 'Males were reported to call 0.3-4.0m up, in shrubs along small hill-streams.',
  'topic_link' => 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Behaviour',
  'info_url' => 'https://example.org/profile/12289');
$descriptionURI = rdfRecordURI($descriptionModule, $description['source'], $description['id']);
check(recordModule('/description/fixture/12289')['mname'] === 'descriptions', 'Description route');
$describes = $citation;
$describes['id'] = 'describes'; $describes['qualifier'] = NULL; $describes['remarks'] = NULL;
$describes['subject_type'] = 'descriptions'; $describes['subject_id'] = $description['id'];
$describes['predicate'] = 'http://purl.obolibrary.org/obo/IAO_0000136';
$describes['object_type'] = 'taxa'; $describes['object_source'] = 'other-source';
$describes['object_id'] = '42';
$rests = $describes;
$rests['id'] = 'rests-on';
$rests['predicate'] = 'http://purl.org/dc/terms/source';
$rests['object_type'] = 'references'; $rests['object_source'] = 'fixture';
$rests['object_id'] = $ref['id'];
$descriptionDB = new FixtureDB($description);
$descriptionDB->links = array($describes, $rests);
$descriptionNodes = rdfResponseNodes($descriptionDB, $descriptionModule, array($description));
$descriptionByID = array_column($descriptionNodes, NULL, '@id');
check($descriptionByID[$descriptionURI]['@type'] === 'http://purl.org/dc/dcmitype/Text', 'Description is a text');
check(strpos($descriptionByID[$descriptionURI]['dc:description'], 'hill-streams') !== FALSE, 'Prose retained');
check($descriptionByID[$descriptionURI]['dc:type'] === 'behaviour', "Topic is the source's own word");
check($descriptionByID[$descriptionURI]['dcterms:type']['@id'] === $description['topic_link'], 'Topic names its Species Profile Model info item');
$untyped = $description; $untyped['id'] = '12292'; $untyped['topic'] = 'song'; $untyped['topic_link'] = '';
check(!isset(rdfNodes($descriptionModule, array($untyped))[0]['dcterms:type']), 'A topic the model has no item for names none');
check($descriptionByID[$descriptionURI]['http://purl.obolibrary.org/obo/IAO_0000136']['@id'] === 'https://api.audioblast.org/taxon/other-source/42', 'Description is about a taxon');
check($descriptionByID[$descriptionURI]['dcterms:source']['@id'] === $uri, 'Description rests on a reference');
check(count($descriptionByID[$descriptionURI]['rdfs:seeAlso']) === 3, 'Source page and incoming and outgoing links');
$nodes = array_merge($nodes, $descriptionNodes);

// Places are described once, and the records made there point at them.
$locationModule = loadModule('locations');
$place = array('source' => 'fixture', 'id' => 'p1', 'name' => "Chapman's Pool, Dorset",
  'continent' => 'Europe', 'countryCode' => 'GB', 'stateProvince' => 'England',
  'county' => 'Dorset', 'island' => '', 'islandGroup' => '', 'locality' => 'On reeds by the pool',
  'decimalLatitude' => '50.6016', 'decimalLongitude' => '-1.9529',
  'coordinateUncertaintyInMeters' => '1000', 'geodeticDatum' => '', 'georeferenceRemarks' => '',
  'minimumElevationInMeters' => '-5', 'maximumElevationInMeters' => '120',
  'info_url' => 'https://example.org/place/1');
$placeURI = rdfRecordURI($locationModule, $place['source'], $place['id']);
check(recordModule('/location/fixture/p1')['mname'] === 'locations', 'Location route');
$madeAt = $citation;
$madeAt['id'] = 'made-at'; $madeAt['qualifier'] = NULL; $madeAt['remarks'] = NULL;
$madeAt['subject_type'] = 'recordings'; $madeAt['subject_id'] = 'rec1';
$madeAt['predicate'] = 'http://rs.tdwg.org/dwc/iri/inDescribedPlace';
$madeAt['object_type'] = 'locations'; $madeAt['object_id'] = $place['id'];
$placeDB = new FixtureDB($place);
$placeDB->links = array($madeAt);
$placeNodes = rdfResponseNodes($placeDB, $locationModule, array($place));
$placeByID = array_column($placeNodes, NULL, '@id');
check($placeByID[$placeURI]['@type'] === 'http://rs.tdwg.org/dwc/terms/Location', 'Place is a location');
check($placeByID[$placeURI]['dwc:locationID'] === $placeURI, 'Location identified by its own URI');
check($placeByID[$placeURI]['rdfs:label'] === "Chapman's Pool, Dorset", 'Place named as its source names it');
check($placeByID[$placeURI]['dwc:decimalLatitude'] === rdfTyped('50.6016', 'xsd:decimal'), 'Coordinates typed');
check($placeByID[$placeURI]['dwc:minimumElevationInMeters'] === rdfTyped('-5', 'xsd:decimal'), 'Elevation below sea level');
check($placeByID[$placeURI]['dwc:geodeticDatum'] === 'EPSG:4326', 'Decimal coordinates mean WGS84');
check(!isset($placeByID[$placeURI]['dwc:island']), 'Empty units omitted');
check($placeByID[$placeURI]['@reverse']['dwciri:inDescribedPlace'][0]['@id'] === 'https://api.audioblast.org/recording/fixture/rec1', 'Recording made at the place');
$given = $place; $given['id'] = 'p2'; $given['geodeticDatum'] = 'OSGB36';
check(rdfNodes($locationModule, array($given))[0]['dwc:geodeticDatum'] === 'OSGB36', "A source's own datum is kept");
$nodes = array_merge($nodes, $placeNodes);
// Vernacular names are the names a taxon is known by in a language, with the
// taxon they name and the reference they were taken from coming from links.
$vernacularModule = loadModule('vernacularnames');
$vernacular = array('source' => 'fixture', 'id' => $ref['id'],
  'vernacularName' => 'le Criquet des pins', 'language' => 'fr', 'locality' => '',
  'remarks' => '');
$vernacularURI = rdfRecordURI($vernacularModule, $vernacular['source'], $vernacular['id']);
check(recordModule('/vernacular-name/fixture/vn1')['mname'] === 'vernacularnames', 'Vernacular name route');
$namesTaxon = $identifiedAs;
$namesTaxon['id'] = 'names-taxon';
$namesTaxon['subject_type'] = 'vernacularnames'; $namesTaxon['subject_id'] = $vernacular['id'];
$namesTaxon['predicate'] = 'http://purl.obolibrary.org/obo/IAO_0000219';
$namedIn = $namesTaxon;
$namedIn['id'] = 'names-from';
$namedIn['predicate'] = 'http://purl.org/dc/terms/source';
$namedIn['object_type'] = 'references'; $namedIn['object_source'] = 'fixture';
$namedIn['object_id'] = $ref['id'];
$vernacularDB = new FixtureDB($vernacular);
$vernacularDB->links = array($namesTaxon, $namedIn);
$vernacularNodes = rdfResponseNodes($vernacularDB, $vernacularModule, array($vernacular));
$vernacularByID = array_column($vernacularNodes, NULL, '@id');
check($vernacularDB->bound[0] === array('vernacularnames', 'fixture', $vernacular['id']), 'Vernacular name identity used in link lookup');
check($vernacularByID[$vernacularURI]['@type'] === 'http://rs.gbif.org/terms/1.0/VernacularName', 'Name is a Darwin Core vernacular name');
check($vernacularByID[$vernacularURI]['dwc:vernacularName'] === array('@value' => 'le Criquet des pins', '@language' => 'fr'), 'Name is a literal in the language it is in, with the article a reference wrote it with');
check($vernacularByID[$vernacularURI]['dcterms:language'] === rdfTyped('fr', 'xsd:language'), 'Language tag given on its own as well, typed as BCP 47 syntax');
check(!isset($vernacularByID[$vernacularURI]['dwc:locality']), 'Empty values omitted');
check($vernacularByID[$vernacularURI]['http://purl.obolibrary.org/obo/IAO_0000219']['@id'] === 'https://api.audioblast.org/taxon/other-source/42', 'Name denotes the taxon it names');
check($vernacularByID[$vernacularURI]['dcterms:source']['@id'] === $uri, 'Name was taken from a reference');
check(strpos(rdfTurtle(array($vernacularByID[$vernacularURI])), '"le Criquet des pins"@fr') !== FALSE, 'Turtle carries the language tag');
// A name whose language a source never recorded is a plain literal, not one in
// a language guessed from the name.
$unrecorded = $vernacular; $unrecorded['id'] = 'vn2'; $unrecorded['language'] = '';
$unrecorded['vernacularName'] = 'North American hoary bat';
$unrecordedNode = rdfNodes($vernacularModule, array($unrecorded))[0];
check($unrecordedNode['dwc:vernacularName'] === 'North American hoary bat', 'Name without a language is a plain literal');
check(!isset($unrecordedNode['dcterms:language']), 'No language invented');
check(rdfLang('Anything', 'Not a tag') === 'Anything', 'A language that is not a tag is left off the literal');
check(rdfLang('', 'fr') === NULL, 'No literal where there is no name');
$_SERVER['REQUEST_URI'] = '/vernacular-name/fixture/book/a%20%231';
$_GET = array('output' => 'JSON'); $vernacularDB->queries = array();
ob_start(); recordAPI($vernacularDB); $vernacularJSON = json_decode(ob_get_clean(), TRUE);
check($vernacularJSON['data'][0] === $vernacular && count($vernacularDB->queries) === 1, 'Vernacular name JSON unchanged, no link query');
$_GET = array(); $_SERVER['HTTP_ACCEPT'] = 'text/turtle';
ob_start(); recordAPI($vernacularDB); $vernacularTTL = ob_get_clean();
check($vernacularTTL === rdfTurtle($vernacularNodes), 'Vernacular name URI serves embedded Turtle');
$nodes = array_merge($nodes, $vernacularNodes);

// Images are records of their own, so one scan covering several recordings is
// described once, with the licence it is under, and linked to each of them.
$imageModule = loadModule('images');
$image = array('source' => 'fixture', 'id' => '134',
  'title' => '399-3_Conocephalus_discolor_409_meta.jpg',
  'url' => 'https://example.org/files/meta.jpg', 'kind' => 'Original metadata scan',
  'creator' => 'Ashleigh Whiffin', 'license' => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
  'post_date' => '2019-09-11', 'mime' => 'image/jpeg', 'bytes' => '910718',
  'width' => '1412', 'height' => '1183', 'caption' => 'The data sheet for tape 399-3');
$imageURI = rdfRecordURI($imageModule, $image['source'], $image['id']);
check(recordModule('/image/fixture/134')['mname'] === 'images', 'Image route');
$documents = $citation;
$documents['id'] = 'documents'; $documents['qualifier'] = NULL; $documents['remarks'] = NULL;
$documents['subject_type'] = 'images'; $documents['subject_source'] = 'fixture';
$documents['subject_id'] = $image['id'];
$documents['predicate'] = 'http://purl.obolibrary.org/obo/IAO_0000136';
$documents['object_type'] = 'recordings'; $documents['object_source'] = 'fixture';
$documents['object_id'] = 'rec1';
$imageDB = new FixtureDB($image);
$imageDB->links = array($documents);
$imageNodes = rdfResponseNodes($imageDB, $imageModule, array($image));
$imageByID = array_column($imageNodes, NULL, '@id');
check($imageByID[$imageURI]['@type'] === array('http://rs.tdwg.org/ac/terms/Media', 'http://purl.org/dc/dcmitype/StillImage'), 'Image is Audiovisual Core media and a still image');
check($imageByID[$imageURI]['dcterms:rights']['@id'] === 'https://creativecommons.org/licenses/by-nc-sa/4.0/', 'Image carries the licence it is under');
check($imageByID[$imageURI]['ac:subtypeLiteral'] === 'Original metadata scan', "The source's own kind of image is a literal");
check($imageByID[$imageURI]['exif:PixelXDimension'] === rdfTyped('1412', 'xsd:decimal'), 'Pixel dimensions typed');
check($imageByID[$imageURI]['ac:caption'] === 'The data sheet for tape 399-3', 'Caption kept');
check($imageByID[$imageURI]['dc:creator'] === 'Ashleigh Whiffin', 'Creator credited');
check($imageByID[$imageURI]['dcterms:available']['@type'] === 'xsd:date', 'Upload date typed');
check($imageByID[$imageURI]['http://purl.obolibrary.org/obo/IAO_0000136']['@id'] === 'https://api.audioblast.org/recording/fixture/rec1', 'Image is about the recording it documents');
$imageService = $imageByID[$imageURI]['ac:hasServiceAccessPoint']['@id'];
check($imageByID[$imageService]['ac:accessURI']['@id'] === 'https://example.org/files/meta.jpg', 'The file itself is a service access point');
check($imageByID[$imageService]['dc:format'] === 'image/jpeg', 'MIME belongs to the service');
check(!isset($imageByID[$imageURI]['ac:accessURI']), 'Access URI belongs to the service, not the image');
// An image whose source gives no licence is served without one rather than
// with a licence it never had.
$unlicensed = $image; $unlicensed['id'] = '20603'; $unlicensed['license'] = '';
$unlicensed['creator'] = ''; $unlicensed['caption'] = '';
$unlicensedNode = rdfNodes($imageModule, array($unlicensed))[0];
check(!isset($unlicensedNode['dcterms:rights']), 'No licence invented');
check(!isset($unlicensedNode['dc:creator']) && !isset($unlicensedNode['ac:caption']), 'Empty values omitted');
$nodes = array_merge($nodes, $imageNodes);

// The names of a taxon are on the taxon's own response: links are looked up by
// the record at either end, not by predicate, so denotes is found there exactly
// as is about is, next to the recordings and trait values of the same taxon.
$namedTaxon = array('source' => 'other-source', 'id' => '42', 'taxon' => 'Example species',
  'rank' => 'species', 'genus' => 'Example');
$namedTaxonURI = 'https://api.audioblast.org/taxon/other-source/42';
$secondName = $namesTaxon;
$secondName['id'] = 'names-taxon-2'; $secondName['subject_id'] = 'vn2';
$aboutIt = $namesTaxon;
$aboutIt['id'] = 'recording-about'; $aboutIt['subject_type'] = 'recordings';
$aboutIt['subject_id'] = 'rec1';
$aboutIt['predicate'] = 'http://purl.obolibrary.org/obo/IAO_0000136';
$namedDB = new FixtureDB($namedTaxon);
$namedDB->links = array($namesTaxon, $secondName, $aboutIt);
$namedTaxonNodes = rdfResponseNodes($namedDB, $taxonModule, array($namedTaxon));
$namedTaxonByID = array_column($namedTaxonNodes, NULL, '@id');
$reverse = $namedTaxonByID[$namedTaxonURI]['@reverse'];
check(count($reverse['http://purl.obolibrary.org/obo/IAO_0000219']) === 2, 'Both names of the taxon are on the taxon');
check($reverse['http://purl.obolibrary.org/obo/IAO_0000219'][0]['@id'] === $vernacularURI, 'Name reached from its taxon');
check(count($reverse['http://purl.obolibrary.org/obo/IAO_0000136']) === 1, 'What is about the taxon is kept apart from what denotes it');
// The taxon carries the names' URIs, not their text: a client follows them, as
// it does for the recordings and trait values of a taxon.
$nodes = array_merge($nodes, $namedTaxonNodes);

// The names themselves are read onto the taxon as dwc:vernacularName, which is
// what Darwin Core defines on dwc:Taxon, each in the language it is in. The
// name records stay linked, as they hold what a name alone does not.
$namedDB->embedded = array(
  $vernacular,
  array('source' => 'fixture', 'id' => 'vn2', 'vernacularName' => 'Pine Grasshopper',
    'language' => 'en', 'locality' => '', 'remarks' => ''));
$namedDB->queries = array(); $namedDB->bound = array();
$embeddedNodes = rdfResponseNodes($namedDB, $taxonModule, array($namedTaxon));
$embeddedByID = array_column($embeddedNodes, NULL, '@id');
$taxonNames = $embeddedByID[$namedTaxonURI]['dwc:vernacularName'];
check(count($namedDB->queries) === 3, 'Names are read in one query, not one per name');
check(count($taxonNames) === 2, 'Both names of the taxon are on the taxon');
check(in_array(array('@value' => 'le Criquet des pins', '@language' => 'fr'), $taxonNames, TRUE), 'Name on the taxon keeps its language');
check(in_array(array('@value' => 'Pine Grasshopper', '@language' => 'en'), $taxonNames, TRUE), 'Every language is kept, not just one');
check(count($embeddedByID[$namedTaxonURI]['@reverse']['http://purl.obolibrary.org/obo/IAO_0000219']) === 2, 'Names stay linked as well as read');
check(strpos(rdfTurtle(array($embeddedByID[$namedTaxonURI])), 'dwc:vernacularName "le Criquet des pins"@fr, "Pine Grasshopper"@en') !== FALSE, 'Turtle gives the taxon both tagged names');
// Only a name that denotes the taxon is a name of it.
$aboutOnly = new FixtureDB($namedTaxon);
$aboutOnly->links = array($aboutIt);
$aboutOnly->embedded = array($vernacular);
$aboutNodes = rdfResponseNodes($aboutOnly, $taxonModule, array($namedTaxon));
$aboutByID = array_column($aboutNodes, NULL, '@id');
check(count($aboutOnly->queries) === 2, 'No name lookup where nothing denotes the taxon');
check(!isset($aboutByID[$namedTaxonURI]['dwc:vernacularName']), 'A link that is only about a taxon does not name it');
// A failed lookup is not a taxon with no names.
$namedDB->fail = TRUE;
check(rdfResponseNodes($namedDB, $taxonModule, array($namedTaxon)) === FALSE, 'Failed name lookup propagates');
$namedDB->fail = FALSE;
$nodes = array_merge($nodes, $embeddedNodes);

// audioBLAST! holds a taxon once for every source that knows it, and a name is
// all that two such rows share. A link to the taxon of an external taxonomy
// says which taxon a row is, so the rows matched to the same taxon are the same
// taxon, and the taxon's own response says which those are: a client reading
// one row is told the others rather than having to gather every match itself.
$AEPYCEROS = 'https://api.checklistbank.org/dataset/3LR/taxon/PQQ';
$matchedTo = function($source, $id, $type, $holder, $taxon, $remarks) use ($citation) {
  $link = $citation;
  $link['source'] = 'CoL'; $link['id'] = 'match-'.$source.'-'.$id;
  $link['subject_type'] = 'taxa'; $link['subject_source'] = $source;
  $link['subject_id'] = $id;
  $link['predicate'] = 'http://www.w3.org/2004/02/skos/core#exactMatch';
  $link['object_type'] = $type; $link['object_source'] = $holder;
  $link['object_id'] = $taxon;
  $link['qualifier'] = NULL; $link['remarks'] = $remarks;
  return($link);
};
// Two sources that disagree about where Aepyceros belongs, one putting it in
// Aepycerotinae and the other in Antilopinae, still hold the same animal.
$impala = array('source' => 'bio.acousti.ca', 'id' => '7785', 'taxon' => 'Aepyceros',
  'rank' => 'Genus', 'subfamily' => 'Aepycerotinae', 'family' => 'Bovidae');
$impalaURI = rdfRecordURI($taxonModule, $impala['source'], $impala['id']);
$otherImpalaURI = rdfRecordURI($taxonModule, 'iNaturalist', '42277');
//The Catalogue of Life is held as a source of its own, so a row is matched to
//its row there and a client resolves the match without leaving audioBLAST!
$colTaxonURI = rdfRecordURI($taxonModule, 'CoL', 'PQQ');
$impalaDB = new FixtureDB($impala);
$impalaDB->links = array($matchedTo('bio.acousti.ca', '7785', 'taxa', 'CoL', 'PQQ', 'Aepyceros; COL26.9'),
                         $matchedTo('iNaturalist', '42277', 'taxa', 'CoL', 'PQQ', 'Aepyceros; COL26.9'));
$impalaNodes = rdfResponseNodes($impalaDB, $taxonModule, array($impala));
$impalaByID = array_column($impalaNodes, NULL, '@id');
$sameTaxon = $impalaByID[$impalaURI]['skos:exactMatch'];
check(count($impalaDB->queries) === 3, 'Equivalents are found in one further query, not one per row');
//A matched taxon is looked up by its type, the source holding it and its id
//there, the same shape as every other record lookup, so one index serves both
check($impalaDB->bound[2] === array('http://www.w3.org/2004/02/skos/core#exactMatch', 'taxa', 'CoL', 'PQQ'), 'Matched taxa are looked up by type, source and id, all bound');
check(strpos($impalaDB->queries[2], 'PQQ') === FALSE, 'Matched taxon is not interpolated into SQL');
check(in_array(rdfIRI($otherImpalaURI), $sameTaxon, TRUE), 'The row of the other source is the same taxon');
// The catalogue's own row stays on it as well: it is what makes the two rows
// equivalent, and it is the row a client follows to read the classification
// that placed them there.
check(in_array(rdfIRI($colTaxonURI), $sameTaxon, TRUE), 'The taxon it was matched to is kept');
check(!in_array(rdfIRI($impalaURI), $sameTaxon, TRUE), 'A row is not listed as the same taxon as itself');
check($impalaByID[$impalaURI]['dwc:subfamily'] === 'Aepycerotinae', "Each row keeps its own source's classification, disagreements and all");
$impalaTurtle = rdfTurtle($impalaNodes);
check(strpos($impalaTurtle, '@prefix skos: <http://www.w3.org/2004/02/skos/core#>') !== FALSE, 'Turtle declares the SKOS prefix it uses');
check(strpos($impalaTurtle, 'skos:exactMatch') !== FALSE, 'Turtle gives the match as a prefixed name');
check(isset($impalaByID['https://api.audioblast.org/link/CoL/match-bio.acousti.ca-7785']), 'The match is a link like any other, with the source that made it');
check($impalaByID['https://api.audioblast.org/link/CoL/match-bio.acousti.ca-7785']['dwc:relationshipAccordingTo'] === 'CoL', 'The match says who made it');
check(strpos($impalaByID['https://api.audioblast.org/link/CoL/match-bio.acousti.ca-7785']['dwc:relationshipRemarks'], 'COL26.9') !== FALSE, 'The match says which release it was made against');
// A taxonomy audioBLAST! does not hold is matched to as an IRI, and reads the
// same way: the catalogue's own row carries its address there.
$outward = new FixtureDB($impala);
$outward->links = array($matchedTo('CoL', 'PQQ', 'iri', '', $AEPYCEROS, 'Aepyceros; COL26.9'));
$colRow = array('source' => 'CoL', 'id' => 'PQQ', 'taxon' => 'Aepyceros', 'rank' => 'Genus');
$outwardNodes = rdfResponseNodes($outward, $taxonModule, array($colRow));
$outwardByID = array_column($outwardNodes, NULL, '@id');
check($outwardByID[$colTaxonURI]['skos:exactMatch'] === rdfIRI($AEPYCEROS), 'The catalogue row carries its address in the catalogue');
// A row matched to a taxon no other row is matched to has no equivalents, and
// is never said to be equivalent to itself.
$aloneDB = new FixtureDB($impala);
$aloneDB->links = array($matchedTo('bio.acousti.ca', '7785', 'taxa', 'CoL', 'PQQ', 'Aepyceros; COL26.9'));
$aloneNodes = rdfResponseNodes($aloneDB, $taxonModule, array($impala));
$aloneByID = array_column($aloneNodes, NULL, '@id');
//The taxon it was matched to is still there; no other row of audioBLAST!'s is
check($aloneByID[$impalaURI]['skos:exactMatch'] === rdfIRI($colTaxonURI), 'A row matched to a taxon no other row reaches has no equivalent row, and is not its own');
// An unmatched row, such as an undescribed species no taxonomy has a name for,
// costs no lookup and is served exactly as before.
$undescribed = array('source' => 'bio.acousti.ca', 'id' => '798',
  'taxon' => '"allpurpose urchip"', 'rank' => 'Species', 'genus' => 'Unassigned');
$undescribedDB = new FixtureDB($undescribed);
$undescribedDB->links = array($aboutIt);
$undescribedNodes = rdfResponseNodes($undescribedDB, $taxonModule, array($undescribed));
$undescribedByID = array_column($undescribedNodes, NULL, '@id');
check(count($undescribedDB->queries) === 2, 'No equivalence lookup for a row that is matched to nothing');
check($undescribedByID[rdfRecordURI($taxonModule, 'bio.acousti.ca', '798')]['dwc:scientificName'] === '"allpurpose urchip"', 'An unmatched taxon is served as its source gives it');
// A failed lookup is not a taxon with no equivalents.
$impalaDB->fail = TRUE;
check(rdfResponseNodes($impalaDB, $taxonModule, array($impala)) === FALSE, 'Failed equivalence lookup propagates');
$impalaDB->fail = FALSE;
$nodes = array_merge($nodes, $impalaNodes, $outwardNodes);

// Onomatopoeia are the words a source renders a taxon's sound with. They have
// a name's shape and are not names, so a rendering is about its taxon and
// never denotes it.
$onomatopoeiaModule = loadModule('onomatopoeia');
$rendering = array('source' => 'fixture', 'id' => $ref['id'], 'word' => 'bow-wow',
  'kind' => 'imitation', 'kind_link' => 'http://purl.org/olia/olia.owl#OnomatopoeticWord',
  'language' => 'en-GB', 'sex' => '', 'lifeStage' => '', 'locality' => '',
  'remarks' => '', 'info_url' => 'https://bio.acousti.ca/node/58101');
$renderingURI = rdfRecordURI($onomatopoeiaModule, $rendering['source'], $rendering['id']);
check(recordModule('/onomatopoeia/fixture/on1')['mname'] === 'onomatopoeia', 'Onomatopoeia route');
$rendersTaxon = $namesTaxon;
$rendersTaxon['id'] = 'renders-taxon';
$rendersTaxon['subject_type'] = 'onomatopoeia'; $rendersTaxon['subject_id'] = $rendering['id'];
$rendersTaxon['predicate'] = 'http://purl.obolibrary.org/obo/IAO_0000136';
$renderedIn = $rendersTaxon;
$renderedIn['id'] = 'renders-from';
$renderedIn['predicate'] = 'http://purl.org/dc/terms/source';
$renderedIn['object_type'] = 'references'; $renderedIn['object_source'] = 'fixture';
$renderedIn['object_id'] = $ref['id'];
$renderingDB = new FixtureDB($rendering);
$renderingDB->links = array($rendersTaxon, $renderedIn);
$renderingNodes = rdfResponseNodes($renderingDB, $onomatopoeiaModule, array($rendering));
$renderingByID = array_column($renderingNodes, NULL, '@id');
check($renderingDB->bound[0] === array('onomatopoeia', 'fixture', $rendering['id']), 'Onomatopoeia identity used in link lookup');
check($renderingByID[$renderingURI]['@type'] === array('http://purl.org/dc/dcmitype/Text', 'http://www.w3.org/ns/lemon/ontolex#LexicalEntry'), 'A worded rendering is a text and a lexical entry');
check($renderingByID[$renderingURI]['rdfs:label'] === array('@value' => 'bow-wow', '@language' => 'en-GB'), 'Word is a literal in the language it is in');
check($renderingByID[$renderingURI]['dc:type'] === 'imitation', "Kind kept as its source's own word");
check($renderingByID[$renderingURI]['dcterms:type']['@id'] === 'http://purl.org/olia/olia.owl#OnomatopoeticWord', 'Kind names the OLiA class beside it');
check($renderingByID[$renderingURI]['dcterms:language'] === rdfTyped('en-GB', 'xsd:language'), 'Language tag given on its own, typed as BCP 47 syntax');
check(!isset($renderingByID[$renderingURI]['dwc:sex']), 'Empty values omitted');
check($renderingByID[$renderingURI]['http://purl.obolibrary.org/obo/IAO_0000136']['@id'] === 'https://api.audioblast.org/taxon/other-source/42', 'Rendering is about the taxon whose sound it renders');
check(!isset($renderingByID[$renderingURI]['http://purl.obolibrary.org/obo/IAO_0000219']), 'A rendering never denotes its taxon');
check($renderingByID[$renderingURI]['dcterms:source']['@id'] === $uri, 'Rendering was taken from a reference');
check(strpos(rdfTurtle(array($renderingByID[$renderingURI])), '"bow-wow"@en-GB') !== FALSE, 'Turtle carries the language tag');
// What the source says about a rendering that its fields have no column for.
$ofAMale = $rendering; $ofAMale['id'] = 'on2'; $ofAMale['word'] = 'cock-a-doodle-do';
$ofAMale['language'] = 'en'; $ofAMale['sex'] = 'male'; $ofAMale['lifeStage'] = 'juvenile';
$ofAMale['locality'] = 'Lokele tribe of the Congo'; $ofAMale['remarks'] = 'Sound of wings';
$ofAMaleNode = rdfNodes($onomatopoeiaModule, array($ofAMale))[0];
check($ofAMaleNode['dwc:sex'] === 'male' && $ofAMaleNode['dwc:lifeStage'] === 'juvenile', 'Sex and life stage a rendering is of');
check($ofAMaleNode['dwc:locality'] === 'Lokele tribe of the Congo', 'Who uses a rendering is its locality');
check($ofAMaleNode['dwc:taxonRemarks'] === 'Sound of wings', 'Remarks kept as the source writes them');
// A kind no vocabulary names takes no IRI, and is not called a word: a line of
// musical notation is a text and nothing more, in no language at all.
$notation = $rendering; $notation['id'] = 'on3'; $notation['word'] = 'E-F-F#';
$notation['kind'] = 'musical notation'; $notation['kind_link'] = ''; $notation['language'] = '';
$notationNode = rdfNodes($onomatopoeiaModule, array($notation))[0];
check($notationNode['@type'] === array('http://purl.org/dc/dcmitype/Text'), 'A rendering that is not a word is only a text');
check($notationNode['dc:type'] === 'musical notation', "Kind still kept as the source's word");
check(!isset($notationNode['dcterms:type']), 'No IRI invented for a kind nothing names');
check($notationNode['rdfs:label'] === 'E-F-F#', 'Notation is a plain literal');
check(!isset($notationNode['dcterms:language']), 'No language invented');
$_SERVER['REQUEST_URI'] = '/onomatopoeia/fixture/book/a%20%231';
$_GET = array('output' => 'JSON'); $renderingDB->queries = array();
ob_start(); recordAPI($renderingDB); $renderingJSON = json_decode(ob_get_clean(), TRUE);
check($renderingJSON['data'][0] === $rendering && count($renderingDB->queries) === 1, 'Onomatopoeia JSON unchanged, no link query');
$_GET = array(); $_SERVER['HTTP_ACCEPT'] = 'text/turtle';
ob_start(); recordAPI($renderingDB); $renderingTTL = ob_get_clean();
check($renderingTTL === rdfTurtle($renderingNodes), 'Onomatopoeia URI serves embedded Turtle');
$nodes = array_merge($nodes, $renderingNodes);

// A rendering is not read onto its taxon as a name: the taxon embed asks for
// what denotes the taxon, and being about it is not that. This is what keeps
// bark out of the names of Canis lupus familiaris.
$renderedTaxon = new FixtureDB($namedTaxon);
$renderedTaxon->links = array($rendersTaxon);
$renderedTaxon->embedded = array($rendering);
$renderedNodes = rdfResponseNodes($renderedTaxon, $taxonModule, array($namedTaxon));
$renderedByID = array_column($renderedNodes, NULL, '@id');
check(count($renderedTaxon->queries) === 2, 'No name lookup for a rendering about the taxon');
check(!isset($renderedByID[$namedTaxonURI]['dwc:vernacularName']), 'A rendering is not one of the names a taxon is known by');
check(count($renderedByID[$namedTaxonURI]['@reverse']['http://purl.obolibrary.org/obo/IAO_0000136']) === 1, 'The rendering is still reached from its taxon');


// A recording's sound, rights and place use the terms Audiovisual Core borrows.
$described = $recording;
$described['sample_rate'] = '44100'; $described['channels'] = '2';
$described['rights_holder'] = 'Natural History Museum, London';
$described['country'] = 'GB'; $described['locality'] = 'A place';
$describedNode = rdfNodes($recordingModule, array($described))[0];
check($describedNode['mo:sample_rate'] === rdfTyped('44100', 'xsd:decimal'), 'Sample rate from the Music Ontology');
check($describedNode['xmpRights:Owner'] === $described['rights_holder'], 'Rights holder is the XMP owner');
check($describedNode['dwc:countryCode'] === 'GB' && $describedNode['dwc:locality'] === 'A place', 'Where the recording was made');
check(!in_array($described['channels'], $describedNode, TRUE), 'Channels are left out, as no term covers them');
$nodes = array_merge($nodes, array($describedNode));

// Annotations use their own ID, never the recording ID, and retain ordinary JSON.
$annotationModule = loadModule('annomate');
$annotation = array('source' => 'fixture', 'annotation_id' => $ref['id'], 'source_id' => 'rec1',
  'time_start' => '0', 'time_end' => '1.25', 'annotator' => 'Observer',
  'annotation_date' => '2026-09-19', 'annotation_info_url' => 'https://example.org/annotation',
  'recording_url' => 'https://example.org/audio.wav', 'taxon' => 'Example species',
  'type' => 'Call', 'lat' => '0', 'lon' => '-1.5', 'contact' => 'Unmapped');
$annotationURI = rdfRecordURI($annotationModule, 'fixture', $annotation['annotation_id']);
$annotationNodes = rdfNodes($annotationModule, array($annotation));
check($annotationNodes[0]['@id'] === $annotationURI, 'Annotation identity uses annotation_id');
check($annotationNodes[0]['ac:startTime'] === rdfTyped('0', 'xsd:decimal'), 'Zero time preserved');
check(!isset($annotationNodes[0]['ac:accessURI']), 'Access URL belongs to recording');
$sparseAnnotation = array('source' => 'fixture', 'annotation_id' => 'sparse');
check(count(rdfNodes($annotationModule, array($sparseAnnotation))) === 1, 'Missing recording creates no invented target');
check(count(annomate_rdf_node($sparseAnnotation, 'https://example.org/roi')) === 2, 'Missing values omitted');
$annotationDB = new FixtureDB($annotation);
$_SERVER['REQUEST_URI'] = '/annotation/fixture/book/a%20%231';
$_GET = array('output' => 'JSON');
ob_start(); recordAPI($annotationDB); $annotationJSON = json_decode(ob_get_clean(), TRUE);
check($annotationJSON['data'][0] === $annotation && count($annotationDB->queries) === 1, 'Annotation JSON keys unchanged');
$_GET = array(); $_SERVER['HTTP_ACCEPT'] = 'application/ld+json';
ob_start(); recordAPI($annotationDB); $annotationLD = json_decode(ob_get_clean(), TRUE);
check($annotationLD['@graph'] === $annotationNodes, 'Annotation URI negotiates JSON-LD');
$_SERVER['HTTP_ACCEPT'] = 'text/turtle';
ob_start(); recordAPI($annotationDB); $annotationTTL = ob_get_clean();
check($annotationTTL === rdfTurtle($annotationNodes), 'Annotation URI negotiates Turtle');
$annotationLink = $citation;
$annotationLink['subject_type'] = 'annomate'; $annotationLink['subject_id'] = $annotation['annotation_id'];
$annotationDB->links = array($annotationLink); $annotationDB->bound = array();
$linkedAnnotations = rdfResponseNodes($annotationDB, $annotationModule, array($annotation));
check($annotationDB->bound[0] === array('annomate', 'fixture', $annotation['annotation_id']), 'Link lookup uses annotation identity');
$nodes = array_merge($nodes, $linkedAnnotations);

// Access metadata describes representations, shared by recording and ROI graphs.
$accessRecording = $recording;
$accessRecording['filename'] = $annotation['recording_url'];
$accessRecording['mime'] = 'audio/wav';
$accessNodes = rdfNodes($recordingModule, array($accessRecording));
$service = $accessNodes[1];
check($annotationNodes[2]['@id'] === $service['@id'], 'Recording and ROI share access point identity');
check(!isset($accessNodes[0]['ac:accessURI']) && !isset($accessNodes[0]['dc:format']), 'Representation metadata moved off recording');
check($service['dc:format'] === 'audio/wav', 'MIME belongs to service');
check(rdfServiceAccessPoint($recordingURI ?? 'https://example.org/recording', '', '') === NULL, 'No empty service');
$formatOnly = rdfServiceAccessPoint('https://example.org/recording', 'local.wav', 'audio/wav');
check(!isset($formatOnly['ac:accessURI']) && $formatOnly['dc:format'] === 'audio/wav', 'Format retained without invented URL');
$alternate = rdfServiceAccessPoint($accessNodes[0]['@id'], 'https://example.org/audio.mp3', 'audio/mpeg');
check($alternate['@id'] !== $service['@id'], 'Distinct URLs have distinct access points');
// Multiple representations remain separate even when they share a MIME type.
$spectrogram = rdfServiceAccessPoint($accessNodes[0]['@id'], 'https://example.org/spectrogram.png', 'image/png');
$thumbnail = rdfServiceAccessPoint($accessNodes[0]['@id'], 'https://example.org/thumbnail.png', 'image/png');
check($spectrogram['@id'] !== $thumbnail['@id'], 'Same-format representations have distinct identities');
$representations = array($service, $spectrogram, $thumbnail);
$representationGraph = $representations;
foreach ($representations as $representation) {
  $representationGraph[] = array('@id' => $accessNodes[0]['@id'],
    'ac:hasServiceAccessPoint' => rdfIRI($representation['@id']));
}
$representationGraph = rdfMergeNodes($representationGraph);
$representationByID = array_column($representationGraph, NULL, '@id');
check(count($representationByID[$accessNodes[0]['@id']]['ac:hasServiceAccessPoint']) === 3, 'Recording can have audio, spectrogram and thumbnail services');
if (isset($argv[1])) {
  file_put_contents($argv[1].'/service-access.jsonld', rdfJSONLD($representationGraph));
  file_put_contents($argv[1].'/service-access.ttl', rdfTurtle($representationGraph));
}
$nodes = array_merge($nodes, $accessNodes);

// What was measured of the file is said of its access point, never of the
// recording, whose duration and sample rate are what the source says.
$measured = $accessRecording;
$measured['duration'] = '30'; $measured['sample_rate'] = '48000';
$measured['calculated_hash'] = str_repeat('ab', 32); $measured['calculated_duration'] = '29.95';
$measured['calculated_sample_rate'] = '44100'; $measured['calculated_channels'] = '2';
$measured['calculated_bit_depth'] = '16'; $measured['calculated_codec'] = 'pcm_s16le';
$measured['calculated_bit_rate'] = '1411200'; $measured['calculated_bytes'] = '5283884';
// Records reach RDF with the module's field names, never its columns'
foreach (array_keys($measured) as $field) {
  check(isset($recordingModule['params'][$field]), $field.' is a field recordings are served with');
}
$measuredNodes = rdfNodes($recordingModule, array($measured));
$measuredService = $measuredNodes[1];
check($measuredService['@id'] === $service['@id'], 'Measuring a file does not change its identity');
check($measuredService['ac:hashFunction'] === 'SHA-256' && $measuredService['ac:hashValue'] === $measured['calculated_hash'], 'Hash in Audiovisual Core terms');
check($measuredService['ac:mediaDuration'] === rdfTyped('29.95', 'xsd:decimal'), 'Measured duration');
check($measuredService['mo:sample_rate'] === rdfTyped('44100', 'xsd:decimal'), 'Measured sample rate');
check($measuredService['mo:channels'] === rdfTyped('2', 'xsd:integer'), 'Measured channels');
check($measuredService['mo:bitsPerSample'] === rdfTyped('16', 'xsd:integer'), 'Measured bit depth');
check($measuredService['mo:encoding'] === 'pcm_s16le', 'Measured codec');
check($measuredNodes[0]['ac:mediaDuration'] === rdfTyped('30', 'xsd:decimal')
  && $measuredNodes[0]['mo:sample_rate'] === rdfTyped('48000', 'xsd:decimal'), 'The source says its own on the recording');
check(array_keys($measuredService) === array('@id', '@type', 'ac:accessURI', 'dc:format', 'ac:hashFunction', 'ac:hashValue',
  'ac:mediaDuration', 'mo:sample_rate', 'mo:channels', 'mo:bitsPerSample', 'mo:encoding', 'ebucore:bitRate', 'ebucore:fileSize'),
  'Nothing more is said of the file');
check($measuredService['ebucore:bitRate'] === rdfTyped('1411200', 'xsd:nonNegativeInteger'), 'Measured bit rate from EBUCore');
check($measuredService['ebucore:fileSize'] === rdfTyped('5283884', 'xsd:double'), 'Measured size from EBUCore, typed as its range is');
$unmeasured = $accessRecording;
foreach (array('hash', 'duration', 'sample_rate', 'channels', 'bit_depth', 'codec', 'bit_rate', 'bytes') as $c) {$unmeasured['calculated_'.$c] = NULL;}
check(rdfNodes($recordingModule, array($unmeasured))[1] === $service, 'A file not measured is described as before');
$nodes = array_merge($nodes, array($measuredService));

// Framing must retain every triple, including when both ends are requested.
$recordingURI = 'https://api.audioblast.org/recording/fixture/rec1';
$frameInput = rdfMergeNodes(array_merge(rdfNodes($module, array($ref)),
  rdfNodes($recordingModule, array($recording)), rdfNodes($links, array($citation))));
$both = rdfFrameIncoming($frameInput, array($uri, $recordingURI));
$bothByID = array_column($both, NULL, '@id');
check(isset($bothByID[$uri]['@reverse']['dcterms:isReferencedBy']), 'Both endpoints: reverse retained');
check(isset($bothByID[$recordingURI]['dcterms:isReferencedBy']), 'Both endpoints: forward retained');
$repeated = $citation; $repeated['id'] = 'citation-other'; $repeated['remarks'] = 'p. 8';
$several = rdfFrameIncoming(rdfMergeNodes(array_merge($frameInput, rdfNodes($links, array($repeated)))), array($uri));
$severalByID = array_column($several, NULL, '@id');
check(count($severalByID[$uri]['@reverse']['dcterms:isReferencedBy']) === 1, 'Repeated assertion has one reverse value');
check(isset($severalByID['https://api.audioblast.org/link/curator/citation-other']), 'Independent assertion retained');
// Two distinct incoming recordings, plus a self-link, keep all targets.
$another = $citation; $another['id'] = 'citation-another'; $another['subject_id'] = 'rec2';
$self = $citation; $self['id'] = 'citation-self'; $self['subject_type'] = 'references'; $self['subject_id'] = $ref['id'];
$manyInput = rdfMergeNodes(array_merge($frameInput, rdfNodes($links, array($another, $self))));
$many = rdfFrameIncoming($manyInput, array($uri));
$manyByID = array_column($many, NULL, '@id');
check(count($manyByID[$uri]['@reverse']['dcterms:isReferencedBy']) === 3, 'Multiple incoming and self links retained');
check(rdfCompactIRI('http://purl.org/dc/terms/isReferencedBy') === 'dcterms:isReferencedBy', 'Known predicate compacted');
check(rdfCompactIRI('https://example.org/custom') === 'https://example.org/custom', 'Unknown predicate retained');
if (isset($argv[1])) {
  file_put_contents($argv[1].'/framing-before.ttl', rdfTurtle($manyInput));
  file_put_contents($argv[1].'/framing-after.jsonld', rdfJSONLD($many));
  file_put_contents($argv[1].'/framing-after.ttl', rdfTurtle($many));
  file_put_contents($argv[1].'/linked-data.jsonld', rdfJSONLD($nodes));
  file_put_contents($argv[1].'/linked-data.ttl', rdfTurtle($nodes));
}
echo "Linked-data fixture and routing checks passed.\n";

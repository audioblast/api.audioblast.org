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
class FixtureDB {
  private $row;
  public $links = array();
  public $bound = array();
  public $queries = array();
  public $fail = FALSE;
  function __construct($row) {$this->row = $row;}
  function prepare($sql) {
    $this->queries[] = $sql;
    if (strpos($sql, 'FROM links WHERE') !== FALSE) {return(new LinkFixtureStatement($this));}
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

// A description says what it is about and what it cites through links.
$descriptionModule = loadModule('descriptions');
$description = array('source' => 'fixture', 'id' => '12289', 'topic' => 'behaviour',
  'value' => 'Males were reported to call 0.3-4.0m up, in shrubs along small hill-streams.',
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

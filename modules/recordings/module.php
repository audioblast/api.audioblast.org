<?php

function recordings_info() {
  $info = array(
    "mname" => "recordings",
    "version" => 1.0,
    "category" => "data",
    //recordings with what audioBlastAnalyse measured of each file joined on
    "table" => "v-recordings",
    "hname" => "Recordings",
    "desc" => "This endpoint allows for the querying of recording metadata held within audioBLAST! With output=JSON-LD or output=Turtle (or, without output, an Accept header asking for application/ld+json or text/turtle), recordings are given as RDF in Audiovisual Core terms. Each recording is identified by https://api.audioblast.org/recording/{source}/{id}, which gives the recording in the same way. The calculated_ fields are what audioBLAST! measured of the file itself, rather than what its source says about it, and are empty for a recording that has not been measured yet: calculated_status says how measuring it went (ok, missing or unreadable).",
    //Recordings as RDF (see core/rdf.php), identified by https://api.audioblast.org/recording/{source}/{id}
    "rdf" => array(
      "links" => TRUE,
      "path" => "recording",
      "node" => "recordings_rdf_node",
      "related" => "recordings_rdf_related"
    ),
    "params" => array(
      "source" => array(
        "desc" => "Source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "id" => array(
        "desc" => "ID",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "multiple" => TRUE,
        "autocomplete" => TRUE,
        "ac" => "ac:providerManagedID"
      ),
      "name" => array(
        "desc" => "Recording name",
        "type" => "string",
        "default" => "",
        "column" => "Title",
        "op" => "contains",
        "fulltext" => TRUE,
        "autocomplete" => TRUE,
        "ac" => "dcterms:title"
      ),
      "taxon" => array(
        "desc" => "Taxon",
        "type" => "string",
        "default" => "",
        "column" => "taxon",
        "op" => "contains",
        "fulltext" => TRUE,
        "autocomplete" => TRUE,
        "suggest" => array(
          "desc"  => "Recordings of same taxon",
          "same_source" => FALSE
        ),
        "ac" => "dwc:scientificName"
      ),
      "filename" => array(
        "desc" => "File name",
        "type" => "string",
        "default" => "",
        "column" => "file",
        "op" => "contains",
        "ac" => "ac:accessURI"
      ),
      "author" => array(
        "desc" => "Author",
        "type" => "string",
        "default" => "",
        "column" => "author",
        "op" => "contains",
        "fulltext" => TRUE,
        "autocomplete" => TRUE,
        "suggest" => array(
          "desc" => "By same contributor in source",
          "same_source" => TRUE
        ),
        "ac" => "dc:creator"
      ),
      "post_date" => array(
        "desc" => "Date the content was uploaded (YYYY-MM-DD)",
        "type" => "string",
        "column" => "post_date",
        "op" => "none",
        "ac" => "dcterms:available"
      ),
      "human_size" => array(
        "desc" => "Human readble size of file",
        "type" => "string",
        "column" => "size",
        "op" => "none"
      ),
      "bytes" => array(
        "desc" => "File size",
        "type" => "range",
        "default" => "",
        "column" => "size_raw",
        "op" => "range"
      ),
      "mime" => array(
        "desc" => "MIME type of content",
        "type" => "string",
        "column" => "type",
        "default" => "",
        "op" => "=",
        "autocomplete" => TRUE,
        "ac" => "dc:format"
      ),
      "recording_type" => array(
        "desc" => "Used to identify soundscape recordings",
        "type" => "string",
        "column" => "NonSpecimen",
        "default" => "",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "date" => array(
        "desc" => "Date the recording was made (YYYY-MM-DD, or YYYY-MM or YYYY when only the month or year is known)",
        "type" => "string",
        "column" => "Date",
        "default" => "",
        "op" => "none",
        "ac" => "xmp:CreateDate"
      ),
      "time" => array(
        "desc" => "Time the recording was made (HH:MM, 24-hour clock)",
        "type" => "string",
        "column" => "Time",
        "default" => "",
        "op" => "none"
      ),
      "time_of_day" => array(
        "desc" => "Time of day in words, where it is not a clock time, e.g. morning",
        "type" => "string",
        "column" => "time_of_day",
        "default" => "",
        "op" => "none",
        "ac" => "ac:timeOfDay"
      ),
      "duration" => array(
        "desc" => "Duration",
        "type" => "range",
        "default" => "",
        "column" => "Duration",
        "op" => "range",
        "ac" => "ac:mediaDuration"
      ),
      "deployment" => array(
        "desc" => "Deployment",
        "type" => "string",
        "column" => "deployment",
        "default" => "",
        "op" => "="
      ),
      "lat" => array(
        "desc" => "Latitude, for recordings that are not part of a deployment",
        "type" => "range",
        "column" => "lat",
        "default" => "",
        "op" => "range",
        "ac" => "dwc:decimalLatitude"
      ),
      "lon" => array(
        "desc" => "Longitude, for recordings that are not part of a deployment",
        "type" => "range",
        "column" => "lon",
        "default" => "",
        "op" => "range",
        "ac" => "dwc:decimalLongitude"
      ),
      "license" => array(
        "desc" => "URL of the recording's licence",
        "type" => "string",
        "column" => "license",
        "default" => "",
        "op" => "=",
        "autocomplete" => TRUE,
        "ac" => "dcterms:rights"
      ),
      "info_url" => array(
        "desc" => "URL of the recording's page at its source",
        "type" => "string",
        "column" => "info_url",
        "default" => "",
        "op" => "none",
        "ac" => "ac:furtherInformationURL"
      ),
      "device" => array(
        "desc" => "Device the recording was made with",
        "type" => "string",
        "column" => "device",
        "default" => "",
        "op" => "contains",
        "ac" => "ac:captureDevice"
      ),
      "rights_holder" => array(
        "desc" => "Who holds the rights in the recording",
        "type" => "string",
        "column" => "rights_holder",
        "default" => "",
        "op" => "contains",
        "autocomplete" => TRUE,
        "ac" => "xmpRights:Owner"
      ),
      "country" => array(
        "desc" => "ISO 3166-1 alpha-2 code of the country the recording was made in, e.g. GB",
        "type" => "string",
        "column" => "country",
        "default" => "",
        "op" => "=",
        "autocomplete" => TRUE,
        "ac" => "dwc:countryCode"
      ),
      "locality" => array(
        "desc" => "Where the recording was made, which is not always where a specimen was collected",
        "type" => "string",
        "column" => "locality",
        "default" => "",
        "op" => "contains",
        "ac" => "dwc:locality"
      ),
      "sample_rate" => array(
        "desc" => "Samples a second of the recording, in Hz",
        "type" => "range",
        "column" => "sample_rate",
        "default" => "",
        "op" => "range",
        "ac" => "mo:sample_rate"
      ),
      "channels" => array(
        "desc" => "Number of channels the recording has, so that a stereo recording has 2",
        "type" => "range",
        "column" => "channels",
        "default" => "",
        "op" => "range"
      ),
      "calculated_hash" => array(
        "desc" => "SHA-256 hash of the file, as measured",
        "type" => "string",
        "column" => "calculated_hash",
        "default" => "",
        "op" => "=",
        "multiple" => TRUE
      ),
      "calculated_duration" => array(
        "desc" => "Duration of the file in seconds, as measured",
        "type" => "range",
        "column" => "calculated_duration",
        "default" => "",
        "op" => "range"
      ),
      "calculated_channels" => array(
        "desc" => "Number of channels in the file, as measured",
        "type" => "range",
        "column" => "calculated_channels",
        "default" => "",
        "op" => "range"
      ),
      "calculated_sample_rate" => array(
        "desc" => "Samples a second in the file, in Hz, as measured",
        "type" => "range",
        "column" => "calculated_sample_rate",
        "default" => "",
        "op" => "range"
      ),
      "calculated_bit_depth" => array(
        "desc" => "Bits a sample is held in, as measured, for lossless formats only",
        "type" => "range",
        "column" => "calculated_bit_depth",
        "default" => "",
        "op" => "range"
      ),
      "calculated_bit_rate" => array(
        "desc" => "Bit rate of the file, in bits a second, as measured",
        "type" => "range",
        "column" => "calculated_bit_rate",
        "default" => "",
        "op" => "range"
      ),
      "calculated_codec" => array(
        "desc" => "Format the audio in the file is in, as measured, e.g. mp3 or pcm_s16le",
        "type" => "string",
        "column" => "calculated_codec",
        "default" => "",
        "op" => "=",
        "multiple" => TRUE,
        "autocomplete" => TRUE
      ),
      "calculated_bytes" => array(
        "desc" => "Size of the file in bytes, as measured",
        "type" => "range",
        "column" => "calculated_size_raw",
        "default" => "",
        "op" => "range"
      ),
      "calculated_status" => array(
        "desc" => "How measuring the file went: ok, missing (there was no file) or unreadable (no audio could be read from it). Empty where it has not been measured",
        "type" => "string",
        "column" => "calculated_status",
        "default" => "",
        "op" => "=",
        "multiple" => TRUE,
        "autocomplete" => TRUE
      ),
      "calculated_error" => array(
        "desc" => "What went wrong in measuring the file, where something did",
        "type" => "string",
        "column" => "calculated_error",
        "default" => "",
        "op" => "none"
      ),
      "calculated_at" => array(
        "desc" => "When the file was measured",
        "type" => "string",
        "column" => "calculated_at",
        "default" => "",
        "op" => "none"
      ),
      "calculated_by" => array(
        "desc" => "What measured the file, as the package and its version, e.g. audioBlastAnalyse 0.2.0",
        "type" => "string",
        "column" => "calculated_by",
        "default" => "",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "format" => array(
        "desc" => "Data representation to return.",
        "type" => "string",
        "allowed" => array(
          "internal",
          "ac"
        ),
       "default" => "internal"
      ),
      "output" => array(
        "desc" => "The format of the returned data",
        "type" => "string",
        "allowed" => array(
          "JSON",
          "nakedJSON",
          "tabulator",
          "JSON-LD",
          "Turtle"
        ),
        "default" => "JSON"
      )
    ),
  );
  return($info);
}

//A recording as an RDF node (see core/rdf.php) at its URI, in Audiovisual Core
//terms. Its page at its source is where more information about it is.
function recordings_rdf_node($recording, $uri) {
  static $providers = NULL;
  if ($providers === NULL) {
    $providers = array();
    foreach (loadModules("source") as $source) {
      $providers[$source["mname"]] = $source["hname"];
    }
  }

  $node = array(
    "@id" => $uri,
    "@type" => array("http://rs.tdwg.org/ac/terms/Media", "http://purl.org/dc/dcmitype/Sound"),
    "dcterms:type" => rdfIRI("http://purl.org/dc/dcmitype/Sound")
  );
  //Soundscapes are described by their content. Audiovisual Core's Content
  //Description vocabulary has no term for them yet, so this is the literal.
  //Other recordings of a taxon are recordings of organisms.
  $soundscape = ($recording["recording_type"] == "Soundscape");
  if ($soundscape) {
    rdfAdd($node, "ac:CVtermLiteral", "Soundscape");
  } else {
    rdfAdd($node, "ac:tag", $recording["recording_type"]);
    if ($recording["taxon"] != "") {
      rdfAdd($node, "ac:subtype", rdfIRI("http://rs.tdwg.org/acsubtype/values/RecordedOrganism"));
    }
  }
  rdfAdd($node, "dcterms:title", $recording["name"]);
  rdfAdd($node, "dwc:scientificName", $recording["taxon"]);
  rdfAdd($node, "dc:creator", $recording["author"]);
  rdfAdd($node, "xmp:CreateDate", rdfDate($recording["date"], $recording["time"]));
  rdfAdd($node, "ac:timeOfDay", $recording["time_of_day"]);
  rdfAdd($node, "ac:mediaDuration", rdfDecimal($recording["duration"]));
  rdfAdd($node, "dwc:decimalLatitude", rdfDecimal($recording["lat"]));
  rdfAdd($node, "dwc:decimalLongitude", rdfDecimal($recording["lon"]));
  rdfAdd($node, "ac:captureDevice", $recording["device"]);
  //Audiovisual Core has no term for the number of channels, so it is left out
  rdfAdd($node, "mo:sample_rate", rdfDecimal($recording["sample_rate"]));
  rdfAdd($node, "dwc:countryCode", $recording["country"]);
  rdfAdd($node, "dwc:locality", $recording["locality"]);
  rdfAdd($node, "dcterms:rights", rdfURL($recording["license"]));
  rdfAdd($node, "xmpRights:Owner", $recording["rights_holder"]);
  rdfAdd($node, "ac:providerLiteral", $providers[$recording["source"]] ?? $recording["source"]);
  rdfAdd($node, "ac:providerManagedID", $recording["id"]);
  rdfAdd($node, "dcterms:available", rdfDate($recording["post_date"]));
  rdfAdd($node, "ac:furtherInformationURL", rdfURL($recording["info_url"]));
  $service = recordings_rdf_service($recording, $uri);
  if ($service !== NULL) {$node["ac:hasServiceAccessPoint"] = rdfIRI($service["@id"]);}
  return($node);
}

function recordings_rdf_related($recording, $uri) {
  $service = recordings_rdf_service($recording, $uri);
  return($service === NULL ? array() : array($service));
}

//The recording's file as a service access point, with what audioBlastAnalyse
//measured of it (the calculated_ fields). They are measurements of the file,
//so they are said of the access point rather than of the recording, where the
//source's own duration and sample rate are, and the two never contradict each
//other. Audiovisual Core's terms are used where it has them, and the Music
//Ontology, which it borrows the sample rate from, where it does not. Bit rate
//and size in bytes have no term in either, and are EBUCore's, typed as its
//ranges are: the size as xsd:double, the bit rate as xsd:nonNegativeInteger.
function recordings_rdf_service($recording, $uri) {
  $service = rdfServiceAccessPoint($uri, $recording["filename"] ?? NULL, $recording["mime"] ?? NULL);
  if ($service === NULL) {return(NULL);}
  $hash = $recording["calculated_hash"] ?? NULL;
  if ($hash !== NULL && $hash !== "") {
    rdfAdd($service, "ac:hashFunction", "SHA-256");
    rdfAdd($service, "ac:hashValue", $hash);
  }
  rdfAdd($service, "ac:mediaDuration", rdfDecimal($recording["calculated_duration"] ?? NULL));
  rdfAdd($service, "mo:sample_rate", rdfDecimal($recording["calculated_sample_rate"] ?? NULL));
  rdfAdd($service, "mo:channels", rdfInteger($recording["calculated_channels"] ?? NULL));
  rdfAdd($service, "mo:bitsPerSample", rdfInteger($recording["calculated_bit_depth"] ?? NULL));
  rdfAdd($service, "mo:encoding", $recording["calculated_codec"] ?? NULL);
  $rate = $recording["calculated_bit_rate"] ?? NULL;
  if (preg_match('/^[0-9]+$/', (string)$rate)) {rdfAdd($service, "ebucore:bitRate", rdfTyped($rate, "xsd:nonNegativeInteger"));}
  $size = $recording["calculated_bytes"] ?? NULL;
  if (preg_match('/^[0-9]+$/', (string)$size)) {rdfAdd($service, "ebucore:fileSize", rdfTyped($size, "xsd:double"));}
  return($service);
}

function recordings_embed_info() {
  $info = array(
    "recording" => array(
        "callback" => "recordings_embed",
        "desc" => "Returns embeddable recording",
        "returns" => "html",
        "embed" => "iframe",
        "params" => array(
          "source" => array(
             "desc" => "Source",
             "type" => "string",
             "default" => "",
             "column" => "source",
             "op" => "="
          ),
          "id" => array(
            "desc" => "ID",
            "type" => "string",
            "default" => "",
            "column" => "id",
            "op" => "="
          ),
          "title" => array(
            "desc" => "Include title in output?",
            "type" => "boolean",
            "allowed" => array(
              "true",
              "false"
            ),
            "default"=> "true"
          ),
          "credit" => array(
            "desc" => "Credit audioblast.org",
            "type" => "boolean",
            "allowed" => array(
              "true",
              "false"
            ),
            "default" => "true"
          ),
          "output" => array(
            "desc" => "Type of player to return.",
            "type" => "string",
            "allowed" => array(
              "html5",
              "zcjs"
            ),
            "default" => "html5"
          )
        )
      )
  );
  return($info);
}


function recordings_embed($f) {
  global $db;
  $sql  = "SELECT * FROM recordings WHERE source = '".$f['source']."' ";
  $sql .= "AND id = '".$f['id']."';";
  $res  = $db->query($sql);
  $file = $res->fetch_assoc();
  switch($f['output']) {
    case 'html5':
      return(recordings_embed_html5($f, $file));
      break;
    case "zcjs":
      return(recordings_embed_zcjs($f, $file));
      break;
  }
}


function recordings_embed_html5($f, $file) {
  $ret  = "<figure class='audioblast-embed-html5-figure'>";
  if ($f["title"] == "true") {
    $ret .= "<figcaption>".$file["Title"].":</figcaption>";
  }
  $ret .= "<audio ";
  $ret .= "controls ";
  $ret .= "src='".$file["file"]."'>";
  $ret .= "Your browser does not support the <code>audio</code> element.";
  $ret .= "</audio>";
  if ($f["credit"] == "true") {
    $ret .= "<p>Powered by <a href='https://audioblast.org'>Audioblast</a>.</p>";
  }
  $ret .= "</figure>";

  $mret = array();
  $mret["html"] = $ret;
  return($mret);
}

function recordings_embed_zcjs($f, $file) {
  $ret  = "<!DOCTYPE html>";
  $ret .= "<html>";
  $ret .= "<head>";
  $ret .= addJavaScript("zcjs");
  $ret .= addJavaScript("plotly");
  $ret .= "</head>";
  $ret .= "<body>";
  $ret .= '<div id="plot-here" width="100%"></div>';
  $ret .= '<script type="text/javascript">';
  $ret .= 'p = new ZCJS("plot-here");';
  $ret .= 'p.setURL("'.$file["file"].'");';
  $ret .= "</script>";
  $ret .= "</body>";
  $ret .= "</html>";
  $mret = array();
  $mret["html"] = $ret;
  return($mret);
}

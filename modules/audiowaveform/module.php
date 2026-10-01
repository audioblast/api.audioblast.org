<?php

function audiowaveform_info() {
  $info = array(
    "mname" => "audiowaveform",
    "version" => 1.1,
    "category" => "analysis",
    "hname" => "Waveform peaks",
    "desc" => "Waveform peaks that audioBLAST! has made from each recording's file with <a href=\"https://github.com/bbc/audiowaveform\">BBC audiowaveform</a>, so that its waveform can be drawn without the audio: the minimum and maximum of each successive block of samples. Each row is one recording and one type, and its value is the URL of the peaks file, which is served as it is rather than through this API. The type json86pps8bit is a <a href=\"https://github.com/bbc/audiowaveform/blob/master/doc/DataFormat.md\">BBC audiowaveform JSON</a> file (version 2), mixed to one channel, with 86 minima and maxima a second at 8 bits. A recording's peaks are also given as peaks_url in /data/recordings/, and as a second service access point in its RDF.",
    "ab-plugin" => TRUE,
    "table" => "analysis-audiowaveform",
    "params" => array(
      "source" => array(
        "desc" => "Filter by source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "=",
        "multiple" => TRUE,
      ),
      "id" => array(
        "desc" => "filter by id within source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "multiple" => TRUE
      ),
      "type" => array(
        "desc" => "The kind of peaks: json86pps8bit is BBC audiowaveform JSON, one channel, 86 points a second, 8-bit",
        "type" => "string",
        "column" => "type",
        "op" => "=",
        "allowed" => array(
          "json86pps8bit"
        )
      ),
      "value" => array(
        "desc" => "URL of the peaks file",
        "type" => "string",
        "column" => "value",
        "op" => "none"
      ),
      "output" => array(
        "desc" => "At present just an array",
        "type" => "string",
        "allowed" => array(
          "JSON"
        ),
        "default" => "JSON"
      )
    )
  );
  return($info);
}

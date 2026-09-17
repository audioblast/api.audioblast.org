<?php

function references_info() {
  $info = array(
    "mname" => "references",
    "version" => 1.0,
    "category" => "data",
    "table" => "references",
    "hname" => "References",
    "desc" => "This endpoint allows for the querying of the bibliographic references held within audioBLAST!",
    "source_notes" => "References are ingested from BibTeX, and are identified by their BibTeX key within each source.",
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
        "desc" => "ID of the reference within its source (its BibTeX key)",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "type" => array(
        "desc" => "Type of reference (BibTeX entry type), e.g. article, book or mastersthesis",
        "type" => "string",
        "default" => "",
        "column" => "type",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "title" => array(
        "desc" => "Title",
        "type" => "string",
        "default" => "",
        "column" => "title",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "author" => array(
        "desc" => "Authors, surname first and separated by semicolons, e.g. Darwin, Charles; von Frisch, Karl",
        "type" => "string",
        "default" => "",
        "column" => "author",
        "op" => "contains"
      ),
      "editor" => array(
        "desc" => "Editors, in the same form as authors",
        "type" => "string",
        "default" => "",
        "column" => "editor",
        "op" => "contains"
      ),
      "year" => array(
        "desc" => "Year of publication",
        "type" => "range",
        "default" => "",
        "column" => "year",
        "op" => "range"
      ),
      "month" => array(
        "desc" => "Month of publication. Some sources, e.g. BioAcoustica, give the date of publication.",
        "type" => "string",
        "default" => "",
        "column" => "month",
        "op" => "contains"
      ),
      "journal" => array(
        "desc" => "Journal",
        "type" => "string",
        "default" => "",
        "column" => "journal",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "booktitle" => array(
        "desc" => "Title of the book or proceedings that the reference is part of",
        "type" => "string",
        "default" => "",
        "column" => "booktitle",
        "op" => "contains"
      ),
      "series" => array(
        "desc" => "Series",
        "type" => "string",
        "default" => "",
        "column" => "series",
        "op" => "contains"
      ),
      "howpublished" => array(
        "desc" => "How an unusual reference was published",
        "type" => "string",
        "default" => "",
        "column" => "howpublished",
        "op" => "contains"
      ),
      "volume" => array(
        "desc" => "Volume",
        "type" => "string",
        "default" => "",
        "column" => "volume",
        "op" => "="
      ),
      "number" => array(
        "desc" => "Number or issue",
        "type" => "string",
        "default" => "",
        "column" => "number",
        "op" => "="
      ),
      "pages" => array(
        "desc" => "Pages",
        "type" => "string",
        "default" => "",
        "column" => "pages",
        "op" => "="
      ),
      "chapter" => array(
        "desc" => "Chapter",
        "type" => "string",
        "default" => "",
        "column" => "chapter",
        "op" => "="
      ),
      "edition" => array(
        "desc" => "Edition",
        "type" => "string",
        "default" => "",
        "column" => "edition",
        "op" => "="
      ),
      "publisher" => array(
        "desc" => "Publisher",
        "type" => "string",
        "default" => "",
        "column" => "publisher",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "organization" => array(
        "desc" => "Organisation, e.g. that sponsored a conference",
        "type" => "string",
        "default" => "",
        "column" => "organization",
        "op" => "contains"
      ),
      "institution" => array(
        "desc" => "Institution that published a report",
        "type" => "string",
        "default" => "",
        "column" => "institution",
        "op" => "contains"
      ),
      "school" => array(
        "desc" => "School (university) of a thesis",
        "type" => "string",
        "default" => "",
        "column" => "school",
        "op" => "contains"
      ),
      "address" => array(
        "desc" => "Place of publication",
        "type" => "string",
        "default" => "",
        "column" => "address",
        "op" => "contains"
      ),
      "type_of_work" => array(
        "desc" => "Type of work (BibTeX type field), e.g. the type of a thesis",
        "type" => "string",
        "default" => "",
        "column" => "type_of_work",
        "op" => "contains"
      ),
      "note" => array(
        "desc" => "Note",
        "type" => "string",
        "default" => "",
        "column" => "note",
        "op" => "contains"
      ),
      "isbn" => array(
        "desc" => "ISBN",
        "type" => "string",
        "default" => "",
        "column" => "isbn",
        "op" => "contains"
      ),
      "issn" => array(
        "desc" => "ISSN",
        "type" => "string",
        "default" => "",
        "column" => "issn",
        "op" => "contains"
      ),
      "doi" => array(
        "desc" => "DOI, without a resolver, e.g. 10.1093/database/bav054",
        "type" => "string",
        "default" => "",
        "column" => "doi",
        "op" => "="
      ),
      "url" => array(
        "desc" => "URL",
        "type" => "string",
        "default" => "",
        "column" => "url",
        "op" => "contains"
      ),
      "attachments" => array(
        "desc" => "URLs of files attached to the reference",
        "type" => "string",
        "default" => "",
        "column" => "attachments",
        "op" => "contains"
      ),
      "keywords" => array(
        "desc" => "Keywords",
        "type" => "string",
        "default" => "",
        "column" => "keywords",
        "op" => "contains"
      ),
      "abstract" => array(
        "desc" => "Abstract",
        "type" => "string",
        "default" => "",
        "column" => "abstract",
        "op" => "contains"
      ),
      "output" => array(
        "desc" => "The format of the returned data",
        "type" => "string",
        "allowed" => array(
          "JSON",
          "nakedJSON",
          "tabulator"
        ),
        "default" => "JSON"
      )
    )
  );
  return($info);
}

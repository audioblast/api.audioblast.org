-- Table for the references module (modules/references/module.php).
--
-- Filled by uploadReferences() in audioBlastIngest, from BibTeX or CSV sources
-- such as the BioAcoustica bibliography. References are keyed by their id
-- within each source (a BibTeX entry's key, and for BioAcoustica the node id
-- of the reference). Fields that a reference does not have are NULL.
--
-- Create the table before deploying the module: data/fetch_data_counts counts
-- every data module's table, so it fails while this one is missing. To add the
-- columns from type_name on to a table created before they were, run
-- references-add-columns.sql instead.

CREATE TABLE `references` (
  `source` varchar(100) NOT NULL,
  `id` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL COMMENT 'BibTeX entry type, e.g. article',
  `title` text,
  `author` text COMMENT 'Surname first, separated by semicolons',
  `editor` text COMMENT 'Surname first, separated by semicolons',
  `year` text,
  `month` text,
  `journal` text,
  `booktitle` text,
  `series` text,
  `howpublished` text,
  `volume` text,
  `number` text,
  `pages` text,
  `chapter` text,
  `edition` text,
  `publisher` text,
  `organization` text,
  `institution` text,
  `school` text,
  `address` text,
  `type_of_work` text COMMENT 'BibTeX type field, e.g. the type of a thesis',
  `note` text,
  `isbn` text,
  `issn` text,
  `doi` text COMMENT 'Without a resolver, e.g. 10.1093/database/bav054',
  `url` text,
  `attachments` text,
  `keywords` text,
  `abstract` text,
  `type_name` varchar(100) COMMENT 'The source''s own name for the type of reference, e.g. Journal Article',
  `journal_abbreviation` text COMMENT 'Abbreviated title of the journal, e.g. Anim Behav',
  `pmid` varchar(20) COMMENT 'PubMed ID',
  `info_url` text COMMENT 'URL of the reference''s page at its source',
  PRIMARY KEY (`source`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

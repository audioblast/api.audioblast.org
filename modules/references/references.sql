-- Table for the references module (modules/references/module.php).
--
-- Filled by uploadReferences() in audioBlastIngest, from BibTeX sources such
-- as the BioAcoustica bibliography. References are keyed by their BibTeX key
-- (for BioAcoustica, the node id of the reference) within each source. Fields
-- that a reference does not have are NULL.
--
-- Create the table before deploying the module: data/fetch_data_counts counts
-- every data module's table, so it fails while this one is missing.

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
  PRIMARY KEY (`source`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Columns added to the references table (modules/references/module.php) for
-- what BioAcoustica's bibliography gives now that it is exported as CSV from
-- its database rather than as BibTeX: the source's own name for the type of
-- reference, the abbreviated title of the journal, the PubMed ID and the
-- reference's page at its source.
--
-- Add the columns before installing the version of audioBlastIngest that
-- reads CSV references and before deploying this module: both fail while the
-- columns are missing. The version of audioBlastIngest installed before it
-- leaves them empty.

ALTER TABLE `references`
  ADD COLUMN `type_name` varchar(100) COMMENT 'The source''s own name for the type of reference, e.g. Journal Article',
  ADD COLUMN `journal_abbreviation` text COMMENT 'Abbreviated title of the journal, e.g. Anim Behav',
  ADD COLUMN `pmid` varchar(20) COMMENT 'PubMed ID',
  ADD COLUMN `info_url` text COMMENT 'URL of the reference''s page at its source';

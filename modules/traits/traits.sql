-- Columns added to the traits table (modules/traits/module.php) for the call a
-- trait was measured on. audioBlastIngest (linkTraits()) fills them by splitting
-- call types as sources write them, e.g. "Courtship Call B (Night)", into the
-- type of call, linked to its term in the Type of Call vocabulary at
-- vocab.audioblast.org, the part of the call (B) and anything else it says
-- (Night). The call type as written stays in Call.Type.
--
-- Add the columns before installing that version of audioBlastIngest and before
-- deploying this module: both fail while the columns are missing. The version of
-- audioBlastIngest installed before it leaves them empty.

ALTER TABLE `traits`
  ADD COLUMN `Call.Part` varchar(20) COMMENT 'Part of the call the trait describes, e.g. B',
  ADD COLUMN `Call.Type.Link` varchar(255) COMMENT 'IRI of the call type''s term in the Type of Call vocabulary',
  ADD COLUMN `Call.Qualifier` text COMMENT 'What else the call type says, e.g. Night';

-- Columns added to the recordings table (modules/recordings/module.php) for each
-- recording's licence, its page at its source and the device it was made with,
-- and for times of day given in words rather than as clock times.
--
-- audioBlastIngest fills them. It also normalises recordings as it uploads them
-- (ISO 8601 dates, HH:MM times), so its next run cleans the values already in
-- the table too.
--
-- Add the columns before installing that version of audioBlastIngest and before
-- deploying this module: both fail while the columns are missing. The version of
-- audioBlastIngest installed before it leaves them empty.

ALTER TABLE `recordings`
  ADD COLUMN `time_of_day` text COMMENT 'Time of day in words, where it is not a clock time, e.g. morning',
  ADD COLUMN `license` varchar(255) COMMENT 'URL of the licence, e.g. https://creativecommons.org/licenses/by/4.0/',
  ADD COLUMN `info_url` text COMMENT 'URL of the recording''s page at its source',
  ADD COLUMN `device` text COMMENT 'Device the recording was made with',
  ADD INDEX `license` (`license`);

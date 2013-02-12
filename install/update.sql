
CREATE TABLE `texts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(32) NOT NULL,
  `language_id` int NOT NULL
) COMMENT='' ENGINE='InnoDB' COLLATE 'utf8_czech_ci';

ALTER TABLE `texts`
ADD `content` text NOT NULL,
COMMENT='';

ALTER TABLE `texts`
DROP `name`,
RENAME TO `text`,
COMMENT='';

INSERT INTO `page` (`name`)
VALUES ('Homepage');

INSERT INTO `page` (`name`)
VALUES ('Kontakt');

ALTER TABLE `text`
ADD `page_id` int(10) unsigned NULL AFTER `id`,
ADD FOREIGN KEY (`page_id`) REFERENCES `page` (`id`),
COMMENT='';

UPDATE `text` SET
`id` = '1',
`page_id` = '1',
`language_id` = '1',
`content` = 'Obsah na titulní straně'
WHERE `id` = '1' COLLATE utf8_bin LIMIT 1;

UPDATE `text` SET
`id` = '2',
`page_id` = '2',
`language_id` = '1',
`content` = 'Obsah na kontaktu'
WHERE `id` = '2' COLLATE utf8_bin LIMIT 1;

INSERT INTO `page` (`name`)
VALUES ('Podmínky');
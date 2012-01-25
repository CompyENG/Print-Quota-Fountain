CREATE TABLE IF NOT EXISTS `papercutDonations` (
  `id` int(255) NOT NULL auto_increment,
  `time` timestamp(14) NOT NULL,
  `username` varchar(8) NOT NULL default '',
  `amount` decimal(10,2) NOT NULL default '0.00',
  `verified` tinyint(1) NOT NULL default '0',
  `comment` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM ;

CREATE TABLE IF NOT EXISTS `papercutRequests` (
  `id` int(255) NOT NULL auto_increment,
  `time` timestamp(14) NOT NULL,
  `username` varchar(8) NOT NULL default '',
  `amount` decimal(10,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM ;
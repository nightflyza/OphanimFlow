create database ophanimflow;
use ophanimflow;

drop table if exists raw_in;
drop table if exists raw_out; 
drop table if exists host_in;
drop table if exists host_out; 
drop table if exists traffstat; 
drop table if exists networks;

create table raw_out (
    ip_dst CHAR(45) NOT NULL,
    port_src INT(2) UNSIGNED NOT NULL,
    ip_proto CHAR(8) NOT NULL, 
    packets INT UNSIGNED NOT NULL,
    bytes BIGINT UNSIGNED NOT NULL,
    stamp_inserted INT(11) NOT NULL,
    stamp_updated INT(11) NOT NULL,
    PRIMARY KEY (ip_dst, port_src, ip_proto, stamp_inserted)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

create table raw_in (
    ip_src CHAR(45) NOT NULL,
    port_dst INT(2) UNSIGNED NOT NULL,
    ip_proto CHAR(8) NOT NULL, 
    packets INT UNSIGNED NOT NULL,
    bytes BIGINT UNSIGNED NOT NULL,
    stamp_inserted INT(11) NOT NULL,
    stamp_updated INT(11) NOT NULL,
    PRIMARY KEY (ip_src, port_dst, ip_proto, stamp_inserted)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


create table host_out (
    ip_dst CHAR(45) NOT NULL,
    packets INT UNSIGNED NOT NULL,
    bytes BIGINT UNSIGNED NOT NULL,
    stamp_inserted INT(11) NOT NULL,
    stamp_updated INT(11) NOT NULL,
    PRIMARY KEY (ip_dst, stamp_inserted)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

create table host_in (
    ip_src CHAR(45) NOT NULL,
    packets INT UNSIGNED NOT NULL,
    bytes BIGINT UNSIGNED NOT NULL,
    stamp_inserted INT(11) NOT NULL,
    stamp_updated INT(11) NOT NULL,
    PRIMARY KEY (ip_src, stamp_inserted)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `traffstat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(16) NOT NULL,
  `month` tinyint NOT NULL,
  `year` smallint NOT NULL,
  `dl` bigint NOT NULL DEFAULT '0',
  `ul` bigint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `month` (`month`),
  KEY `year` (`year`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE `networks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `network` varchar(20) NOT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
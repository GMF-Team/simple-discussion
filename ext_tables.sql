#
# Table structure for table 'tx_simplediscussion_domain_model_comment'
#
CREATE TABLE tx_simplediscussion_domain_model_comment (

	name varchar(255) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	comment text,
	control varchar(255) DEFAULT '' NOT NULL,
	website varchar(255) DEFAULT '' NOT NULL,
	reference int(11) DEFAULT '0' NOT NULL

);

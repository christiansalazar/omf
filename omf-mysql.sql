SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE  TABLE IF NOT EXISTS `omf_object` (
	  `id` INT NOT NULL AUTO_INCREMENT
	, `classname` CHAR(40) NOT NULL
	, `aux_id` VARCHAR(45) NULL
	, `data` BLOB NULL
	, PRIMARY KEY (`id`)
	, INDEX `omf_object_classname_idx` (`classname` ASC)
)ENGINE = InnoDB;

CREATE  TABLE IF NOT EXISTS `omf_relationship` (
	  `id` INT NOT NULL AUTO_INCREMENT
	, `parent` INT NOT NULL
	, `child` INT NOT NULL
 	, `name` VARCHAR(45) NOT NULL
  	, `data` BLOB NULL
	, PRIMARY KEY (`id`)
	, INDEX `omf_relationship_parent_idx` (`parent` ASC)
	, INDEX `omf_relationship_child_idx` (`child` ASC)
	, INDEX `omf_relationship_parent_name_idx` (`parent` ASC , `name` ASC)
	, INDEX `omf_relationship_child_name_idx` (`child` ASC , `name` ASC)
  	, CONSTRAINT `fk_omf_object_relationship_parent`
		FOREIGN KEY (`parent` )
		REFERENCES `omf_object` (`id` )
		ON DELETE CASCADE
		ON UPDATE CASCADE
  	, CONSTRAINT `fk_omf_object_relationship_child`
		FOREIGN KEY (`child` )
		REFERENCES `omf_object` (`id` )
		ON DELETE CASCADE
		ON UPDATE CASCADE
)ENGINE = InnoDB;

CREATE  TABLE IF NOT EXISTS `omf_index` (
	  `id` INT NOT NULL AUTO_INCREMENT
	, `classname` CHAR(20) NOT NULL
	, `metaname` VARCHAR(45) NULL
	, `hashvalue` VARCHAR(45) NULL
	, `object_id` INT NOT NULL
	, PRIMARY KEY (`id`)
	, INDEX `omf_index1_idx` (`classname` ASC, `metaname` ASC, `object_id` ASC)
	, INDEX `omf_index2_idx` (`classname` ASC, `metaname` ASC, `hashvalue` ASC)
  	, CONSTRAINT `fk_omf_index_object`
		FOREIGN KEY (`object_id` )
		REFERENCES `omf_object` (`id` )
		ON DELETE CASCADE
		ON UPDATE CASCADE
)ENGINE = InnoDB;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

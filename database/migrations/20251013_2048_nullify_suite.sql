SET FOREIGN_KEY_CHECKS=0;
START TRANSACTION;

-- Nullify all values in the `suite` column for table `clientes`
UPDATE `clientes` SET `suite` = NULL;

COMMIT;
SET FOREIGN_KEY_CHECKS=1;

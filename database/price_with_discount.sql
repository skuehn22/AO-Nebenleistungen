DELIMITER $$

USE `test`$$

DROP PROCEDURE IF EXISTS `price_with_discount`$$

CREATE DEFINER=`test`@`localhost` PROCEDURE `price_with_discount`()
BEGIN
	  DECLARE p_id INTEGER;
	  DECLARE p_name VARCHAR(100);
	  DECLARE p_price DECIMAL (5, 2);
	  DECLARE done BOOLEAN DEFAULT FALSE;
	  DECLARE product_discount SMALLINT;
	  DECLARE amount_of_discount DECIMAL (5, 2);
	 
	  DECLARE c CURSOR FOR SELECT id, NAME, price FROM product;
	  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
	 
	  OPEN c;
	  FETCH c INTO p_id, p_name, p_price;
	  CREATE TEMPORARY TABLE tmp_price(product_name VARCHAR(100), price_with_discount DECIMAL (5, 2), discount_description TEXT);
	  WHILE NOT done
	  DO
	    SELECT d.value INTO product_discount FROM discount d WHERE d.product_id = p_id;
	    IF product_discount IS NULL THEN
	      SET product_discount = 0;
	    END IF;
	    IF product_discount = 0 THEN
	      INSERT INTO tmp_price VALUES (p_name, p_price, 'Discount is not available');
	    ELSE
	      SET amount_of_discount = (p_price * (product_discount / 100));
	      INSERT INTO tmp_price VALUES (p_name, p_price - amount_of_discount, CONCAT('Discount ', product_discount, '% amount of discount $', amount_of_discount));
	    END IF;
	    FETCH c INTO p_id, p_name, p_price;
	  END WHILE;
	 
	  SELECT * FROM tmp_price;
	  DROP TABLE tmp_price;
    END$$

DELIMITER ;
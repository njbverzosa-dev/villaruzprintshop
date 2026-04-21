SET @new_id = 0;
UPDATE `order_status_history` 
SET `id` = (@new_id := @new_id + 1) 
ORDER BY `id` ASC;

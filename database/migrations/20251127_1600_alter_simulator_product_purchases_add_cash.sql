ALTER TABLE simulator_product_purchases
  ADD COLUMN cash_with_fabiana_usd DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER purchased_qtd;

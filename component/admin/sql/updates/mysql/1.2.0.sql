ALTER TABLE `#__decarofinance_obligations`
  ADD COLUMN `debtor_component` VARCHAR(100) NULL AFTER `source_id`,
  ADD COLUMN `debtor_entity` VARCHAR(100) NULL AFTER `debtor_component`,
  ADD COLUMN `debtor_id` VARCHAR(191) NULL AFTER `debtor_entity`,
  ADD KEY `idx_debtor` (`debtor_component`,`debtor_entity`,`debtor_id`);
ALTER TABLE `#__decarofinance_payments`
  ADD COLUMN `payer_component` VARCHAR(100) NULL AFTER `external_key`,
  ADD COLUMN `payer_entity` VARCHAR(100) NULL AFTER `payer_component`,
  ADD COLUMN `payer_id` VARCHAR(191) NULL AFTER `payer_entity`,
  ADD KEY `idx_payer` (`payer_component`,`payer_entity`,`payer_id`);

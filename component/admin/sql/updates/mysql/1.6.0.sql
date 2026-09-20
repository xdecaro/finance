CREATE TABLE IF NOT EXISTS `#__decarofinance_cost_centers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_key` VARCHAR(191) NOT NULL,
  `code` VARCHAR(64) NULL,
  `title` VARCHAR(255) NOT NULL,
  `owner_component` VARCHAR(100) NULL,
  `owner_entity` VARCHAR(100) NULL,
  `owner_id` VARCHAR(191) NULL,
  `source_component` VARCHAR(100) NULL,
  `source_entity` VARCHAR(100) NULL,
  `source_id` VARCHAR(191) NULL,
  `state` TINYINT NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_external_key` (`external_key`),
  KEY `idx_owner` (`owner_component`,`owner_entity`,`owner_id`),
  KEY `idx_source` (`source_component`,`source_entity`,`source_id`),
  KEY `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__decarofinance_budget_lines`
  ADD COLUMN `cost_center_id` BIGINT UNSIGNED NULL AFTER `planned_amount`,
  ADD KEY `idx_cost_center` (`cost_center_id`);

ALTER TABLE `#__decarofinance_transactions`
  ADD COLUMN `cost_center_id` BIGINT UNSIGNED NULL AFTER `budget_line_id`,
  ADD KEY `idx_cost_center` (`cost_center_id`);

ALTER TABLE `#__decarofinance_orders`
  ADD COLUMN `cost_center_id` BIGINT UNSIGNED NULL AFTER `budget_line_id`,
  ADD KEY `idx_cost_center` (`cost_center_id`);

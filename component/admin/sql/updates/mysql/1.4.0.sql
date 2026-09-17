ALTER TABLE `#__decarofinance_budgets`
  ADD COLUMN `owner_component` VARCHAR(100) NULL AFTER `period_end`,
  ADD COLUMN `owner_entity` VARCHAR(100) NULL AFTER `owner_component`,
  ADD COLUMN `owner_id` VARCHAR(191) NULL AFTER `owner_entity`,
  ADD COLUMN `currency` CHAR(3) NOT NULL DEFAULT 'EUR' AFTER `owner_id`,
  ADD KEY `idx_owner` (`owner_component`,`owner_entity`,`owner_id`);

ALTER TABLE `#__decarofinance_budget_lines`
  ADD COLUMN `code` VARCHAR(64) NULL AFTER `kind`,
  ADD COLUMN `category` VARCHAR(100) NULL AFTER `code`,
  ADD KEY `idx_category` (`category`);

ALTER TABLE `#__decarofinance_transactions`
  ADD COLUMN `account_id` BIGINT UNSIGNED NULL AFTER `external_key`,
  ADD COLUMN `budget_line_id` INT UNSIGNED NULL AFTER `account_id`,
  ADD COLUMN `category` VARCHAR(100) NULL AFTER `direction`,
  ADD COLUMN `counterparty_component` VARCHAR(100) NULL AFTER `source_id`,
  ADD COLUMN `counterparty_entity` VARCHAR(100) NULL AFTER `counterparty_component`,
  ADD COLUMN `counterparty_id` VARCHAR(191) NULL AFTER `counterparty_entity`,
  ADD COLUMN `evidence_component` VARCHAR(100) NULL AFTER `counterparty_id`,
  ADD COLUMN `evidence_entity` VARCHAR(100) NULL AFTER `evidence_component`,
  ADD COLUMN `evidence_id` VARCHAR(191) NULL AFTER `evidence_entity`,
  ADD COLUMN `created` DATETIME NULL AFTER `description`,
  ADD COLUMN `created_by` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `created`,
  ADD KEY `idx_account` (`account_id`),
  ADD KEY `idx_budget_line` (`budget_line_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_counterparty` (`counterparty_component`,`counterparty_entity`,`counterparty_id`);

CREATE TABLE IF NOT EXISTS `#__decarofinance_accounts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_key` VARCHAR(191) NULL,
  `owner_component` VARCHAR(100) NULL,
  `owner_entity` VARCHAR(100) NULL,
  `owner_id` VARCHAR(191) NULL,
  `name` VARCHAR(255) NOT NULL,
  `account_type` VARCHAR(32) NOT NULL DEFAULT 'bank',
  `identifier` VARCHAR(191) NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `opening_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `state` TINYINT NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_external_key` (`external_key`),
  KEY `idx_owner` (`owner_component`,`owner_entity`,`owner_id`),
  KEY `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__decarofinance_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_key` VARCHAR(191) NULL,
  `direction` VARCHAR(16) NOT NULL,
  `account_id` BIGINT UNSIGNED NULL,
  `budget_line_id` INT UNSIGNED NULL,
  `owner_component` VARCHAR(100) NULL,
  `owner_entity` VARCHAR(100) NULL,
  `owner_id` VARCHAR(191) NULL,
  `counterparty_component` VARCHAR(100) NULL,
  `counterparty_entity` VARCHAR(100) NULL,
  `counterparty_id` VARCHAR(191) NULL,
  `category` VARCHAR(100) NULL,
  `description` VARCHAR(500) NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `due_date` DATE NULL,
  `required_approvals` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
  `source_component` VARCHAR(100) NULL,
  `source_entity` VARCHAR(100) NULL,
  `source_id` VARCHAR(191) NULL,
  `approved_at` DATETIME NULL,
  `executed_at` DATETIME NULL,
  `transaction_id` BIGINT UNSIGNED NULL,
  `created` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_external_key` (`external_key`),
  KEY `idx_status_due` (`status`,`due_date`),
  KEY `idx_owner` (`owner_component`,`owner_entity`,`owner_id`),
  KEY `idx_account` (`account_id`),
  KEY `idx_budget_line` (`budget_line_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__decarofinance_order_approvals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `step` TINYINT UNSIGNED NOT NULL,
  `role` VARCHAR(64) NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `decision` VARCHAR(16) NOT NULL DEFAULT 'approved',
  `note` VARCHAR(500) NULL,
  `decided_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_step` (`order_id`,`step`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

<?php

declare(strict_types=1);

$joomlaRoot = rtrim((string) getenv('FINANCE_JOOMLA_ROOT'), DIRECTORY_SEPARATOR);
if ($joomlaRoot === '' || !is_file($joomlaRoot . '/includes/defines.php')) {
    fwrite(STDERR, "FINANCE_JOOMLA_ROOT does not point to an installed Joomla site.\n");
    exit(1);
}

define('_JEXEC', 1);
define('JPATH_BASE', $joomlaRoot);
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

$container = Factory::getContainer();
$container->alias('session', 'session.cli')
    ->alias('JSession', 'session.cli')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\SessionInterface::class, 'session.cli');
$app = $container->get(\Joomla\Console\Application::class);
Factory::$application = $app;
$app->createExtensionNamespaceMap();

$component = $app->bootComponent('com_decarofinance');
if (!is_object($component) || !method_exists($component, 'getFinanceService')) {
    fwrite(STDERR, "Finance public service unavailable.\n");
    exit(1);
}
$finance = $component->getFinanceService();

$obligationId = $finance->createObligation([
    'external_key' => 'ci:obligation:1',
    'source_component' => 'com_example',
    'source_entity' => 'entry',
    'source_id' => '1',
    'debtor_component' => 'com_example',
    'debtor_entity' => 'team',
    'debtor_id' => '10',
    'kind' => 'ci_fee',
    'amount' => '100.00',
    'currency' => 'EUR',
], 1);
$paymentId = $finance->recordPayment([
    'external_key' => 'ci:payment:1',
    'payer_component' => 'com_example',
    'payer_entity' => 'team',
    'payer_id' => '10',
    'amount' => '100.00',
    'currency' => 'EUR',
], 1);
$finance->allocatePayment($paymentId, $obligationId, '40.00');
$obligation = $finance->getObligation($obligationId);
if (!is_array($obligation) || ($obligation['status'] ?? '') !== 'partial') {
    fwrite(STDERR, "Payment allocation/status update failed.\n");
    exit(1);
}

$account1 = $finance->getOrCreateDepositAccount('com_example', 'team', '10', 'EUR');
$account2 = $finance->getOrCreateDepositAccount('com_example', 'team', '10', 'EUR');
if ($account1 < 1 || $account1 !== $account2) {
    fwrite(STDERR, "Deposit account creation is not stable.\n");
    exit(1);
}
$credit1 = $finance->postDepositMovement($account1, 'credit', '500.00', ['external_key' => 'ci:deposit:credit:1'], 1);
$credit2 = $finance->postDepositMovement($account1, 'credit', '500.00', ['external_key' => 'ci:deposit:credit:1'], 1);
if ($credit1 < 1 || $credit1 !== $credit2) {
    fwrite(STDERR, "Deposit movement idempotency failed.\n");
    exit(1);
}
$finance->postDepositMovement($account1, 'debit', '-25.00', ['external_key' => 'ci:deposit:debit:1'], 1);
if (abs($finance->getDepositBalance($account1) - 475.0) > 0.0001) {
    fwrite(STDERR, "Deposit balance is incorrect.\n");
    exit(1);
}

$budgetId = $finance->createBudget('CI Budget', '2026-01-01', '2026-12-31', 1);
$lineId = $finance->addBudgetLine($budgetId, 'expense', 'CI Expense', '250.00');
if ($budgetId < 1 || $lineId < 1) {
    fwrite(STDERR, "Budget writes failed.\n");
    exit(1);
}

echo "Finance reference-safe runtime writes OK\n";

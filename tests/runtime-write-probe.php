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

$syncObligation = [
    'external_key' => 'ci:sync:obligation:1',
    'source_component' => 'com_example',
    'source_entity' => 'due',
    'source_id' => '22',
    'debtor_component' => 'com_example',
    'debtor_entity' => 'member',
    'debtor_id' => '44',
    'kind' => 'membership_due',
    'description' => 'CI membership due',
    'amount' => '80.00',
    'currency' => 'EUR',
    'due_date' => '2026-12-31',
];
$syncObligationId1 = $finance->upsertObligation($syncObligation, 1);
$syncObligation['amount'] = '90.00';
$syncObligationId2 = $finance->upsertObligation($syncObligation, 1);
$syncObligationRow = $finance->getObligation($syncObligationId2);
if ($syncObligationId1 < 1 || $syncObligationId1 !== $syncObligationId2 || !is_array($syncObligationRow) || abs((float) $syncObligationRow['amount'] - 90.0) > 0.0001) {
    fwrite(STDERR, "Obligation upsert did not update an open obligation in place.\n");
    exit(1);
}

$syncPayment = [
    'external_key' => 'ci:sync:payment:1',
    'payer_component' => 'com_example',
    'payer_entity' => 'member',
    'payer_id' => '44',
    'amount' => '50.00',
    'currency' => 'EUR',
    'paid_at' => '2026-09-09 12:00:00',
    'method' => 'bank_transfer',
    'reference' => 'CI-SYNC-PAYMENT',
];
$syncPaymentId1 = $finance->upsertPayment($syncPayment, 1);
$syncPayment['amount'] = '60.00';
$syncPaymentId2 = $finance->upsertPayment($syncPayment, 1);
$syncPaymentRow = $finance->getPayment($syncPaymentId2);
if ($syncPaymentId1 < 1 || $syncPaymentId1 !== $syncPaymentId2 || !is_array($syncPaymentRow) || abs((float) $syncPaymentRow['amount'] - 60.0) > 0.0001) {
    fwrite(STDERR, "Payment upsert did not update an unallocated payment in place.\n");
    exit(1);
}

$finance->allocatePaymentIdempotent($syncPaymentId2, $syncObligationId2, '60.00');
$finance->allocatePaymentIdempotent($syncPaymentId2, $syncObligationId2, '60.00');
$syncObligationRow = $finance->getObligation($syncObligationId2);
if (!is_array($syncObligationRow) || ($syncObligationRow['status'] ?? '') !== 'partial') {
    fwrite(STDERR, "Replay-safe allocation did not preserve partial status.\n");
    exit(1);
}

if ($finance->upsertObligation($syncObligation, 1) !== $syncObligationId2 || $finance->upsertPayment($syncPayment, 1) !== $syncPaymentId2) {
    fwrite(STDERR, "Unchanged replay after allocation is not idempotent.\n");
    exit(1);
}

$changedObligationRejected = false;
try {
    $changed = $syncObligation;
    $changed['amount'] = '100.00';
    $finance->upsertObligation($changed, 1);
} catch (\RuntimeException) {
    $changedObligationRejected = true;
}
if (!$changedObligationRejected) {
    fwrite(STDERR, "Allocated obligation accepted a conflicting upsert.\n");
    exit(1);
}

$changedPaymentRejected = false;
try {
    $changed = $syncPayment;
    $changed['amount'] = '70.00';
    $finance->upsertPayment($changed, 1);
} catch (\RuntimeException) {
    $changedPaymentRejected = true;
}
if (!$changedPaymentRejected) {
    fwrite(STDERR, "Allocated payment accepted a conflicting upsert.\n");
    exit(1);
}

$conflictingAllocationRejected = false;
try {
    $finance->allocatePaymentIdempotent($syncPaymentId2, $syncObligationId2, '50.00');
} catch (\RuntimeException) {
    $conflictingAllocationRejected = true;
}
if (!$conflictingAllocationRejected) {
    fwrite(STDERR, "Replay-safe allocation accepted a conflicting amount.\n");
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

echo "Finance replay-safe runtime writes OK\n";

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
$lineId = $finance->addBudgetLine($budgetId, 'expense', 'CI Expense', '250,00');
if ($budgetId < 1 || $lineId < 1) {
    fwrite(STDERR, "Budget writes failed.\n");
    exit(1);
}

$commaObligationId = $finance->createObligation([
    'external_key' => 'ci:obligation:comma',
    'source_component' => 'com_example',
    'source_entity' => 'entry',
    'source_id' => 'comma',
    'kind' => 'localized_amount',
    'amount' => '1.234,56',
    'currency' => 'EUR',
], 1);
$commaObligation = $finance->getObligation($commaObligationId);
if (!is_array($commaObligation) || abs((float) $commaObligation['amount'] - 1234.56) > 0.0001) {
    fwrite(STDERR, "Localized monetary input was not normalised correctly.\n");
    exit(1);
}

$accountId1 = $finance->createAccount([
    'external_key' => 'ci:account:operations',
    'owner_component' => 'com_example',
    'owner_entity' => 'organization',
    'owner_id' => 'rome',
    'name' => 'CI Operations',
    'account_type' => 'bank',
    'identifier' => 'CI-IBAN',
    'currency' => 'EUR',
    'opening_balance' => '1.000,00',
], 1);
$accountId2 = $finance->createAccount([
    'external_key' => 'ci:account:operations',
    'owner_component' => 'com_example',
    'owner_entity' => 'organization',
    'owner_id' => 'rome',
    'name' => 'CI Operations',
    'account_type' => 'bank',
    'identifier' => 'CI-IBAN',
    'currency' => 'EUR',
    'opening_balance' => '1000.00',
], 1);
if ($accountId1 < 1 || $accountId1 !== $accountId2) {
    fwrite(STDERR, "Financial account idempotency failed.\n");
    exit(1);
}

$institutionalBudgetId = $finance->createBudget('CI Institutional Budget', '2026-01-01', '2026-12-31', 1, [
    'owner_component' => 'com_example',
    'owner_entity' => 'organization',
    'owner_id' => 'rome',
    'currency' => 'EUR',
]);
$expenseLineId = $finance->addBudgetLine($institutionalBudgetId, 'expense', 'Institutional operations', '1.000,00', [
    'code' => 'OPS',
    'category' => 'operations',
]);

$orderId = $finance->createOrder([
    'external_key' => 'ci:order:expense:1',
    'direction' => 'expense',
    'account_id' => $accountId1,
    'budget_line_id' => $expenseLineId,
    'owner_component' => 'com_example',
    'owner_entity' => 'organization',
    'owner_id' => 'rome',
    'counterparty_component' => 'com_example',
    'counterparty_entity' => 'supplier',
    'counterparty_id' => 'supplier-1',
    'category' => 'operations',
    'description' => 'CI approved institutional expense',
    'amount' => '250.00',
    'currency' => 'EUR',
    'required_approvals' => 2,
    'source_component' => 'com_example',
    'source_entity' => 'request',
    'source_id' => 'expense-1',
    'evidence_component' => 'com_example',
    'evidence_entity' => 'document',
    'evidence_id' => 'receipt-1',
], 1);
$finance->approveOrder($orderId, 1, 'president', 2, 'First approval');

$sameApproverRejected = false;
try {
    $finance->approveOrder($orderId, 2, 'treasurer', 2, 'Invalid repeated approver');
} catch (\RuntimeException) {
    $sameApproverRejected = true;
}
if (!$sameApproverRejected) {
    fwrite(STDERR, "The same user was allowed to approve two order steps.\n");
    exit(1);
}

$finance->approveOrder($orderId, 2, 'treasurer', 3, 'Second approval');
$order = $finance->getOrder($orderId);
if (!is_array($order) || ($order['status'] ?? '') !== 'approved') {
    fwrite(STDERR, "Multi-step order approval failed.\n");
    exit(1);
}

$transactionId1 = $finance->executeOrder($orderId, 1);
$transactionId2 = $finance->executeOrder($orderId, 1);
if ($transactionId1 < 1 || $transactionId1 !== $transactionId2) {
    fwrite(STDERR, "Order execution is not replay-safe.\n");
    exit(1);
}

$availability = $finance->getBudgetLineAvailability($expenseLineId);
if (abs((float) $availability['planned'] - 1000.0) > 0.0001
    || abs((float) $availability['realized'] - 250.0) > 0.0001
    || abs((float) $availability['committed']) > 0.0001
    || abs((float) $availability['available'] - 750.0) > 0.0001) {
    fwrite(STDERR, "Budget planned/realized/committed availability is incorrect.\n");
    exit(1);
}

$query = $component->getFinanceQueryService();
$accounts = $query->listAccounts();
$accountRow = null;
foreach ($accounts as $candidate) {
    if ((int) ($candidate['id'] ?? 0) === $accountId1) {
        $accountRow = $candidate;
        break;
    }
}
if (!is_array($accountRow) || abs((float) $accountRow['balance'] - 750.0) > 0.0001) {
    fwrite(STDERR, "Financial account balance is incorrect after order execution.\n");
    exit(1);
}

$transactions = $query->listTransactions();
$transactionRow = null;
foreach ($transactions as $candidate) {
    if ((int) ($candidate['id'] ?? 0) === $transactionId1) {
        $transactionRow = $candidate;
        break;
    }
}
if (!is_array($transactionRow)
    || (string) ($transactionRow['evidence_id'] ?? '') !== 'receipt-1'
    || (int) ($transactionRow['budget_line_id'] ?? 0) !== $expenseLineId) {
    fwrite(STDERR, "Executed order did not preserve budget/evidence references.\n");
    exit(1);
}

$overBudgetOrderId = $finance->createOrder([
    'external_key' => 'ci:order:expense:over-budget',
    'direction' => 'expense',
    'account_id' => $accountId1,
    'budget_line_id' => $expenseLineId,
    'category' => 'operations',
    'description' => 'CI over-budget expense',
    'amount' => '800.00',
    'currency' => 'EUR',
    'required_approvals' => 2,
], 1);
$finance->approveOrder($overBudgetOrderId, 1, 'president', 4);

$coverageRejected = false;
try {
    $finance->approveOrder($overBudgetOrderId, 2, 'treasurer', 5);
} catch (\RuntimeException) {
    $coverageRejected = true;
}
if (!$coverageRejected) {
    fwrite(STDERR, "Final approval ignored insufficient budget coverage.\n");
    exit(1);
}
$overBudgetOrder = $finance->getOrder($overBudgetOrderId);
if (!is_array($overBudgetOrder) || ($overBudgetOrder['status'] ?? '') !== 'pending') {
    fwrite(STDERR, "Rejected final approval did not preserve the prior order state.\n");
    exit(1);
}
if (count($query->listOrderApprovals($overBudgetOrderId)) !== 1) {
    fwrite(STDERR, "Rejected final approval was not rolled back atomically.\n");
    exit(1);
}

$cashAccountId = $finance->createAccount([
    'external_key' => 'ci:account:cash',
    'code' => 'CASH',
    'owner_component' => 'com_example',
    'owner_entity' => 'organization',
    'owner_id' => 'rome',
    'name' => 'CI Cash',
    'account_type' => 'cash',
    'currency' => 'EUR',
    'opening_balance' => '100.00',
], 1);
if ($cashAccountId < 1) {
    fwrite(STDERR, "Cash account creation failed.\n");
    exit(1);
}

$transferId1 = $finance->transferBetweenAccounts([
    'external_key' => 'ci:transfer:1',
    'from_account_id' => $accountId1,
    'to_account_id' => $cashAccountId,
    'amount' => '100.00',
    'currency' => 'EUR',
    'description' => 'CI internal transfer',
], 1);
$transferId2 = $finance->transferBetweenAccounts([
    'external_key' => 'ci:transfer:1',
    'from_account_id' => $accountId1,
    'to_account_id' => $cashAccountId,
    'amount' => '100.00',
    'currency' => 'EUR',
    'description' => 'CI internal transfer',
], 1);
if ($transferId1 < 1 || $transferId1 !== $transferId2) {
    fwrite(STDERR, "Transfer idempotency failed.\n");
    exit(1);
}
$conflictingTransferRejected = false;
try {
    $finance->transferBetweenAccounts([
        'external_key' => 'ci:transfer:1',
        'from_account_id' => $accountId1,
        'to_account_id' => $cashAccountId,
        'amount' => '101.00',
        'currency' => 'EUR',
    ], 1);
} catch (\RuntimeException) {
    $conflictingTransferRejected = true;
}
if (!$conflictingTransferRejected) {
    fwrite(STDERR, "Conflicting transfer replay was accepted.\n");
    exit(1);
}
if (abs($finance->getAccountBalance($accountId1) - 650.0) > 0.0001
    || abs($finance->getAccountBalance($cashAccountId) - 200.0) > 0.0001) {
    fwrite(STDERR, "Transfer did not preserve paired account balances.\n");
    exit(1);
}

$reservedTransferCategoryRejected = false;
try {
    $finance->recordTransaction([
        'external_key' => 'ci:fake-transfer:1',
        'account_id' => $accountId1,
        'direction' => 'expense',
        'category' => 'internal_transfer',
        'amount' => '1.00',
        'currency' => 'EUR',
    ], 1);
} catch (\InvalidArgumentException) {
    $reservedTransferCategoryRejected = true;
}
if (!$reservedTransferCategoryRejected) {
    fwrite(STDERR, "Reserved internal-transfer category accepted a direct transaction.\n");
    exit(1);
}

$summaryAfterTransfer = $query->getSummary('EUR');
if (abs((float) $summaryAfterTransfer['expense_total'] - 250.0) > 0.0001
    || abs((float) $summaryAfterTransfer['income_total']) > 0.0001) {
    fwrite(STDERR, "Internal transfer incorrectly inflated operating income/expense totals.\n");
    exit(1);
}

$cashCheckId = $finance->recordCashCheck([
    'external_key' => 'ci:cash-check:1',
    'account_id' => $cashAccountId,
    'actual_balance' => '195.00',
    'note' => 'CI physical cash count',
    'evidence_component' => 'com_example',
    'evidence_entity' => 'document',
    'evidence_id' => 'cash-check-proof-1',
], 8);
if ($cashCheckId < 1) {
    fwrite(STDERR, "Cash check creation failed.\n");
    exit(1);
}
$cashCheckReplay = $finance->recordCashCheck([
    'external_key' => 'ci:cash-check:1',
    'account_id' => $cashAccountId,
    'actual_balance' => '195.00',
], 8);
if ($cashCheckReplay !== $cashCheckId) {
    fwrite(STDERR, "Cash check replay was not idempotent.\n");
    exit(1);
}
$conflictingCashCheckRejected = false;
try {
    $finance->recordCashCheck([
        'external_key' => 'ci:cash-check:1',
        'account_id' => $cashAccountId,
        'actual_balance' => '194.00',
    ], 8);
} catch (\RuntimeException) {
    $conflictingCashCheckRejected = true;
}
if (!$conflictingCashCheckRejected) {
    fwrite(STDERR, "Conflicting cash-check replay was accepted.\n");
    exit(1);
}
$cashChecks = $query->listCashChecks();
$cashCheckRow = null;
foreach ($cashChecks as $candidate) {
    if ((int) ($candidate['id'] ?? 0) === $cashCheckId) {
        $cashCheckRow = $candidate;
        break;
    }
}
if (!is_array($cashCheckRow)
    || abs((float) $cashCheckRow['expected_balance'] - 200.0) > 0.0001
    || abs((float) $cashCheckRow['actual_balance'] - 195.0) > 0.0001
    || abs((float) $cashCheckRow['difference'] + 5.0) > 0.0001
    || (string) ($cashCheckRow['evidence_id'] ?? '') !== 'cash-check-proof-1') {
    fwrite(STDERR, "Cash check reconciliation snapshot is incorrect.\n");
    exit(1);
}

$statementId = $finance->createStatement([
    'external_key' => 'ci:statement:1',
    'statement_type' => 'management',
    'title' => 'CI Management Statement',
    'period_start' => '2026-01-01',
    'period_end' => '2026-12-31',
    'currency' => 'EUR',
    'owner_component' => 'com_example',
    'owner_entity' => 'organization',
    'owner_id' => 'rome',
    'document_component' => 'com_example',
    'document_entity' => 'document',
    'document_id' => 'statement-document-1',
], 1);
$lineA = $finance->addStatementLine($statementId, [
    'section_code' => 'REVENUE',
    'line_code' => 'A1',
    'label' => 'Membership revenue',
    'line_type' => 'amount',
    'amount' => '500.00',
    'sort_order' => 10,
], 1);
$lineB = $finance->addStatementLine($statementId, [
    'section_code' => 'NOTES',
    'line_code' => 'N1',
    'label' => 'Method note',
    'line_type' => 'note',
    'text_value' => 'Operational snapshot for CI.',
    'sort_order' => 20,
], 1);
if ($statementId < 1 || $lineA < 1 || $lineB < 1) {
    fwrite(STDERR, "Statement creation failed.\n");
    exit(1);
}

$finance->finaliseStatement($statementId, 9);
$statement = $finance->getStatement($statementId);
if (!is_array($statement) || ($statement['status'] ?? '') !== 'finalised' || (int) ($statement['finalised_by'] ?? 0) !== 9) {
    fwrite(STDERR, "Statement finalisation failed.\n");
    exit(1);
}

$lineAfterFinaliseRejected = false;
try {
    $finance->addStatementLine($statementId, [
        'label' => 'Late line',
        'line_type' => 'amount',
        'amount' => '1.00',
    ], 1);
} catch (\RuntimeException) {
    $lineAfterFinaliseRejected = true;
}
if (!$lineAfterFinaliseRejected) {
    fwrite(STDERR, "Finalised statement accepted a new line.\n");
    exit(1);
}

$sameStatementApproverRejected = false;
try {
    $finance->approveStatement($statementId, 9);
} catch (\RuntimeException) {
    $sameStatementApproverRejected = true;
}
if (!$sameStatementApproverRejected) {
    fwrite(STDERR, "Statement finaliser was allowed to self-approve.\n");
    exit(1);
}

$finance->approveStatement($statementId, 10);
$statement = $finance->getStatement($statementId);
if (!is_array($statement) || ($statement['status'] ?? '') !== 'approved' || (int) ($statement['approved_by'] ?? 0) !== 10) {
    fwrite(STDERR, "Statement approval failed.\n");
    exit(1);
}
if (count($query->listStatementLines($statementId)) !== 2) {
    fwrite(STDERR, "Statement lines were not preserved after approval.\n");
    exit(1);
}

$summary15 = $query->getReportSummary('EUR');
if ((int) ($summary15['cash_check_variances'] ?? 0) < 1
    || abs((float) ($summary15['cash_variance_total'] ?? 0) + 5.0) > 0.0001) {
    fwrite(STDERR, "Finance 1.5 reconciliation summary is incorrect.\n");
    exit(1);
}

echo "Finance 1.5 reporting and reconciliation runtime writes OK\n";

<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$servicePath = $root . '/component/admin/src/Service/FinanceService.php';
$source = is_file($servicePath) ? (string) file_get_contents($servicePath) : '';

$fail = static function (string $message): never {
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
};

if ($source === '') {
    $fail('FinanceService.php is missing or empty.');
}

foreach ([
    "insertObject('#__decarofinance_payment_allocations',(object)",
    "updateObject('#__decarofinance_obligations',(object)",
    "insertObject('#__decarofinance_deposit_accounts',(object)",
    "insertObject('#__decarofinance_budgets',(object)",
    "insertObject('#__decarofinance_budget_lines',(object)",
] as $forbidden) {
    if (str_contains(str_replace(' ', '', $source), str_replace(' ', '', $forbidden))) {
        $fail('Reference-unsafe Joomla database write remains: ' . $forbidden);
    }
}

foreach (['$allocation=', '$statusRow=', '$row=(object)'] as $required) {
    if (!str_contains(str_replace(' ', '', $source), str_replace(' ', '', $required))) {
        $fail('Expected named database write object is missing: ' . $required);
    }
}

fwrite(STDOUT, "Finance database write contract OK\n");

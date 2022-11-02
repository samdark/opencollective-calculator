<?php
// Calculates monthly credit - debit to know how fast funds are earned / spent.

$file = fopen('transactions.csv', 'r');
if ($file === false) {
    throw new RuntimeException('Unable to open the file.');
}

$results = [];

while (($line = fgetcsv($file)) !== false) {
    if (count($line) !== 25) {
        continue;
    }

    [
        $datetime,
        $shortId,
        $shortGroup,
        $description,
        $type,
        $kind,
        $isRefund,
        $isRefunded,
        $shortRefundId,
        $displayAmount,
        $amount,
        $paymentProcessorFee,
        $netAmount,
        $balance,
        $currency,
        $accountSlug,
        $accountName,
        $oppositeAccountSlug,
        $oppositeAccountName,
        $paymentMethodService,
        $paymentMethodType,
        $expenseType,
        $expenseTags,
        $payoutMethodType,
        $merchantId
    ] = $line;

    $slot = date('Y-m', strtotime($datetime));
    if (!array_key_exists($slot, $results)) {
        $results[$slot] = [
            'credit' => 0,
            'debit' => 0,
        ];
    }

    if ($type === 'CREDIT') {
        $results[$slot]['credit'] += $netAmount;
    } elseif ($type === 'DEBIT') {
        $results[$slot]['debit'] += $netAmount;
    }
}
fclose($file);

foreach ($results as $month => $result) {
    $debit = $result['debit'];
    $credit = $result['credit'];
    $sum = $debit + $credit;
    echo "$month\t$sum\n";
}
echo "\n\n";

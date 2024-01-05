<?php
// Calculates detailed statistic

if ($argc !== 3) {
    echo "Invalid command. Example:\nphp stat.php 2022-10-01 2022-10-30\n";
    exit;
}

$url = 'https://rest.opencollective.com/v2/yiisoft/transactions.csv?fetchAll=1&includeGiftCardTransactions=1&includeIncognitoTransactions=1&includeChildrenTransactions=1&dateFrom=' . $argv[1] . 'T00%3A00%3A00.000Z&dateTo=' . $argv[2] . 'T23%3A59%3A59.999Z&flattenPaymentProcessorFee=1&fields=datetime%2CshortId%2CshortGroup%2Cdescription%2Ctype%2Ckind%2CisRefund%2CisRefunded%2CshortRefundId%2CdisplayAmount%2Camount%2CpaymentProcessorFee%2CnetAmount%2Cbalance%2Ccurrency%2CaccountSlug%2CaccountName%2CoppositeAccountSlug%2CoppositeAccountName%2CpaymentMethodService%2CpaymentMethodType%2CexpenseType%2CexpenseTags%2CpayoutMethodType%2CmerchantId%2CorderMemo';

$data = file_get_contents($url);
if ($data === false) {
    throw new RuntimeException('Unable to load the data.');
}

$gitHubCredit = 0;
$totalCreditWithoutGitHub = 0;
$totalDebit = 0;
$results = [];

foreach (explode("\n", $data) as $line) {
    $line = str_getcsv($line);
    if (count($line) !== 26) {
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
        $merchantId,
        $orderMemo,
    ] = $line;

    // Skip Open Collective Fee
    if ($kind === 'HOST_FEE') {
        continue;
    }

    if (!in_array($type, ['CREDIT', 'DEBIT'], true)) {
        continue;
    }

    $slot = $oppositeAccountName;
    if (!array_key_exists($slot, $results)) {
        $results[$slot] = [
            'CREDIT' => 0,
            'DEBIT' => 0,
        ];
    }

    $results[$slot][$type] += $netAmount;

    if ($type === 'CREDIT') {
        if ($oppositeAccountSlug === 'github-sponsors') {
            $gitHubCredit += $netAmount;
        } else {
            $totalCreditWithoutGitHub += $netAmount;
        }
    } else {
        $totalDebit += $netAmount;
    }
}

uasort(
    $results,
    static function ($a, $b) {
        return $b['CREDIT'] === $a['CREDIT']
            ? $a['DEBIT'] <=> $b['DEBIT']
            : $b['CREDIT'] <=> $a['CREDIT'];
    }
);

foreach ($results as $name => $result) {
    echo str_pad($name, 60, '_')
        . ' +'
        . str_pad($result['CREDIT'] . '$', 10)
        . ' '
        . str_pad($result['DEBIT'] . '$', 10)
        . "\n";
}
echo "\n";

echo 'GitHub Sponsors +' . $gitHubCredit . "$\n";
echo 'OpenCollective +' . $totalCreditWithoutGitHub . "$\n";

echo "\n";

echo 'Total Credit ' . ($totalCreditWithoutGitHub + $gitHubCredit) . "$\n";
echo 'Total Debit ' . $totalDebit . "$\n";

echo "\n";

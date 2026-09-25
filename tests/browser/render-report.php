<?php

// Render the real report template with synthetic records, without querying or writing the database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\URL::forceRootUrl(getenv('KITA_TEST_URL') ?: 'http://127.0.0.1:8765');
$lines = collect(range(1, 18))->map(fn ($i) => (object) [
    'productId' => $i, 'name' => 'Responsive test product with a long descriptive name '.$i,
    'category' => 'Household and everyday essentials', 'orderedQty' => 12, 'deliveredQty' => 5,
    'poQty' => 12, 'unit' => 'Piece', 'unitCost' => 100, 'lineTotal' => 1200,
]);
$order = (object) ['id' => 'PO-RESPONSIVE-01234567890123456789', 'supplierName' => 'Responsive test supplier with a long registered business name', 'status' => 'Partially Received', 'itemRequestId' => null, 'orderedValue' => 21600];
$purchase = (object) ['requested_at' => '2026-09-24 09:00:00', 'approved_at' => '2026-09-25 09:00:00', 'requestedBy' => 'Responsive Test Manager', 'approvedBy' => 'Responsive Test Admin', 'notes' => str_repeat('REFERENCE', 30), 'adminNote' => 'Approved for delivery'];
$receipt = (object) ['id' => 'RCV-RESPONSIVE-01234567890123456789', 'date' => '2026-09-25', 'received_at' => '2026-09-25 10:00:00', 'receivedBy' => 'Responsive Test Manager', 'deliveryReference' => 'SUPPLIER-REFERENCE-01234567890123456789', 'deliveryStatus' => 'Partially Received', 'outcome' => 'Partial delivery with missing items', 'inspection' => json_encode($lines->map(fn ($line) => ['name' => $line->name, 'ordered' => 12, 'received' => 5, 'missing' => 7, 'excess' => 0, 'unit' => 'Piece'])->all())];
$html = view('purchase-report', ['order' => $order, 'purchase' => $purchase, 'supplier' => null, 'lines' => $lines, 'receipts' => collect([$receipt]), 'receiptLines' => collect([$receipt->id => $lines])])->render();
file_put_contents(__DIR__.'/../../.tmp/responsive-results/report.html', $html);

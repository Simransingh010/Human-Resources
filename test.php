<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Services\BulkOperationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeBatchController extends Controller
{
    // Optional: list all recent batches for this firm
    public function index()
    {
        $batches = Batch::latest()->get(['id','title','modulecomponent','action','created_at']);
        return view('batches.index', compact('batches'));
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'title'   => 'nullable|string|max:255',
            'updates' => 'required|array',
            'updates.*.id'         => 'required|integer',
            'updates.*.attributes' => 'required|array',
        ]);

        $batch = BulkOperationService::start(
            'employees',             // moduleComponent
            'bulk-update',
            $validated['title']
        );

        DB::transaction(function() use ($validated, $batch) {
            foreach ($validated['updates'] as $u) {
                $emp = \App\Models\Employee::where('firm_id', session('firm_id'))
                    ->findOrFail($u['id']);
                $original = $emp->getAttributes();
                $emp->update($u['attributes']);
                BulkOperationService::logUpdate($batch, $emp, $original);
            }
        });

        return response()->json(['batch_id' => $batch->id]);
    }

    public function importInvoiceWithItems(array $payload)
    {
        // start one batch for the entire operation
        $batch = BulkOperationService::start('invoices', 'invoice+items import');

        DB::transaction(function() use ($payload, $batch) {
            // 1) create the invoice
            $invoice = Invoice::create($payload['invoice']);
            BulkOperationService::logInsert($batch, $invoice);

            // 2) create each invoice_item
            foreach ($payload['items'] as $itemData) {
                $item = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    ...$itemData
                ]);
                BulkOperationService::logInsert($batch, $item);

                // 3) update stock on the product
                $product         = Product::find($itemData['product_id']);
                $originalProduct = $product->getAttributes();

                $product->decrement('stock', $itemData['quantity']);
                BulkOperationService::logUpdate($batch, $product, $originalProduct);
            }

            // (you could even insert into pivot tables or run raw queries—
            // see §4 below)
        });

        return $batch->id;  // UI can now offer “Rollback batch #123”
    }

    public function rollback(Batch $batch)
    {
        BulkOperationService::rollback($batch);
        return back()->with('status', "Batch #{$batch->id} rolled back.");
    }
}

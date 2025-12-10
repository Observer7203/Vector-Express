<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = $request->user()
            ->invoices()
            ->with(['order.carrier.company'])
            ->latest()
            ->paginate(15);

        return response()->json($invoices);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);

        return response()->json([
            'invoice' => $invoice->load(['order.quote.shipment.items', 'order.carrier.company', 'user']),
        ]);
    }

    public function downloadPdf(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['order.quote.shipment.items', 'order.carrier.company', 'user']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
        ]);

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('pay', $invoice);

        if (!$invoice->isPending() && !$invoice->isOverdue()) {
            return response()->json([
                'message' => 'Invoice cannot be paid',
            ], 400);
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:card,bank_transfer,kaspi'],
        ]);

        // In a real implementation, process payment here
        // For now, just mark as paid

        $invoice->markAsPaid($validated['payment_method']);

        return response()->json([
            'message' => 'Payment processed successfully',
            'invoice' => $invoice->fresh(),
        ]);
    }
}

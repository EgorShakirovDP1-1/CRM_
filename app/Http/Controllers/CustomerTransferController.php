<?php

namespace App\Http\Controllers;

use App\Services\CustomerTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerTransferController extends Controller
{
    public function __construct(private readonly CustomerTransferService $transfer) {}

    public function export(): BinaryFileResponse
    {
        return response()->download(
            $this->transfer->exportCsv(),
            'customers-'.now()->format('Y-m-d').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        )->deleteFileAfterSend(true);
    }

    public function import(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);
        $file = $request->file('file');
        if ($file === null) {
            abort(422, 'The uploaded CSV file is missing.');
        }
        $count = $this->transfer->import($file);

        if ($request->expectsJson()) {
            return response()->json(['data' => ['imported' => $count]], 201);
        }

        return back()->with('success', __('crm.customers_imported', ['count' => $count]));
    }
}

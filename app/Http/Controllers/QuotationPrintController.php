<?php
namespace App\Http\Controllers;

use App\Models\Service;

class QuotationPrintController extends Controller
{
    public function overview(Service $service)
    {
        if (! $service->items_offer || (is_countable($service->items_offer) && count($service->items_offer) === 0)) {
            return redirect()->back()->with('info', 'Silakan isi item penawaran terlebih dahulu.');
        }

        return view('prints.quotation-overview', compact('service'));
    }

    public function detail(Service $service)
    {
        if (! $service->items_offer || (is_countable($service->items_offer) && count($service->items_offer) === 0)) {
            return redirect()->back()->with('info', 'Silakan isi item penawaran terlebih dahulu.');
        }

        return view('prints.quotation-detail', compact('service'));
    } 
}

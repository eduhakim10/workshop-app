<?php
namespace App\Http\Controllers;

use App\Models\Service;

class QuotationPrintController extends Controller
{
    public function overview(Service $service)
    {
      
        return view('prints.quotation-overview', compact('service'));
    }

    public function detail(Service $service)
    {
        return view('prints.quotation-detail', compact('service'));
    }
}

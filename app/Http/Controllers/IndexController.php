<?php

namespace App\Http\Controllers;

use App\Models\aboutCallback;
use App\Models\aboutDelivery;
use App\Models\aboutPayment;
use App\Models\Abouts;
use App\Models\Advantage;
use App\Models\Category;
use App\Models\Company;
use App\Models\Item;
use App\Models\News;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Subcategory;
use App\Models\Title;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index()
    {
        $title = Title::first();
        $response = [
            'logo' => $title->logo,
            'title' => $title->title,
            'phone' => $title->phone,
            'mail' => $title->mail,
            'address' => $title->address,
        ];
        return response($response, 200);
    }

    public function partner()
    {
        $partners = Partner::all();
        return response($partners, 200);
    }

    public function payment()
    {
        $payments = Payment::all();
        return response($payments, 200);
    }

    public function news()
    {
        $news = News::all();
        return response($news, 200);
    }

    public function company()
    {
        $company = Company::all();
        return response($company, 200);
    }

    public function advantage()
    {
        $advantage = Advantage::all();
        return response($advantage, 200);
    }

    public function about()
    {
        $about = Abouts::first();
        $delivery = aboutDelivery::first();
        $deliveryPayment = aboutPayment::first();
        $deliveryCallback = aboutCallback::first();
        $response = [
            'about' => $about,
            'delivery' => $delivery,
            'deliveryPayment' => $deliveryPayment,
            'deliveryCallback' => $deliveryCallback
        ];
        return response($response, 200);
    }
}

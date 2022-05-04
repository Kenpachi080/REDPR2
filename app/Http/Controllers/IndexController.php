<?php

namespace App\Http\Controllers;

use App\Models\AboutCallback;
use App\Models\AboutDelivery;
use App\Models\AboutPayment;
use App\Models\Abouts;
use App\Models\Advantage;
use App\Models\Category;
use App\Models\Company;
use App\Models\Item;
use App\Models\News;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Slider;
use App\Models\Subcategory;
use App\Models\Title;
use App\Models\TitleCard;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * @var string
     */
    private $url;

    public function __construct()
    {
        $this->url = env('APP_URL', 'http://127.0.0.1:8000');
        $this->url = $this->url . "/storage/";
    }

    /**
     * @OA\Get(path="/api/index",
     *   tags={"view"},
     *   operationId="viewIndex",
     *   summary="Информация про сайт",
     * @OA\Response(
     *    response=200,
     *    description="Возврощается полная информация про сайт",
     *   )
     * )
     */
    public function index()
    {
        $title = Title::first();
        $title->logo = $this->url . $title->logo;
        return response($title, 200);
    }

    /**
     * @OA\Get(path="/api/partner",
     *   tags={"view"},
     *   operationId="viewpartner",
     *   summary="Партнёры",
     * @OA\Response(
     *    response=200,
     *    description="Информация про парнеров",
     *   )
     * )
     */
    public function partner()
    {
        $partners = Partner::all();
        foreach ($partners as $block) {
            $block->logo = $this->url . $block->logo;
        }
        return response($partners, 200);
    }

    /**
     * @OA\Get(path="/api/payment",
     *   tags={"view"},
     *   operationId="viewpayment",
     *   summary="Способы оплаты",
     * @OA\Response(
     *    response=200,
     *    description="Информация про способы оплаты",
     *   )
     * )
     */
    public function payment()
    {
        $payments = Payment::all();
        foreach ($payments as $block) {
            $block->image = $this->url . $block->image;
        }
        return response($payments, 200);
    }

    /**
     * @OA\Get(path="/api/news",
     *   tags={"view"},
     *   operationId="viewnews",
     *   summary="Новости",
     * @OA\Response(
     *    response=200,
     *    description="Новости",
     *   )
     * )
     */
    public function news()
    {
        $news = News::all();
        foreach ($news as $block) {
            $block->image = $this->url . $block->image;
        }
        return response($news, 200);
    }

    /**
     * @OA\Get(path="/api/news/{id}",
     *   tags={"view"},
     *   operationId="viewsolonews",
     *   summary="Отдельная новость",
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Айди новости",
     *         required=true,
     *      ),
     * @OA\Response(
     *    response=200,
     *    description="Отдельная новость",
     *   )
     * )
     */
    public function viewnews($id)
    {
        $news = News::where('id', '=', $id)->first();
        $news->image = $this->url . $news->image;
        return response($news, 200);
    }
    /**
     * @OA\Get(path="/api/company",
     *   tags={"view"},
     *   operationId="viewcompany",
     *   summary="Компании",
     * @OA\Response(
     *    response=200,
     *    description="Информация про компании",
     *   )
     * )
     */
    public function company()
    {
        $company = Company::all();
        foreach ($company as $block) {
            $block->image = $this->url . $block->image;
        }
        return response($company, 200);
    }
    /**
     * @OA\Get(path="/api/advantage",
     *   tags={"view"},
     *   operationId="viewadvantage",
     *   summary="Достижения",
     * @OA\Response(
     *    response=200,
     *    description="Информация про достижения",
     *   )
     * )
     */
    public function advantage()
    {
        $advantage = Advantage::all();
        foreach ($advantage as $block) {
            $block->image = $this->url . $block->image;
        }
        return response($advantage, 200);
    }
    /**
     * @OA\Get(path="/api/about",
     *   tags={"view"},
     *   operationId="viewabout",
     *   summary="О нас",
     * @OA\Response(
     *    response=200,
     *    description="Информация про нас",
     *   )
     * )
     */
    public function about()
    {
        $about = Abouts::first();
        $about->image = "$this->url" . "$about->image";
        $delivery = AboutDelivery::first();
        $delivery->сourierdeliveryimage = $this->url . $delivery->сourierdeliveryimage;
        $delivery->selfdeliveryimage = $this->url . $delivery->selfdeliveryimage;
        $deliveryPayment = AboutPayment::first();
        $deliveryPayment->imageblock1 = $this->url . $deliveryPayment->imageblock1;
        $deliveryPayment->imageblock2 = $this->url . $deliveryPayment->imageblock2;
        $deliveryPayment->imageblock3 = $this->url . $deliveryPayment->imageblock3;
        $deliveryCallback = AboutCallback::first();
        $response = [
            'about' => $about,
            'delivery' => $delivery,
            'deliveryPayment' => $deliveryPayment,
            'deliveryCallback' => $deliveryCallback
        ];
        return response($response, 200);
    }
    /**
     * @OA\Get(path="/api/banners",
     *   tags={"view"},
     *   operationId="viewbanners",
     *   summary="Баннеры",
     * @OA\Response(
     *    response=200,
     *    description="Информация про баннеры",
     *   )
     * )
     */
    public function banners()
    {
        $banners = TitleCard::all();
        foreach ($banners as $banner) {
            $banner->image = $this->url . $banner->image;
        }
        return response($banners, 200);
    }
    /**
     * @OA\Get(path="/api/sliders",
     *   tags={"view"},
     *   operationId="viewsliders",
     *   summary="Слайдпер",
     * @OA\Response(
     *    response=200,
     *    description="Информация про слайдер",
     *   )
     * )
     */
    public function sliders()
    {
        $sliders = Slider::all();
        foreach ($sliders as $slider) {
            $slider->image = $this->url . $slider->image;
        }
        return response($sliders, 200);
    }
}


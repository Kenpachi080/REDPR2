<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Basket;
use App\Models\Contacts;
use App\Models\Item;
use App\Models\Orders;
use App\Models\OrdersItem;
use App\Models\TypeDelivery;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes\Contact;


class OrderController extends Controller
{
    public function __construct()
    {
        $this->url = env('APP_URL', 'http://127.0.0.1:8000');
        $this->url = $this->url . "/storage/";
    }

    public function create(OrderRequest $request)
    {
        $endsum = 0;
        if ($request->api_token) {
            $user = User::where('api_token', '=', $request->api_token)->first();
            if ($user) {
                Auth::login($user);
                $id = Auth::id();
            } else {
                $id = null;
            }
        } else {
            $id = null;
        }
        $create = [
            'countitem' => $request->countitem,
            'deliverytype' => $request->deliverytype,
            'name' => $request->name,
            'phone' => $request->phone,
            'secondphone' => $request->secondphone,
            'email' => $request->email,
            'typepayment' => $request->typepayment,
            'paid' => $request->paid,
            'status' => 1,
        ];
        if ($id) {
            $create['UserID'] = $id;
        }
        if ($request->deliverytype == 2) {
            $create['city'] = $request->city;
            $create['region'] = $request->region;
            $create['house'] = $request->house;
        }
        $priceDelivery = TypeDelivery::where('id', '=', $request->deliverytype)->first();
        $order = Orders::create($create);
        $items = [];
        $favoriteItems = $this->checkuser($request->api_token);
        foreach ($request->items as $block) {
            $item = Item::where('id', '=', $block['id'])->first();
            if (!$item) {
                continue;
            }
            if ($item->discount) {
                $price = $item->discount;
            } else {
                $price = $item->price;
            }
            $endsum = $endsum + $price * $block['count'];
            if (isset($favoriteItems) && $favoriteItems != []) {
                if (array_search($item->id, $favoriteItems)) {
                    $isFavorite = 1;
                } else {
                    $isFavorite = 0;
                }
            } else {
                $isFavorite = 0;
            }
            $orderItem = OrdersItem::create([
                'OrderID' => $order->id,
                'ItemID' => $block['id'],
                'endsum' => $price * $block['count'],
                'count' => $block['count'],
            ]);
            $orderItem->isFavorite = $isFavorite;
            array_push($items, $orderItem);
        }
        if (!$items) {
            return response(['message' => '???????????? ???? ????????????????????'], 404);
        }
        $endsum = $endsum + $priceDelivery->price;
        $order->endsum = $endsum;
        $order->sum = $endsum;
        $order->save();
        $order = Orders::where('id', '=', $order->id)->first();
        $response = [];
        $response['Order'] = [];
        $response['Items'] = [];
        array_push($response['Order'], $order);
        array_push($response['Items'], $items);
        return response($response, 201);
    }

    /**
     * @OA\Post(
     * path="/api/order/view",
     * summary="???????????????????? ????????????",
     * description="???????????????????? ????????????",
     * operationId="orderview",
     * tags={"order"},
     * @OA\RequestBody(
     *    required=true,
     *    description="?????? ??????????",
     *    @OA\JsonContent(
     *       required={""},
     *       @OA\Property(property="api_token", type="string", format="string", example="6WxjM0XOruMPWPnJKEAPHNIMwNpe0bAU7iGWswoKrQDuXC5MNUmuJh1Y4GuG"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack ?? ??????????????",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function view(Request $request)
    {
        $order = Orders::where('orders.UserID', '=', Auth::id())
            ->leftjoin('type_deliveries', 'type_deliveries.id', '=', 'orders.deliverytype')
            ->leftjoin('type_payments', 'type_payments.id', '=', 'orders.typepayment')
            ->leftjoin('statuses', 'statuses.id', 'orders.status')
            ->select('orders.id', 'orders.sum', 'orders.name', 'orders.phone',
                'orders.secondphone', 'orders.email', 'orders.endsum', 'orders.paid', 'orders.UserID',
                'type_deliveries.type as deliverytype', 'type_payments.type as typepayment',
                'orders.city', 'orders.region', 'orders.house', 'statuses.name as status'
                , 'orders.created_at', 'orders.updated_at')
            ->get();
        $favoriteItems = $this->checkuser($request->api_token);
        $contacts = Contacts::all();
        foreach ($order as $item) {
            $orderItem = OrdersItem::where('OrderID', '=', $item->id)->get();
            foreach ($orderItem as $block) {

                $block->item = $this->items($block->ItemID, $favoriteItems);
            }
            $item->items = $orderItem;
            if ($item->paid == 1) {
                $item->paid = "????????????????";
            } else {
                $item->paid = "???? ????????????????";
            }
            $item->contacts = $contacts;
        }
        return response($order, 200);
    }

    /**
     * @OA\Post(
     * path="/api/order/view/{id}",
     * summary="???????????????????? ????????????",
     * description="???????????????????? ????????????",
     * operationId="orderviewsingle",
     * tags={"order"},
     * @OA\RequestBody(
     *    required=true,
     *    description="?????? ??????????",
     *    @OA\JsonContent(
     *       required={""},
     *       @OA\Property(property="api_token", type="string", format="string", example="6WxjM0XOruMPWPnJKEAPHNIMwNpe0bAU7iGWswoKrQDuXC5MNUmuJh1Y4GuG"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack ?? ??????????????",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function viewsingle(Request $request, $id)
    {
        $order = Orders::leftjoin('type_deliveries', 'type_deliveries.id', '=', 'orders.deliverytype')
            ->leftjoin('type_payments', 'type_payments.id', '=', 'orders.typepayment')
            ->leftjoin('statuses', 'statuses.id', 'orders.status')
            ->where('orders.id', '=', $id)
            ->select('orders.id', 'orders.sum', 'orders.name', 'orders.phone',
                'orders.secondphone', 'orders.email', 'orders.endsum', 'orders.paid', 'orders.UserID',
                'type_deliveries.type as deliverytype', 'type_payments.type as typepayment', 'orders.city', 'orders.region', 'orders.house'
            , 'orders.created_at', 'orders.updated_at', 'statuses.name as status'
            )
            ->first();
        if ($order) {
            if (Auth::id() != $order->UserID) {
                return response(['message' => '???????????? ????????????????'], 403);
            }
        } else {
            return response(['message' => '???????? ????????????'], 404);
        }
        $contacts = Contacts::all();
        $favoriteItems = $this->checkuser($request->api_token);
        $orderItem = OrdersItem::where('OrderID', '=', $order->id)->get();
        foreach ($orderItem as $block) {
            $block->item = $this->items($block->ItemID, $favoriteItems);
        }
        $order->items = $orderItem;
        if ($order->paid == 1) {
            $order->paid = "????????????????";
        } else {
            $order->paid = "???? ????????????????";
        }
        $order->contacts = $contacts;
        return response($order, 200);
    }

    /**
     * @OA\Post(
     * path="/api/order/search",
     * summary="???????????????????? ???????????? ???? ??????????????",
     * description="???????????????????? ???????????? ???? ??????????????",
     * operationId="orderviewstatus",
     * tags={"order"},
     * @OA\RequestBody(
     *    required=true,
     *    description="?????? ??????????",
     *    @OA\JsonContent(
     *       required={"status"},
     *       @OA\Property(property="api_token", type="string", format="string", example="6WxjM0XOruMPWPnJKEAPHNIMwNpe0bAU7iGWswoKrQDuXC5MNUmuJh1Y4GuG"),
     *       @OA\Property(property="status", type="string", format="string", example="1"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack ?? ??????????????",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function search(Request $request)
    {
        if ($request->status != 0) {
            $tempStatus = '=';
            $status = $request->status;
        } else {
            $tempStatus = '!=';
            $status = 'null';
        }
        $order = Orders::leftjoin('type_deliveries', 'type_deliveries.id', '=', 'orders.deliverytype')
            ->leftjoin('type_payments', 'type_payments.id', '=', 'orders.typepayment')
            ->leftjoin('statuses', 'statuses.id', '=', 'orders.status')
            ->where('orders.UserID', '=', Auth::id())
            ->where('orders.status', $tempStatus, $status)
            ->select('orders.id', 'orders.sum', 'orders.name', 'orders.phone',
                'orders.secondphone', 'orders.email', 'orders.endsum', 'orders.paid', 'orders.UserID',
                'type_deliveries.type as deliverytype', 'type_payments.type as typepayment', 'statuses.name as status'
            , 'orders.created_at', 'orders.updated_at'
            )
            ->get();
        $favoriteItems = $this->checkuser($request->api_token);
        foreach ($order as $item) {
            $orderItem = OrdersItem::where('OrderID', '=', $item->id)->get();
            foreach ($orderItem as $block) {
                $block->item = $this->items($block->ItemID, $favoriteItems);
            }
            $item->items = $orderItem;
        }
        return $order;
    }

    private function items($item_id, $favoriteItems)
    {
        $item = Item::where("id", '=', $item_id)->first();
        $item->image = $this->url . $item->image;
        $item->images = $this->multiimage(json_decode($item->images));
        if (isset($favoriteItems) && $favoriteItems != []) {
            if (array_search($item->id, $favoriteItems)) {
                $isFavorite = 1;
            } else {
                $isFavorite = 0;
            }
        } else {
            $isFavorite = 0;
        }
        $item->isFavorite = $isFavorite;
        return $item;
    }

    private function multiimage($image)
    {
        $return = [];
        if ($image) {
            if (gettype($image) == 'array') {
                foreach ($image as $value) {
                    $return[] = $this->url . $value;
                }
            }
        } else {
            $return = [];
        }
        return $return;
    }

    private function checkuser($token)
    {
        $favoriteItems = [];
        if ($token) {
            $user = User::where('api_token', '=', $token)->first();
            if ($user) {
                Auth::login($user);
                $favorite = Basket::where('UserID', '=', Auth::id())->get();
                if (count($favorite) > 0) {
                    foreach ($favorite as $key) {
                        $favoriteItems[$key->ItemID] = $key->ItemID;
                    }
                }
            }
        }
        return $favoriteItems;
    }
}

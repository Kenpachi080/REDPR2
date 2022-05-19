<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Basket;
use App\Models\Item;
use App\Models\Orders;
use App\Models\OrdersItem;
use App\Models\TypeDelivery;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


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
            $orderItem = OrdersItem::create([
                'OrderID' => $order->id,
                'ItemID' => $block['id'],
                'endsum' => $price,
                'count' => $block['count'],
            ]);
            array_push($items, $orderItem);
        }
        if (!$items) {
            return response(['message' => 'Товары не существуют'], 404);
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
     * summary="Посмотреть заказы",
     * description="Посмотреть заказы",
     * operationId="orderview",
     * tags={"order"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={""},
     *       @OA\Property(property="api_token", type="string", format="string", example="6WxjM0XOruMPWPnJKEAPHNIMwNpe0bAU7iGWswoKrQDuXC5MNUmuJh1Y4GuG"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack с товаром",
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
            ->select('orders.id', 'orders.sum', 'orders.name', 'orders.phone',
                'orders.secondphone', 'orders.email', 'orders.endsum', 'orders.paid',
                'orders.created_at', 'orders.UserID',
                'type_deliveries.type as deliverytype', 'type_payments.type as typepayment')
            ->get();
        foreach ($order as $item) {
            $orderItem = OrdersItem::where('OrderID', '=', $item->id)->get();
            foreach ($orderItem as $block) {
                $block->item = $this->items($block->ItemID);
            }
            $item->items = $orderItem;
        }
        return response($order, 200);
    }

    /**
     * @OA\Post(
     * path="/api/order/view/{id}",
     * summary="Посмотреть заказы",
     * description="Посмотреть заказы",
     * operationId="orderviewsingle",
     * tags={"order"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={""},
     *       @OA\Property(property="api_token", type="string", format="string", example="6WxjM0XOruMPWPnJKEAPHNIMwNpe0bAU7iGWswoKrQDuXC5MNUmuJh1Y4GuG"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack с товаром",
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
            ->where('orders.id', '=', $id)
            ->select('orders.id', 'orders.sum', 'orders.name', 'orders.phone',
                'orders.secondphone', 'orders.email', 'orders.endsum', 'orders.paid',
                'orders.created_at', 'orders.UserID',
                'type_deliveries.type as deliverytype', 'type_payments.type as typepayment')
            ->first();
        if ($order) {
            if (Auth::id() != $order->UserID) {
                return response(['message' => 'Доступ запрещен'], 403);
            }
        } else {
            return response(['message' => 'Нету заказа'], 404);
        }
        $orderItem = OrdersItem::where('OrderID', '=', $order->id)->get();
        foreach ($orderItem as $block) {
            $block->item = $this->items($block->ItemID);
        }
        $order->items = $orderItem;
        return response($order, 200);
    }

    /**
     * @OA\Post(
     * path="/api/order/search",
     * summary="Посмотреть заказы по статусу",
     * description="Посмотреть заказы по статусу",
     * operationId="orderviewstatus",
     * tags={"order"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"status"},
     *       @OA\Property(property="api_token", type="string", format="string", example="6WxjM0XOruMPWPnJKEAPHNIMwNpe0bAU7iGWswoKrQDuXC5MNUmuJh1Y4GuG"),
     *       @OA\Property(property="status", type="string", format="string", example="1"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack с товаром",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function search(Request $request)
    {
        $order = Orders::leftjoin('type_deliveries', 'type_deliveries.id', '=', 'orders.deliverytype')
            ->leftjoin('type_payments', 'type_payments.id', '=', 'orders.typepayment')
            ->leftjoin('statuses', 'statuses.id', '=', 'orders.status')
            ->where('orders.UserID', '=', Auth::id())
            ->where('orders.status', '=', $request->status)
            ->select('orders.id', 'orders.sum', 'orders.name', 'orders.phone',
                'orders.secondphone', 'orders.email', 'orders.endsum', 'orders.paid',
                'orders.created_at', 'orders.UserID',
                'type_deliveries.type as deliverytype', 'type_payments.type as typepayment', 'statuses.name as status')
            ->get();
        foreach ($order as $item) {
            $orderItem = OrdersItem::where('OrderID', '=', $item->id)->get();
            foreach ($orderItem as $block) {
                $block->item = $this->items($block->ItemID);
            }
            $item->items = $orderItem;
        }
        return $order;
    }

    private function items($item_id)
    {
        $item = Item::where("id", '=', $item_id)->first();
        $item->image = $this->url . $item->image;
        $item->images = $this->multiimage(json_decode($item->images));
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
}

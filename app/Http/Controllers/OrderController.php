<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Item;
use App\Models\Orders;
use App\Models\OrdersItem;
use Illuminate\Http\Request;



class OrderController extends Controller
{
    public function create(Request $request)
    {
//        dd($request);
        $order = Orders::create([
            'countitem' => $request->countitem,
            'sum' => $request->sum,
            'deliverytype' => $request->deliverytype,
            'name' => $request->name,
            'phone' => $request->phone,
            'secondphone' => $request->secondphone,
            'email' => $request->email,
            'endsum' => $request->endsum,
            'typepayment' => $request->typepayment,
            'paid' => $request->paid]);
        $items = [];
        foreach ($request->items as $key => $value) {
            $validate = Item::where('id', '=', $key)->first();
            if (!$validate) {
                continue;
            }
            $item = OrdersItem::create([
                'OrderID' => $order->id,
                'ItemID' => $key,
                'endsum' => $request->itemsum[$key],
                'count' => $value,
            ]);
            array_push($items, $item);
        }
        $response = [];
        $response['Заказ'] = [];
        $response['Товары'] = [];
        array_push($response['Заказ'], $order);
        array_push($response['Товары'], $items);
        return response($response, 201);
    }
}

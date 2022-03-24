<?php

namespace App\Http\Controllers;

use App\Models\Basket;
use Illuminate\Http\Request;

class BasketController extends Controller
{
    public function addItem(Request $request)
    {
        $data = $request->validate([
            'UserID' => 'required',
            'ItemID' => 'required',
        ]);
        $basket = Basket::where('UserID', '=', $data['UserID'])->where('ItemID', '=', $data['ItemID'])->first();
        if ($basket) {
            return response([
                'message' => 'Данный товар уже в корзине'
            ]);
        }
        Basket::create([
            'UserID' => $data['UserID'],
            'ItemID' => $data['ItemID'],
        ]);

        return response([
            'message' => 'Товар был успешно добавлен'
        ]);
    }

    public function view(Request $request)
    {
        $data = $request->validate([
            'UserID' => 'required'
        ]);

        $basket = Basket::where('baskets.UserID', '=', $data['UserID'])
            ->leftjoin('items', 'items.id', '=', 'baskets.ItemID')
            ->get();
        if (count($basket) == 0) {
            return response([
                'message' => 'Товара в корзине нет:('
            ], 401);
        }
        $response = [];
        foreach ($basket as $item) {
            $baskets = [];
            $baskets['image'] = $item->image;
            $baskets['title'] = $item->title;
            $baskets['subcontent'] = $item->subcontent;
            $baskets['price'] = $item->price;
            $baskets['discount'] = $item->discount;
            array_push($response, $baskets);
        }
        return response($response, 201);
    }

    public function delete(Request $request)
    {
        $data = $request->validate([
            'UserID' => 'required',
            'ItemID' => 'required'
        ]);

        $basket = Basket::where('UserID', '=', $data['UserID'])->where('ItemID', '=', $data['ItemID'])->first();
        if (!$basket) {
            return response([
                'message' => 'Товар не был найден',
            ], 401);
        }
        $basket->delete();

        return response([
            'message' => 'Товар был удален'
        ], 201);
    }
}

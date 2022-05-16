<?php

namespace App\Http\Controllers;

use App\Models\Basket;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{

    public function __construct()
    {
        $this->url = env('APP_URL', 'http://127.0.0.1:8000');
        $this->url = $this->url . "/storage/";
    }

    /**
     * @OA\Post(
     * path="/api/favorite/add",
     * summary="Добавить предмет в избранные",
     * description="Добавить в избранные",
     * operationId="additem",
     * tags={"favorite"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"api_token, ItemID"},
     *       @OA\Property(property="api_token", type="string", format="string", example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM"),
     *       @OA\Property(property="ItemID", type="string", format="string", example="10"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Товары",
     *        )
     *     )
     * )
     */
    public function addItem(Request $request)
    {
        $data = $request->validate([
            'ItemID' => 'required',
        ]);
        $user = Auth::id();
        $basket = Basket::where('UserID', '=', $user)->where('ItemID', '=', $data['ItemID'])->first();
        if ($basket) {
            return response([
                'message' => 'Данный товар уже в корзине'
            ]);
        }
        $item = Item::where('id', '=', $data['ItemID'])->first();
        if (!$item) {
            return response([
                'message' => 'Товар не существует'
            ]);
        }
        Basket::create([
            'UserID' => $user,
            'ItemID' => $data['ItemID'],
        ]);
        return response([
            'message' => 'Товар был успешно добавлен'
        ], 201);
    }

    /**
     * @OA\Post(
     * path="/api/favorite/view",
     * summary="Посмотреть избранные",
     * description="Посмотреть избранные",
     * operationId="view",
     * tags={"favorite"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"api_token"},
     *       @OA\Property(property="api_token", type="string", format="string", example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Товары",
     *        )
     *     )
     * )
     */
    public function view()
    {
        $user = Auth::id();
        $basket = Basket::where('baskets.UserID', '=', $user)
            ->leftjoin('items', 'items.id', '=', 'baskets.ItemID')
            ->get();
        if (count($basket) == 0) {
            return response([
                'message' => 'Товаров в корзине нет:('
            ], 404);
        }
        foreach ($basket as $item) {
            $item->image = $this->url . $item->image;
            $item->images = $this->multiimage($item->images);
            $item->isFavorite = 1;
        }
        return response($basket, 201);
    }

    /**
     * @OA\Post(
     * path="/api/favorite/delete",
     * summary="Убрать предмет из избранных",
     * description="Убрать из избранные",
     * operationId="deleteitem",
     * tags={"favorite"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"api_token, ItemID"},
     *       @OA\Property(property="api_token", type="string", format="string", example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM"),
     *       @OA\Property(property="ItemID", type="string", format="string", example="10"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Товары",
     *        )
     *     )
     * )
     */
    public function delete(Request $request)
    {
        $data = $request->validate([
            'ItemID' => 'required'
        ]);
        $user = Auth::id();
        $basket = Basket::where('UserID', '=', $user)->where('ItemID', '=', $data['ItemID'])->first();
        if (!$basket) {
            return response([
                'message' => 'Товар не был найден',
            ], 404);
        }
        $basket->delete();

        return response([
            'message' => 'Товар был удален'
        ], 200);
    }

    private function multiimage($image)
    {
        $return = [];
        if ($image) {
            foreach ($image as $value) {
                $return[] = $this->url.$value;
            }
        } else {
            $return = [];
        }
        return $return;
    }
}

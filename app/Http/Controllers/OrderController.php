<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Item;
use App\Models\Orders;
use App\Models\OrdersItem;
use Illuminate\Http\Request;



class OrderController extends Controller
{
//    /**
//     * @OA\Post(
//     * path="/api/order/create",
//     * summary="Создать заказ",
//     * description="Создать заказ",
//     * operationId="orderCreate",
//     * tags={"order"},
//     * @OA\RequestBody(
//     *    required=true,
//     *    description="Request",
//     *    @OA\JsonContent(
//     *       required={"deliverytype, name, phone, secondphone, email, typepayment, paid, items"},
//     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
//     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
//     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
//     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
//     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
//     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
//     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
//     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
//     *       @OA\Property(property="api_token", type="string", format="string", example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM"),
//     *  ),
//     * ),
//     * @OA\Response(
//     *    response=201,
//     *    description="Возврощается полная информация про пользователя, и его токен для дальнейшей работы с юзером",
//     *    @OA\JsonContent(
//     *       type="object",
//     *             @OA\Property(
//     *                property="user",
//     *                type="object",
//     *               example={
//     *                  "id": 8,
//     *                     "role_id": 2,
//     *                     "name": "+7708",
//     *                     "email": "testemail@mail.ru",
//     *                     "avatar": "users/default.png",
//     *                     "email_verified_at": null,
//     *                     "settings": null,
//     *                     "created_at": "2022-04-20T19:31:30.000000Z",
//     *                     "updated_at": "2022-04-20T19:58:44.000000Z",
//     *                     "fio": null,
//     *                   "telephone": null,
//     *                     "birthday": null,
//     *                     "address": null,
//     *                     "api_token": "FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM"
//     *                  }
//     *              ),
//     *     @OA\Property(
//     *                property="token",
//     *                type="string",
//     *               example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM",
//     *              ),
//     *     ),
//     *        )
//     *     )
//     * )
//     */
    public function create(OrderRequest $request)
    {
        $endsum = 0;
        $order = Orders::create([
            'countitem' => $request->countitem,
            'deliverytype' => $request->deliverytype,
            'name' => $request->name,
            'phone' => $request->phone,
            'secondphone' => $request->secondphone,
            'email' => $request->email,
            'typepayment' => $request->typepayment,
            'paid' => $request->paid]);
        $items = [];
        foreach ($request->items as $key => $value) {
            $item = Item::where('id', '=', $key)->first();
            if (!$item) {
                continue;
            }
            if ($item->discount) {
                $price = $item->discount;
            } else {
                $price = $item->price;
            }
            $endsum = ($endsum + $price) * $value;
            $orderItem = OrdersItem::create([
                'OrderID' => $order->id,
                'ItemID' => $key,
                'endsum' => $price,
                'count' => $value,
            ]);
            array_push($items, $orderItem);
        }
        $order->endsum = $endsum;
        $order->sum = $endsum;
        $order->save();
        $response = [];
        $response['Заказ'] = [];
        $response['Товары'] = [];
        array_push($response['Заказ'], $order);
        array_push($response['Товары'], $items);
        return response($response, 201);
    }
}

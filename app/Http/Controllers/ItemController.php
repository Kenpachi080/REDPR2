<?php

namespace App\Http\Controllers;

use App\Models\Basket;
use App\Models\Category;
use App\Models\Item;
use App\Models\Mostdescription;
use App\Models\Mostitem;
use App\Models\Subcategory;
use App\Models\Titledescription;
use App\Models\User as User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    public function __construct()
    {
        $this->url = env('APP_URL', 'http://127.0.0.1:8000');
        $this->url = $this->url . "/storage/";
    }

    /**
     * @OA\Post(
     * path="/api/items",
     * summary="Поиск по каталогу",
     * description="Поиск по каталогу",
     * operationId="item",
     * tags={"item"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"search"},
     *       @OA\Property(property="search", type="string", format="string", example="Гипсокартон"),
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
    public function items(Request $request)
    {
        $item = Item::where('title', 'LIKE', "$request->search%")->get();
        if (count($item) < 1) {
            return response(['message' => 'Ничего не найден'], 404);
        }
        $favoriteItems = $this->checkuser($request->api_token);
        foreach ($item as $value) {
            $value->image = $this->url . $value->image;
            if (isset($favoriteItems) && $favoriteItems != []) {
                if (array_search($value->id, $favoriteItems)) {
                    $value->isFavorite = 1;
                } else {
                    $value->isFavorite = 0;
                }
            } else {
                $value->isFavorite = 0;
            }
            $value->images = $this->multiimage(json_decode($value->images));
        }
        return response($item, 200);
    }

    /**
     * @OA\Post(
     * path="/api/items/{id}",
     * summary="Отдельный товар",
     * description="Отдельный товар",
     * operationId="itemsolo",
     * tags={"item"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"id"},
     *       @OA\Property(property="id", type="string", format="string", example="Айди товара"),
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
    public function viewitem(Request $request, $item_id)
    {
        $item = Item::where('id', '=', $item_id)
            ->first();
        if (!$item) {
            return response([
                'message' => 'Товар не был найден'
            ], 404);
        }
        $description = Item::leftjoin('titledescriptions', 'titledescriptions.categoryid', '=', 'items.CategoryID')
            ->where('items.id', '=', $item->id)
            ->select('items.id as item_id', 'titledescriptions.id as titleid', 'titledescriptions.title as title')
            ->get();
        foreach ($description as $block) {
            $valueDesc = Mostdescription::where('mostdescriptions.titledescription_id', '=', $block->titleid)
                ->leftjoin('valuedescriptions', 'valuedescriptions.id', '=', 'mostdescriptions.valuedescription_id')
                ->leftjoin('mostitems', 'mostitems.valuedescription_id', '=', 'mostdescriptions.valuedescription_id')
                ->where('mostitems.item_id', '=', $block->item_id)
                ->select('valuedescriptions.*')
                ->first();
            if ($valueDesc) {
                $block->description = $valueDesc->title;
            }
        }
        $item->image = $this->url . $item->image;
        $favoriteItems = $this->checkuser($request->api_token);
        if (isset($favoriteItems) && $favoriteItems != []) {
            if (array_search($item->id, $favoriteItems)) {
                $item->isFavorite = 1;
            } else {
                $item->isFavorite = 0;
            }
        } else {
            $item->isFavorite = 0;
        }
        $item->images = $this->multiimage(json_decode($item->images));
        $item->description = $description;
        return response($item, 200);
    }

    /**
     * @OA\Post(
     * path="/api/items/searchproducts",
     * summary="Отдельный товар",
     * description="Отдельный товар",
     * operationId="searchproducts",
     * tags={"item"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"search"},
     *       @OA\Property(property="description", type="string", format="string", example="5"),
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
    public function searchproducts(Request $request)
    {
        $response = $most = \App\Models\Mostitem::leftjoin('items', 'items.id', '=', 'mostitems.item_id')
            ->select('items.id', 'items.title', 'items.image', 'items.subcontent', 'items.content', 'items.price',
                'items.discount', 'items.count', 'items.CategoryID', 'items.SubCategoryID');
        if (gettype($request->description) == "array") {
            if ($request->description) {
                foreach ($request->description as $key => $value) {
                    $response = $most->where('mostitems.valuedescription_id', '=', $value);
                }
            }
        } else {
            $response = $most->where('mostitems.valuedescription_id', '=', $request->description);
        }
        $response = $most->get();
        $favoriteItems = $this->checkuser($request->api_token);
        foreach ($response as $value) {
            $value->image = $this->url . $value->image;
            if (isset($favoriteItems) && $favoriteItems != []) {
                if (array_search($value->id, $favoriteItems)) {
                    $value->isFavorite = 1;
                } else {
                    $value->isFavorite = 0;
                }
            } else {
                $value->isFavorite = 0;
            }
            $value->images = $this->multiimage(json_decode($value->images));
        }
        return response($response, 200);
    }

    /**
     * @OA\Get(path="/api/items/category",
     *   tags={"item"},
     *   operationId="itemcategory",
     *   summary="Категория",
     * @OA\Response(
     *    response=200,
     *    description="Информация про категории",
     *   )
     * )
     */
    public function category()
    {
        $categories = Category::all();

        foreach ($categories as $category) {
            $category->subcategory = Subcategory::where('CategoryID', '=', $category->id)->select('id', 'name', 'image')->get();
            foreach ($category->subcategory as $block) {
                $block->image = $this->url . $block->image;
            }
            $category->image = $this->url . $category->image;
        }
        return response()->json(['categories' => $categories]);
    }

    /**
     * @OA\Get(path="/api/items/description",
     *   tags={"item"},
     *   operationId="description",
     *   summary="Описание",
     *      @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Категория",
     *         required=false,
     *      ),
     * @OA\Response(
     *    response=200,
     *    description="Описание товара",
     *   )
     * )
     */
    public function description(Request $request)
    {
        if ($request->category) {
            $category = Category::where('id', '=', $request->category)->get();
        } else {
            $category = Category::all();
        }
        foreach ($category as $item) {
            $descriptions = Titledescription::where('CategoryID', '=', $item->id)
                ->select('id', 'title', 'categoryid')
                ->get();
            foreach ($descriptions as $value) {
                $most = Mostdescription::
                leftjoin('valuedescriptions', 'valuedescriptions.id', '=', 'mostdescriptions.valuedescription_id')
                    ->where('mostdescriptions.titledescription_id', '=', $value->id)
                    ->select('mostdescriptions.id', 'valuedescriptions.title')
                    ->get();
                $value->count = $most;
                $return[] = $value;
            }
            $item->description = $return;
        }
        return response($category, 200);
    }


    /**
     * @OA\Post(
     * path="/api/items/category/item",
     * summary="Поиск товаров по категории",
     * description="Поиск товаров по категории",
     * operationId="categoryitem",
     * tags={"item"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"search"},
     *       @OA\Property(property="categoryID", type="string", format="string", example="1"),
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
    public function categoryitem(Request $request)
    {
        if (!$request->categoryID) {
            return response([
                'message' => 'не отправлен id'
            ], 400);
        }
        $item = Item::where('CategoryID', '=', $request->categoryID)->get();
        if (count($item) < 1) {
            return response([
                'Товаров этой категории нету'
            ], 404);
        }
        $favoriteItems = $this->checkuser($request->api_token);
        foreach ($item as $value) {
            $value->image = $this->url . $value->image;
            if (isset($favoriteItems) && $favoriteItems != []) {
                if (array_search($value->id, $favoriteItems)) {
                    $value->isFavorite = 1;
                } else {
                    $value->isFavorite = 0;
                }
            } else {
                $value->isFavorite = 0;
            }
            $value->images = $this->multiimage(json_decode($value->images));
        }
        $response = [
            'items' => $item
        ];
        return response($response, 200);

    }

    /**
     * @OA\Post(
     * path="/api/items/subcategory/item",
     * summary="Поиск товаров по подКатегории",
     * description="Поиск товаров по подКатегории",
     * operationId="subcategoryID",
     * tags={"item"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"search"},
     *       @OA\Property(property="subcategoryID", type="string", format="string", example="1"),
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
    public function subcategoryitem(Request $request)
    {
        if (!$request->subcategoryID) {
            return response([
                'message' => 'не отправлен id'
            ], 400);
        }
        $item = Item::where('SubCategoryID', '=', $request->subcategoryID)->get();
        if (count($item) < 1) {
            return response([
                'Товаров этой подкатегории нету'
            ], 404);
        }
        $favoriteItems = $this->checkuser($request->api_token);
        foreach ($item as $value) {
            $value->image = $this->url . $value->image;
            if (isset($favoriteItems) && $favoriteItems != []) {
                if (array_search($value->id, $favoriteItems)) {
                    $value->isFavorite = 1;
                } else {
                    $value->isFavorite = 0;
                }
            } else {
                $value->isFavorite = 0;
            }
            $value->images = $this->multiimage(json_decode($value->images));
        }
        $response = [
            'item' => $item
        ];
        return response($response, 200);
    }

    /**
     * @OA\Post(
     * path="/api/popular",
     * summary="Популярные товары",
     * description="Популярные товары",
     * operationId="itempopular",
     * tags={"item"},
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
    public function popular(Request $request)
    {
        $item = Item::where('popular', '=', '1')->get();
        if (count($item) < 1) {
            return response([
                'message' => 'товаров нету'
            ], 204);
        }
        $favoriteItems = $this->checkuser($request->api_token);
        foreach ($item as $value) {
            $value->image = $this->url . $value->image;
            if (isset($favoriteItems) && $favoriteItems != []) {
                if (array_search($value->id, $favoriteItems)) {
                    $value->isFavorite = 1;
                } else {
                    $value->isFavorite = 0;
                }
            } else {
                $value->isFavorite = 0;
            }
            $value->images = $this->multiimage(json_decode($value->images));
        }
        return response($item, 200);
    }

    /**
     * @OA\Post(
     * path="/api/items/discount",
     * summary="Популярные товары",
     * description="Популярные товары",
     * operationId="itemdiscount",
     * tags={"item"},
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
    public function discount(Request $request)
    {
        $item = Item::where('discount', '>', '1')->get();
        if (count($item) < 1) {
            return response([
                'message' => 'товаров нету'
            ], 204);
        }
        $favoriteItems = $this->checkuser($request->api_token);
        foreach ($item as $value) {
            $value->image = $this->url . $value->image;
            if (isset($favoriteItems) && $favoriteItems != []) {
                if (array_search($value->id, $favoriteItems)) {
                    $value->isFavorite = 1;
                } else {
                    $value->isFavorite = 0;
                }
            } else {
                $value->isFavorite = 0;
            }
            $value->images = $this->multiimage(json_decode($value->images));
        }
        return response($item, 200);
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


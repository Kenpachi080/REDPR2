<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Mostdescription;
use App\Models\Subcategory;
use App\Models\Titledescription;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct()
    {
        $this->url = env('APP_URL', 'http://127.0.0.1:8000');
        $this->url = $this->url . "/storage/";
    }

    /**
     * @OA\Get(path="/api/items/",
     *   tags={"item"},
     *   operationId="item",
     *   summary="Поиск по каталогу",
     *      @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Название товара",
     *         required=true,
     *      ),
     * @OA\Response(
     *    response=200,
     *    description="Поиск по каталогу",
     *   )
     * )
     */
    public function items(Request $request)
    {
//        $category = Category::all();
//        $response = [];
//        foreach ($category as $block) {
//            $subcategory = Subcategory::where('CategoryID', '=', $block->id)->get();
//            $end = [
//                'category' => $block,
//                'subcategory' => $subcategory,
//            ];
//            array_push($response, $end);
//        }
//        return response($response, 200);
        $item = Item::where('title', 'LIKE', "$request->search%")->get();
        if (count($item) < 1) {
            return response(['message' => 'Ничего не найден'], 404);
        }
        foreach ($item as $value) {
            $value->image = $this->url . $value->image;
        }
        return response($item, 200);
    }

    /**
     * @OA\Get(path="/api/items/{id}",
     *   tags={"item"},
     *   operationId="itemsolo",
     *   summary="Отдельный товар",
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Айди товара",
     *         required=true,
     *      ),
     * @OA\Response(
     *    response=200,
     *    description="Отдельный товар",
     *   )
     * )
     */
    public function viewitem($item_id)
    {
        $item = Item::where('id', '=', $item_id)
            // ->select('id', 'image', 'title', 'subcontent', 'content', 'price', 'discount', 'count', )
            ->first();
        $item->image = $this->url . $item->image;
        if (!$item) {
            return response([
                'message' => 'Товар не был найден'
            ], 404);
        }
        return response($item, 200);
    }

    /**
     * @OA\Get(path="/api/items/searchproducts",
     *   tags={"item"},
     *   operationId="searchproducts",
     *   summary="Отдельный товар",
     *      @OA\Parameter(
     *         name="description",
     *         in="query",
     *         description="Айди описания(4)",
     *         required=true,
     *      ),
     * @OA\Response(
     *    response=200,
     *    description="Отдельный товар",
     *   )
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
            $category->subcategory = Subcategory::where('CategoryID', '=', $category->id)->select('id', 'name')->get();
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
        $response = [];
        foreach ($category as $item) {
            $descriptions = Titledescription::where('CategoryID', '=', $item->id)
                ->select('id', 'title', 'categoryid')
                ->get();
            $return = [];
            $response[$item->name] = [];
            foreach ($descriptions as $value) {
                $most = Mostdescription::
                leftjoin('valuedescriptions', 'valuedescriptions.id', '=', 'mostdescriptions.valuedescription_id')
                    ->where('mostdescriptions.titledescription_id', '=', $value->id)
                    ->select('mostdescriptions.id', 'valuedescriptions.title')
                    ->get();
                $return[$value->title] = $most;
            }
            array_push($response[$item->name], $return);
        }
        return response($response, 200);
    }

    /**
     * @OA\Get(path="/api/items/category/item",
     *   tags={"item"},
     *   operationId="categoryitem",
     *   summary="Поиск товаров по категории",
     *      @OA\Parameter(
     *         name="categoryID",
     *         in="query",
     *         description="Категория",
     *         required=true,
     *      ),
     * @OA\Response(
     *    response=200,
     *    description="Поиск товаров по категории",
     *   )
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

        $response = [
            'items' => $item
        ];
        return response($response, 200);
    }

    /**
     * @OA\Get(path="/api/items/subcategory/item",
     *   tags={"item"},
     *   operationId="subcategoryID",
     *   summary="Поиск товаров по подКатегории",
     *      @OA\Parameter(
     *         name="subcategoryID",
     *         in="query",
     *         description="ПодКатегория",
     *         required=true,
     *      ),
     * @OA\Response(
     *    response=200,
     *    description="Поиск товаров по подКатегории",
     *   )
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
        return response($item, 200);
    }

    /**
     * @OA\Get(path="/api/popular",
     *   tags={"item"},
     *   operationId="itempopular",
     *   summary="Популярные товары",
     * @OA\Response(
     *    response=200,
     *    description="Популярные товары",
     *   )
     * )
     */
    public function popular()
    {
        $item = Item::where('popular', '=', '1')->get();
        if (count($item) < 1) {
            return response([
                'message' => 'товаров нету'
            ], 204);
        }
        foreach ($item as $value) {
            $value->image = $this->url . $value->image;
        }
        return response($item, 200);
    }
}


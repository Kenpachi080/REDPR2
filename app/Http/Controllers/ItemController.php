<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function items()
    {
        $items = Item::all();
        $categorys = Category::all();
        $subcategoryes = Subcategory::all();
        foreach ($categorys as $category) {
            $array_category[$category->name] = [];
            $eachSubCategoryes = $subcategoryes->where('CategoryID', '=', $category->id);
            foreach ($eachSubCategoryes as $subcategory) {
                $modelitem = $items
                    ->where('SubCategoryID', '=', $subcategory->id)
                    ->where('CategoryID', '=', $category->id);
                $item = [];
                foreach ($modelitem as $block) {
                    array_push($item, $block);
                }
                $array_subcategory = [];
                $array_subcategory[$subcategory->name] = $item;
                array_push($array_category[$category->name], $array_subcategory);
            }
        }
        return response($array_category, 200);
    }

    public function viewitem($item_id)
    {
        $item = Item::where('id', '=', $item_id)
            ->select('id', 'image', 'title', 'subcontent', 'content', 'price', 'discount', 'count')
            ->first();
        if (!$item) {
            return response([
                'message' => 'Товар не был найден'
            ], 404);
        }
        return response($item, 200);
    }

    public function search(Request $request)
    {
        $item = Item::all();
        if ($request->price) {
            $item->where('price', '>', $request->price['min'])
                ->where('price', '<', $request->price['max']);
        }
    }
}

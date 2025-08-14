<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DisountController extends Controller
{
    public function set(Request $request){

        $validData = Validator::make($request->all(), [
            'discount' => ['required', 'exists:discounts,code']
        ]);

        if ( ! $validData->passes() ){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }

        $discount = Discount::whereCode($request->discount)->first();

        if ( ! $discount->isActive() ){
            return response()->json(['status' => 0, 'error' => ["discount" => "مهلت استفاده از این کد تخفیف به پایان رسیده است"]]);
        }


        if ( ! $discount->isAvailableUser(auth()->user()->id) ){
            return response()->json(['status' => 0, 'error' => ["discount" => "شما قادر به استفاده از این کد تخفیف نمیباشید"]]);
        }




        if ( $discount->courses()->count() ){

            $cartCollect = collect();
            foreach (Cart::content() as $item) {
                $cartCollect->add($item->id->id);
            }

            if ( ! $cartCollect->intersect($discount->courses->pluck('id'))->count() ){
                return response()->json(['status' => 0, 'error' => ["discount" => "این کد تخفیف شامل دوره‌های انتخابی شما نمیباشد"]]);
            }


            foreach (Cart::content() as $item) {
                Cart::setDiscount($item->rowId , 0);
            }

            foreach (Cart::content() as $item) {
                if ( in_array($item->id->id, $discount->courses->pluck('id')->toArray()) ){
                    Cart::setDiscount($item->rowId , $discount->percentage);
                }
            }
            return response()->json(['status' => 1, 'discountTotal' => Cart::discount(0, '.', ','), 'priceTotal' => Cart::priceTotal(0, '.', ',') , 'finishPrice' =>  number_format((Cart::priceTotal(0, '', '') - Cart::discount(0, '', '')),  0, '.' , ','), 'msg' => 'discount set successfully !']);
        }else{

            foreach (Cart::content() as $item) {
                Cart::setDiscount($item->rowId , 0);
            }

            foreach (Cart::content() as $item) {
                Cart::setDiscount($item->rowId , $discount->percentage);
            }
            return response()->json(['status' => 1, 'discountTotal' => Cart::discount(0, '.', ','), 'priceTotal' => Cart::priceTotal(0, '.', ',') , 'finishPrice' => number_format((Cart::priceTotal(0, '', '') - Cart::discount(0, '', '')),  0, '.' , ','), 'msg' => 'discount set successfully !']);
        }





    }
}

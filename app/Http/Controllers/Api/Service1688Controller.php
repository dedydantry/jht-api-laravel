<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Category1688;
use App\Models\Order;
use App\Models\Payment1688;
use App\Models\Payment1688Log;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductKeyword;
use App\Models\ProductNote;
use App\Models\Seller;
use App\Models\Variant;
use App\Services\Service1688;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Service1688Controller extends Controller{
    
    public function signature(Request $request)
    {

        $productId = $request->get('product_id');
        $path = $request->get('path') .'/'. config('caribarang.app_key_1688');
        $type = $request->get('type');
        $accessToken = env('ACCESS_TOKEN_1688'); //Service1688::token();

        if($type == 'relation'){
            $queryNoSignature   = [
                'productIdList'       => [(int)$productId],
                'access_token'      => $accessToken
            ];
        }else if($type == 'product_detail'){
            $queryNoSignature   = [
                'productId'           => strval($productId),
                'access_token'      => $accessToken
            ];
        }else if($type == 'search'){
            $queryNoSignature   = [
                'keyWord'           => $productId,
                'access_token'      => $accessToken
            ];
        }

        $codeSign = $this->generateSignature($queryNoSignature, $path);

        return response()->json([
            'signature' => $codeSign, 
            'access_token' => $accessToken, 
            'path' => config('caribarang.host_1688') . $path,
            'query' => $queryNoSignature
        ]);
    }

    protected function generateSignature($params, $path)
    {
        foreach ($params as $key => $val) {
            if (is_array($val) OR is_object($val)) {
                $aliParams[] = $key . json_encode($val);
                continue;
            }
            $aliParams[] = $key . $val;
        }

        sort($aliParams);
        $sign_str = join('', $aliParams);
        $sign_str = $path . $sign_str;
        $codeSign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str,  config('caribarang.app_secret_1688'), true)));
        return $codeSign;
    }

    public function store(Request $request)
    {
       try {
           DB::beginTransaction();
           $reqCategory =  $request->get('category');
           $paramsCategory = $reqCategory['group'];

           Category1688::upsert($paramsCategory, ['category_id_1688']);
   
           $seller = Seller::firstOrCreate(
               ['seller_id_1688' => $request->get('seller_id')],
               [
                   'name' => $request->get('seller_name'),
                   'address' => $request->get('seller_address')
               ]
            );
            
            $checkProduct = Product::select('id', 'uuid', 'product_id_1688')->where('product_id_1688', $request->get('product_id'))->first();
            $params = collect([
                'seller_id' => $seller->id,
                'category_id' =>  null,
                'subcategory_id' => null,
                'category_id_1688' => $reqCategory['id'] ?  $reqCategory['id'] : null,
                'product_id_1688' => $request->get('product_id'),
                'uuid' => $checkProduct ? $checkProduct->uuid : (string) Str::uuid(),
                'name' => $request->get('subject')['cn'],
                'name_en' => $request->get('subject')['en'],
                'price' => $request->get('prices')['fix'],
                'price_type' => $request->get('prices')['price_type'],
                'stock' => $request->get('stock'),
                'moq' => $request->get('moq'),
                'cover' => $request->get('images')[0],
                'weight' => $request->get('weight'),
                'height' => $request->get('height'),
                'length' => $request->get('length'),
                'variant_type' => $request->get('variant_type'),
                'last_updated' => $request->get('last_updated'),
            ]);
            
            if($checkProduct){
                $params = $params->except(['uuid', 'product_id_1688']);
                Product::where('product_id_1688', $checkProduct->product_id_1688)->update($params->toArray());
                $product = $checkProduct;
            }else{
                $product = Product::create($params->toArray());
            }

            Variant::where('product_id', $product->id)->delete();
            ProductImage::where('product_id', $product->id)->delete();
            ProductNote::where('product_id', $product->id)->delete();
            PriceRange::where('product_id', $product->id)->delete();
            
            $productId = $product->id;

            $variantCollection = collect($request->get('variants'));

            foreach($variantCollection as $key=>$value){
                $variant = new Variant();
                $variant->product_id = $productId;
                $variant->name = $value['name'];
                $variant->name_en = $value['name_en'];
                $variant->cover = $value['image'];
                $variant->save();

                if($product->variant_type == 'multiple_item'){
                    $variant->items()->createMany(collect($value['items'])->map(function($q)use($variant){
                            return [
                                'product_variant_id' => $variant->id,
                                'stock' => $q['stock'],
                                'price' => $q['price'],
                                'retail_price' => $q['retail_price'],
                                'name' => $q['name'],
                                'name_en' => $q['name_en'],
                                'sku_id' => $q['sku_id'],
                                'spec_id' => $q['spec_id'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                        })->toArray()
                    );
                }else{
                    $variant->items()->insert([
                        [
                            'product_variant_id' => $variant->id,
                            'stock' => $value['stock'],
                            'price' => $value['price'],
                            'retail_price' => $value['retail_price'],
                            'name' => $value['name'],
                            'name_en' => $value['name_en'],
                            'sku_id' => $value['sku_id'],
                            'spec_id' => $value['spec_id'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    ]);
                }
            }

            $product->note()->create(['note' => $request->get('description')]);
            $imgCollection = collect($request->get('images'));
            
            $imgParams = $imgCollection->map(function($q){
                return[
                    'url' => $q,
                    'file_type' => 'image'
                ];
            });
            if($request->get('video')) {
                $imgParams->prepend(['url' => $request->get('video'), 'file_type' => 'video']);
            }
            $product->images()->createMany($imgParams);

            if($product->price_type == 'RANGE'){
                $product->ranges()->createMany($request->get('prices')['ranges']);
            }

            DB::commit();
           return response()->json($product);
       } catch (\Exception $th) {
           DB::rollBack();
           throw $th;
           return response()->json(['error' => $th->getMessage()]);
       }

    }

    public function callbackMessage(Request $request)
    {
        $message = ['message' => $request->get('message')];
        $signature = $request->get('_aop_signature');
        $path = '';

        $aliParams = array();

        foreach ($message as $key => $val) {
            if (is_array($val) OR is_object($val)) {
                $aliParams[] = $key . json_encode($val);
                continue;
            }
            $aliParams[] = $key . $val;
        }

        sort($aliParams);
        $sign_str = join('', $aliParams);
        $sign_str = $path . $sign_str;
        $codeSign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str,  config('caribarang.app_key_1688'), true)));

        if($signature != $codeSign){
            return response()->json([
                'status' => false,
                'message' => "Signature invalid",
                'data' => $request->all()
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => "Message received",
        ]);
    }

    public function channel()
    {
        return response()->json([
            'status' => true,
            'data' => 'message received'
        ]);
    }

    public function previewBeforeOrder(Request $request)
    {
        try {
            $order = $request->get('order');
            $path               = 'param2/1/com.alibaba.trade/alibaba.createOrder.preview/' . config('caribarang.app_key_1688');
            $accessToken = Service1688::token();
            $query = [
                'addressParam'      => config('warehouseaddress.shijing.address'),
                'cargoParamList'    => $order,
                'flow'              => 'general',
                'access_token'      => $accessToken,
            ];

            $codeSign = $this->generateSignature($query, $path);
            $query['_aop_signature'] =  $codeSign;
            $url = config('caribarang.host_1688') . $path;
            $query['addressParam'] = json_encode($query['addressParam']);
            $query['cargoParamList'] = json_encode($query['cargoParamList']);

            $post = Http::asForm()->post($url, $query);
            $response = $post->object();
            if(isset($response->orderPreviewResuslt)){
                $response = $response->orderPreviewResuslt[0];
                $warehouseDeliveryFee = $response->sumCarriage ? ($response->sumCarriage / 100) : 0;
                return response()->json([
                    'warehouse_delivery_fee' => $warehouseDeliveryFee,
                    'order' => collect($response->cargoList)->map(function($q){
                         return[
                             'finalUnitPrice' => $q->finalUnitPrice,
                             'specId' => isset($q->specId) ? $q->specId : null,
                             'totalPrice' => $q->amount
                         ];
                    })
                ]);
            }

            if(isset($response->success) && $response->success === false){
                return response()->json([
                    'message' => $response->errorMsg
                ]);
            }
            return response()->json(['message' => null]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => $e->getMessage()
            ]);
        }

    }

    public function createOrder(Order $order)
    {
        try {
            if($order->order_id_1688) return response()->json(['status' => false, 'data' => 'Order has created']);
            $order->load(['cart.items', 'user.markingCode']);
            $productId = $order->cart->product_id_1688;
            $accessToken = Service1688::token();
    
            $items = $order->cart->items->map(function($q)use($productId){
                return[
                    'specId' => $q->spec_id,
                    'quantity' => $q->quantity,
                    'offerId' => $productId
                ];
            });
            
            $noteReplace =  $order->user->markingCode ? $order->user->markingCode->marking_code . ' - ' .$order->order_number :  $order->order_number;
            $path =  'param2/1/com.alibaba.trade/alibaba.trade.createCrossOrder/' . config('caribarang.app_key_1688');
            $query = [
                'addressParam' => config('warehouseaddress.shijing.address'),
                'cargoParamList' => $items,
                'tradeType' => 'fxassure',
                'flow' => 'general',
                'message' => sprintf(config('warehouseaddress.shijing.note'), $noteReplace),
                'access_token'      => $accessToken,
            ];

            $codeSign = $this->generateSignature($query, $path);
            $query['_aop_signature'] =  $codeSign;
            $query['addressParam'] = json_encode($query['addressParam']);
            $query['cargoParamList'] = json_encode($query['cargoParamList']);
    
            $url = config('caribarang.host_1688') . $path;
            $post = Http::asForm()->post($url, $query);
            $response = $post->object();
            if(isset($response->success) && $response->success === true){
                $order->order_id_1688 = $response->result->orderId;
                $order->save();

                $total =  $response->result->totalSuccessAmount / 100;
                $shipppingFee =  $response->result->postFee / 100;
                $productPrice = $total - $shipppingFee;
                $order->order1688()->create([
                    'product_price' => $productPrice,
                    'shipping_fee' => $shipppingFee,
                    'total' => $total
                ]);

                return response()->json([
                    'status' => true,
                    'data' => $order
                ]);
            }
    
            return response()->json(['status' => false, 'data' => $response]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => $e->getMessage()
            ]);
        }
    }

    public function viewOrder(Order $order)
    {
        try {
            if(!$order->order_id_1688) return response()->json(['status' => false, 'data' => 'Invalid order_id_1688']);
            $accessToken = Service1688::token();
            $path =  'param2/1/com.alibaba.trade/alibaba.trade.get.buyerView/' . config('caribarang.app_key_1688');
            $orderId = (int) $order->order_id_1688;
            $query = [
                'webSite'           => '1688',
                'orderId'           => $orderId,
                'access_token'      => $accessToken,
            ];
    
            $codeSign = $this->generateSignature($query, $path);
            $query['_aop_signature'] =  $codeSign;
    
            $url = config('caribarang.host_1688') . $path;
            $post = Http::asForm()->post($url, $query);
            $response = $post->object();
        
            return response()->json(['status'=>true, 'data' => $response]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => $e->getMessage()
            ]);
        }
       
    }

    public function cancelOrder(Order $order)
    {
        try {
            if(!$order->order_id_1688) return response()->json(['status' => false, 'data' => 'Invalid order_id_1688']);
            $accessToken = Service1688::token();
            $path =  'param2/1/com.alibaba.trade/alibaba.trade.cancel/' . config('caribarang.app_key_1688');
            $orderId = (int) $order->order_id_1688;
            $query = [
                'webSite' => '1688',
                'tradeID' => $orderId,
                'cancelReason' => 'sellerGoodsLack:卖家库存不足;',
                'access_token' => $accessToken,
            ];

            $codeSign = $this->generateSignature($query, $path);
            $query['_aop_signature'] =  $codeSign;
    
            $url = config('caribarang.host_1688') . $path;
            $post = Http::asForm()->post($url, $query);
            $response = $post->object();

            if(isset($response->success) && $response->success === true) {
                $order->order_id_1688 = null;
                $order->save();
                $order->order1688()->delete();
                return response()->json([
                    'status' => true,
                    'data' => 'Success to cancel order'
                ]);
            }
            

            return response()->json([
                'status' => false,
                'data' => $response->errorMessage
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => $e->getMessage()
            ]);
        }
    }

    public function payment(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'order_list' => 'array|required',
            'email' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'data' => $validator->errors(),
            ]);
        }

       try {
           $orderList = $request->get('order_list');
           $accessToken = Service1688::token();
            
        //    $path =  'param2/1/com.alibaba.trade/alibaba.crossBorderPay.url.get/' . config('caribarang.app_key_1688');
           $path =  'param2/1/com.alibaba.trade/alibaba.alipay.url.get/' . config('caribarang.app_key_1688');
           $query = [
               'orderIdList' => $orderList,
               'access_token' => $accessToken
           ];
           $codeSign = $this->generateSignature($query, $path);
           $query['_aop_signature'] =  $codeSign;
           $query['orderIdList'] = json_encode($query['orderIdList']);
           $url = config('caribarang.host_1688') . $path;
           $post = Http::asForm()->post($url, $query);
           $admin = Admin::where('email', $request->get('email'))->first();
           $response = $post->object();
           if(isset($response->success) && ($response->success == 'true' || $response->success === true) ) {
               $payment = Payment1688::create(['link' => $response->payUrl]);
               Order::whereIn('order_id_1688', $orderList)->update(['payment_1688_id' => $payment->id, 'bulk_payment_at' => now()]);
               Payment1688Log::create([
                   'admin_id' => $admin ? $admin->id : 1,
                   'action' => 'Generate payment link',
               ]);
               return response()->json(['status'=>true, 'data' => $payment]);
           }
           return response()->json(['status'=>false, 'data' => $response]);
       } catch (\Exception $e) {
           return response()->json([
                'status' => false,
                'data' => $e->getMessage()
            ]);
       }
    }

    public function search(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'keyword' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'data' => $validator->errors(),
            ]);
        }
        try {
            $keyword = $request->get('keyword');
            $accessToken = env('ACCESS_TOKEN_1688'); //Service1688::token();
            // alibaba.cross.similar.offer.search
            $path = 'param2/1/com.alibaba.product/alibaba.product.suggest.crossBorder/' . config('caribarang.app_key_1688');
            $query = [
                'keyWord' => $keyword,
                'access_token' => $accessToken
            ];
            $codeSign = $this->generateSignature($query, $path);
            $query['_aop_signature'] =  $codeSign;

            $url = config('caribarang.host_1688') . $path;
            $post = Http::asForm()->post($url, $query);
            $response = $post->object();
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => $e->getMessage()
            ]);
        }
    }

    public function category()
    {
        try {
            $accessToken = Service1688::token();
            $path = 'param2/1/com.alibaba.product/alibaba.category.get/' . config('caribarang.app_key_1688');
            $query = [
                'categoryID' => 0,
                'access_token' => $accessToken
            ];
            $codeSign = $this->generateSignature($query, $path);
            $query['_aop_signature'] =  $codeSign;

            $url = config('caribarang.host_1688') . $path;
            $post = Http::asForm()->post($url, $query);
            $response = $post->object();
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => $e->getMessage()
            ]);
        }
    }

    public function storeSuggest(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $productRequest = collect( $request->product);
            $keyword = $request->keyword;
            $productIdArr = $productRequest->pluck('product_id_1688')->toArray();

            $check = Product::select('id' ,'product_id_1688')->whereIn('product_id_1688', $productIdArr)->get();
            if(count($check)){
                $productRequest = $productRequest->whereNotIn('product_id_1688', $check->pluck('product_id_1688'));
            }
            Product::insert($productRequest->toArray());
            $productOnlyId =  $productRequest->pluck('product_id_1688')->toArray();
            $productInsert = Product::select('id')->whereIn('product_id_1688', $productOnlyId)->get();
            $keywordParams = $productInsert->map(function($q)use($keyword){
                return[
                    'product_id' => $q->id,
                    'keyword' => $keyword
                ];
            });
            ProductKeyword::insert($keywordParams->toArray());
            ProductKeyword::whereIn('product_id', $productInsert->pluck('id')->toArray())->searchable();
            DB::commit();
            return response()->json($productRequest);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json(['error'=>$th->getMessage()]);

        }
    }
}
<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\ProductImage;
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

        $accessToken = Service1688::token();

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
        }

        $codeSign = $this->generateSignature($queryNoSignature, $path);

        return response()->json([
            'signature' => $codeSign, 
            'access_token' => Service1688::token(), 
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
           $paramsCategory = $request->get('category')['group'];

           Category::upsert($paramsCategory, ['name']);
           $category = Category::where('name', $request->get('category')['name_cn'])->first();
   
           $seller = Seller::firstOrCreate(
               ['seller_id_1688' => $request->get('seller_id')],
               [
                   'name' => $request->get('seller_name'),
                   'address' => $request->get('seller_address')
               ]
            );
   
           $product = Product::updateOrCreate(
               ['product_id_1688' => $request->get('product_id')],
               [
                    'seller_id' => $seller->id,
                    'category_id' => $category ? $category->id : null,
                    'subcategory_id' => null,
                    'uuid' => (string) Str::uuid(),
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
                ]
            );

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
                'addressParam'      => config('warehouseaddress.greenline.address'),
                'cargoParamList'    => $order,
                'flow'              => 'general',
                'access_token'      => $accessToken,
            ];

            $codeSign = $this->generateSignature($query, $path);
            $query['_aop_signature'] =  $codeSign;
            $url = config('caribarang.host_1688') . $path;
            $post = Http::asForm()->post($url, $query);
            $response = $post->object();
            return response()->json($response);
        } catch (\Exception $e) {
            throw $e;
            return response()->json([
                'status' => false,
                'data' => $e->getMessage()
            ]);
        }

    }
}
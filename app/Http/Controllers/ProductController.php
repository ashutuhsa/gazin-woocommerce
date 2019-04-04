<?php

namespace App\Http\Controllers;

use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function save_categories()
    {
        echo "this was done..";
        exit;
        $ch = curl_init('http://macgyver.gazinatacado.com.br/v1/parceiroonline/produtos/descricao');
//        $ch = curl_init('http://macgyver.gazinatacado.com.br/v1/parceiroonline/produtos/descricao?idproduto=11114');
        // TRUE para NÃO incluir o cabeçalho na saída.
        $token = env('TOKEN_GAZIN');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$token}",
        "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $products = curl_exec($ch);
        $resposta = curl_getinfo($ch , CURLINFO_HTTP_CODE);
        curl_close($ch);

        $separatorCategory = '##';
        $json = json_decode($products, true);

        $tmpParent = [];
        $tmpSub = [];

        foreach ($json as $j) {
//            print_r($j['dados']);
            foreach($j['dados'] as $detail) {
                // add parent category
                $currentCategories = $detail['categoria'];
                $category = strstr($currentCategories, $separatorCategory)
                    ? explode($separatorCategory, $currentCategories)
                    : $currentCategories;

                $woocommerce = new WooCommerceController;
                if (is_array($category)) {
                    if (!in_array($category[0], $tmpParent)) {
                        Log::info("Category added: {$category[0]}");
                        $parent = $woocommerce->save_category($category[0]);
                        $tmpParent[] = $category[0];
                    }

                    if (!in_array($category[1], $tmpSub)) {
                        Log::info("Sub Category added: {$category[1]}");
                        $subcategory
                            = $woocommerce->save_category($category[1],
                            $parent->id);
                        $tmpSub[] = $category[1];
                    }
                } else {
                    if (!in_array($category, $tmpParent) && !empty($category)) {
                        print_r($category);
                        Log::info("Category added: {$category}");
                        $parent = $woocommerce->save_category($category);
                        $tmpParent[] = $category;
                    }
                }
            }
        }
        Log::info("Syncronized.");
        echo "Syncronized.";
    }

    public function save_products()
    {
//        $ch = curl_init('http://macgyver.gazinatacado.com.br/v1/parceiroonline/produtos/descricao');
        $ch = curl_init('http://macgyver.gazinatacado.com.br/v1/parceiroonline/produtos/descricao?idproduto=11114');
        // TRUE para NÃO incluir o cabeçalho na saída.
        $token = env('TOKEN_GAZIN');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$token}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $products = curl_exec($ch);
        $resposta = curl_getinfo($ch , CURLINFO_HTTP_CODE);
        curl_close($ch);

        $separatorCategory = '##';
        $json = json_decode($products, true);

        $woocommerce = new WooCommerceController;

        $currentProduct = 0;
        foreach ($json as $j) {
//            print_r($j); // all products
//            print_r($j['dados']); // specific product
            $firstProduct = true;
            $images = [];
            $variantsOpcao1 = [];
            $variantsOption2 = [];

            // first loop because of the variants
            foreach($j['dados'] as $detail) {
                $variantsOption1[] = $detail['gradex'];
                $variantsOption2[] = $detail['gradey'];
            }

            foreach($j['dados'] as $detail) {
                // add parent category
                $currentCategories = $detail['categoria'];
                $category = strstr($currentCategories, $separatorCategory)
                    ? explode($separatorCategory, $currentCategories)
                    : $currentCategories;

                // tracking the category
                if (is_array($category)) {
                    $parentCategory = $category[0];
                } else {
                    $parentCategory = $category;
                }

                Log::info("Parent category: {$parentCategory}");
                // Find the category
                $parentCat = Term::where('name', 'like', '%' . $parentCategory . '%')->first();
                $subCat = is_array($category)
                    ? Term::where('name', 'like', '%' . $category[1] . '%')->first()
                    : null;

                $categoriesArray = [];
                $categoriesArray[] = ['id' => $parentCat->term_id];

                if (!empty($subCat)) {
                    $categoriesArray[] = ['id' => $subCat->term_id];
                }

                $currentPictures = explode(',', $detail['fotos']);
                foreach ($currentPictures as $pic) {
                    if (!empty($pic))
                        $images[]['src'] = $pic;
                }

                // main product
                if ($firstProduct) {
                    $data = [
                        'name' => $detail['descricao'],
                        'type' => 'variable',
                        'regular_price' => '999.00',
                        'description' => $detail['descricao_tecnica'],
                        'short_description' => $detail['descricao_tecnica'],
                        "weight" => "{$detail['peso']}",
                        "manage_stock" => true,
                        "stock_quantity" => $detail['quantidadevolume'],
                        "dimensions" => [
                            "length" => $detail['comprimento'],
                            "width" => $detail['largura'],
                            "height" => $detail['altura']
                        ],
                        "attributes" => [
                            [
                                "id" => 1,
                                "name" => "Opção 1",
                                'variation' => true,
                                'visible' => true,
                                "options" => $variantsOption1
                            ],
                            [
                                "id" => 2,
                                "name" => "Opção 2",
                                'variation' => true,
                                'visible' => true,
                                "options" => $variantsOption2
                            ],
                        ],
                        "parent_id" => 0,
                        "shipping_required" => true,
                        "sku" => $detail['ean'],
                        'categories' => $categoriesArray,
                        'images' => $images
                    ];
                    $product = $woocommerce->save_product($data);
                    $currentProduct = $product;
                } else {
                // variants
                    $data = [
                        'regular_price' => '999.00',
                        "shipping_required" => true,
                        "sku" => $detail['ean'],
                        "attributes" => [
                            [
                                "id" => 1,
                                "option" => "{$detail['gradex']}"
                            ],
                            [
                                "id" => 2,
                                "option" => "{$detail['gradey']}"
                            ],
                        ],
                    ];
                    Log::info("@@@@@@@@@ ---->>>>>> Variant");
                    Log::info($data);
                    $productVariant = $woocommerce->save_productVariant($currentProduct->id, $data);
                }
                $firstProduct = false;
            }
        }
        Log::info("Syncronized.");
        echo "Syncronized.";
    }
}

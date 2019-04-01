<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function save_categories()
    {
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
                        $parent = $woocommerce->save_category($category[0]);
                        $tmpParent[] = $category[0];
                    }

                    if (!in_array($category[1], $tmpSub)) {
                        $subcategory
                            = $woocommerce->save_category($category[1],
                            $parent->id);
                        $tmpSub[] = $category[1];
                    }
                } else {
                    if (!in_array($category, $tmpParent)) {
                        $parent = $woocommerce->save_category($category);
                        $tmpParent[] = $category;
                    }
                }
            }
        }
        echo "Syncronized.";
    }
}

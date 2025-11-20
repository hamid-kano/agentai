<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI;

class AIAgentController extends Controller
{
    public function handle(Request $request)
    {
        $client = OpenAI::client(env('OPENAI_API_KEY'));

        $userMessage = $request->input('message');

        // إرسال الرسالة إلى OpenAI
        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => file_get_contents(base_path('ai/system_prompt.txt'))],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'functions' => [
                [
                    "name" => "searchRestaurants",
                    "description" => "البحث عن مطاعم شغالة حسب الكلمات المفتاحية",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "keyword" => ["type" => "string"],
                            "only_open" => ["type" => "boolean"]
                        ],
                        "required" => ["keyword"]
                    ]
                ],
                [
                    "name" => "addToCart",
                    "description" => "إضافة عنصر للسلة",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "item_id" => ["type" => "integer"],
                            "quantity" => ["type" => "integer"]
                        ],
                        "required" => ["item_id"]
                    ]
                ],
            ]
        ]);

        // هل الوكيل طلب تنفيذ Function ؟
        $message = $response['choices'][0]['message'];

        if (isset($message['function_call'])) {
            $fn = $message['function_call']['name'];
            $args = json_decode($message['function_call']['arguments'], true);

            switch ($fn) {
                case "searchRestaurants":
                    return $this->searchRestaurants($args);
                case "addToCart":
                    return $this->addToCart($args);
            }
        }

        // مجرد رد نصي
        return response()->json([
            "reply" => $message['content']
        ]);
    }

    private function searchRestaurants($args)
    {
        // مثال Database
        $restaurants = \DB::table("restaurants")
            ->where("name", "LIKE", "%".$args["keyword"]."%")
            ->when($args["only_open"] ?? false, function ($q) {
                $q->where("is_open", 1);
            })
            ->get();

        return response()->json([
            "function" => "searchRestaurants",
            "data" => $restaurants
        ]);
    }

    private function addToCart($args)
    {
        // مثال
        return response()->json([
            "function" => "addToCart",
            "added" => $args
        ]);
    }
}

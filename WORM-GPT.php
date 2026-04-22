<?php
// إعداد ترويسة الصفحة لترجع JSON
header('Content-Type: application/json; charset=utf-8');

// دالة الترجمة وكشف اللغة باستخدام جوجل
function translate_system($text, $target_lang, $source_lang = 'auto') {
    $query = urlencode($text);
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=$source_lang&tl=$target_lang&dt=t&q=$query";
    
    $response = file_get_contents($url);
    if ($response) {
        $result = json_decode($response, true);
        
        // إذا كنا نريد كشف اللغة فقط
        if ($target_lang == 'detect') {
            return $result[2]; // يعيد كود اللغة مثل 'ar' أو 'en'
        }

        // تجميع النص المترجم
        $translated = "";
        foreach ($result[0] as $sentence) {
            $translated .= $sentence[0];
        }
        return $translated;
    }
    return $text;
}

// استلام النص من المستخدم (عبر GET أو POST)
$user_text = $_REQUEST['text'] ?? '';

if (empty($user_text)) {
    echo json_encode(["error" => "Please provide 'text' parameter"]);
    exit;
}

// 1. كشف لغة المستخدم
$detected_lang = translate_system($user_text, 'detect');

// 2. إرسال الطلب لـ WormGPT API
$worm_url = "https://sii3.top/api/error/wormgpt.php";
$api_key = "DarkAI-WormGPT-9A775B691774FAD5F4E66700";

$ch = curl_init($worm_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'key' => $api_key,
    'text' => $user_text
]);

$worm_response = curl_exec($ch);
curl_close($ch);

$worm_data = json_decode($worm_response, true);
$english_reply = $worm_data['response'] ?? 'No response from API';

// 3. ترجمة الرد من الإنجليزية إلى لغة المستخدم إذا لم تكن إنجليزية
$final_reply = $english_reply;
if ($detected_lang != 'en') {
    $final_reply = translate_system($english_reply, $detected_lang, 'en');
}

// 4. إخراج النتيجة بالنمط المطلوب
$output = [
    "reply" => $final_reply,
    "dev"   => "https://t.me/i_mmx",
    "ch"    => "https://t.me/ULTRA_CODE_1"
];

echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

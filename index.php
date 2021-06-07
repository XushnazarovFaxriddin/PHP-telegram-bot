<?php

define('BOT_TOKEN', '1733536815:AAF_2onvLSLSzphvnYRU1WqtD0MUoUTaYDk');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');


function apiRequestWebhook($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  $payload = json_encode($parameters);
  header('Content-Type: application/json');
  header('Content-Length: '.strlen($payload));
  echo $payload;

  return true;
}

function exec_curl_request($handle) {
  $response = curl_exec($handle);

  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    error_log("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {
    // do not wat to DDOS server if something goes wrong
    sleep(10);
    return false;
  } else if ($http_code != 200) {
    $response = json_decode($response, true);
    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;
  } else {
    $response = json_decode($response, true);
    if (isset($response['description'])) {
      error_log("Request was successful: {$response['description']}\n");
    }
    $response = $response['result'];
  }

  return $response;
}

function apiRequest($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  foreach ($parameters as $key => &$val) {
    // encoding to JSON array parameters, for example reply_markup
    if (!is_numeric($val) && !is_string($val)) {
      $val = json_encode($val);
    }
  }
  $url = API_URL.$method.'?'.http_build_query($parameters);

  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);

  return exec_curl_request($handle);
}

function apiRequestJson($method, $parameters) {
  if (!is_string($method)) {
    error_log("Method name must be a string\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Parameters must be an array\n");
    return false;
  }

  $parameters["method"] = $method;

  $handle = curl_init(API_URL);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);
  curl_setopt($handle, CURLOPT_POST, true);
  curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
  curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

  return exec_curl_request($handle);
}


function is($text) {
    return preg_match('/[А-Яа-яЁё]/u', $text);
}


function processMessage($message) {
  // process incoming message
  $message_id = $message['message_id'];
  $chat_id = $message['chat']['id'];
  if (isset($message['text'])) {
    // incoming text message
    $text = $message['text'];

    if (strpos($text, "/start") === 0) {
      apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => "🖐 Assalomu aleykum😊.
@Text_Replence_bot'imizga xush kelibsiz.
Ushbu 🤖bot orqali siz  💬matnlaringizni ⚡️tez va 👌mukamal ravishda Lotinchadan Krillgacha yoki aksincha Krillchadan Lotinchaga oʻgirishingiz mumkin!

❕Botni ishlatish juda osson😉. Buning uchun siz shunchaki /start bosing va 📑matn yuboring boʻldi✅ endi 🤖bot siz yuborgan 💬xabarni suniy 🧠ong yordamida oʻzgartirib beradi💥.
💨Tez kunda botga 🆕yangi funcksiyalar va botning 📱mobil, 💻desktop dasturlarini hamda 🌐web saytini joylaymiz😱.

Qani botga matn yuborib koʻringchi ...",'reply_markup' => array(
        'keyboard' => array(array('Botni ishlatish', 'Admin')),
        'one_time_keyboard' => true,
        'resize_keyboard' => true)));
    } else if ($text === "Botni ishlatish" || $text === "Admin") {
      apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => "Bu boʻlimda texnik ishlar olib borilmoqda yaqin vaqtlarda ishga tushiriladi.
Botni ishlatish uchun matn yuboring!"));
    } else if (strpos($text, "/stop") === 0) {
      apiRequest("sendMessage", array('chat_id'=> $chat_id,
      "text"=> 'bot toxtadi!'));
    } else {
      $txt=is($text);
      $trs=new TransliteratorLtCr();
      if ($txt==1){
       $kirlot=$trs->toLatin($text);
        apiRequestWebhook("sendMessage",array('chat_id'=>$chat_id,
        'reply_to_message'=>$message_id,
        "text"=>$kirlot));
      }
      else{
      $lotkir=$trs->toCyrill($text);
      apiRequestWebhook("sendMessage",array('chat_id'=>$chat_id,
      'reply_to_message'=>$message_id,
      "text"=> $lotkir));
      }
    //apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => "$txt"));
    }
  } else {
    apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Men faqat matnli xabarlarga javob qaytaraman!'));
  }

  
}


define('WEBHOOK_URL', 'https://my-site.example.com/secret-path-for-webhooks/');

if (php_sapi_name() == 'cli') {
  // if run from console, set or delete webhook
  apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
  exit;
}


$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
  // receive wrong update, must not happen
  exit;
}

if (isset($update["message"])) {
  processMessage($update["message"]);
}



class TransliteratorLtCr
{
    private static $rl_words = ["Sheʼr","abzas","aksent","avianoses","batsilla","biomitsin","botsman","bronenoses","brutselloz","cherepitsa","dotsent","fransuz","gaubitsa","gers","glitserin","gorchitsa","gusenitsa","inersiya","inssenirovka","kalsiy","kansler","koeffitsient","konferens-zal","konsepsiya","konsern","konsert","konslager","kultivatsiya","kvars","litsey","lotsman","marganes","minonoses","munitsipalitet","ofitsiant","penitsillin","platsdarm","platskarta","politsmeyster","jinsiyat","pensiya","ranes","ritsar","sellofan","selluloid","selluloza","selsiy","sement","sentner","sentrifuga","senz","senzor","senzura","sex","shnitsel","shveysar","siferblat","silindr","silindrik","singa","sink","sirk","sirkulyar","sisterna","sitrus","sotsiologik","sotsiologiya","ssenariy","kultivator","kultivatsiya","kuryer","lager","losos","mebel","medal","medalyon","menshevik","menshevizm","migren","mikrofilm","mil","model","neft","nikel","nippel","nol","noyabr","oktabr","palto","panel","parallel","parol","parter","patrul","pavilyon","pedal","plastir","pochtalyon","porshen","portfel","povest","predoxranitel","premyera","pristan","puls","pyesa","rels","relyef","rentabel","rezba","ritsar","rol","royal","rul","seld","selsiy","sentabr","shinel","shnitsel","shpatel","shpilka","shpindel","shtapel","shtempel","shtepsel","spektakl","spiral","sterjen","sudya","sulfat","tabel","tekstil","tokar","tulen","tunnel","umivalnik","valeryanka","vals","veksel","velvet","ventil","vermishel","vimpel","violonchel","volfram","volt","volta","voltmetr","vulgar","yakor","yanvar","yuriskonsult","ansambl","artel","artikl","aryergard","asfalt","atelye","avtomobil","balzam","banderol","batalyon","bilyard","binokl","bolshevik","budilnik","bulvar","dalton","dekabr","delfin","devalvatsiya","dirijabl","dizel","dizel-motor","duel","dvigatel","emulsiya","eskadrilya","fakultativ","fakultet","falsifikator","falsifikatsiya","feldmarshal","feldsher","festival","fevral","filtr","folklor","fotoalbom","fotoatelye","gantel","gastrol","gilza","gospital","gotovalniy","grifel","impuls","insult","intervyu","inyeksiya","italyan","iyul","iyun","kabel","kalendar","kanifol","karamel","kartel","karusel","katapulta","kinofestival","kinofilm","kisel","kitel","kobalt","kompanyon","konferansye","obyekt","razyezd","subyekt","syezd","syomka","Abzas","Aksent","Avianoses","Batsilla","Biomitsin","Botsman","Bronenoses","Brutselloz","Cherepitsa","Dotsent","Fransuz","Gaubitsa","Gers","Glitserin","Gorchitsa","Gusenitsa","Inersiya","Inssenirovka","Kalsiy","Kansler","Koeffitsient","Konferens-Zal","Konsepsiya","Konsern","Konsert","Konslager","Kultivatsiya","Kvars","Litsey","Lotsman","Marganes","Minonoses","Munitsipalitet","Ofitsiant","Penitsillin","Platsdarm","Platskarta","Politsmeyster","Jinsiyat","Pensiya","Ranes","Ritsar","Sellofan","Selluloid","Selluloza","Selsiy","Sement","Sentner","Sentrifuga","Senz","Senzor","Senzura","Sex","Shnitsel","Shveysar","Siferblat","Silindr","Silindrik","Singa","Sink","Sirk","Sirkulyar","Sisterna","Sitrus","Sotsiologik","Sotsiologiya","Ssenariy","Kultivator","Kultivatsiya","Kuryer","Lager","Losos","Mebel","Medal","Medalyon","Menshevik","Menshevizm","Migren","Mikrofilm"," Mil ","Model","Neft","Nikel","Nippel","Nol","Noyabr","Oktabr","Palto","Panel","Parallel","Parol","Parter","Patrul","Pavilyon","Pedal","Plastir","Pochtalyon","Porshen","Portfel","Povest","Predoxranitel","Premyera","Pristan","Puls","Pyesa","Rels","Relyef","Rentabel","Rezba","Ritsar","Rol","Royal","Rul","Seld","Selsiy","Sentabr","Shinel","Shnitsel","Shpatel","Shpilka","Shpindel","Shtapel","Shtempel","Shtepsel","Spektakl","Spiral","Sterjen","Sudya","Sulfat","Tabel","Tekstil","Tokar","Tulen","Tunnel","Umivalnik","Valeryanka","Vals","Veksel","Velvet","Ventil","Vermishel","Vimpel","Violonchel","Volfram","Volt","Volta","Voltmetr","Vulgar","Yakor","Yanvar","Yuriskonsult","Ansambl","Artel","Artikl","Aryergard","Asfalt","Atelye","Avtomobil","Balzam","Banderol","Batalyon","Bilyard","Binokl","Bolshevik","Budilnik","Bulvar","Dalton","Dekabr","Delfin","Devalvatsiya","Dirijabl","Dizel","Dizel-Motor","Duel","Dvigatel","Emulsiya","Eskadrilya","Fakultativ","Fakultet","Falsifikator","Falsifikatsiya","Feldmarshal","Feldsher","Festival","Fevral","Filtr","Folklor","Fotoalbom","Fotoatelye","Gantel","Gastrol","Gilza","Gospital","Gotovalniy","Grifel","Impuls","Insult","Intervyu","Inyeksiya","Italyan","Iyul","Iyun","Kabel","Kalendar","Kanifol","Karamel","Kartel","Karusel","Katapulta","Kinofestival","Kinofilm","Kisel","Kitel","Kobalt","Kompanyon","Konferansye","Obyekt","Razyezd","Subyekt","Syezd","Syomka","ABZAS","AKSENT","AVIANOSES","BATSILLA","BIOMITSIN","BOTSMAN","BRONENOSES","BRUTSELLOZ","CHEREPITSA","DOTSENT","FRANSUZ","GAUBITSA","GERS","GLITSERIN","GORCHITSA","GUSENITSA","INERSIYA","INSSENIROVKA","KALSIY","KANSLER","KOEFFITSIENT","KONFERENS-ZAL","KONSEPSIYA","KONSERN","KONSERT","KONSLAGER","KULTIVATSIYA","KVARS","LITSEY","LOTSMAN","MARGANES","MINONOSES","MUNITSIPALITET","OFITSIANT","PENITSILLIN","PLATSDARM","PLATSKARTA","POLITSMEYSTER","JINSIYAT","PENSIYA","RANES","RITSAR","SELLOFAN","SELLULOID","SELLULOZA","SELSIY","SEMENT","SENTNER","SENTRIFUGA","SENZ","SENZOR","SENZURA","SEX","SHNITSEL","SHVEYSAR","SIFERBLAT","SILINDR","SILINDRIK","SINGA","SINK","SIRK","SIRKULYAR","SISTERNA","SITRUS","SOTSIOLOGIK","SOTSIOLOGIYA","SSENARIY","KULTIVATOR","KULTIVATSIYA","KURYER","LAGER","LOSOS","MEBEL","MEDAL","MEDALYON","MENSHEVIK","MENSHEVIZM","MIGREN","MIKROFILM"," MIL ","MODEL","NEFT","NIKEL","NIPPEL","NOL","NOYABR","OKTABR","PALTO","PANEL","PARALLEL","PAROL","PARTER","PATRUL","PAVILYON","PEDAL","PLASTIR","POCHTALYON","PORSHEN","PORTFEL","POVEST","PREDOXRANITEL","PREMYERA","PRISTAN","PULS","PYESA","RELS","RELYEF","RENTABEL","REZBA","RITSAR","ROL","ROYAL","RUL","SELD","SELSIY","SENTABR","SHINEL","SHNITSEL","SHPATEL","SHPILKA","SHPINDEL","SHTAPEL","SHTEMPEL","SHTEPSEL","SPEKTAKL","SPIRAL","STERJEN","SUDYA","SULFAT","TABEL","TEKSTIL","TOKAR","TULEN","TUNNEL","UMIVALNIK","VALERYANKA","VALS","VEKSEL","VELVET","VENTIL","VERMISHEL","VIMPEL","VIOLONCHEL","VOLFRAM","VOLT","VOLTA","VOLTMETR","VULGAR","YAKOR","YANVAR","YURISKONSULT","ANSAMBL","ARTEL","ARTIKL","ARYERGARD","ASFALT","ATELYE","AVTOMOBIL","BALZAM","BANDEROL","BATALYON","BILYARD","BINOKL","BOLSHEVIK","BUDILNIK","BULVAR","DALTON","DEKABR","DELFIN","DEVALVATSIYA","DIRIJABL","DIZEL","DIZEL-MOTOR","DUEL","DVIGATEL","EMULSIYA","ESKADRILYA","FAKULTATIV","FAKULTET","FALSIFIKATOR","FALSIFIKATSIYA","FELDMARSHAL","FELDSHER","FESTIVAL","FEVRAL","FILTR","FOLKLOR","FOTOALBOM","FOTOATELYE","GANTEL","GASTROL","GILZA","GOSPITAL","GOTOVALNIY","GRIFEL","IMPULS","INSULT","INTERVYU","INYEKSIYA","ITALYAN","IYUL","IYUN","KABEL","KALENDAR","KANIFOL","KARAMEL","KARTEL","KARUSEL","KATAPULTA","KINOFESTIVAL","KINOFILM","KISEL","KITEL","KOBALT","KOMPANYON","KONFERANSYE","OBYEKT","RAZYEZD","SUBYEKT","SYEZD","SYOMKA"];
    private static $rc_words=["Шеʼр","абзац","акцент","авианосец","бацилла","биомицин","боцман","броненосец","бруцеллоз","черепица","доцент","француз","гаубица","герц","глицерин","горчица","гусеница","инерция","инсценировка","кальций","канцлер","коэффициент","конференц-зал","консепция","концерн","концерт","концлагер","культивация","кварц","лицей","лоцман","марганец","миноносец","муниципалитет","официант","пенициллин","плацдарм","плацкарта","полицмейстер","жинсият","пенсия","ранец","рицарь","целлофан","целлюлоид","целлюлоза","цельсий","цемент","центнер","центрифуга","ценз","цензор","цензура","цех","шницель","швейцар","циферблат","цилиндр","цилиндрик","цинга","цинк","цирк","циркуляр","цистерна","цитрус","социологик","социология","сценарий","культиватор","культивация","курьер","лагерь","лосось","мебель","медаль","медальон","меньшевик","меньшевизм","мигрень","микрофильм","миль","модель","нефть","никель","ниппель","ноль","ноябрь","октябрь","пальто","панель","параллель","пароль","партьер","патруль","павильон","педаль","пластирь","почтальон","поршень","портфель","повесть","предохранитель","премьера","пристань","пульс","пьеса","рельс","рельеф","рентабель","резьба","рицарь","роль","рояль","руль","сельд","цельсий","сентябрь","шинель","шницель","шпатель","шпилька","шпиндель","штапель","штемпель","штепсель","спектакль","спираль","стержень","судья","сульфат","табель","текстиль","токарь","тюлень","туннель","умивальник","валерьянка","вальс","вексель","вельвет","вентиль","вермишель","вимпель","виолончель","вольфрам","вольт","вольта","вольтметр","вульгар","якорь","январь","юрисконсульт","ансамбль","артель","артикль","арьергард","асфальт","ателье","автомобиль","бальзам","бандероль","батальон","бильярд","бинокль","большевик","будильник","бульвар","дальтон","декабрь","дельфин","девальвация","дирижабль","дизель","дизель-мотор","дуэль","двигатель","эмульсия","эскадрилья","факультатив","факультет","фальсификатор","фальсификация","фельдмаршал","фельдшер","фестиваль","февраль","фильтр","фольклор","фотоальбом","фотоателье","гантель","гастроль","гильза","госпиталь","готовальний","грифель","импульс","инсульт","интервью","иньекция","итальян","июль","июнь","кабель","календарь","канифоль","карамель","картель","карусель","катапульта","кинофестиваль","кинофильм","кисель","китель","кобальт","компаньон","конферансье","объект","разъезд","субъект","съезд","съёмка","Абзац","Акцент","Авианосец","Бацилла","Биомицин","Боцман","Броненосец","Бруцеллоз","Черепица","Доцент","Француз","Гаубица","Герц","Глицерин","Горчица","Гусеница","Инерция","Инсценировка","Кальций","Канцлер","Коэффициент","Конференц-Зал","Консепция","Концерн","Концерт","Концлагер","Культивация","Кварц","Лицей","Лоцман","Марганец","Миноносец","Муниципалитет","Официант","Пенициллин","Плацдарм","Плацкарта","Полицмейстер","Жинсият","Пенсия","Ранец","Рицарь","Целлофан","Целлюлоид","Целлюлоза","Цельсий","Цемент","Центнер","Центрифуга","Ценз","Цензор","Цензура","Цех","Шницель","Швейцар","Циферблат","Цилиндр","Цилиндрик","Цинга","Цинк","Цирк","Циркуляр","Цистерна","Цитрус","Социологик","Социология","Сценарий","Культиватор","Культивация","Курьер","Лагерь","Лосось","Мебель","Медаль","Медальон","Меньшевик","Меньшевизм","Мигрень","Микрофильм"," Миль ","Модель","Нефть","Никель","Ниппель","Ноль","Ноябрь","Октябрь","Пальто","Панель","Параллель","Пароль","Партьер","Патруль","Павильон","Педаль","Пластирь","Почтальон","Поршень","Портфель","Повесть","Предохранитель","Премьера","Пристань","Пульс","Пьеса","Рельс","Рельеф","Рентабель","Резьба","Рицарь","Роль","Рояль","Руль","Сельд","Цельсий","Сентябрь","Шинель","Шницель","Шпатель","Шпилька","Шпиндель","Штапель","Штемпель","Штепсель","Спектакль","Спираль","Стержень","Судья","Сульфат","Табель","Текстиль","Токарь","Тюлень","Туннель","Умивальник","Валерьянка","Вальс","Вексель","Вельвет","Вентиль","Вермишель","Вимпель","Виолончель","Вольфрам","Вольт","Вольта","Вольтметр","Вульгар","Якорь","Январь","Юрисконсульт","Ансамбль","Артель","Артикль","Арьергард","Асфальт","Ателье","Автомобиль","Бальзам","Бандероль","Батальон","Бильярд","Бинокль","Большевик","Будильник","Бульвар","Дальтон","Декабрь","Дельфин","Девальвация","Дирижабль","Дизель","Дизель-Мотор","Дуэль","Двигатель","Эмульсия","Эскадрилья","Факультатив","Факультет","Фальсификатор","Фальсификация","Фельдмаршал","Фельдшер","Фестиваль","Февраль","Фильтр","Фольклор","Фотоальбом","Фотоателье","Гантель","Гастроль","Гильза","Госпиталь","Готовальний","Грифель","Импульс","Инсульт","Интервью","Иньекция","Итальян","Июль","Июнь","Кабель","Календарь","Канифоль","Карамель","Картель","Карусель","Катапульта","Кинофестиваль","Кинофильм","Кисель","Китель","Кобальт","Компаньон","Конферансье","Объект","Разъезд","Субъект","Съезд","Съёмка","АБЗАЦ","АКЦЕНТ","АВИАНОСЕЦ","БАЦИЛЛА","БИОМИЦИН","БОЦМАН","БРОНЕНОСЕЦ","БРУЦЕЛЛОЗ","ЧЕРЕПИЦА","ДОЦЕНТ","ФРАНЦУЗ","ГАУБИЦА","ГЕРЦ","ГЛИЦЕРИН","ГОРЧИЦА","ГУСЕНИЦА","ИНЕРЦИЯ","ИНСЦЕНИРОВКА","КАЛЬЦИЙ","КАНЦЛЕР","КОЭФФИЦИЕНТ","КОНФЕРЕНЦ-ЗАЛ","КОНСЕПЦИЯ","КОНЦЕРН","КОНЦЕРТ","КОНЦЛАГЕР","КУЛЬТИВАЦИЯ","КВАРЦ","ЛИЦЕЙ","ЛОЦМАН","МАРГАНЕЦ","МИНОНОСЕЦ","МУНИЦИПАЛИТЕТ","ОФИЦИАНТ","ПЕНИЦИЛЛИН","ПЛАЦДАРМ","ПЛАЦКАРТА","ПОЛИЦМЕЙСТЕР","ЖИНСИЯТ","ПЕНСИЯ","РАНЕЦ","РИЦАРЬ","ЦЕЛЛОФАН","ЦЕЛЛЮЛОИД","ЦЕЛЛЮЛОЗА","ЦЕЛЬСИЙ","ЦЕМЕНТ","ЦЕНТНЕР","ЦЕНТРИФУГА","ЦЕНЗ","ЦЕНЗОР","ЦЕНЗУРА","ЦЕХ","ШНИЦЕЛЬ","ШВЕЙЦАР","ЦИФЕРБЛАТ","ЦИЛИНДР","ЦИЛИНДРИК","ЦИНГА","ЦИНК","ЦИРК","ЦИРКУЛЯР","ЦИСТЕРНА","ЦИТРУС","СОЦИОЛОГИК","СОЦИОЛОГИЯ","СЦЕНАРИЙ","КУЛЬТИВАТОР","КУЛЬТИВАЦИЯ","КУРЬЕР","ЛАГЕРЬ","ЛОСОСЬ","МЕБЕЛЬ","МЕДАЛЬ","МЕДАЛЬОН","МЕНЬШЕВИК","МЕНЬШЕВИЗМ","МИГРЕНЬ","МИКРОФИЛЬМ"," МИЛЬ ","МОДЕЛЬ","НЕФТЬ","НИКЕЛЬ","НИППЕЛЬ","НОЛЬ","НОЯБРЬ","ОКТЯБРЬ","ПАЛЬТО","ПАНЕЛЬ","ПАРАЛЛЕЛЬ","ПАРОЛЬ","ПАРТЬЕР","ПАТРУЛЬ","ПАВИЛЬОН","ПЕДАЛЬ","ПЛАСТИРЬ","ПОЧТАЛЬОН","ПОРШЕНЬ","ПОРТФЕЛЬ","ПОВЕСТЬ","ПРЕДОХРАНИТЕЛЬ","ПРЕМЬЕРА","ПРИСТАНЬ","ПУЛЬС","ПЬЕСА","РЕЛЬС","РЕЛЬЕФ","РЕНТАБЕЛЬ","РЕЗЬБА","РИЦАРЬ","РОЛЬ","РОЯЛЬ","РУЛЬ","СЕЛЬД","ЦЕЛЬСИЙ","СЕНТЯБРЬ","ШИНЕЛЬ","ШНИЦЕЛЬ","ШПАТЕЛЬ","ШПИЛЬКА","ШПИНДЕЛЬ","ШТАПЕЛЬ","ШТЕМПЕЛЬ","ШТЕПСЕЛЬ","СПЕКТАКЛЬ","СПИРАЛЬ","СТЕРЖЕНЬ","СУДЬЯ","СУЛЬФАТ","ТАБЕЛЬ","ТЕКСТИЛЬ","ТОКАРЬ","ТЮЛЕНЬ","ТУННЕЛЬ","УМИВАЛЬНИК","ВАЛЕРЬЯНКА","ВАЛЬС","ВЕКСЕЛЬ","ВЕЛЬВЕТ","ВЕНТИЛЬ","ВЕРМИШЕЛЬ","ВИМПЕЛЬ","ВИОЛОНЧЕЛЬ","ВОЛЬФРАМ","ВОЛЬТ","ВОЛЬТА","ВОЛЬТМЕТР","ВУЛЬГАР","ЯКОРЬ","ЯНВАРЬ","ЮРИСКОНСУЛЬТ","АНСАМБЛЬ","АРТЕЛЬ","АРТИКЛЬ","АРЬЕРГАРД","АСФАЛЬТ","АТЕЛЬЕ","АВТОМОБИЛЬ","БАЛЬЗАМ","БАНДЕРОЛЬ","БАТАЛЬОН","БИЛЬЯРД","БИНОКЛЬ","БОЛЬШЕВИК","БУДИЛЬНИК","БУЛЬВАР","ДАЛЬТОН","ДЕКАБРЬ","ДЕЛЬФИН","ДЕВАЛЬВАЦИЯ","ДИРИЖАБЛЬ","ДИЗЕЛЬ","ДИЗЕЛЬ-МОТОР","ДУЭЛЬ","ДВИГАТЕЛЬ","ЭМУЛЬСИЯ","ЭСКАДРИЛЬЯ","ФАКУЛЬТАТИВ","ФАКУЛЬТЕТ","ФАЛЬСИФИКАТОР","ФАЛЬСИФИКАЦИЯ","ФЕЛЬДМАРШАЛ","ФЕЛЬДШЕР","ФЕСТИВАЛЬ","ФЕВРАЛЬ","ФИЛЬТР","ФОЛЬКЛОР","ФОТОАЛЬБОМ","ФОТОАТЕЛЬЕ","ГАНТЕЛЬ","ГАСТРОЛЬ","ГИЛЬЗА","ГОСПИТАЛЬ","ГОТОВАЛЬНИЙ","ГРИФЕЛЬ","ИМПУЛЬС","ИНСУЛЬТ","ИНТЕРВЬЮ","ИНЬЕКЦИЯ","ИТАЛЬЯН","ИЮЛЬ","ИЮНЬ","КАБЕЛЬ","КАЛЕНДАРЬ","КАНИФОЛЬ","КАРАМЕЛЬ","КАРТЕЛЬ","КАРУСЕЛЬ","КАТАПУЛЬТА","КИНОФЕСТИВАЛЬ","КИНОФИЛЬМ","КИСЕЛЬ","КИТЕЛЬ","КОБАЛЬТ","КОМПАНЬОН","КОНФЕРАНСЬЕ","ОБЪЕКТ","РАЗЪЕЗД","СУБЪЕКТ","СЪЕЗД","СЪЁМКА"];
    private static $l_ts=["SINGARI","Singari","singari","PRINSIP","Prinsip","prinsip","KSIYA","ksiya","KSION","ksion","NSIYA","nsiya","NSION","nsion","TSION","tsion","TSIST","tsist","TSIZM","tsizm","TSIT","tsit","DETSI","detsi","TSEVT","tsevt","TSEPT","tsept","TSER","tser","TSIA","tsia","SIA","sia","TSIKL","tsikl","SIKL","sikl","VITSE","vitse","TSIYA","tsiya","TSIO","tsio","TSIU","tsiu","SIU","siu"];
    private static $c_ts=["СИНГАРИ","Сингари","сингари","ПРИНЦИП","Принцип","принцип","КЦИЯ","кция","КЦИОН","кцион","НЦИЯ","нция","НЦИОН","нцион","ЦИОН","цион","ЦИСТ","цист","ЦИЗМ","цизм","ЦИТ","цит","ДЕЦИ","деци","ЦЕВТ","цевт","ЦЕПТ","цепт","ЦЕР","цер","ЦИА","циа","ЦИА","циа","ЦИКЛ","цикл","ЦИКЛ","цикл","ВИЦЕ","вице","ЦИЯ","ция","ЦИО","цио","ЦИУ","циу","ЦИУ","циу"];
    private static $l_letters_l2c=["YO'","Yo'","yo'","YO","Yo","yo","YA","Ya","ya","YE","Ye","ye","YU","Yu","yu","CH","Ch","ch","S'H","S'h","s'h","SH","Sh","sh","A","a","B","b","D","d","F","f","G","g","H","h","I","i","J","j","K","k","L","l","M","m","N","n","O","o","P","p","Q","q","R","r","S","s","T","t","U","u","V","v","X","x","Y","y","Z","z"];
    private static $c_letters_l2c=["ЙЎ","Йў","йў","Ё","Ё","ё","Я","Я","я","Е","Е","е","Ю","Ю","ю","Ч","Ч","ч","СҲ","Сҳ","сҳ","Ш","Ш","ш","А","а","Б","б","Д","д","Ф","ф","Г","г","Ҳ","ҳ","И","и","Ж","ж","К","к","Л","л","М","м","Н","н","О","о","П","п","Қ","қ","Р","р","С","с","Т","т","У","у","В","в","Х","х","Й","й","З","з"];
    private static $c_letters_c2l=["ЕЪ","Еъ","еъ","СҲ","Сҳ","сҳ","ЬЕ","ье","ЬЁ","ьё","ЪЕ","ъе","ЪЁ","ъё","А","а","Б","б","В","в","Г","г","Д","д","ё","Ж","ж","З","з","И","и","Й","й","К","к","Л","л","М","м","Н","н","О","о","П","п","Р","р","С","с","Т","т","У","у","Ф","ф","Х","х","ч","ш","Э","э","ю","я","Ў","ў","Қ","қ","Ғ","ғ","Ҳ","ҳ","Ъ","ъ","Ь","ь","У","у"];
    private static $l_letters_c2l=["E’","E’","e’","S’H","S’h","s’h","YE","ye","YO","yo","YE","ye","YO","yo","A","a","B","b","V","v","G","g","D","d","yo","J","j","Z","z","I","i","Y","y","K","k","L","l","M","m","N","n","O","o","P","p","R","r","S","s","T","t","U","u","F","f","X","x","ch","sh","E","e","yu","ya","O‘","o‘","Q","q","G‘","g‘","H","h","’","’","","","W","w"];
    private static function replaceArray($text, $a1, $a2){
        for($i = 0; $i<count($a1);$i++){
            $pat = "#$a1[$i]#";
            $text = preg_replace($pat, $a2[$i], $text);
        }
        return $text;

    }
    private static function replaceWordArray($text,$a1,$a2){
        for($i = 0; $i < count($a1); $i++){
            $pat = "#\\b$a1[$i]#";
            $text = preg_replace($pat, $a2[$i], $text);
        }
        return $text;
    }
    public static function toCyrill($text){
        $text = str_replace(['G’','G\'','G`','G‘','Gʻ'], 'Ғ', $text);
        $text = str_replace(['g’','g\'','g`','g‘','gʻ'], 'ғ', $text);
        $text = str_replace(['O’','O\'','O`','O‘','Oʻ'], "Ў", $text);
        $text = str_replace(['o’','o\'','o`','o‘','oʻ'], "ў", $text);
        $text = str_replace(['\'','`','‘',], "’", $text);
        $text = preg_replace('#bMЎJ#', 'МЎЪЖ', $text);
        $text = preg_replace('#bMўj#', 'Мўъж', $text);
        $text = preg_replace('#bmўj#', 'мўъж', $text);
        $text = preg_replace('#bMЎT#', 'МЎЪТ', $text);
        $text = preg_replace('#bMўt#', 'Мўът', $text);
        $text = preg_replace('#bmўt#', 'мўът', $text);
        $text = preg_replace('#“([^“”]+)”#', '«$1»', $text);
        $text = preg_replace('#"([^"]+)"#', '«$1»', $text);
        $text = preg_replace('#-da\b#', 'dа', $text);
        $text = preg_replace('#-ku\b#', 'ku', $text);
        $text = preg_replace('#-chi\b#', 'chi', $text);
        $text = preg_replace('#-yu\b#', 'yu', $text);
        $text = preg_replace('#-u\b#', 'u', $text);
        $text = self::replaceWordArray($text, self::$rl_words, self::$rc_words);
        $text = self::replaceArray($text, self::$l_ts, self::$c_ts);
        $text = preg_replace('#’([A-Z])#', 'Ъ$1', $text);
        $text = preg_replace('#’([a-z])#', 'ъ$1', $text);
        $text = self::replaceArray($text, self::$l_letters_l2c, self::$c_letters_l2c);
        $text = preg_replace('#/^E|([^БВГДЕЁЖЗИЙКЛМНПРСТФХЦЧШЪЫЬЭЮЯЎҚҒҲбвгдеёжзийклмнпрстфхцчшъыьэюяўқғҳ])E|([\s+])E#', '$1$2Э', $text);
        $text = preg_replace('#/^e|([^БВГДЕЁЖЗИЙКЛМНПРСТФХЦЧШЪЫЬЭЮЯЎҚҒҲбвгдеёжзийклмнпрстфхцчшъыьэюяўқғҳ])e|([\s+])e#', '$1$2э', $text);
        $text = preg_replace('#e#', 'е', $text);
        $text = preg_replace('#([аоу])эв#', '$1ев', $text);
        $text = preg_replace('#([АаОоУу])ЭВ#', '1ЕВ', $text);
        $text = preg_replace('#(\s)миль([^\w])|\wмиль([^\w])|^миль([^\w])#', '$1мил$2$3', $text);
        return $text;
    }
    public static function toLatin($text){
        $text = self::replaceWordArray($text, self::$rc_words, self::$rl_words);
        $text = preg_replace('#"([^"]+)"#', '“$1”', $text);
        $text = preg_replace('#«([^»]+)»#', '“$1”', $text);
        $text = self::replaceArray($text, self::$c_letters_c2l, self::$l_letters_c2l);
        $text = preg_replace('#([A-Z])Ё|Ё([A-Z])#', "$1YO$2", $text);
        $text = preg_replace('#Ё([a-z])|Ё(\s+)|Ё#', "Yo$1$2", $text);
        $text = preg_replace('#([A-Z])Ч|Ч([A-Z])#', "$1CH$2", $text);
        $text = preg_replace('#Ч([a-z])|Ч(\s+)|Ч#', "Ch$1$2", $text);
        $text = preg_replace('#([A-Z])Ш|Ш([A-Z])#', "$1SH$2", $text);
        $text = preg_replace('#Ш([a-z])|Ш(\s+)|Ш#', "Sh$1$2", $text);
        $text = preg_replace('#([A-Z])Ю|Ю([A-Z])#', "$1YU$2", $text);
        $text = preg_replace('#Ю([a-z])|Ю(\s+)|Ю#', "Yu$1$2", $text);
        $text = preg_replace('#([A-Z])Я|Я([A-Z])#', "$1YA$2", $text);
        $text = preg_replace('#Я([a-z])|Я(\s+)|Я#', "Ya$1$2", $text);
        $text = preg_replace('#([AOUЕI])Ц([AOUЕI])#', "$1TS$2", $text);
        $text = preg_replace('#([aouеi])ц([aouеi])#', "$1ts$2", $text);
        $text = preg_replace('#Ц#', "S", $text);
        $text = preg_replace('#ц#', "s", $text);
        $text = preg_replace('#([^\w])Е([A-Z])|([AOUEI])Е([A-Z])|^Е([A-Z])#', "$1$3YE$2$4$5", $text);
        $text = preg_replace('#([^\w])Е([a-z])|([^\w])Е([^\w])|^Е([a-z])|^Е([^\w])|([^\w])Е#', "$1$3$7Ye$2$4$5$6", $text);
        $text = preg_replace('#^е|([^\w])е|([aouei])е#', "$1$2ye", $text);
        $text = preg_replace('#е#', "e", $text);
        $text = preg_replace('#‘’#', "‘", $text);
        return $text;
    }
}


?>
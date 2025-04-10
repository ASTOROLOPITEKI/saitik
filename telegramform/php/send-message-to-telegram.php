<?php
// Токен
const TOKEN = '7988228556:AAGWp3_gsjAfsqQdB1HLduLUQ0wFXpTdRe8';
// ID чата
const CHATID = '1965071153';

// Расширенный список типов файлов
$types = array(
    'image/gif', 'image/png', 'image/jpeg', 'application/pdf',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain'
);

// Максимальный размер файла
$size = 1073741824; // 2 ГБ

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fileSendStatus = '';
    $textSendStatus = '';
    
    if (!empty($_POST['name']) && !empty($_POST['phone'])) {

        $txt = "";
        $txt .= "Новый посланец:%0A";
        $txt .= "━━━━━━━━━━━━━━%0A";
        $txt .= "Имя: " . strip_tags(trim(urlencode($_POST['name']))) . "%0A";
        $txt .= "Телефон: " . strip_tags(trim(urlencode($_POST['phone']))) . "%0A";
        $txt .= "почта: " . strip_tags(trim(urlencode($_POST['mail']))) . "%0A";

        $textSendStatus = @file_get_contents('https://api.telegram.org/bot'. TOKEN .'/sendMessage?chat_id=' . CHATID . '&parse_mode=html&text=' . $txt); 

        if(isset(json_decode($textSendStatus)->{'ok'}) && json_decode($textSendStatus)->{'ok'}) {
            // Проверяем наличие файлов
            if (isset($_FILES['files']) && !empty($_FILES['files']['tmp_name'][0])) {
                
                // Создаем временную директорию если её нет
                $path = __DIR__ . '/tmp/';
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                
                $mediaData = [];
                $postContent = [
                    'chat_id' => CHATID,
                ];

                foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                    $fileName = $_FILES['files']['name'][$key];
                    $fileType = $_FILES['files']['type'][$key];
                    $fileSize = $_FILES['files']['size'][$key];
                    
                    // Проверяем размер и тип файла
                    if ($fileSize < $size && in_array($fileType, $types)) {
                        $filePath = $path . $fileName;
                        
                        if (move_uploaded_file($tmp_name, $filePath)) {
                            $postContent[$fileName] = new CURLFile(realpath($filePath));
                            $mediaData[] = [
                                'type' => 'document',
                                'media' => 'attach://' . $fileName
                            ];
                        }
                    }
                }

                if (!empty($mediaData)) {
                    $postContent['media'] = json_encode($mediaData);
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot" . TOKEN . "/sendMediaGroup");
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postContent);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    
                    $fileSendStatus = curl_exec($ch);
                    
                    if (curl_errno($ch)) {
                        error_log('Curl error: ' . curl_error($ch));
                    }
                    
                    curl_close($ch);
                    
                    // Очищаем временные файлы
                    array_map('unlink', glob($path . "*"));
                }
            }
            
            echo json_encode('SUCCESS');
        } else {
            echo json_encode('ERROR');
        }
    } else {
        echo json_encode('NOTVALID');
    }
} else {
    header("Location: /");
}
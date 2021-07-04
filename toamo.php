<?php 
require_once "ebClientAmo.php";

define('AC_PHONE_CID', '56809');
define('AC_EMAIL_CID', '56811');
define('AC_RESP_CID', '6218059'); 
define('AC_STATUS_CID', '34307515');

$config = array(
    'secret_key' => "####",
    'intagration_id' => "####",
    'client_domen' => "####",
    'redirect_uri' => "https://####/toamo.php",
    'auth_token' => "###############" //auth токен
);


if (isset($_COOKIE['utm_source'])) {$utm_source = $_COOKIE['utm_source'];}
if (isset($_COOKIE['utm_medium'])) {$utm_medium = $_COOKIE['utm_medium'];}
if (isset($_COOKIE['utm_campaign'])) {$utm_campaign = $_COOKIE['utm_campaign'];}
if (isset($_COOKIE['utm_content'])) {$utm_content = $_COOKIE['utm_content'];}
if (isset($_COOKIE['utm_term'])) {$utm_term = $_COOKIE['utm_term'];} 


$amo = new EbClientAmocrm($config['secret_key'], $config['intagration_id'], $config['client_domen'], $config['redirect_uri'], $config['auth_token']);

function get_contact(){
    // 143 - отказ   142 - успешно 
    global $amo, $phone, $name,$email;
    $contact = $amo ->get_contacts_by_pnone($phone)[1]['_embedded']['items'][0]; //получаем контакт через телефон
   
    if(!$contact){
        $contact_config = array(
            'name' => $name,
            'custom_fields' => [
                [
                    'id' => AC_PHONE_CID,
                    'value' => $phone,
                    'enum' => 'WORK'
                ],
                   [
                    'id' => AC_EMAIL_CID,
                    'value' => $email,
                    'enum' => 'WORK'
                ]
            ]
        );
        $c = $amo -> create_contact($contact_config);
        return $c[1]['_embedded']['items'][0]['id'];
    }else{

               $leads = $contact['leads']['id']; // все сделки контакта
        $completed = true;
        foreach($leads as $i){
            $status = $amo->get_leads($i)[1]['_embedded']['items'][0]['status_id'];
            if($status != '143' && $status != '142'){
                //сделка уже существует 
                $completed = false;
                echo $status."<br>";
            }
        }
        if($completed){ //если у контакта нет текущих сделок
            echo "1"; 
            return $contact['id'];
        }else{
            echo "2";
            return false;
        }
    }
}


echo "<pre>";
$contact_id = get_contact();


if($contact_id){

    $lead_config = array(
        'contacts_id' => array($contact_id),
        'name' => 'Заявка с сайта',
        'responsible_user_id' => AC_RESP_CID,
        'status_id' => AC_STATUS_CID,
        'custom_fields' => [

            ['id' => 642689,'value' => $utm_source],
            ['id' => 642691,'value' => $utm_medium],
            ['id' => 642693,'value' => $utm_campaign],
            ['id' => 642695,'value' => $utm_content],
            ['id' => 642697,'value' => $utm_term],

        ]
    );
    $amo->create_lead($lead_config);
    //print_r($test->get_leads());
}else{
    $lead = $amo ->get_contacts_by_pnone($phone)[1]['_embedded']['items'][0]['leads']['id'][0]; 
    $new_lead_id_status =  AC_STATUS_CID; //id статуса новой сделки
    $leads['update'] = array([
        'updated_at' => strtotime("now"),
        'id' => $lead,
        'status_id' => $new_lead_id_status,
    ]);
    $amo->update_lead($leads);
    $amo->create_note($lead, 'Повторная заявка с сайта');

    $tasks['add'] = array(
        #Привязываем к сделке
        array(
            'element_id' => $lead, #ID сделки
            'element_type' => 2, #Показываем, что это - сделка, а не контакт
            'task_type' => 3, 
            'text' => 'Проверить повторную заявку'
        )
    );
    $amo->create_task($tasks);
}


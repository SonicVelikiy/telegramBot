<?php

include 'Telegram.php';
include 'User.php';
include 'Districts.php';
include 'TrainingCentres.php';
include 'Subjects.php';


$bot_token = '';


$telegram = new Telegram($bot_token);

$text = $telegram->Text();

$chatID = $telegram->ChatID();

$callback_query = $telegram->Callback_Query();


if ($chatID == null) {

    $chatID = 635793263;

}


$user = new User($chatID);
$districts = new Districts($user->getLanguage());
$trainingCentres = new TrainingCentres();
$subjects = new Subjects($user->getLanguage());

echo $user->getStep();


if ($callback_query !== null && $callback_query != '') {

    $callback_data = $telegram->Callback_Data();


    $chatID = $telegram->Callback_ChatID();

    if ($callback_data == 'back') {
        if ($user->getPage() > 0) $user->setPage($user->getPage() - 1);
        $content = array('chat_id' => $telegram->ChatID(), 'message_id' => $callback_query['message']['message_id'], 'text' => 'Выберите учебные центры 👇🏻', 'reply_markup' => getAllTrainingCentresList());
        $telegram->editMessageText($content);
    } elseif ($callback_data == 'forward') {
        if ($user->getPage() < round(count($trainingCentres->getNames()) / 5)) $user->setPage($user->getPage() + 1);
        $content = array('chat_id' => $telegram->ChatID(), 'message_id' => $callback_query['message']['message_id'], 'text' => 'Выберите учебные центры 👇🏻', 'reply_markup' => getAllTrainingCentresList());
        $telegram->editMessageText($content);
    }if ($user->getStep() == "subjects" || $user->getStep() == "main_page") {
        $mtext = base64_decode($trainingCentres->getInfo($callback_data));

        $content = array('chat_id' => $chatID, 'text' => $mtext);
        $telegram->sendMessage($content);
    }


    $content = ['callback_query_id' => $telegram->Callback_ID(), 'text' => '', 'show_alert' => false];

    $telegram->answerCallbackQuery($content);

}

if ($text == '/start') {
    $option = [[$telegram->buildKeyboardButton("Русский 🇷🇺"), $telegram->buildKeyboardButton("O'zbek tili 🇺🇿")],$telegram->buildKeyboardButton("Enlish us")];
    $keyb = $telegram->buildKeyBoard($option, $onetime=false, $resize=true);
    $content = array('chat_id' => $chatID, 'reply_markup' => $keyb, 'text' => "Пожалуйста выберите язык.
 \nIltimos, tilni tanlang.");
    $telegram->sendMessage($content);

} elseif ($text == "Русский 🇷🇺") {
    $user->setLanguage('ru');

    showMainPage();
} elseif ($text == "O'zbek tili 🇺🇿") {
    $user->setLanguage('uz');

    showMainPage();
} elseif ($text == '🇺🇿🔄🇷🇺 '.$user->getText('btn_change_lang')) {

    if ($user->getLanguage() == 'ru') {
        $user->setLanguage('uz');
    } else {
        $user->setLanguage('ru');
    }

    showMainPage();

//    sendChangeLangInlineKeyboard();

} elseif (substr($text, 5) == $user->getText('btn_main1')) {

    sendDistricts();

} elseif (substr($text, 5) == $user->getText('btn_main2')) {

    sendAllTrainingCentresList();

} elseif (substr($text, 5) == $user->getText('btn_main_page')) {

    showMainPage();

} elseif (substr($text, 5) == $user->getText('btn_back')) {
    switch ($user->getStep()) {
        case "districts":
            showMainPage();
            break;
        case "subjects":
            sendDistricts();
            break;

    }
} elseif ($user->getStep() == "districts") {
    $districtsName = substr($text, 5);
    if (in_array($districtsName, $districts->getAllDistricts(), false)) {

        $user->setStep("subjects");

        $district = $districts->getKeywordByName($districtsName);
        $user->setDistrict($district);

        $msubjects = $subjects->getAllSubjects();
        for ($i = 0; $i < count($msubjects); $i++) {
            $msubjects[$i] = "▫️ " . $msubjects[$i];
        }
        msendKeyBoard($msubjects, $user->getText("text_choose_subject") . " 👇🏻");

    }

} elseif ($user->getStep() == "subjects") {
    $subjectName = substr($text, 7);
    if (in_array($subjectName, $subjects->getAllSubjects(), false)) {
        $subject = $subjects->getKeywordByName($subjectName);
        $district = $user->getDistrict();
        $tcList = [];
        foreach ($trainingCentres->getNames() as $name) {
            if ($trainingCentres->getDistrict($name) == $district && in_array($subject, $trainingCentres->getSubjects($name), false)) {
                $tcList [] = $name;
            }
        }

        sendTrainingCentresList($tcList);
    }

}

function showMainPage()
{
    global $user;
    $user->setStep("main_page");

    $buttons = ["🔖 " . $user->getText('btn_main1'), "💎 " . $user->getText('btn_main2')];

    msendKeyBoard($buttons, $user->getText('text_main') . " 👇🏻");
}

function msendKeyBoard($buttons, $text)
{

    global $user;

    global $telegram;

    $option = [];

    if (count($buttons) % 2 == 0) {

        for ($i = 0; $i < count($buttons); $i += 2) {

            $option[] = array($telegram->buildKeyboardButton($buttons[$i]), $telegram->buildKeyboardButton($buttons[$i + 1]));

        }

    } else {

        for ($i = 0; $i < count($buttons) - 1; $i += 2) {

            $option[] = array($telegram->buildKeyboardButton($buttons[$i]), $telegram->buildKeyboardButton($buttons[$i + 1]));

        }

        $option[] = array($telegram->buildKeyboardButton(end($buttons)));

    }

    if ($user->getStep() == "main_page") {

        $option[] = array($telegram->buildKeyboardButton("🇺🇿🔄🇷🇺 ".$user->getText("btn_change_lang")));

    } else {

        $option[] = array($telegram->buildKeyboardButton("🔙 " . $user->getText('btn_back')), $telegram->buildKeyboardButton("🔙 " . $user->getText('btn_main_page')));

    }


    $keyb = $telegram->buildKeyBoard($option, $onetime = false, $resize = true);


    $content = array('chat_id' => $telegram->ChatID(), 'reply_markup' => $keyb, 'text' => $text);

    $telegram->sendMessage($content);

}


function sendAllTrainingCentresList()
{

    global $telegram, $trainingCentres, $user;

    $text = "Выберите учебные центры" . " 👇🏻";

    $buttons = $callback_dates = $trainingCentres->getNames();

    $option = array();

    if (round(count($trainingCentres->getNames()) / 5) != $user->getPage()) {

        for ($i = $user->getPage() * 5; $i < $user->getPage() * 5 + 5; $i++) {

            $option[] = array($telegram->buildInlineKeyBoardButton(stripslashes("☑️" . $buttons[$i] . "☑️"), $url = '', $callback_data = $callback_dates[$i]));

        }

    } else {

        for ($i = $user->getPage() * 5; $i < $user->getPage() * 5 + count($buttons) % 5; $i++) {

            $option[] = array($telegram->buildInlineKeyBoardButton(stripslashes("☑️" . $buttons[$i] . "☑️"), $url = '', $callback_data = $callback_dates[$i]));

        }

    }

    $option[] = array($telegram->buildInlineKeyBoardButton("⬅️", $url = '', $callback_data = 'back'), $telegram->buildInlineKeyBoardButton("➡️", $url = '', $callback_data = 'forward'));

    $keyb = $telegram->buildInlineKeyBoard($option);


    $content = array('chat_id' => $telegram->ChatID(), 'reply_markup' => $keyb, 'text' => $text);

    $telegram->sendMessage($content);

}

function getAllTrainingCentresList()
{

    global $telegram, $trainingCentres, $user;

    $text = "Выберите учебные центры" . " 👇🏻";

    $buttons = $callback_dates = $trainingCentres->getNames();

    $option = array();

    if (round(count($trainingCentres->getNames()) / 5) != $user->getPage()) {

        for ($i = $user->getPage() * 5; $i < $user->getPage() * 5 + 5; $i++) {

            $option[] = array($telegram->buildInlineKeyBoardButton(stripslashes("☑️" . $buttons[$i] . "☑️"), $url = '', $callback_data = $callback_dates[$i]));

        }

    } else {

        for ($i = $user->getPage() * 5; $i < $user->getPage() * 5 + count($buttons) % 5; $i++) {

            $option[] = array($telegram->buildInlineKeyBoardButton(stripslashes("☑️" . $buttons[$i] . "☑️"), $url = '', $callback_data = $callback_dates[$i]));

        }

    }

    $option[] = array($telegram->buildInlineKeyBoardButton("⬅️", $url = '', $callback_data = 'back'), $telegram->buildInlineKeyBoardButton("➡️", $url = '', $callback_data = 'forward'));


    $keyb = $telegram->buildInlineKeyBoard($option);

    return $keyb;

}

function sendTrainingCentresList($buttons)
{

    global $telegram;

    if ($buttons !== []) {
        $text = "Выберите учебные центры" . " 👇🏻";

        $callback_dates = $buttons;

        $option = array();

        if (count($buttons) <= 5) {

            for ($i = 0; $i < count($buttons); $i++) {

                $option[] = array($telegram->buildInlineKeyBoardButton(stripslashes("☑️" . $buttons[$i] . "☑️"), $url = '', $callback_data = $callback_dates[$i]));

            }

        } else {

            for ($i = 0; $i < 5; $i++) {

                $option[] = array($telegram->buildInlineKeyBoardButton(stripslashes("☑️" . $buttons[$i] . "☑️"), $url = '', $callback_data = $callback_dates[$i]));

            }

            $option[] = array($telegram->buildInlineKeyBoardButton("⬅️", $url = '', $callback_data = 'back'), $telegram->buildInlineKeyBoardButton("➡️", $url = '', $callback_data = 'forward'));

        }


        $keyb = $telegram->buildInlineKeyBoard($option);

        $content = array('chat_id' => $telegram->ChatID(), 'reply_markup' => $keyb, 'text' => $text);

        $telegram->sendMessage($content);

    } else {
        $content = array('chat_id' => $telegram->ChatID(), 'text' => "Нет учебных центров в этом районе по этому предмету 🙃");

        $telegram->sendMessage($content);
    }


}

function sendDistricts()
{
    global $districts, $user;

    $user->setStep("districts");

    $mdistricts = $districts->getAllDistricts();

    for ($i = 0; $i < count($mdistricts); $i++) {
        $mdistricts[$i] = "📍 " . $mdistricts[$i];
    }

    msendKeyBoard($mdistricts, $user->getText('text_choose_district') . " 👇🏻");
}


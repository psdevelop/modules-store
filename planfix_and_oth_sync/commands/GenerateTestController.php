<?php

namespace app\commands;

use app\components\enums\ContactTypesEnum;
use app\models\cabinet\CabinetAccountNote;
use app\models\cabinet\CabinetAdvertiser;
use app\models\cabinet\CabinetAdvertiserUser;
use app\models\cabinet\CabinetAffiliate;
use app\models\cabinet\CabinetAffiliateUser;
use app\models\cabinet\CabinetBase;
use app\models\cabinet\CabinetChat;
use app\models\cabinet\CabinetChatExternalAgent;
use app\models\cabinet\CabinetChatMessage;
use app\models\cabinet\CabinetCompany;
use app\models\cabinet\CabinetEmployee;
use app\models\planfix\PlanfixContact;
use Yii;
use yii\console\Controller;


/**
 * Class GenerateTestController
 */
class GenerateTestController extends Controller
{

    /**
     *
     */
    public function actionContactsview()
    {
        $planfixs = PlanfixContact::find(['target' => 'company']);
        print_r($planfixs);
    }

    public function actionGenerateAll($count = 100, $db = 'dbLeads')
    {
        $this->actionGenerateEmployees($count, $db);
        $this->actionGenerateContacts($count, $db);
        $this->actionGenerateNotes($count, $db);
        $this->actionGenerateChat($count, $db);
    }

    public function actionGenerateEmployees($count = 10, $db = 'dbLeads')
    {
        CabinetBase::setDb($db);
        print "CabinetEmployee\n";
        Yii::$app->{$db}->createCommand()->truncateTable(CabinetEmployee::$table)->execute();
        $emails = ["af@leads.su_fake", "vp@leads.su", "rb@leads.su_fake"];
        for ($i = 0; $i <= count($emails) - 1; $i++) {
            $object = new CabinetEmployee();

            $object->synchronized = rand(0, 1);
            $object->icq = 'icq-' . rand(10000000000, 100000000000);
            $object->skype = 'skype-' . rand(100000, 999999);
            $object->join_date = date('Y-m-d H:i:s', time() - rand(0, 60 * 60 * 24 * 800));
            $object->last_login = date('Y-m-d H:i:s', time() - rand(0, 60 * 60 * 24 * 10));;
            $object->status = 'active';
            $object->photo = null;
            $object->wants_email = 0;
            $object->wants_sms = 0;
            $object->modified = $object->join_date;
            $object->email = $emails[$i];
            $object->first_name = '[EMPLOYE] ' . $this->getRand(['Ивантест', 'Мариятест', 'Петротеcт', 'Васятест']);
            $object->last_name = $this->getRand(['Ивантест', 'Мариятест', 'Петротеcт', 'Васятест']) . "о";
            $object->phone = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->cell_phone = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->title = implode(' ', [$object->first_name, $object->last_name, 'с номером телефона', $object->phone, ', присоединившийся к нам:', $object->join_date]);
            $object->password = '';
            $object->access = '';
            $object->api_key = md5($object->title);
            $object->timezone_id = 0;
            $object->employee_type = $this->getRand([null, 'affiliate_manager']);
            $object->secret_key = md5('secret_' . $object->title);
            $object->site_show = rand(0, 1);

            $object->save();
            print $object->email . "\n";
        }
    }

    public function getRand($array)
    {
        if (is_array($array)) {
            return $array[array_rand($array)];
        }
        return null;
    }

    public function actionGenerateContacts($count = 100, $db = 'dbLeads')
    {
        CabinetBase::setDb($db);

        print "CabinetAdvertiser\n";
        Yii::$app->{$db}->createCommand()->truncateTable(CabinetAdvertiser::$table)->execute();
        for ($i = 0; $i < $count; $i++) {
            $object = new CabinetAdvertiser();

            $object->synchronized = rand(0, 1);
            $object->date_added = date('Y-m-d H:i:s', time() - rand(0, 60 * 60 * 24 * 800));
            $object->status = 'active';
            $object->company = $this->getRand(['Компания', 'Фирма', 'Организация']) . ' ' . $this->getRand(['Васильки', 'Ромашки', 'Лютики']);
            $object->address1 = $this->getRand(['г.', 'пос.']) . ' ' . $this->getRand(['Ижевск', 'Казань', 'Москва']);
            $object->address2 = $this->getRand(['ул.', 'просп.', 'пл.']) . ' ' . $this->getRand(['Ленина', 'Карла Маркса', 'Энгельса']) . ', ' . rand(10, 150);
            $object->other = null;
            $object->zipcode = rand(100000, 999999);
            $object->phone = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->fax = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->website = 'www.testDomain-' . rand(1000, 9999) . '.ru';
            $object->employee_id = $this->getRandModel(CabinetEmployee::class)->id;
            $object->signup_ip = null;
            $object->modified = $object->date_added;
            $object->ref_id = null;
            $object->cell_phone = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->icq = 'icq-' . rand(10000000000, 100000000000);
            $object->skype = 'skype-' . rand(100000, 999999);
            $object->preferable_contact = null;
            $object->advertiser_group_id = 0;
            $object->city_id = 0;
            $object->region_id = 0;
            $object->country_id = 0;
            $object->api_enabled = rand(0, 1);
            $object->api_login = '';
            $object->api_password = '';
            $object->api_key = '';
            $object->api_url = '';
            $object->api_assoc = '';
            $object->referrer = '';
            $object->source = '';
            $object->confirm_cell_phone = rand(0, 1);
            $object->confirm_payment_data = rand(0, 1);

            $object->save();
            print $object->company . '(' . $object->phone . ')' . "\n";
        }

        print "CabinetAffiliate\n";
        Yii::$app->{$db}->createCommand()->truncateTable(CabinetAffiliate::$table)->execute();
        for ($i = 0; $i < $count; $i++) {
            $object = new CabinetAffiliate();

            $object->synchronized = rand(0, 1);
            $object->employee_id = $this->getRandModel(CabinetEmployee::class)->id;
            $object->company = $this->getRand(['Компания', 'Фирма', 'Организация']) . ' ' . $this->getRand(['Васильки', 'Ромашки', 'Лютики']);
            $object->address1 = $this->getRand(['г.', 'пос.']) . ' ' . $this->getRand(['Ижевск', 'Казань', 'Москва']);
            $object->address2 = $this->getRand(['ул.', 'просп.', 'пл. ']) . ' ' . $this->getRand(['Ленина', 'Карла Маркса', 'Энгельса']) . ', ' . rand(10, 150);
            $object->other = null;
            $object->zipcode = rand(100000, 999999);
            $object->phone = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->fax = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->website = 'www.testDomain-' . rand(1000, 9999) . '.ru';
            $object->signup_ip = null;
            $object->date_added = date('Y-m-d H:i:s', time() - rand(0, 60 * 60 * 24 * 800));
            $object->status = 'active';
            $object->payment_method = null;
            $object->method_data = null;
            $object->referral_id = null;
            $object->affiliate_tier_id = null;
            $object->ref_id = null;
            $object->modified = $object->date_added;
            $object->is_active = rand(0, 1);
            $object->is_system = rand(0, 1);
            $object->affiliate_group_id = $this->getRandModel(CabinetBase::class, 'affiliate_groups')->id;
            $object->payment_type_id = null;
            $object->payment_type_data_to_drop = null;
            $object->cell_phone = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->icq = 'icq-' . rand(10000000000, 100000000000);
            $object->skype = 'skype-' . rand(100000, 999999);
            $object->preferable_contact = null;
            $object->backurl_default = null;
            $object->backurl_geo = null;
            $object->backurl_browser = null;
            $object->backurl_conversions_total = null;
            $object->backurl_conversions_revenue = null;
            $object->city_id = 0;
            $object->region_id = 0;
            $object->country_id = 0;
            $object->legal_type = "individual";
            $object->legal_type_name = 'Физическое лицо';
            $object->balance = rand(0, 1000000);
            $object->points = rand(0, 500);
            $object->hold_in = rand(1000, 70000);
            $object->hold_out = rand(7, 14);
            $object->confirm_cell_phone = null;
            $object->confirm_payment_data = null;
            $object->frequency_payout = null;
            $object->payment_period = null;
            $object->privileges_level = 'none';
            $object->referrer = null;
            $object->source = null;
            $object->disable_payout = rand(0, 1);
            $object->is_distrust = rand(0, 1);
            $object->distrust_hint = null;
            $object->birthday = date('Y-m-d H:i:s', time() - rand(60 * 60 * 24 * 365 * 18, 60 * 60 * 24 * 365 * 45));
            $object->referral_rate = 0.5;
            $object->hold_days = rand(7, 14);
            $object->payment_employee_note = null;
            $object->test_mode = 0;
            $object->display_name = $object->company;
            $object->credit_limit = rand(0, 50) * 1000;
            $object->is_available_tor = null;

            $object->save();
            print $object->company . '(' . $object->phone . ')' . "\n";
        }

        print "CabinetAffiliateUser\n";
        Yii::$app->{$db}->createCommand()->truncateTable(CabinetAffiliateUser::$table)->execute();
        for ($i = 0; $i < $count; $i++) {
            $object = new CabinetAffiliateUser();

            $object->synchronized = rand(0, 1);
            $object->join_date = date('Y-m-d H:i:s', time() - rand(0, 60 * 60 * 24 * 800));
            $object->last_login = date('Y-m-d H:i:s', time() - rand(0, 60 * 60 * 24 * 10));;
            $object->affiliate_id = $this->getRandModel(CabinetAffiliate::class)->id;
            $object->status = 'active';
            $object->wants_email = 0;
            $object->wants_sms = 0;
            $object->modified = $object->join_date;
            $object->email = $db . '-email' . time() . '-' . rand(10000, 99999) . '@test.rutest';
            $object->first_name = $this->getRand(['Иван', 'Мария', 'Петр', 'Вася']);
            $object->last_name = $this->getRand(['Маркес', 'Гарсия', 'Санчес', 'Ромеро']);
            $object->phone = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->cell_phone = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->icq = 'icq-' . rand(10000000000, 100000000000);
            $object->skype = 'skype-' . rand(100000, 999999);
            $object->preferable_contact = null;
            $object->title = implode(' ', [$object->first_name, $object->last_name, 'с номером телефона', $object->phone, ', присоединившийся к нам:', $object->join_date]);
            $object->password = '$2a$12$iJC0umI9xR8ldPDjFaeJsuxAlBcwugeDlrilvuyGQUI/vIMcqV/eG';
            $object->is_creator = rand(0, 1);
            $object->confirm_cell_phone = rand(0, 1);
            $object->access = 'a:5:{i:0;s:9:"Affiliate";i:1;s:15:"Affiliate.stats";i:2;s:26:"Affiliate.offer_management";i:3;s:25:"Affiliate.user_management";i:4;s:28:"Affiliate.account_management";}';
            $object->api_key = md5($object->title);
            $object->timezone_id = 0;
            $object->photo = null;
            $object->birthday = date('Y-m-d H:i:s', time() - rand(60 * 60 * 24 * 365 * 18, 60 * 60 * 24 * 365 * 45));
            $object->secret_key = md5('secret_' . $object->title);

            $object->save();
            print $object->title . "\n";
        }

        print "CabinetAdvertiserUser\n";
        Yii::$app->{$db}->createCommand()->truncateTable(CabinetAdvertiserUser::$table)->execute();
        for ($i = 0; $i < $count; $i++) {
            $object = new CabinetAdvertiserUser();

            $object->synchronized = rand(0, 1);
            $object->join_date = date('Y-m-d H:i:s', time() - rand(0, 60 * 60 * 24 * 800));
            $object->last_login = date('Y-m-d H:i:s', time() - rand(0, 60 * 60 * 24 * 10));;
            $object->advertiser_id = $this->getRandModel(CabinetAdvertiser::class)->id;
            $object->status = 'active';
            $object->wants_email = 0;
            $object->wants_sms = 0;
            $object->modified = $object->join_date;
            $object->email = $db . '-email' . time() . '-' . rand(10000, 99999) . '@test.rutest';
            $object->first_name = $this->getRand(['Иван', 'Мария', 'Петр', 'Вася']);
            $object->last_name = $this->getRand(['Маркес', 'Гарсия', 'Санчес', 'Ромеро']);
            $object->phone = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->cell_phone = '+7' . rand(900, 999) . rand(1000000, 9999999);
            $object->icq = 'icq-' . rand(10000000000, 100000000000);
            $object->skype = 'skype-' . rand(100000, 999999);
            $object->preferable_contact = null;
            $object->title = implode(' ', [$object->first_name, $object->last_name, 'с номером телефона', $object->phone, ', присоединившийся к нам:', $object->join_date]);
            $object->password = '';
            $object->is_creator = rand(0, 1);
            $object->confirm_cell_phone = rand(0, 1);
            $object->access = rand(0, 1);
            $object->api_key = md5($object->title);
            $object->timezone_id = 0;
            $object->photo = null;
            $object->birthday = date('Y-m-d H:i:s', time() - rand(60 * 60 * 24 * 365 * 18, 60 * 60 * 24 * 365 * 45));
            $object->secret_key = md5('secret_' . $object->title);

            $object->save();
            print $object->title . "\n";
        }

    }

    /**
     * CabinetBase
     * @param object $modelClass CabinetBase
     * @param null $table
     * @return mixed
     */
    public function getRandModel($modelClass, $table = null)
    {
        if ($table) {
            $modelClass::$table = $table;
        }
        $max = $modelClass::find()->count();
        $offset = rand(0, $max - 1);
        return $modelClass::find()->offset($offset)->one();
    }

    public function actionGenerateNotes($count = 10, $db = 'dbLeads',  $mode = false)
    {
        $clientModels = [
            ContactTypesEnum::TYPE_AFFILIATE => CabinetAffiliate::class,
            ContactTypesEnum::TYPE_ADVERTISER => CabinetAdvertiser::class,
        ];

        if($mode == 'flush') {
            Yii::$app->{$db}->createCommand()->truncateTable(CabinetAccountNote::$table)->execute();
        }

        CabinetBase::setDb($db);
        for ($i = 0; $i < $count; $i++) {
            $objectNote = new CabinetAccountNote();
            $objectNote->note = '[-' . $db . '-] Тестовая заметка ' . date('Y-m-d H:i:s', time()) . ' [' . rand(10000, 99999) . ']';
            $objectNote->created = date('Y-m-d H:i:s', $st = time() - rand(1 * 24 * 60 * 60, 400 * 24 * 60 * 60));
            $objectNote->type = $this->getRand(array_keys($clientModels));

            $objectNote->account_id = $this->getRandModel($clientModels[$objectNote->type])->id;

            $objectNote->employee_id = $this->getRandModel(CabinetEmployee::class)->id;
            $objectNote->save();
        }
    }

    public function actionGenerateChat($count = 10, $db = 'dbLeads', $mode = false)
    {
        $clientModels = [
            ContactTypesEnum::TYPE_AFFILIATE => CabinetAffiliate::class,
            ContactTypesEnum::TYPE_ADVERTISER => CabinetAdvertiser::class,
            'guest' => null
        ];

        CabinetBase::setDb($db);

        if($mode == 'flush') {
            Yii::$app->{$db}->createCommand()->truncateTable(CabinetChatExternalAgent::$table)->execute();
            Yii::$app->{$db}->createCommand()->truncateTable(CabinetChat::$table)->execute();
            Yii::$app->{$db}->createCommand()->truncateTable(CabinetChatMessage::$table)->execute();
        }

        for ($i = 0; $i < $count; $i++) {
            $objectAgent = new CabinetChatExternalAgent();
            /**
             * @var $agent CabinetEmployee
             */
            $agent = $this->getRandModel(CabinetEmployee::class);
            if(CabinetChatExternalAgent::find()->where(['=','email',$agent->email])->all()) {
                continue;
            }
            $objectAgent->employee_id = $agent->id;
            $objectAgent->email = $agent->email;
            $objectAgent->name = $agent->first_name . ' ' . $agent->last_name;
            $objectAgent->external_id = 'j_' . substr($db, 0, 3) . '_000'.substr(time(),-3,3).'-' . $i;
            $objectAgent->save();
        }
        for ($i = 0; $i < $count; $i++) {
            $object = new CabinetChat();
            $object->account_type = $this->getRand(array_keys($clientModels));
            if ($clientModels[$object->account_type] !== null) {
                $object->account_id = $this->getRandModel($clientModels[$object->account_type])->id;
            } else {
                $object->account_id = 0;
            }
            $object->start_date = date('Y-m-d H:i:s', $st = time() - rand(1 * 24 * 60 * 60, 400 * 24 * 60 * 60));
            $object->end_date = date('Y-m-d H:i:s', $st + rand(1 * 24 * 60 * 60, 400 * 24 * 60 * 60));
            $object->chat_type = $this->getRand(['online', 'offline']);
            $object->external_agent_id = $this->getRandModel(CabinetChatExternalAgent::class)->external_id;
            $object->created = date('Y-m-d H:i:s', $st - 10);
            $object->params = $this->getRand([
                '{"visitor":{"name":"КОНТАКТ ДЛЯ ПОИСКА","email":"' . $this->getRandModel(CabinetAffiliateUser::class)->email . '","phone":null,"number":null}}',
                '{"visitor":{"name":"ФАНТОМ","email":"email-to-generate-phantom-' . rand(1000, 9999) . '@phantoms.ph","phone":null,"number":null}}',
                '{"visitor":{"name":"ФАНТОМ","email":null,"phone":null,"number":null}}'
            ]);
            $object->save();

            for ($j = 0; $j < $count; $j++) {
                $objectChild = new CabinetChatMessage();
                $objectChild->chat_id = $object->id;
                $objectChild->message_type = $this->getRand(['client', 'agent']);
                $objectChild->message = 'Тестовое сообщение ' . rand(1, 100);
                $objectChild->created = date('Y-m-d H:i:s', $st + rand(1 * 24 * 60 * 60, 400 * 24 * 60 * 60));
                $objectChild->save();
            }
        }

    }

    public function actionUpdateData($db = 'dbLeads')
    {
        CabinetBase::setDb($db);
        /**
         * @var $object CabinetAffiliate
         */
        $object = $this->getRandModel(CabinetAffiliate::class);
        $object->company = '*  ' . $object->company;
        $object->modified = date('Y-m-d H:i:s', time());
        $object->save();

        /**
         * @var $object CabinetAdvertiser
         */
        $object = $this->getRandModel(CabinetAdvertiser::class);
        $object->company = '*  ' . $object->company;
        $object->modified = date('Y-m-d H:i:s', time());
        $object->save();
        print_r($object->toArray());
        print "\n";

        /**
         * @var $object CabinetAffiliateUser
         */
        $object = $this->getRandModel(CabinetAffiliateUser::class);
        $object->first_name = '*  ' . $object->first_name;
        $object->modified = date('Y-m-d H:i:s', time());
        $object->save();
        print_r($object->toArray());
        print "\n";
        /**
         * @var $object CabinetAdvertiserUser
         */
        $object = $this->getRandModel(CabinetAdvertiserUser::class);
        $object->first_name = '*  ' . $object->first_name;
        $object->modified = date('Y-m-d H:i:s', time());
        $object->save();
        print_r($object->toArray());
        print "\n";
    }

    public function actionExplain()
    {
        $test = CabinetCompany::explainAllJoined(ContactTypesEnum::TYPE_AFFILIATE, date('Y-m-d H:i:s', 0), date('Y-m-d H:i:s', time()));
        print_r($test);
        return $test;
    }

}

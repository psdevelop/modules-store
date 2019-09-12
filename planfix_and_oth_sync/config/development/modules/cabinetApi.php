<?php
return [
    'class' => 'app\components\CabinetAPI',
    'leadsApiKey' => getenv('cabinet_leads_api_key'),
    'tradeApiKey' => getenv('cabinet_trade_api_key'),
    'leadsApiUrl' => getenv('cabinet_leads_api_url'),
    'tradeApiUrl' => getenv('cabinet_trade_api_url'),
];

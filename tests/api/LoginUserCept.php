<?php 
$I = new ApiTester($scenario);
$I->wantTo('test the login API with an email and password');
$I->sendGET('http://rti-codeception.api.localhost.com/login', ['email' => 'rtilte@reol.com', 'password' => '123123']);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContains('success');
$I->seeResponseContains('token');
$token = $I->grabDataFromJsonResponse('data.token');

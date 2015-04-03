<?php 
$I = new ApiTester($scenario);
$I->wantTo('test the login API with an email that does not exists');
$I->sendGET('http://rti-codeception.api.localhost.com/login', ['email' => 'rtilte@reol.coma', 'password' => '123123']);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContains('fail');
$I->seeResponseContains('Incorrect user name');
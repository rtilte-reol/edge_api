<?php 
$I = new ApiTester($scenario);
$I->wantTo('test the login API with a wrong password');
$I->sendGET('http://rti-codeception.api.localhost.com/login', ['email' => 'rtilte@reol.com', 'password' => '123123123']);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContains('fail');
$I->seeResponseContains('Incorrect password');
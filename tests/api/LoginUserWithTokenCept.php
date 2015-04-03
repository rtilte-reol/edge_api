<?php 
$I = new ApiTester($scenario);
$I->wantTo('test the login API with an access token (remember me)');

$I->amGoingTo("retrieve a token with the email/password combination first");
$I->sendGET('http://rti-codeception.api.localhost.com/login', ['email' => 'rtilte@reol.com', 'password' => '123123']);
$token = $I->grabDataFromJsonResponse('data.token');

$I->comment("\n");
$I->amGoingTo("Test the login with access-token instead of login/password");
$I->sendGET('http://rti-codeception.api.localhost.com/login', ['access-token' => $token]);
$I->seeResponseCodeIs(200);
$I->seeResponseIsJson();
$I->seeResponseContains('success');
$I->seeResponseContains('token');

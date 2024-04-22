<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Checkout\AuthorizeController;
use App\Http\Controllers\Api\Checkout\FlutterwaveController;
use App\Http\Controllers\Api\Checkout\InstamojoController;
use App\Http\Controllers\Api\Checkout\ManualController;
use App\Http\Controllers\Api\Checkout\MercadopagoController as AppMercadopagoController;
use App\Http\Controllers\Api\Checkout\MollieController;
use App\Http\Controllers\Api\Checkout\PayeerController as AppPayeerController;
use App\Http\Controllers\Api\Checkout\PaypalController;
use App\Http\Controllers\Api\Checkout\PaystackController;
use App\Http\Controllers\Api\Checkout\PaytmController;
use App\Http\Controllers\Api\Checkout\PerfectMoneyController as AppPerfectMoneyController;
use App\Http\Controllers\Api\Checkout\RazorpayController;
use App\Http\Controllers\Api\Checkout\SkrillController as AppSkrillController;
use App\Http\Controllers\Api\Checkout\StripeController;
use App\Http\Controllers\Api\Deposit\AuthorizeController as AppAuthorizeController;
use App\Http\Controllers\Api\Deposit\BlockIoController;
use App\Http\Controllers\Api\Deposit\FlutterwaveController as AppFlutterwaveController;
use App\Http\Controllers\Api\Deposit\InstamojoController as AppInstamojoController;
use App\Http\Controllers\Api\Deposit\ManualController as AppManualController;
use App\Http\Controllers\Api\Deposit\MercadopagoController;
use App\Http\Controllers\Api\Deposit\MollieController as AppMollieController;
use App\Http\Controllers\Api\Deposit\PayeerController;
use App\Http\Controllers\Api\Deposit\PaypalController as AppPaypalController;
use App\Http\Controllers\Api\Deposit\PaystackController as AppPaystackController;
use App\Http\Controllers\Api\Deposit\PaytmController as AppPaytmController;
use App\Http\Controllers\Api\Deposit\PerfectMoneyController;
use App\Http\Controllers\Api\Deposit\RazorpayController as AppRazorpayController;
use App\Http\Controllers\Api\Deposit\SkrillController;
use App\Http\Controllers\Api\Deposit\StripeController as AppStripeController;
use App\Http\Controllers\Api\Front\BlogController;
use App\Http\Controllers\Api\Front\FrontendController;
use App\Http\Controllers\Api\User\DashboardController;
use App\Http\Controllers\Api\User\DepositController;
use App\Http\Controllers\Api\User\InvestController;
use App\Http\Controllers\Api\User\PayoutController;
use App\Http\Controllers\Api\User\ReferralController;
use App\Http\Controllers\Api\User\RequestController;
use App\Http\Controllers\Api\User\SupportController;
use App\Http\Controllers\Api\User\TransferController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'user'], function () {
    Route::post('registration', [AuthController::class,'register']);
    Route::post('login', [AuthController::class,'login']);
    Route::get('logout', [AuthController::class,'logout']);
    Route::post('reset/password', [AuthController::class,'reset_password']);
    Route::post('change/password', [AuthController::class,'change_password']);
    Route::post('social/login', [AuthController::class,'social_login']);
    Route::post('refresh/token', [AuthController::class,'refresh']);
    Route::get('details', [AuthController::class,'details']);

    Route::group(['middleware' => 'auth:api'], function (){
        Route::get('dashboard',[DashboardController::class,'dashboard']);
        Route::get('transactions',[DashboardController::class,'transactions']);

        Route::get('plans',[InvestController::class,'plans']);
        Route::post('/invest/main',[InvestController::class,'mainWallet']);
        Route::post('/invest/interest',[InvestController::class,'interestWallet']);
        Route::post('/invest/checkout',[InvestController::class,'investAmount']);

        Route::get('deposit/history',[DepositController::class,'history']);
        Route::post('/deposit',[DepositController::class,'deposit']);

        Route::get('withdraw-methods',[PayoutController::class,'methods']);
        Route::get('withdraw-history',[PayoutController::class,'history']);
        Route::post('withdraw-create',[PayoutController::class,'store']);
        Route::get('withdraw-details/{id}',[PayoutController::class,'details']);

        Route::get('money-transfer/history',[TransferController::class,'history']);
        Route::post('money-transfer',[TransferController::class,'store']);

        Route::get('request-money/history',[RequestController::class,'requestHistory']);
        Route::post('request-money',[RequestController::class,'store']);
        Route::get('money-send/{id}',[RequestController::class,'send']);
        Route::get('request-details/{id}',[RequestController::class,'details']);
        Route::get('receive-request-money',[RequestController::class,'receiveHistory']);

        Route::get('referrers',[ReferralController::class,'referred']);
        Route::get('referrer-commissions',[ReferralController::class,'commissions']);

        Route::get('tickets',[SupportController::class,'allTickets']);
        Route::post('ticket-create',[SupportController::class,'store']);
        Route::get('ticket-show/{id}',[SupportController::class,'show']);
        Route::post('ticket-reply/{id}',[SupportController::class,'reply']);

        Route::post('profile/update', [UserController::class,'update']);
        Route::post('password/update', [UserController::class,'updatePassword']);

    });
    Route::post('/checkout/stripe-submit', [StripeController::class,'store'])->name('api.checkout.stripe.submit');
    Route::post('/authorize-submit', [AuthorizeController::class,'store'])->name('api.checkout.authorize.submit');
    Route::post('/checkout/manual-submit', [ManualController::class,'store'])->name('api.checkout.manual.submit');
    Route::post('/checkout/paystack/submit', [PaystackController::class,'store'])->name('api.checkout.paystack.submit');

    Route::post('/checkout/paypal-submit', [PaypalController::class,'store'])->name('api.checkout.paypal.submit');
    Route::get('/checkout/paypal/deposit/notify', [PaypalController::class,'notify'])->name('api.checkout.paypal.notify');
    Route::get('/checkout/paypal/deposit/cancel', [PaypalController::class,'cancel'])->name('api.checkout.paypal.cancel');

    Route::post('/checkout/flutter/submit', [FlutterwaveController::class,'store'])->name('api.checkout.flutter.submit');
    Route::post('/checkout/flutter/notify', [FlutterwaveController::class,'notify'])->name('api.checkout.flutter.notify');

    Route::post('/checkout/molly-submit', [MollieController::class,'store'])->name('api.checkout.molly.submit');
    Route::get('/checkout/molly-notify', [MollieController::class,'notify'])->name('api.checkout.molly.notify');

    Route::post('/checkout/razorpay-submit', [RazorpayController::class,'store'])->name('api.checkout.razorpay.submit');
    Route::post('/checkout/razorpay-notify', [RazorpayController::class,'notify'])->name('api.checkout.razorpay.notify');
    Route::get('/checkout/razorpay-notify/cancel', [RazorpayController::class,'cancel'])->name('api.checkout.razorpay.cancel');

    Route::post('/checkout/paytm-submit', [PaytmController::class,'store'])->name('api.checkout.paytm.submit');
    Route::post('/checkout/paytm-callback', [PaytmController::class,'paytmCallback'])->name('api.checkout.paytm.notify');

    Route::post('/checkout/instamojo-submit', [InstamojoController::class,'store'])->name('api.checkout.instamojo.submit');
    Route::get('/checkout/instamojo-callback', [InstamojoController::class,'notify'])->name('api.checkout.instamojo.notify');
    Route::get('/checkout/instamojo/cancle', [InstamojoController::class,'cancel'])->name('api.checkout.instamojo.cancel');

    Route::post('/checkout/mercadopago-submit', [AppMercadopagoController::class,'store'])->name('api.checkout.mercadopago.submit');

    Route::post('/checkout/blockio-submit', [BlockIoController::class,'deposit'])->name('api.checkout.blockio.submit');
    Route::post('/checkout/blockio/notify', [BlockIoController::class,'blockiocallback'])->name('api.checkout.blockio.notify');

    Route::post('/checkout/coinpay-submit', [CoinPaymentController::class,'deposit'])->name('api.checkout.coinpay.submit');
    Route::post('/checkout/coinpay/notify', [CoinPaymentController::class,'coincallback'])->name('api.checkout.coinpay.notify');

    Route::post('/checkout/coingate-submit', [CoinGateController::class,'deposit'])->name('api.checkout.coingate.submit');
    Route::post('/checkout/coingate/notify', [CoinGateController::class,'coingetCallback'])->name('api.checkout.coingate.notify');

    Route::post('/checkout/perfectmoney-submit', [AppPerfectMoneyController::class,'store'])->name('api.checkout.perfectmoney.submit');
    Route::any('/checkout/perfectmoney-notify', [AppPerfectMoneyController::class,'notify'])->name('api.checkout.perfectmoney.notify');

    Route::post('/checkout/payeer-submit', [AppPayeerController::class,'store'])->name('api.checkout.payeer.submit');
    Route::any('/checkout/payeer-notify', [AppPayeerController::class,'notify'])->name('api.checkout.payeer.notify');

    Route::post('/checkout/skrill-submit', [AppSkrillController::class,'store'])->name('api.checkout.skrill.submit');
    Route::any('/checkout/skrill-notify', [AppSkrillController::class,'notify'])->name('api.checkout.skrill.notify');

    Route::get('/invest/checkout/{id}',[InvestController::class,'checkout'])->name('api.user.invest.checkout');


    Route::post('/deposit/stripe-submit', [AppStripeController::class,'store'])->name('api.deposit.stripe.submit');
    Route::post('/deposit/authorize-submit', [AppAuthorizeController::class,'store'])->name('api.deposit.authorize.submit');
    Route::post('/deposit/manual-submit', [AppManualController::class,'store'])->name('api.deposit.manual.submit');
    Route::post('/deposit/paystack/submit', [AppPaystackController::class,'store'])->name('api.deposit.paystack.submit');

    Route::post('/deposit/paypal-submit', [AppPaypalController::class,'store'])->name('api.deposit.paypal.submit');
    Route::get('/deposit/paypal/deposit/notify', [AppPaypalController::class,'notify'])->name('api.deposit.paypal.notify');
    Route::get('/deposit/paypal/deposit/cancel', [AppPaypalController::class,'cancel'])->name('api.deposit.paypal.cancel');

    Route::post('/deposit/razorpay-submit', [AppRazorpayController::class,'store'])->name('api.deposit.razorpay.submit');
    Route::post('/deposit/razorpay-notify', [AppRazorpayController::class,'notify'])->name('api.deposit.razorpay.notify');
    Route::get('/deposit/razorpay-notify/cancel', [AppRazorpayController::class,'cancel'])->name('api.deposit.razorpay.cancel');

    Route::post('/deposit/paytm-submit', [AppPaytmController::class,'store'])->name('api.deposit.paytm.submit');
    Route::post('/deposit/paytm-callback', [AppPaytmController::class,'paytmCallback'])->name('api.deposit.paytm.notify');

    Route::post('/deposit/instamojo-submit', [AppInstamojoController::class,'store'])->name('api.deposit.instamojo.submit');
    Route::get('/deposit/instamojo-callback', [AppInstamojoController::class,'notify'])->name('api.deposit.instamojo.notify');
    Route::get('/deposit/instamojo/cancle', [AppInstamojoController::class,'cancel'])->name('api.deposit.instamojo.cancel');

    Route::post('/deposit/mercadopago-submit', [MercadopagoController::class,'store'])->name('api.deposit.mercadopago.submit');

    Route::post('/deposit/flutter/submit', [AppFlutterwaveController::class,'store'])->name('api.deposit.flutter.submit');
    Route::post('/deposit/flutter/notify', [AppFlutterwaveController::class,'notify'])->name('api.deposit.flutter.notify');

    Route::post('/deposit/molly-submit', [AppMollieController::class,'store'])->name('api.deposit.molly.submit');
    Route::get('/deposit/molly-notify', [AppMollieController::class,'notify'])->name('api.deposit.molly.notify');

    Route::post('/deposit/perfectmoney-submit', [PerfectMoneyController::class,'store'])->name('api.deposit.perfectmoney.submit');
    Route::any('/deposit/perfectmoney-notify', [PerfectMoneyController::class,'notify'])->name('api.deposit.perfectmoney.notify');

    Route::post('/deposit/payeer-submit', [PayeerController::class,'store'])->name('api.deposit.payeer.submit');
    Route::any('/deposit/payeer-notify', [PayeerController::class,'notify'])->name('api.deposit.payeer.notify');

    Route::post('/deposit/skrill-submit', [SkrillController::class,'store'])->name('api.deposit.skrill.submit');
    Route::any('/deposit/skrill-notify', [SkrillController::class,'notify'])->name('api.deposit.skrill.notify');

    Route::post('/deposit/blockio-submit', [BlockIoController::class,'deposit'])->name('api.deposit.blockio.submit');
    Route::post('/deposit/blockio/notify', [BlockIoController::class,'blockiocallback'])->name('api.deposit.blockio.notify');

    Route::get('/deposit/confirm/{id}',[DepositController::class,'confirm_deposit'])->name('api.user.deposit.confirm');
});

Route::get('languages',[FrontendController::class,'languages']);
Route::get('default-language',[FrontendController::class,'defaultLanguage']);
Route::get('language/{id}',[FrontendController::class,'language']);

Route::get('currencies',[FrontendController::class,'currencies']);
Route::get('default-currency',[FrontendController::class,'defaultCurrency']);
Route::get('currency/{id}',[FrontendController::class,'currency']);

Route::get('banner',[FrontendController::class,'banner']);
Route::get('about',[FrontendController::class,'about']);
Route::get('profit_calculator',[FrontendController::class,'profit_calculator']);
Route::post('profit_calculate',[FrontendController::class,'calculate']);
Route::get('plans',[FrontendController::class,'plans']);
Route::get('all-plans',[FrontendController::class,'allPlans']);
Route::get('partners',[FrontendController::class,'partners']);
Route::get('transactions',[FrontendController::class,'transactions']);
Route::get('how_to_start',[FrontendController::class,'how_to_start']);
Route::get('features',[FrontendController::class,'features']);
Route::get('referrals',[FrontendController::class,'referrals']);
Route::get('teams',[FrontendController::class,'teams']);
Route::get('testimonials',[FrontendController::class,'testimonials']);
Route::get('ctas',[FrontendController::class,'ctas']);
Route::get('blogs',[FrontendController::class,'blogs']);
Route::get('payment_gateways',[FrontendController::class,'payment_gateways']);

Route::get('pages',[FrontendController::class,'pages']);
Route::get('page/{slug}',[FrontendController::class,'page']);

Route::get('all-blogs',[BlogController::class,'blogs']);
Route::get('recent-blogs',[BlogController::class,'recentBlogs']);
Route::get('blog-category',[BlogController::class,'blogCategory']);
Route::get('blogs/{slug}',[BlogController::class,'blogDetails']);

Route::post('contact',[FrontendController::class,'contact']);
Route::get('contact-info',[FrontendController::class,'info']);

Route::fallback(function () {
    return response()->json(['status' => false, 'data' => [], 'error' => ['message' => 'Not Found!']], 404);
});

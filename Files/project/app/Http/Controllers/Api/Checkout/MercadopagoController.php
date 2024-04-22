<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use MercadoPago;
use Auth;

class MercadopagoController extends Controller
{
    public $orderRepositorty;

    public function __construct(OrderRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
    }

    public function store(Request $request){
        $gs = Generalsetting::findOrFail(1);
        $invest = Invest::findOrFail($request->invest_id);
        $user = User::findOrFail($invest->user_id);


        $payment_amount =  $invest->amount;
        $data = PaymentGateway::whereKeyword('mercadopago')->first();
        $paydata = $data->convertAutoData();
        MercadoPago\SDK::setAccessToken($paydata['token']);
        $payment = new MercadoPago\Payment();

        $payment->transaction_amount = $payment_amount;
        $payment->token = $request->token;
        $payment->description = 'Checkout '.$gs->title;
        $payment->installments = 1;
        $payment->payer = array(
        "email" => $user ? $user->email : 'example@gmail.com'
        );
        $payment->save();

        if ($payment->status == 'approved') {
            $addionalData = ['status'=>'running', 'method'=>$request->method];
            $this->orderRepositorty->apiOrder($request,$invest,$addionalData);

            return redirect()->route('api.user.invest.checkout',$invest->id)->with('message','Invest successfully complete.');
        }else{
            return redirect()->route('api.user.invest.checkout',$invest->id)->with('unsuccess','Something went wrong!');
        }

    }
}

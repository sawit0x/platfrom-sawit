<?php

namespace App\Http\Controllers\Checkout;

use App\Classes\GeniusMailer;
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
        $order = new Invest();

        $plan = Plan::whereId($request->plan_id)->first();
        $gs = Generalsetting::findOrFail(1);
        $item_name = $gs->title." Invest";
        $item_number = Str::random(4).time();

        $order['transaction_no'] = $item_number;
        $order['user_id'] = $request->user_id;
        $order['plan_id'] = $plan->id;
        $order['currency_id'] = $request->currency_id;
        $order['method'] = $request->method;

        if($request->currency_id){
            $currencyValue = Currency::where('id',$request->currency_id)->first();
            $order['amount'] = $request->amount/$currencyValue->value;
            $profitAmount = ($request->amount * $plan->profit_percentage)/100;
            $order['profit_amount'] = $profitAmount/$currencyValue->value;
        }

        if($plan->lifetime_return){
            $order['lifetime_return'] = 1;
        }

        if($plan->captial_return){
            $order['capital_back'] = 1;
            $order['profit_repeat'] = 0;
        }

        $order['payment_status'] = "pending";
        $order['status'] = 0;


        $order['profit_time'] = Carbon::now()->addHours($plan->schedule_hour);
        $order->save();


        $payment_amount =  $order->amount;
        $data = PaymentGateway::whereKeyword('mercadopago')->first();
        $paydata = $data->convertAutoData();
        MercadoPago\SDK::setAccessToken($paydata['token']);
        $payment = new MercadoPago\Payment();
        

        $payment->transaction_amount = $payment_amount;
        
        $payment->token = $request->token;
        
        $payment->description = 'Checkout '.$gs->title;
        $payment->installments = 1;
        $payment->payer = array(
        "email" => Auth::check() ? Auth::user()->email : 'example@gmail.com'
        );
        
        $payment->save();
       

        if ($payment->status == 'approved') {
            $order['payment_status'] = "completed";
            $order['status'] = 1;
            $order->save();

            $this->orderRepositorty->callAfterOrder($request,$order);
            return redirect()->route('user.invest.history')->with('message','Invest successfully complete.');
        }else{
            return redirect()->route('user.invest.history')->with('unsuccess','Something went wrong!');
        }

    }
}

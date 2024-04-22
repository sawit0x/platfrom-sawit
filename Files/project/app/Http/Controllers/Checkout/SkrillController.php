<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Models\PaymentGateway;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SkrillController extends Controller
{
    public $orderRepositorty;
    public  $allusers = [];

    public function __construct(OrderRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
        $this->payment = PaymentGateway::whereKeyword('skrill')->first();
        $this->paydata = $this->payment->convertAutoData();
    }

    public function store(Request $request){
        $gs = Generalsetting::findOrFail(1);
        $invest_name = $gs->title." Invest";
        $invest_number = Str::random(4).time();

        $addionalData = ['item_number'=>$invest_number];
        $this->orderRepositorty->order($request,'pending',$addionalData);

        $currency = Currency::whereId($request->currency_id)->first();
        $amount =  $request->amount/$currency->value;

        $info['pay_to_email'] = trim($this->paydata['email']);
        $info['transaction_id'] = $invest_number;
        $info['status_url'] = route('checkout.skrill.notify');
        $info['language'] = 'EN';
        $info['amount'] = round($amount,2);
        $info['currency'] = $request->currency_code;
        $info['detail1_description'] = $gs->title;
        $info['detail1_text'] = "Pay To ".$gs->title;

        $data['info'] = $info;
        $data['method'] = "POST";
        $data['url'] = "https://pay.skrill.com";

        return view('payment.redirect',compact('data'));
    }

    public function notify(Request $request){
        $invest = Invest::where('transaction_no', $request->transaction_id)->first();

        $concatFields = $request->merchant_id
                        . $request->transaction_id
                        . strtoupper(md5(trim($this->paydata['secret'])))
                        . $request->mb_amount
                        . $request->mb_currency
                        . $request->status;

        if (strtoupper(md5($concatFields)) == $request->md5sig && $request->pay_to_email == trim($this->paydata['email']) && $invest->status = '0') {
            $this->orderRepositorty->callAfterOrder($request,$invest);
            return redirect()->route('user.invest.history')->with('message','Invest successfully complete.');
        }else{
            return redirect()->route('user.invest.plans')->with('unsuccess','Something went wrong!');
        }
    }
}

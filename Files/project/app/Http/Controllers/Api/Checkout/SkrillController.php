<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Models\PaymentGateway;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
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

        $invest = Invest::findOrFail($request->invest_id);

        Session::put('method',$request->method);
        Session::put('invest_id',$request->invest_id);

        $info['pay_to_email'] = trim($this->paydata['email']);
        $info['transaction_id'] = $invest->transaction_no;
        $info['status_url'] = route('api.checkout.skrill.notify');
        $info['language'] = 'EN';
        $info['amount'] = round($request->amount,2);
        $info['currency'] = $request->currency_code;
        $info['detail1_description'] = $gs->title;
        $info['detail1_text'] = "Pay To ".$gs->title;

        $data['info'] = $info;
        $data['method'] = "POST";
        $data['url'] = "https://pay.skrill.com";

        return view('payment.redirect',compact('data'));
    }

    public function notify(Request $request){
        $method = Session::get('method');
        $invest_id = Session::get('invest_id');

        $invest = Invest::findOrFail($invest_id);

        $concatFields = $request->merchant_id
                        . $request->transaction_id
                        . strtoupper(md5(trim($this->paydata['secret'])))
                        . $request->mb_amount
                        . $request->mb_currency
                        . $request->status;

        if (strtoupper(md5($concatFields)) == $request->md5sig && $request->pay_to_email == trim($this->paydata['email']) && $invest->status = '0') {
            $addionalData = ['status'=>'running','method'=>$method];
            $this->orderRepositorty->apiOrder($request,$invest,$addionalData);

            return redirect()->route('api.user.invest.checkout',$invest->id)->with('message','Invest successfully complete.');
        }else{
            return redirect()->route('api.user.invest.checkout',$invest->id)->with('unsuccess','Something went wrong!');
        }
    }
}

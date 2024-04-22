<?php

namespace App\Http\Controllers\Deposit;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use App\Repositories\DepositRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SkrillController extends Controller
{
    public $orderRepositorty;
    public  $allusers = [];

    public function __construct(DepositRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
        $this->payment = PaymentGateway::whereKeyword('skrill')->first();
        $this->paydata = $this->payment->convertAutoData();
    }

    public function store(Request $request){
        $gs = Generalsetting::findOrFail(1);
        $deposit_number = Str::random(4).time();

        $currency = Currency::whereId($request->currency_id)->first();
        $amountToAdd = $request->amount/$currency->value;

        $addionalData = ['item_number'=>$deposit_number];
        $this->orderRepositorty->deposit($request,'pending',$addionalData);

        $info['pay_to_email'] = trim($this->paydata['email']);
        $info['transaction_id'] = $deposit_number;
        $info['status_url'] = route('deposit.skrill.notify');
        $info['language'] = 'EN';
        $info['amount'] = round($amountToAdd,2);
        $info['currency'] = $currency->name;
        $info['detail1_description'] = $gs->title;
        $info['detail1_text'] = "Pay To ".$gs->title;

        $data['info'] = $info;
        $data['method'] = "POST";
        $data['url'] = "https://pay.skrill.com";

        return view('payment.redirect',compact('data'));
    }

    public function notify(Request $request){
        $deposit = Deposit::where('deposit_number',$request->transaction_id)->where('status','pending')->first();
        $user = auth()->user();

        $concatFields = $request->merchant_id
                        . $request->transaction_id
                        . strtoupper(md5(trim($this->paydata['secret'])))
                        . $request->mb_amount
                        . $request->mb_currency
                        . $request->status;

        if (strtoupper(md5($concatFields)) == $request->md5sig && $request->pay_to_email == trim($this->paydata['email']) && $deposit->status = 'pending') {
            $deposit->status = "complete";
            $deposit->save();

            $user->balance += $deposit->amount;
            $user->save();

            $this->orderRepositorty->callAfterOrder($request,$deposit);
            return redirect()->route('user.deposit.index')->with('message','Deposit successfully complete.');
        }else{
            return redirect()->route('user.deposit.create')->with('unsuccess','Something went wrong!');
        }
    }
}

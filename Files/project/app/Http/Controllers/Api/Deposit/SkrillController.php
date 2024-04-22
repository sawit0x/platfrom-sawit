<?php

namespace App\Http\Controllers\Api\Deposit;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use App\Repositories\DepositRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
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
        $deposit = Deposit::findOrFail($request->deposit_id);
        $gs = Generalsetting::findOrFail(1);
        $deposit_number = $deposit->deposit_number;

        Session::put('method',$request->method);
        Session::put('deposit_id',$request->deposit_id);

        $info['pay_to_email'] = trim($this->paydata['email']);
        $info['transaction_id'] = $deposit_number;
        $info['status_url'] = route('deposit.skrill.notify');
        $info['language'] = 'EN';
        $info['amount'] = round($deposit->amount,2);
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
        $deposit_id = Session::get('deposit_id');
        $deposit = Deposit::findOrFail($deposit_id);
        $user = User::findOrFail($deposit->user_id);

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
            return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('message','Deposit successfully complete.');
        }else{
            return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('unsuccess','Something went wrong!');
        }
    }
}

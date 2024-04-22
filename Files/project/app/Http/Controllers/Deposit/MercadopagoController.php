<?php

namespace App\Http\Controllers\Deposit;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use MercadoPago;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Repositories\DepositRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MercadopagoController extends Controller
{
    public $orderRepositorty;
    public  $allusers = [];

    public function __construct(DepositRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
       
    }

    public function store(Request $request){
        $gs = Generalsetting::findOrFail(1);
        $user = auth()->user();

        $item_name = $gs->title." Deposit";
        $item_number = Str::random(12);
        $item_amount = $request->amount;

        $currency = Currency::whereId($request->currency_id)->first();
        $amountToAdd = $request->amount/$currency->value;

        $addionalData = ['item_number'=>$item_number];
        $this->orderRepositorty->deposit($request,'pending',$addionalData);

        $payment_amount =  $request->amount/$currency->value;
        $data = PaymentGateway::whereKeyword('mercadopago')->first();
        $paydata = $data->convertAutoData();
        MercadoPago\SDK::setAccessToken($paydata['token']);
        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = $payment_amount;
        $payment->token = $request->token;
        $payment->description = 'Deposit '.$gs->title;
        $payment->installments = 1;
        $payment->payer = array(
        "email" => Auth::check() ? Auth::user()->email : 'example@gmail.com'
        );
        
        $payment->save();

        if ($payment->status == 'approved') {
            
            $deposit = Deposit::where('deposit_number',$item_number)->where('status','pending')->first();
            $deposit->charge_id = $payment->payer->id;
            $deposit->status = 'complete';
            $deposit->save();

            $user->balance += $deposit->amount;
            $user->save();

            $this->orderRepositorty->callAfterOrder($request,$deposit);
            return redirect()->route('user.deposit.index')->with('message','Deposit successfully complete.');
        }else{
            return redirect()->route('user.deposit.create')->with('unsuccess','Something Went wrong!');
        }
    }
}

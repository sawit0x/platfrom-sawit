<?php

namespace App\Http\Controllers\Api\Deposit;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use MercadoPago;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
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
        $this->payment = PaymentGateway::whereKeyword('skrill')->first();
        $this->paydata = $this->payment->convertAutoData();
    }

    public function store(Request $request){
        $deposit = Deposit::findOrFail($request->deposit_id);
        $gs = Generalsetting::findOrFail(1);
        $user = User::findOrFail($deposit->user_id);

        $item_name = $gs->title." Deposit";
        $item_number = $deposit->deposit_number;
        $item_amount = $request->amount;

        $payment_amount =  $item_amount;
        $data = PaymentGateway::whereKeyword('mercadopago')->first();
        $paydata = $data->convertAutoData();
        MercadoPago\SDK::setAccessToken($paydata['token']);
        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = $payment_amount;
        $payment->token = $request->token;
        $payment->description = 'Deposit '.$gs->title;
        $payment->installments = 1;
        $payment->payer = array(
        "email" => $user  ? $user ->email : 'example@gmail.com'
        );
        $payment->save();

        if ($payment->status == 'approved') {
            $deposit->charge_id = $payment->payer->id;
            $deposit->status = 'complete';
            $deposit->update();

            $user->balance += $deposit->amount;
            $user->save();

            return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('message','Deposit successfully complete.');
        }else{
            return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('unsuccess','Something Went wrong!');
        }
    }
}

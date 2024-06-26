<?php

namespace App\Http\Controllers\Api\Deposit;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Repositories\DepositRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PayeerController extends Controller
{
    public $orderRepositorty;
    public  $allusers = [];

    public function __construct(DepositRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
        $this->payment = PaymentGateway::whereKeyword('payeer')->first();
        $this->paydata = $this->payment->convertAutoData();
    }

    public function store(Request $request){
        $deposit = Deposit::findOrFail($request->deposit_id);
        $gs = Generalsetting::findOrFail(1);
        $deposit_name = $gs->title." Deposit";
        $deposit_number = $deposit->deposit_number;

        Session::put('method',$request->method);
        Session::put('deposit_id',$request->deposit_id);

        $arHash = [
            trim($this->paydata['merchant_id']),
            $deposit_number,
            $request->amount,
            $request->currency_code,
            base64_encode("Pay To $gs->title"),
            trim($this->paydata['secret_key'])
        ];

        $info['m_shop'] = trim($this->paydata['merchant_id']);
        $info['m_orderid'] = $deposit_number;
        $info['m_amount'] = $request->amount;
        $info['m_curr'] = $request->currency_code;
        $info['m_desc'] = base64_encode("Pay To $gs->title");
        $info['m_sign'] = strtoupper(hash('sha256', implode(":", $arHash)));
        $info['lang'] = 'en';

        $data['info'] = $info;
        $data['method'] = "GET";
        $data['url'] = "https://payeer.com/merchant";


        return view('payment.redirect',compact('data'));
    }

    public function notify(Request $request)
    {
        if (isset($request->m_operation_id) && isset($request->m_sign)) {
            $sign_hash = strtoupper(hash('sha256', implode(":", array(
                $request->m_operation_id,
                $request->m_operation_ps,
                $request->m_operation_date,
                $request->m_operation_pay_date,
                $request->m_shop,
                $request->m_orderid,
                $request->m_amount,
                $request->m_curr,
                $request->m_desc,
                $request->m_status,
                $this->paydata['secret_key']
            ))));

            $method = Session::get('method');
            $deposit_id = Session::get('deposit_id');
            $deposit = Deposit::findOrFail($deposit_id);

            if ($request->m_sign != $sign_hash) {
                return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('unsuccess','digital signature not matched!');
            }
            else {

                $user = User::findOrFail($deposit->user_id);
                if ($request->m_amount == round($deposit->amount,2) && $request->m_status == 'success' && $deposit->status == 'pending') {
                    $deposit->status = "complete";
                    $deposit->update();

                    $user->balance += $deposit->amount;
                    $user->save();

                    Session::forget('method');
                    Session::forget('deposit_id');

                } else {
                    return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('unsuccess','transaction was unsuccessful!');
                }
            }
        } else {
            return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('unsuccess','transaction was unsuccessful!');
        }
    }
}

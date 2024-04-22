<?php

namespace App\Http\Controllers\Api\Deposit;

use App\Models\Deposit;
use App\Models\Currency;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\DepositRepository;
use Illuminate\Support\Facades\Session;

class PerfectMoneyController extends Controller
{
    public $orderRepositorty;
    public  $allusers = [];

    public function __construct(DepositRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
        $this->payment = PaymentGateway::whereKeyword('perfectmoney')->first();
        $this->paydata = $this->payment->convertAutoData();
    }

    public function store(Request $request){
        $deposit = Deposit::findOrFail($request->deposit_id);
        $gs = Generalsetting::findOrFail(1);
        $deposit_name = $gs->title." Deposit";
        $deposit_number = $deposit->deposit_number;

        $user = User::findOrFail($deposit->user_id);
        Session::put('method',$request->method);
        Session::put('deposit_id',$request->deposit_id);

        $info['PAYEE_ACCOUNT'] = trim($this->paydata['wallet_code']);
        $info['PAYEE_NAME'] = $deposit_name;
        $info['PAYMENT_ID'] = $deposit_number;
        $info['PAYMENT_AMOUNT'] = round($deposit->amount,2);
        $info['PAYMENT_UNITS'] = $request->currency_code;

        $info['STATUS_URL'] = route('api.deposit.perfectmoney.notify');
        $info['PAYMENT_URL'] = route('api.user.deposit.confirm',$deposit->id);
        $info['PAYMENT_URL_METHOD'] = 'POST';
        $info['NOPAYMENT_URL'] = route('api.user.deposit.confirm',$deposit->id);
        $info['NOPAYMENT_URL_METHOD'] = 'POST';
        $info['SUGGESTED_MEMO'] = $user->name;
        $info['BAGGAGE_FIELDS'] = 'IDENT';

        $data['info'] = $info;
        $data['method'] = 'post';
        $data['url'] = 'https://perfectmoney.is/api/step1.asp';

        return view('payment.redirect',compact('data'));
    }

    public function notify(Request $request){
        $method = Session::get('method');
        $deposit_id = Session::get('deposit_id');
        $deposit = Deposit::findOrFail($deposit_id);

        $user = User::findOrFail($deposit->user_id);
        $alt_passphrase = strtoupper(md5($this->paydata['alternative_passphrase']));

        define('ALTERNATE_PHRASE_HASH', $alt_passphrase);
        define('PATH_TO_LOG', '/somewhere/out/of/document_root/');
        $string =
            $_POST['PAYMENT_ID'] . ':' . $_POST['PAYEE_ACCOUNT'] . ':' .
            $_POST['PAYMENT_AMOUNT'] . ':' . $_POST['PAYMENT_UNITS'] . ':' .
            $_POST['PAYMENT_BATCH_NUM'] . ':' .
            $_POST['PAYER_ACCOUNT'] . ':' . ALTERNATE_PHRASE_HASH . ':' .
            $_POST['TIMESTAMPGMT'];

        $hash = strtoupper(md5($string));
        $hash2 = $_POST['V2_HASH'];

        if ($hash == $hash2) {

            foreach ($_POST as $key => $value) {
                $details[$key] = $value;
            }

            $pay_amount = $_POST['PAYMENT_AMOUNT'];

            $track = $_POST['PAYMENT_ID'];
            if ($_POST['PAYEE_ACCOUNT'] == $this->paydata['wallet_code'] && $pay_amount == round($deposit->amount,2) && $deposit->status == "pending") {
                $deposit->txnid = $details;
                $deposit->status = "complete";
                $deposit->update();

                $user->balance += $deposit->amount;
                $user->save();

                Session::forget('method');
                Session::forget('deposit_id');

                return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('message','Deposit successfully complete.');
            }else{
                return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('unsuccess','Something went wrong!');
            }
        }else{
            return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('unsuccess','Something went wrong!');
        }
    }
}

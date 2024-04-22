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
        $gs = Generalsetting::findOrFail(1);
        $deposit_name = $gs->title." Deposit";
        $deposit_number = Str::random(4).time();

        $addionalData = ['item_number'=>$deposit_number];
        $this->orderRepositorty->deposit($request,'pending',$addionalData);

        $currency = Currency::whereId($request->currency_id)->first();
        $amount =  $request->amount/$currency->value;

        $info['PAYEE_ACCOUNT'] = trim($this->paydata['wallet_code']);
        $info['PAYEE_NAME'] = $deposit_name;
        $info['PAYMENT_ID'] = $deposit_number;
        $info['PAYMENT_AMOUNT'] = round($amount,2);
        $info['PAYMENT_UNITS'] = $currency->name;

        $info['STATUS_URL'] = route('deposit.perfectmoney.notify');
        $info['PAYMENT_URL'] = route('user.deposit.index');
        $info['PAYMENT_URL_METHOD'] = 'POST';
        $info['NOPAYMENT_URL'] = route('user.deposit.create');
        $info['NOPAYMENT_URL_METHOD'] = 'POST';
        $info['SUGGESTED_MEMO'] = auth()->user()->name;
        $info['BAGGAGE_FIELDS'] = 'IDENT';

        $data['info'] = $info;
        $data['method'] = 'post';
        $data['url'] = 'https://perfectmoney.is/api/step1.asp';

        return view('payment.redirect',compact('data'));
    }

    public function notify(Request $request){
        $deposit = Deposit::where('deposit_number',$_POST['PAYMENT_ID'])->where('status','pending')->first();
        $user = auth()->user();
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
                $deposit->save();

                $user->balance += $deposit->amount;
                $user->save();

                $this->orderRepositorty->callAfterOrder($request,$deposit);

                return redirect()->route('user.deposit.index')->with('message','Deposit successfully complete.');
            }else{
                return redirect()->route('user.deposit.create')->with('unsuccess','Something went wrong!');
            }
        }else{
            return redirect()->route('user.deposit.create')->with('unsuccess','Something went wrong!');
        }
    }
}

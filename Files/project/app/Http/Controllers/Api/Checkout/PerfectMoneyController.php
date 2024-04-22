<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PerfectMoneyController extends Controller
{
    public $orderRepositorty;
    public  $allusers = [];

    public function __construct(OrderRepository $orderRepositorty)
    {
        $this->orderRepositorty = $orderRepositorty;
        $this->payment = PaymentGateway::whereKeyword('perfectmoney')->first();
        $this->paydata = $this->payment->convertAutoData();
    }

    public function store(Request $request){
        $method = Session::get('method');
        $invest_id = Session::get('invest_id');

        $invest = Invest::findOrFail($invest_id);
        $user = User::findOrFail($invest->user_id);

        $gs = Generalsetting::findOrFail(1);
        $invest_name = $gs->title." Invest";

        Session::put('method',$request->method);
        Session::put('invest_id',$request->invest_id);

        $info['PAYEE_ACCOUNT'] = trim($this->paydata['wallet_code']);
        $info['PAYEE_NAME'] = $invest_name;
        $info['PAYMENT_ID'] = $invest->transaction_no;
        $info['PAYMENT_AMOUNT'] = round($request->amount,2);
        $info['PAYMENT_UNITS'] = $request->currency_code;

        $info['STATUS_URL'] = route('api.checkout.perfectmoney.notify');
        $info['PAYMENT_URL'] = route('api.user.invest.checkout',$invest->id);
        $info['PAYMENT_URL_METHOD'] = 'POST';
        $info['NOPAYMENT_URL'] = route('api.user.invest.checkout',$invest->id);
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
        $invest_id = Session::get('invest_id');

        $invest = Invest::findOrFail($invest_id);

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
            if ($_POST['PAYEE_ACCOUNT'] == $this->paydata['wallet_code'] && $pay_amount == round($invest->amount,2) && $invest->status == 0) {

                $addionalData = ['txnid'=>$details, 'status'=>'running', 'method'=>$method];
                $this->orderRepositorty->apiOrder($request,$invest,$addionalData);

                return redirect()->route('api.user.invest.checkout',$invest->id)->with('message','Invest successfully complete.');
            }else{
                return redirect()->route('api.user.invest.checkout',$invest->id)->with('unsuccess','Something went wrong!');
            }
        }else{
            return redirect()->route('api.user.invest.checkout',$invest->id)->with('unsuccess','Something went wrong!');
        }
    }
}

<?php

namespace App\Http\Controllers\Api\Deposit;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Classes\GeniusMailer;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\PaymentGateway;
use App\Models\Transaction as AppTransaction;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

use PayPal\{
    Api\Item,
    Api\Payer,
    Api\Amount,
    Api\Payment,
    Api\ItemList,
    Rest\ApiContext,
    Api\Transaction,
    Api\RedirectUrls,
    Api\PaymentExecution,
    Auth\OAuthTokenCredential
};

class PaypalController extends Controller
{
    private $_api_context;

    public function __construct()
    {
        $data = PaymentGateway::whereKeyword('paypal')->first();
        $paydata = $data->convertAutoData();

        $paypal_conf = \Config::get('paypal');
        $paypal_conf['client_id'] = $paydata['client_id'];
        $paypal_conf['secret'] = $paydata['client_secret'];
        $paypal_conf['settings']['mode'] = $paydata['sandbox_check'] == 1 ? 'sandbox' : 'live';
        $this->_api_context = new ApiContext(new OAuthTokenCredential(
            $paypal_conf['client_id'],
            $paypal_conf['secret'])
        );
        $this->_api_context->setConfig($paypal_conf['settings']);
    }

    public function store(Request $request){

        $settings = Generalsetting::findOrFail(1);
        $deposit = Deposit::findOrFail($request->deposit_id);

        $cancel_url = route('api.deposit.paypal.cancel');
        $notify_url = route('api.deposit.paypal.notify');

        $item_name = $settings->title." Deposit";
        $item_number = Str::random(12);
        $item_amount = $request->amount;

        $support = ['USD','EUR'];
        if(!in_array($request->currency_code,$support)){
            return redirect()->back()->with('warning','Please Select USD Or EUR Currency For Paypal.');
        }

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $item_1 = new Item();
        $item_1->setName($item_name)
            ->setCurrency('USD')
            ->setQuantity(1)
            ->setPrice($item_amount);
        $item_list = new ItemList();
        $item_list->setItems(array($item_1));
        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($item_amount);
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription($item_name.' Via Paypal');
        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl($notify_url)
            ->setCancelUrl($cancel_url);
        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));


        try {
            $payment->create($this->_api_context);
        } catch (\PayPal\Exception\PPConnectionException $ex) {
            return redirect()->back()->with('unsuccess',$ex->getMessage());
        }
        foreach ($payment->getLinks() as $link) {
            if ($link->getRel() == 'approval_url') {
                $redirect_url = $link->getHref();
                break;
            }
        }

        Session::put('method',$request->method);
        Session::put('deposit_id',$request->deposit_id);
        Session::put('paypal_payment_id', $payment->getId());

        if (isset($redirect_url)) {
            return Redirect::away($redirect_url);
        }


        return redirect()->back()->with('unsuccess','Unknown error occurred');

        if (isset($redirect_url)) {
            return Redirect::away($redirect_url);
        }
        return redirect()->back()->with('unsuccess','Unknown error occurred');

    }

    public function notify(Request $request)
    {
        $payment_id = Session::get('paypal_payment_id');
        if (empty( $request['PayerID']) || empty( $request['token'])) {
            return redirect()->back()->with('error', 'Payment Failed');
        }

        $payment = Payment::get($payment_id, $this->_api_context);
        $execution = new PaymentExecution();
        $execution->setPayerId($request['PayerID']);


        $method = Session::get('method');
        $deposit_id = Session::get('deposit_id');

        $result = $payment->execute($execution, $this->_api_context);

        if ($result->getState() == 'approved') {
            $resp = json_decode($payment, true);

            $deposit = Deposit::findOrFail($deposit_id);
            $data['txnid'] = $resp['transactions'][0]['related_resources'][0]['sale']['id'];
            $data['method'] = $method;
            $data['status'] = "complete";
            $deposit->update($data);

            $gs =  Generalsetting::findOrFail(1);
            $user =User::findOrFail($deposit->user_id);

            if($gs->is_smtp == 1)
            {
                $data = [
                    'to' => $user->email,
                    'type' => "Deposit",
                    'cname' => $user->name,
                    'oamount' => $deposit->amount,
                    'aname' => "",
                    'aemail' => "",
                    'wtitle' => "",
                ];

                $mailer = new GeniusMailer();
                $mailer->sendAutoMail($data);
            }
            else
            {
                $to = $user->email;
                $subject = " You have deposited successfully.";
                $msg = "Hello ".$user->name."!\nYou have invested successfully.\nThank you.";
                $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                mail($to,$subject,$msg,$headers);
            }

            $user->balance += $deposit->amount;
            $user->save();

            $trans = new AppTransaction();
            $trans->email = $user->email;
            $trans->amount = $deposit->amount;
            $trans->type = "Deposit";
            $trans->profit = "plus";
            $trans->txnid = $deposit->deposit_number;
            $trans->user_id = $user->id;
            $trans->save();

            Session::forget('deposit_id');
            Session::forget('method');
            Session::forget('paypal_payment_id');

            return redirect()->route('api.user.deposit.confirm',$deposit->id)->with('success','Deposit amount '.$deposit->amount.' (USD) successfully!');
        }

    }
}

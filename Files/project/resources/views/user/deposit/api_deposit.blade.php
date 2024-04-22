<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    @if(isset($page->meta_tag) && isset($page->meta_description))
        <meta name="keywords" content="{{ $page->meta_tag }}">
        <meta name="description" content="@php echo $page->meta_description; @endphp">
    @elseif(isset($blog->meta_tag) && isset($blog->meta_description))
        <meta name="keywords" content="{{ $blog->meta_tag }}">
        <meta name="description" content="@php echo $blog->meta_description; @endphp">
    @else
        <meta name="keywords" content="{{ $seo->meta_keys }}">
        <meta name="author" content="GeniusOcean">
    @endif
    <title>{{$gs->title}}</title>

    <link rel="stylesheet" href="{{asset('assets/front/css/bootstrap.min.css')}}" />
    <link rel="stylesheet" href="{{asset('assets/front/css/animate.css')}}" />
    <link rel="stylesheet" href="{{asset('assets/front/css/all.min.css')}}" />
    <link rel="stylesheet" href="{{asset('assets/front/css/lightbox.min.css')}}" />
    <link rel="stylesheet" href="{{asset('assets/front/css/odometer.css')}}" />
    <link rel="stylesheet" href="{{asset('assets/front/css/owl.min.css')}}" />
    <link rel="stylesheet" href="{{asset('assets/front/css/main.css')}}" />
    <link rel="stylesheet" href="{{asset('assets/front/css/toastr.min.css')}}">
    <link rel="stylesheet" href="{{ asset('assets/front/css/styles.php?color='.str_replace('#','',$gs->colors)) }}">
    @if ($default_font->font_value)
        <link href="https://fonts.googleapis.com/css?family={{ $default_font->font_value }}&display=swap" rel="stylesheet">
    @else
        <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
    @endif

    @if ($default_font->font_family)
        <link rel="stylesheet" id="colorr" href="{{ asset('assets/front/css/font.php?font_familly='.$default_font->font_family) }}">
    @else
        <link rel="stylesheet" id="colorr" href="{{ asset('assets/front/css/font.php?font_familly='."Open Sans") }}">
    @endif

    <link rel="shortcut icon" href="{{asset('assets/images/'.$gs->favicon)}}">
    @stack('css')

    @if(!empty($seo->google_analytics))
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag() {
				dataLayer.push(arguments);
		}
		gtag('js', new Date());
		gtag('config', '{{ $seo->google_analytics }}');
	</script>
	@endif
</head>

<body>

    <!-- Contact Section -->
    <section class="contact-section overflow-hidden bg--gradient-light pb-100 pt-100 border-bottom">
        <div class="container">
            <div class="mt-5">
                <div class="contact-wrapper bg--body border rounded">
                    <div class="section-header">
                        <h3 class="section-header__subtitle mb-0">@lang('Deposit Confirm')</h3>
                    </div>

                    @includeIf('includes.flash')
                    <form id="" class="deposit-form" action="" method="POST" enctype="multipart/form-data">
                        @csrf

                        <input type="hidden" name="deposit_id" value="{{ $deposit->id }}">
                        <div class="row gy-3 gy-md-4">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label required">{{__('Payment Method')}}</label>
                                <select name="method" id="withmethod" class="form-control" required>
                                    <option value="">{{ __('Select Payment Method') }}</option>
                                    @foreach ($gateways as $gateway)
                                            @if ($gateway->type == 'manual')
                                                <option value="Manual" data-details="{{$gateway->details}}">{{ $gateway->title }}</option>
                                            @endif
                                        @if (in_array($gateway->keyword,$availableGatways))
                                            <option value="{{$gateway->keyword}}">{{ $gateway->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        <input type="hidden" name="currency_sign" value="{{ $deposit_currency->sign }}">
                        <input type="hidden" id="currencyCode" name="currency_code" value="{{ $deposit_currency->name }}">
                        <input type="hidden" name="currency_id" value="{{ $deposit_currency->id }}">
                        <input type="hidden" id="ref_id" name="paystack_txn" value="">
                        <input type="hidden" id="UserEmail" name="user_email" value="{{ $deposit->user->email}}">
                        <input type="hidden" name="paystackInfo" id="paystackInfo" value="{{ $paystackKey }}">

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="form-label required">{{__('Deposit Amount')}}</label>
                                <input name="amount" id="amount" class="form-control" autocomplete="off" placeholder="{{__('0.0')}}" type="number" value="{{ $deposit->amount }}" min="1" required readonly>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div id="card-view" class="col-md-12 d-none">
                                <div class="row gy-3">
                                    <input type="hidden" name="cmd" value="_xclick">
                                    <input type="hidden" name="no_note" value="1">
                                    <input type="hidden" name="lc" value="UK">
                                    <input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest">

                                    <div class="col-md-6">
                                        <input type="text" class="form-control card-elements" name="cardNumber" placeholder="{{ __('Card Number') }}" autocomplete="off" required autofocus oninput="validateCard(this.value);"/>
                                        <span id="errCard"></span>
                                    </div>

                                    <div class="col-lg-6 cardRow">
                                        <input type="text" class="form-control card-elements" placeholder="{{ ('Card CVC') }}" name="cardCVC" oninput="validateCVC(this.value);">
                                        <span id="errCVC"></span>
                                    </div>

                                    <div class="col-lg-6">
                                        <input type="text" class="form-control card-elements" placeholder="{{ __('Month') }}" name="month" >
                                    </div>

                                    <div class="col-lg-6">
                                        <input type="text" class="form-control card-elements" placeholder="{{ __('Year') }}" name="year">
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div id="mergado-view" class="col-md-12 d-none">
                                <div class="row gy-3">
                                    <div class="row gy-3">
                                        <div class="col-md-6">
                                            <input class="form-control mergado-elements" type="text" placeholder="{{ __('Credit Card Number') }}" id="cardNumber" data-checkout="cardNumber" onselectstart="return false" autocomplete="off" />
                                        </div>

                                        <div class="col-md-6">
                                            <input class="form-control mergado-elements" type="text" id="securityCode" data-checkout="securityCode" placeholder="{{ __('Security Code') }}" onselectstart="return false" autocomplete="off" />
                                        </div>

                                        <div class="col-md-6">
                                            <input class="form-control mergado-elements" type="text" id="cardExpirationMonth" data-checkout="cardExpirationMonth" placeholder="{{ __('Expiration Month') }}" autocomplete="off" />
                                        </div>

                                        <div class="col-md-6">
                                            <input class="form-control mergado-elements" type="text" id="cardExpirationYear" data-checkout="cardExpirationYear" placeholder="{{ __('Expiration Year') }}" autocomplete="off" />
                                        </div>

                                        <div class="col-md-6">
                                            <input class="form-control mergado-elements" type="text" id="cardholderName" data-checkout="cardholderName" placeholder="{{ __('Card Holder Name') }}" />
                                        </div>

                                        <div class="col-md-6">
                                            <select class="form-control mergado-elements col-lg-9 pl-0" id="docType" data-checkout="docType" required></select>
                                        </div>

                                        <div class="col-md-6">
                                            <input class="form-control mergado-elements" type="text" id="docNumber" data-checkout="docNumber" placeholder="{{ __('Document Number') }}" />
                                        </div>
                                    </div>

                                    <input type="hidden" id="installments" value="1" />
                                    <input type="hidden" name="description" />
                                    <input type="hidden" name="paymentMethodId" />
                                </div>
                            </div>
                            </div>

                        <div class="col-sm-12 mt-4 manual-payment d-none">
                            <div class="card default--card">
                                <div class="card-body">
                                <div class="row">

                                    <div class="col-sm-12 pb-2 manual-payment-details">
                                    </div>

                                    <div class="col-sm-12">
                                    <label class="form-label required">@lang('Transaction ID')#</label>
                                    <input class="form-control" name="txn_id4" type="text" placeholder="Transaction ID" id="manual_transaction_id">
                                    </div>

                                </div>
                                </div>
                            </div>
                        </div>



                        <div class="col-sm-12">
                            <div class="form-group">
                                <label class="form-label">{{__('Description')}}</label>
                                <textarea name="details" class="form-control nic-edit" cols="30" rows="5" placeholder="{{__('Receive account details')}}"></textarea>
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <button type="submit" class="cmn--btn bg--primary submit-btn w-100 border-0">{{__('Submit')}}</button>

                        </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </section>
    <!-- Contact Section -->

    <script src="{{asset('assets/front/js/jquery-3.6.0.min.js')}}"></script>
    <script src="{{asset('assets/front/js/bootstrap.min.js')}}"></script>
    <script src="{{asset('assets/front/js/viewport.jquery.js')}}"></script>
    <script src="{{asset('assets/front/js/odometer.min.js')}}"></script>
    <script src="{{asset('assets/front/js/lightbox.min.js')}}"></script>
    <script src="{{asset('assets/front/js/owl.min.js')}}"></script>
    <script src="{{asset('assets/front/js/toastr.min.js')}}"></script>
    <script src="{{asset('assets/front/js/notify.js')}}"></script>
    <script src="{{asset('assets/front/js/main.js')}}"></script>
    <script src="{{asset('assets/front/js/custom.js')}}"></script>

    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js"></script>

    <script type="text/javascript">
    'use strict';

    $(document).on('change','#withmethod',function(){
        var val = $(this).val();

        if(val == 'stripe')
        {
            $('.deposit-form').prop('action','{{ route('api.deposit.stripe.submit') }}');
            $('#card-view').removeClass('d-none');
            $('.card-elements').prop('required',true);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'authorize.net')
        {
            $('.deposit-form').prop('action','{{ route('api.deposit.authorize.submit') }}');
            $('#card-view').removeClass('d-none');
            $('.card-elements').prop('required',true);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'paystack') {
            $('.deposit-form').prop('action','{{ route('api.deposit.paystack.submit') }}');
            $('.deposit-form').prop('id','step1-form');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'paypal') {
            $('.deposit-form').prop('action','{{ route('api.deposit.paypal.submit') }}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'paytm') {
            $('.deposit-form').prop('action','{{ route('api.deposit.paytm.submit') }}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'instamojo') {
            $('.deposit-form').prop('action','{{ route('api.deposit.instamojo.submit') }}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'razorpay') {
            $('.deposit-form').prop('action','{{ route('api.deposit.razorpay.submit') }}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'skrill'){
            $('.deposit-form').prop('action','{{ route('deposit.skrill.submit') }}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'payeer'){
            $('.deposit-form').prop('action','{{ route('deposit.payeer.submit') }}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'perfectmoney'){
            $('.deposit-form').prop('action','{{ route('deposit.perfectmoney.submit') }}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'mercadopago')
        {
            $('.deposit-form').prop('action','{{ route('deposit.mercadopago.submit') }}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').removeClass('d-none');
            $('.mergado-elements').prop('required',true);
            $('.deposit-form').prop('id','mercadopago');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'flutterwave')
        {
            $('.deposit-form').prop('action','{{ route('deposit.flutter.submit') }}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'mollie') {
            $('.deposit-form').prop('action','{{ route('deposit.molly.submit') }}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'block.io.btc' || val == 'block.io.ltc' || val == 'block.io.dgc') {
            $('.deposit-form').prop('action','{{route('deposit.blockio.submit')}}');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',false);
            $('.manual-payment').addClass('d-none');
        }

        if(val == 'Manual'){
            $('.deposit-form').prop('action','{{route('api.deposit.manual.submit')}}');
            $('.manual-payment').removeClass('d-none');
            $('#card-view').addClass('d-none');
            $('.card-elements').prop('required',false);
            $('#mergado-view').addClass('d-none');
            $('.mergado-elements').prop('required',false);
            $('.deposit-form').prop('id','');
            $('#manual_transaction_id').prop('required',true);
            const details = $(this).find(':selected').data('details');
            $('.manual-payment-details').empty();
            $('.manual-payment-details').append(`<font size="3">${details}</font>`)
        }

    });



    $(document).on('submit','#step1-form',function(){
        alert('ok');
        var val = $('#sub').val();
        var total = $('#amount').val();
        var paystackInfo = $('#paystackInfo').val();
        var curr = $('#currencyCode').val();
        total = Math.round(total);
        if(val == 0)
        {
        var handler = PaystackPop.setup({
            key: paystackInfo,
            email: $('#UserEmail').val(),
            amount: total * 100,
            currency: curr,
            ref: ''+Math.floor((Math.random() * 1000000000) + 1),
            callback: function(response){
            $('#ref_id').val(response.reference);
            $('#sub').val('1');
            $('#final-btn').click();
            },
            onClose: function(){
            window.location.reload();
            }
        });
        handler.openIframe();
            return false;
        }
        else {
            $('#preloader').show();
            return true;
        }
    });

</script>

    <script>
        window.Mercadopago.setPublishableKey("{{ $mercadoKey }}");
        window.Mercadopago.getIdentificationTypes();

        $(document).on('change','#withmethod',function(){
            let method = $(this).val();

            if(method == 'mercadopago'){
                function addEvent(to, type, fn){
                    if(document.addEventListener){
                        to.addEventListener(type, fn, false);
                    } else if(document.attachEvent){
                        to.attachEvent('on'+type, fn);
                    } else {
                        to['on'+type] = fn;
                    }
                };

                addEvent(document.querySelector('#cardNumber'), 'keyup', guessingPaymentMethod);
                addEvent(document.querySelector('#cardNumber'), 'change', guessingPaymentMethod);

                function getBin() {
                    var ccNumber = document.querySelector('input[data-checkout="cardNumber"]');
                    return ccNumber.value.replace(/[ .-]/g, '').slice(0, 6);
                };

                function guessingPaymentMethod(event) {
                    var bin = getBin();

                    if (event.type == "keyup") {
                        if (bin.length >= 6) {
                            window.Mercadopago.getPaymentMethod({
                                "bin": bin
                            }, setPaymentMethodInfo);
                        }
                    } else {
                        setTimeout(function() {
                            if (bin.length >= 6) {
                                window.Mercadopago.getPaymentMethod({
                                    "bin": bin
                                }, setPaymentMethodInfo);
                            }
                        }, 100);
                    }
                };

                function setPaymentMethodInfo(status, response) {
                    if (status == 200) {
                        const paymentMethodElement = document.querySelector('input[name=paymentMethodId]');

                        if (paymentMethodElement) {
                            paymentMethodElement.value = response[0].id;
                        } else {
                            const input = document.createElement('input');
                            input.setAttribute('name', 'paymentMethodId');
                            input.setAttribute('type', 'hidden');
                            input.setAttribute('value', response[0].id);

                            form.appendChild(input);
                        }

                        Mercadopago.getInstallments({
                            "bin": getBin(),
                            "amount": parseFloat(document.querySelector('#amount').value),
                        }, setInstallmentInfo);

                    } else {
                        alert(`payment method info error: ${response}`);
                    }
                };

                addEvent(document.querySelector('#mercadopago'), 'submit', function doPay(event){
                        event.preventDefault();
                        var $form = document.querySelector('#mercadopago');
                        window.Mercadopago.createToken($form, sdkResponseHandler);
                        return false;
                    });
            }

        })

        function sdkResponseHandler(status, response) {
            if (status != 200 && status != 201) {
                alert("Some of your information is wrong!");
                $('#preloader').hide();

            }else{
                var form = document.querySelector('#mercadopago');
                var card = document.createElement('input');
                card.setAttribute('name', 'token');
                card.setAttribute('type', 'hidden');
                card.setAttribute('value', response.id);
                form.appendChild(card);
                doSubmit=true;
                form.submit();
            }
        };


        function setInstallmentInfo(status, response) {
            var selectorInstallments = document.querySelector("#installments"),
            fragment = document.createDocumentFragment();
            selectorInstallments.length = 0;

            if (response.length > 0) {
                var option = new Option("Escolha...", '-1'),
                payerCosts = response[0].payer_costs;
                fragment.appendChild(option);

                for (var i = 0; i < payerCosts.length; i++) {
                    fragment.appendChild(new Option(payerCosts[i].recommended_message, payerCosts[i].installments));
                }

                selectorInstallments.appendChild(fragment);
                selectorInstallments.removeAttribute('disabled');
            }
        };
    </script>

    <script type="text/javascript">
        'use strict';

        var cnstatus = false;
        var dateStatus = false;
        var cvcStatus = false;

        function validateCard(cn) {
            cnstatus = Stripe.card.validateCardNumber(cn);
            if (!cnstatus) {
            $("#errCard").html('Card number not valid<br>');
            } else {
            $("#errCard").html('');
            }
            btnStatusChange();
        }

        function validateCVC(cvc) {
            cvcStatus = Stripe.card.validateCVC(cvc);
            if (!cvcStatus) {
            $("#errCVC").html('CVC number not valid');
            } else {
            $("#errCVC").html('');
            }
            btnStatusChange();
        }
    </script>

    <script type="text/javascript" src="{{ asset('assets/front/js/payvalid.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/front/js/paymin.js') }}"></script>
    <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript" src="{{ asset('assets/front/js/payform.js') }}"></script>

</body>

</html>


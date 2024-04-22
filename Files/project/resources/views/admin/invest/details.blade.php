@extends('layouts.load')
@section('content')

                        <div class="content-area no-padding">
                            <div class="add-product-content">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="product-description">
                                            <div class="body-area" id="modalEdit">

                                            <div class="table-responsive show-table">
                                                <table class="table">
                                                    <tr>
                                                        <th>{{ __("User ID#") }}</th>
                                                        <td>{{$invest->user->id}}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>{{ __("User Name") }}</th>
                                                        <td>
                                                            <a href="{{route('admin-user-show',$invest->user->id)}}" target="_blank">{{$invest->user->name}}</a>
                                                        </td>
                                                    </tr>

                                                    @if ($invest->amount != NULL)
                                                        <tr>
                                                            <th>{{ __("Invest Amount") }}</th>
                                                            <td>${{ round($invest->amount, 2) }}</td>
                                                        </tr>
                                                    @endif

                                                    @if ($invest->method == 'Manual')
                                                        <tr>
                                                            <th>{{ __("Transaction ID/Number") }}</th>
                                                            <td>{{$invest->txnid}}</td>
                                                        </tr>  
                                                    @endif

                                                    @if ($invest->coin_amount != NULL)
                                                        <tr>
                                                            <th>{{ __("Coin Amount") }}</th>
                                                            <td>{{ round($invest->coin_amount, 2) }}</td>
                                                        </tr>
                                                    @endif

                                                    <tr>
                                                        <th>{{ __("Invest Status") }}</th>
                                                        @if ($invest->status == 0)
                                                            <td>
                                                                <td>{{ __('Pending') }}</td>
                                                            </td>
                                                        @elseif ($invest->status == 1)
                                                            <td>
                                                                <td>{{ __('Running') }}</td>
                                                            </td>
                                                        @else 
                                                            <td>
                                                                <td>{{ __('Completed') }}</td>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <th>{{ __("Lifetime Return") }}</th>
                                                        @if ($invest->lifetime_return == 1)
                                                            <td>
                                                                <td>{{ __('YES') }}</td>
                                                            </td>
                                                        @else 
                                                            <td>
                                                                <td>{{ __('NO') }}</td>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <th>{{ __("Capital Back") }}</th>
                                                        @if ($invest->capital_back == 1)
                                                            <td>
                                                                <td>{{ __('YES') }}</td>
                                                            </td>
                                                        @else 
                                                            <td>
                                                                <td>{{ __('NO') }}</td>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <th>{{ __("Payment Status") }}</th>
                                                        @if ($invest->payment_status == 'pending')
                                                            <td>
                                                                <td>{{ __('PENDING') }}</td>
                                                            </td>
                                                        @else 
                                                            <td>
                                                                <td>{{ __('COMPLETED') }}</td>
                                                            </td>
                                                        @endif
                                                    </tr>

                                                    <tr>
                                                        <th>{{ __("User Email") }}</th>
                                                        <td>{{$invest->user->email}}</td>
                                                    </tr>

                                                    <tr>
                                                        <th>{{ __("User Phone") }}</th>
                                                        <td>{{$invest->user->phone}}</td>
                                                    </tr>

                                                    <tr>
                                                        <th>{{ __("Invest Method") }}</th>
                                                        <td>{{$invest->method}}</td>
                                                    </tr>
                                                </table>
                                            </div>


                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

@endsection
